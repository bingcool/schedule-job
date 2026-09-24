#!/usr/bin/env python3
"""Generate InterfaceApi *ApiInterface from App/Router + Controller public methods."""

from __future__ import annotations

import re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
ROUTER_CRON = ROOT / "App/Router/Module/CronManager.php"
ROUTER_STAFF = ROOT / "App/Router/Module/StaffManager.php"

CONTROLLERS: dict[str, Path] = {
    "CronTaskManagerController": ROOT / "App/Module/Cron/Controller/CronTaskManagerController.php",
    "CronRobotController": ROOT / "App/Module/Cron/Controller/CronRobotController.php",
    "StaffAuthController": ROOT / "App/Module/Staff/Controller/StaffAuthController.php",
    "StaffUserController": ROOT / "App/Module/Staff/Controller/StaffUserController.php",
    "StaffRoleController": ROOT / "App/Module/Staff/Controller/StaffRoleController.php",
}

MODULE_NS = {
    "Cron": "InterfaceApi\\ScheduleJob\\App\\Module\\Cron",
    "Staff": "InterfaceApi\\ScheduleJob\\App\\Module\\Staff",
}

ROUTE_GROUP_NAME = {
    "CronTaskManagerController": ("cron", "/api/v1"),
    "CronRobotController": ("cron-robot", "/api/v1"),
    "StaffAuthController": ("staff-auth", "/api/v1"),
    "StaffUserController": ("staff-user", "/api/v1"),
    "StaffRoleController": ("staff-role", "/api/v1"),
}

INTERFACE_CLASS_DESC: dict[str, str] = {
    "CronTaskManagerController": "Cron 任务、节点、分组、日志、Dashboard 与 Agent 回调 API",
    "CronRobotController": "告警机器人 Webhook 配置 API",
    "StaffAuthController": "登录与会话、当前用户资料与密码",
    "StaffUserController": "后台用户账号、角色与节点组授权",
    "StaffRoleController": "角色、菜单与权限配置",
}


def php_single_quoted(s: str) -> str:
    return s.replace("\\", "\\\\").replace("'", "\\'")


def parse_class_description(path: Path) -> str:
    text = path.read_text(encoding="utf-8")
    m = re.search(r"/\*\*\s*\n\s*\*\s*([^*\n@]+)", text)
    if m:
        line = m.group(1).strip()
        if line and not line.startswith("Route:"):
            return line
    return INTERFACE_CLASS_DESC.get(path.stem.replace(".php", ""), "")


def parse_api_operations(path: Path) -> dict[str, str]:
    """Map action name -> human description (from ApiOperation or method docblock)."""
    text = path.read_text(encoding="utf-8")
    ops: dict[str, str] = {}
    op_re = re.compile(
        r"#\[\s*ApiOperation\s*\(\s*(?:\n\s*)?"
        r"(?:\"([^\"]+)\"|'((?:\\'|[^'])*)')"
        r"(?:\s*\n\s*)?\)\s*\]\s*public\s+function\s+(\w+)\s*\(",
        re.MULTILINE,
    )
    for m in op_re.finditer(text):
        desc = (m.group(1) or m.group(2) or "").replace("\\'", "'")
        ops[m.group(3)] = desc.strip()
    doc_re = re.compile(
        r"/\*\*(?:(?!\*/).)*?\*/\s*"
        r"(?:#\[\s*ApiOperation[^\]]+\]\s*)?"
        r"public\s+function\s+(\w+)\s*\(",
        re.DOTALL,
    )
    for m in doc_re.finditer(text):
        name = m.group(1)
        if name in ops:
            continue
        block = m.group(0)
        for raw in block.split("\n"):
            line = re.sub(r"^\s*\*\s?", "", raw).strip()
            if not line or line in ("/**", "*/") or line.startswith("@") or line.startswith("Route:"):
                continue
            if line.startswith("```") or "curl " in line.lower():
                continue
            if line.startswith("Route:") or line in ("/", "*/"):
                continue
            ops[name] = line.rstrip("。").strip()
            break
    return ops


def parse_router(path: Path) -> list[tuple[str, str, str, str]]:
    """Return list of (http_method, path, controller_short, method)."""
    text = path.read_text(encoding="utf-8")
    entries: list[tuple[str, str, str, str]] = []
    prefix = "api/v1"
    lines = text.splitlines()
    route_re = re.compile(
        r"Route::(get|post|put|delete|patch|head|options)\(\s*'([^']+)',\s*\[",
    )
    route_match_re = re.compile(
        r"Route::match\(\s*\[[^\]]+\],\s*'([^']+)',\s*\[",
    )
    dispatch_re = re.compile(
        r"'dispatch_route'\s*=>\s*\[(\w+Controller)::class,\s*'(\w+)'\]",
    )
    i = 0
    while i < len(lines):
        line = lines[i]
        gm = re.search(r"Route::group\(\[\s*'prefix'\s*=>\s*'([^']+)'", line)
        if gm:
            prefix = gm.group(1)
        rm = route_re.search(line) or route_match_re.search(line)
        if rm:
            if rm.re is route_match_re:
                subpath = rm.group(1)
                mm = re.search(r"match\(\s*\[\s*'(\w+)'", line)
                verb = mm.group(1).upper() if mm else "POST"
            else:
                verb, subpath = rm.group(1), rm.group(2)
                verb = verb.upper()
            block_lines = [line]
            j = i + 1
            while j < len(lines):
                block_lines.append(lines[j])
                if "]);" in lines[j]:
                    break
                j += 1
            block = "\n".join(block_lines)
            dm = dispatch_re.search(block)
            if dm:
                ctrl, action = dm.group(1), dm.group(2)
                full_path = "/" + prefix.strip("/") + "/" + subpath.strip("/")
                entries.append((verb, full_path, ctrl, action))
        i += 1

    return entries


def parse_controller_uses(path: Path) -> dict[str, str]:
    text = path.read_text(encoding="utf-8")
    uses: dict[str, str] = {}
    for m in re.finditer(r"^use\s+([\w\\]+);\s*$", text, re.MULTILINE):
        fqcn = m.group(1)
        uses[fqcn.split("\\")[-1]] = fqcn
    return uses


def parse_controller_methods(path: Path) -> dict[str, tuple[str, str]]:
    """method -> (params with types, return type short name)."""
    text = path.read_text(encoding="utf-8")
    out: dict[str, tuple[str, str]] = {}
    for m in re.finditer(
        r"public\s+function\s+(\w+)\s*\(([^)]*)\)\s*:\s*([\w\\]+)",
        text,
    ):
        name, params, ret = m.group(1), m.group(2).strip(), m.group(3)
        if name == "__construct":
            continue
        out[name] = (params, ret)
    return out


def interface_name(controller: str) -> str:
    return controller.replace("Controller", "ApiInterface")


def module_for(controller: str) -> str:
    return "Cron" if controller.startswith("Cron") else "Staff"


def resolve_type(short: str, uses: dict[str, str]) -> str:
    if short in uses:
        return uses[short]
    return short


def build_interface(
    controller: str,
    routes: list[tuple[str, str, str, str]],
    signatures: dict[str, tuple[str, str]],
    uses: dict[str, str],
    operations: dict[str, str],
    class_desc: str,
) -> str:
    mod = module_for(controller)
    iface = interface_name(controller)
    ns = f"{MODULE_NS[mod]}\\Interface"
    group_name, prefix = ROUTE_GROUP_NAME[controller]

    methods_lines: list[str] = []

    ctrl_routes = [(v, p, a) for v, p, c, a in routes if c == controller]
    by_action = {a: (v, p) for v, p, a in ctrl_routes}

    import_uses: set[str] = set()
    for action, (params, ret) in signatures.items():
        if action not in by_action:
            if action == "register" and controller == "StaffAuthController":
                continue
            continue
        verb, path = by_action[action]
        # path relative to prefix for Route attribute
        rel = path
        pfx = prefix.rstrip("/")
        if pfx and path.startswith(pfx):
            rel = path[len(pfx) :] or "/"
        if not rel.startswith("/"):
            rel = "/" + rel

        ret_fq = resolve_type(ret, uses)
        if ret_fq != "void":
            import_uses.add(ret_fq)
        param_decl = ""
        if params:
            pt, pv = params.split("$", 1)
            pt = pt.strip()
            pv = "$" + pv.strip()
            pt_fq = resolve_type(pt, uses)
            import_uses.add(pt_fq)
            param_decl = f"{pt} {pv}"
        else:
            param_decl = ""

        sig_params = f"({param_decl})" if param_decl else "()"
        ret_short = ret_fq.split("\\")[-1]
        desc = operations.get(action) or action
        desc_php = php_single_quoted(desc)
        doc_lines = [
            "    /**",
            f"     * {desc}",
        ]
        if param_decl:
            doc_lines.append(f"     * @param {pt} $request 请求参数（字段见 Request DTO 上 ApiProperty）")
        doc_lines.append(
            f"     * @return {ret_short} 响应 data（字段见 Response / 嵌套 DTO 上 ApiProperty）"
        )
        doc_lines.append("     */")
        method_block = "\n".join(doc_lines) + "\n"
        method_block += f"    #[ApiOperation('{desc_php}')]\n"
        method_block += f"    #[Route(method: '{verb}', path: '{rel}')]\n"
        method_block += f"    public function {action}{sig_params}: {ret_short};"
        methods_lines.append(method_block)

    use_lines = []
    for fqcn in sorted(import_uses):
        use_lines.append(f"use {fqcn};")
    use_lines.append("use InterfaceApi\\Support\\ApiController;")
    use_lines.append("use InterfaceApi\\Support\\ApiOperation;")
    use_lines.append("use InterfaceApi\\Support\\Route;")
    use_lines.append("use InterfaceApi\\Support\\RouteGroup;")

    body = "\n\n".join(methods_lines)
    class_doc = class_desc or iface.replace("ApiInterface", "")
    class_desc_php = php_single_quoted(class_doc)
    return f"""<?php

declare(strict_types=1);

namespace {ns};

{chr(10).join(use_lines)}

/**
 * {class_doc}
 *
 * 入参 / 出参字段定义见各 Request、Response 及其 DTO 属性上的 {{@see \\InterfaceApi\\Support\\ApiProperty}}。
 */
#[ApiController(description: '{class_desc_php}')]
#[RouteGroup(prefix: '{prefix}', name: '{group_name}')]
interface {iface}
{{
{body}
}}
"""


def patch_controller(controller: str, path: Path) -> None:
    iface = interface_name(controller)
    mod = module_for(controller)
    fqcn = f"{MODULE_NS[mod]}\\Interface\\{iface}"
    text = path.read_text(encoding="utf-8")
    short_iface = iface
    if f"implements {short_iface}" in text:
        return
    if f"use {fqcn};" not in text:
        needle = "namespace App\\Module\\"
        idx = text.find(needle)
        if idx >= 0:
            semi = text.find(";\n", idx)
            if semi >= 0:
                insert_at = semi + 2
                text = text[:insert_at] + f"\nuse {fqcn};\n" + text[insert_at:]
    if f"implements {iface}" not in text:
        text = text.replace(
            "extends BController",
            f"extends BController implements {iface}",
            1,
        )
    path.write_text(text, encoding="utf-8")


def main() -> None:
    cron_routes = parse_router(ROUTER_CRON)
    staff_routes = parse_router(ROUTER_STAFF)
    all_routes = cron_routes + staff_routes

    for controller, path in CONTROLLERS.items():
        sigs = parse_controller_methods(path)
        uses = parse_controller_uses(path)
        ops = parse_api_operations(path)
        class_desc = parse_class_description(path) or INTERFACE_CLASS_DESC.get(controller, "")
        php = build_interface(controller, all_routes, sigs, uses, ops, class_desc)
        mod = module_for(controller)
        out_dir = ROOT / "InterfaceApi/ScheduleJob/App/Module" / mod / "Interface"
        out_dir.mkdir(parents=True, exist_ok=True)
        out_file = out_dir / f"{interface_name(controller)}.php"
        out_file.write_text(php, encoding="utf-8")
        print("wrote", out_file.relative_to(ROOT))
        patch_controller(controller, path)
        print("patched", path.relative_to(ROOT))


if __name__ == "__main__":
    main()
