<?php

declare(strict_types=1);

namespace App\Module\Cron\Kubernetes;

use App\Module\Cron\Entity\CronTaskEntity;
use App\Module\Cron\FailureReason;
use Swoolefy\Worker\Cron\CronProcess;
use Swoolefy\Worker\Cron\ExecutionStatus;
use Swoolefy\Worker\Cron\KubernetesExecutor;
use Swoolefy\Support\Kubernetes\ApiException;
use Swoolefy\Support\Kubernetes\Client;
use Swoolefy\Support\Kubernetes\ClientInterface;
use Swoolefy\Support\Kubernetes\JobStatus;
use Swoolefy\Support\Kubernetes\JobTemplateBuilder;

/**
 * Lease 过期后按集群 Job 真实状态收尾（方案 §11.3）。
 *
 * 不能走 Shell 语义「过期 = WORKER_CRASH」：Job 可能已经 Complete，
 * 也可能还在跑。仍 Active、或集群 API 暂时不可达时，不删 Job、不改执行记录
 * （{@see self::KEY_DEFER}），等下一轮再看。Admin 已取消且 Job 仍 Active：DELETE。
 * 不补 Create。
 */
final class KubernetesCrashRecovery
{
    /** resolve() 返回此键为 true 时：本轮不收尾（Job 仍在跑，或集群暂时不可达）。 */
    public const KEY_DEFER = 'defer';

    public function __construct(private readonly ?ClientInterface $client = null)
    {
    }

    /**
     * @param array<string, mixed> $row cron_task_log 行
     * @return array{status:int,failure_reason:string,message:string,defer?:bool}|null
     *         null=不是 K8s 执行，走默认 Recovery
     */
    public function resolve(array $row): ?array
    {
        $context = $this->resolveContext($row);
        if ($context === null) {
            return null;
        }

        $from = (int) ($row['status'] ?? 0);
        $namespace = $context['namespace'];
        $jobName = $context['job_name'];

        if ($namespace === '' || $jobName === '') {
            return $this->decision(
                $from,
                ExecutionStatus::FAILED,
                FailureReason::WORKER_CRASH,
                'Lease 过期且未记录 Kubernetes Job 句柄，视为 Create 前崩溃，不补创建',
            );
        }

        try {
            $client = $this->client ?? Client::fromEnv();
        } catch (\Throwable $e) {
            return $this->defer(sprintf(
                'Lease 过期后无法初始化 Kubernetes 客户端（%s），保留 Job 与执行记录，下一轮再收割',
                $e->getMessage(),
            ));
        }

        try {
            $job = $this->loadJob($client, $namespace, $jobName, $context['exec_batch_id']);
        } catch (ApiException $e) {
            if ($e->isNotFound()) {
                return $this->decision(
                    $from,
                    ExecutionStatus::FAILED,
                    FailureReason::WORKER_CRASH,
                    sprintf('Lease 过期后集群中已无 Job %s/%s，视为 Create 前崩溃或已被回收，不补创建', $namespace, $jobName),
                );
            }

            return $this->defer(sprintf(
                'Lease 过期后读取 Job %s/%s 失败（%s），保留 Job 与执行记录，下一轮再收割',
                $namespace,
                $jobName,
                $e->getMessage(),
            ));
        } catch (\Throwable $e) {
            return $this->defer(sprintf(
                'Lease 过期后读取 Job %s/%s 异常（%s），保留 Job 与执行记录，下一轮再收割',
                $namespace,
                $jobName,
                $e->getMessage(),
            ));
        }

        $outcome = JobStatus::classify($job);
        if ($outcome !== null) {
            [$resultStatus, $reason] = $outcome;
            $status = match ($resultStatus) {
                JobStatus::COMPLETE => ExecutionStatus::SUCCESS,
                JobStatus::DEADLINE_EXCEEDED => ExecutionStatus::TIMEOUT,
                default => ExecutionStatus::FAILED,
            };
            $failureReason = match ($status) {
                ExecutionStatus::TIMEOUT => FailureReason::TIMEOUT,
                ExecutionStatus::SUCCESS => '',
                default => FailureReason::EXECUTION_ERROR,
            };

            return $this->decision(
                $from,
                $status,
                $failureReason,
                sprintf('Lease 过期后回读 Kubernetes Job %s/%s：%s', $namespace, $jobName, $reason),
            );
        }

        if ($from === ExecutionStatus::CANCEL_REQUESTED) {
            try {
                $client->deleteJob($namespace, $jobName);
            } catch (\Throwable) {
            }

            return $this->decision(
                $from,
                ExecutionStatus::CANCELLED,
                FailureReason::CANCELLED,
                sprintf('取消请求已生效，已删除仍在运行的 Kubernetes Job %s/%s', $namespace, $jobName),
            );
        }

        return $this->defer(sprintf(
            'Lease 过期时 Job %s/%s 仍在运行，保留 Job 与执行记录，下一轮再收割',
            $namespace,
            $jobName,
        ));
    }

    /**
     * @return array{status:int,failure_reason:string,message:string,defer:bool}
     */
    private function defer(string $message): array
    {
        return [
            'status' => ExecutionStatus::RUNNING,
            'failure_reason' => '',
            'message' => $message,
            self::KEY_DEFER => true,
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return array{namespace:string,job_name:string,exec_batch_id:string}|null
     */
    private function resolveContext(array $row): ?array
    {
        $taskItem = $this->taskItem($row);
        $cronId = (int) ($row['cron_id'] ?? 0);
        $task = null;
        try {
            $task = $cronId > 0 ? CronTaskEntity::query()->where('id', $cronId)->find() : null;
        } catch (\Throwable) {
            $task = null;
        }
        $taskArr = is_object($task) && method_exists($task, 'toArray') ? $task->toArray() : (is_array($task) ? $task : []);
        $execType = (int) ($taskArr['exec_type'] ?? 0);
        $k8sSpec = $taskArr['k8s_spec'] ?? [];
        if (is_string($k8sSpec)) {
            $decoded = json_decode($k8sSpec, true);
            $k8sSpec = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($k8sSpec)) {
            $k8sSpec = [];
        }

        $hasMeta = ($taskItem['k8s_job_name'] ?? '') !== '' || ($taskItem['k8s_namespace'] ?? '') !== '';
        if ($execType !== CronProcess::EXEC_K8S_TYPE && !$hasMeta) {
            return null;
        }

        $namespace = trim((string) ($taskItem['k8s_namespace'] ?? ($k8sSpec['namespace'] ?? '')));
        $jobName = trim((string) ($taskItem['k8s_job_name'] ?? ''));
        $batchId = (string) ($row['exec_batch_id'] ?? '');
        $attempt = (int) ($taskItem['k8s_attempt'] ?? 0);
        if ($jobName === '' && $batchId !== '') {
            $jobName = KubernetesExecutor::jobName($batchId, $attempt > 0 ? $attempt : 1);
        }

        return [
            'namespace' => $namespace,
            'job_name' => $jobName,
            'exec_batch_id' => $batchId,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function loadJob(
        ClientInterface $client,
        string $namespace,
        string $jobName,
        string $execBatchId,
    ): array {
        try {
            return $client->getJob($namespace, $jobName);
        } catch (ApiException $e) {
            if (!$e->isNotFound() || $execBatchId === '') {
                throw $e;
            }
        }

        $jobs = $client->listJobs($namespace, sprintf(
            '%s=%s,%s=%s',
            JobTemplateBuilder::LABEL_MANAGED_BY,
            JobTemplateBuilder::MANAGED_BY,
            JobTemplateBuilder::LABEL_EXEC_BATCH_ID,
            $execBatchId,
        ));
        if ($jobs === []) {
            throw new ApiException('not found', 404, 'NotFound');
        }

        return $jobs[0];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function taskItem(array $row): array
    {
        $taskItem = $row['task_item'] ?? [];
        if (is_string($taskItem)) {
            $decoded = json_decode($taskItem, true);
            $taskItem = is_array($decoded) ? $decoded : [];
        }

        return is_array($taskItem) ? $taskItem : [];
    }

    /**
     * @return array{status:int,failure_reason:string,message:string}
     */
    private function decision(int $from, int $status, string $failureReason, string $message): array
    {
        if ($from === ExecutionStatus::CANCEL_REQUESTED) {
            $status = ExecutionStatus::CANCELLED;
            $failureReason = FailureReason::CANCELLED;
        }

        return [
            'status' => $status,
            'failure_reason' => $failureReason,
            'message' => $message,
        ];
    }
}
