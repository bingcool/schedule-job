# HTTP Client（自动生成）

本目录下的 `*Api.php` 由 **InterfaceApi 契约接口**（同级模块 `Interface/` 中的 `*ApiInterface`）经脚本生成，文件头含 `// @generated` 标记。

## 如何更新

在 schedule-job 仓库根目录执行：

```bash
php InterfaceApi/bin/generate-client.php --service=ScheduleJob/App
```

修改路由或方法签名时，请先改 `Interface/` 下的接口与 Request/Response，再运行上述命令重新生成 Client。

## 禁止直接编辑

- **请勿**手工修改本目录中的 `*.php` Client 实现。
- **禁止** AI、Agent、Copilot 等工具直接编辑或「优化」这些文件；变更应通过契约接口与生成脚本完成。
