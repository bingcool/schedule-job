#!/usr/bin/env python3
"""Migrate App Module contract PHP files into InterfaceApi/ScheduleJob/App/."""

from __future__ import annotations

import shutil
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
APP_MODULE = ROOT / "App" / "Module"
OUT_ROOT = ROOT / "InterfaceApi" / "ScheduleJob" / "App" / "Module"

OLD_NS_PREFIX = "App\\Module\\"
NEW_NS_PREFIX = "InterfaceApi\\ScheduleJob\\App\\Module\\"

SOURCE_REL_DIRS = [
    "Common/Dto",
    "Common/Http",
    "Cron/Dto",
    "Cron/Request",
    "Cron/Response",
    "Staff/Dto",
    "Staff/Request",
    "Staff/Response",
]

USE_REPLACEMENTS = [
    ("use App\\Module\\", f"use {NEW_NS_PREFIX}"),
    ("use Swoolefy\\Annotation\\ApiProperty;", "use InterfaceApi\\Support\\ApiProperty;"),
    ("use Swoolefy\\Annotation\\ArrayList;", "use InterfaceApi\\Support\\ArrayList;"),
    ("use Swoolefy\\Annotation\\StringToInt;", "use InterfaceApi\\Support\\StringToInt;"),
    ("use Swoolefy\\Annotation\\IntToString;", "use InterfaceApi\\Support\\IntToString;"),
    ("use Swoolefy\\Annotation\\Validation\\ValidationRule;", "use InterfaceApi\\Support\\ValidationRule;"),
    ("use Swoolefy\\Http\\BaseRequest;", "use InterfaceApi\\Support\\BaseRequest;"),
    ("use Swoolefy\\Http\\BaseResponse;", "use InterfaceApi\\Support\\BaseResponse;"),
    ("use Swoolefy\\Http\\BasePageResultResponse;", f"use {NEW_NS_PREFIX}Common\\Http\\BasePageResultResponse;"),
    ("use Swoolefy\\Http\\BasePageRequest;", "use InterfaceApi\\Support\\BasePageRequest;"),
    ("use Swoolefy\\Core\\Dto\\AbstractDto;", "use InterfaceApi\\Support\\AbstractDto;"),
    ("use Swoolefy\\Core\\Dto\\ArrayDto;", "use InterfaceApi\\Support\\ArrayDto;"),
]

CONTENT_REPLACEMENTS = [
    ("namespace App\\Module\\", f"namespace {NEW_NS_PREFIX}"),
    ("@see \\App\\Module\\", f"@see \\{NEW_NS_PREFIX}"),
    ("{@see \\App\\Module\\", f"{{@see \\{NEW_NS_PREFIX}"),
] + USE_REPLACEMENTS


def transform(content: str) -> str:
    for old, new in CONTENT_REPLACEMENTS:
        content = content.replace(old, new)
    return content


def collect_sources() -> list[Path]:
    files: list[Path] = []
    for rel in SOURCE_REL_DIRS:
        base = APP_MODULE / rel
        if not base.is_dir():
            continue
        files.extend(sorted(base.rglob("*.php")))
    return files


def write_base_http_helpers() -> None:
    http_dir = OUT_ROOT / "Common" / "Http"
    http_dir.mkdir(parents=True, exist_ok=True)

    base_list = http_dir / "BaseListResponse.php"
    if not base_list.exists():
        base_list.write_text(
            """<?php

declare(strict_types=1);

namespace InterfaceApi\\ScheduleJob\\App\\Module\\Common\\Http;

use InterfaceApi\\Support\\BaseResponse;

/** 非分页列表 API 响应基类。data 为 {@see \\InterfaceApi\\Support\\AbstractListDataDto} 子类（total + list）。 */
class BaseListResponse extends BaseResponse
{
}
""",
            encoding="utf-8",
        )

    base_page = http_dir / "BasePageResultResponse.php"
    if not base_page.exists():
        base_page.write_text(
            """<?php

declare(strict_types=1);

namespace InterfaceApi\\ScheduleJob\\App\\Module\\Common\\Http;

use InterfaceApi\\Support\\BaseResponse;

/** 分页列表 API 响应基类。 */
class BasePageResultResponse extends BaseResponse
{
}
""",
            encoding="utf-8",
        )


def patch_abstract_list_data_dto(path: Path) -> None:
    if path.name != "AbstractListDataDto.php":
        return
    text = path.read_text(encoding="utf-8")
    text = text.replace(
        "abstract class AbstractListDataDto extends AbstractDto",
        "abstract class AbstractListDataDto extends \\InterfaceApi\\Support\\AbstractListDataDto",
    )
    path.write_text(text, encoding="utf-8")


def main() -> None:
    sources = collect_sources()
    if not sources:
        raise SystemExit("No source contract files found")

    migrated: list[Path] = []
    for src in sources:
        rel = src.relative_to(APP_MODULE)
        dest = OUT_ROOT / rel
        dest.parent.mkdir(parents=True, exist_ok=True)
        content = transform(src.read_text(encoding="utf-8"))
        dest.write_text(content, encoding="utf-8")
        patch_abstract_list_data_dto(dest)
        migrated.append(dest)

    write_base_http_helpers()

    # Remove originals under App/Module
    for rel in SOURCE_REL_DIRS:
        base = APP_MODULE / rel
        if base.is_dir():
            shutil.rmtree(base)

    print(f"Migrated {len(migrated)} files to {OUT_ROOT.relative_to(ROOT)}")


if __name__ == "__main__":
    main()
