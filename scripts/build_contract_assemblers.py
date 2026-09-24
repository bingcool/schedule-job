#!/usr/bin/env python3
"""Generate App-layer assemblers from InterfaceApi DTO static factories (one-time helper)."""

from __future__ import annotations

from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
IA = ROOT / "InterfaceApi" / "ScheduleJob" / "App" / "Module"

CRON_ASSEMBLER = ROOT / "App" / "Module" / "Cron" / "Assembler" / "CronContractDtoAssembler.php"
STAFF_ASSEMBLER = ROOT / "App" / "Module" / "Staff" / "Assembler" / "StaffContractDtoAssembler.php"


def strip_factory_from_dto(path: Path, method_names: list[str]) -> None:
    text = path.read_text(encoding="utf-8")
    for name in method_names:
        marker = f"public static function {name}("
        start = text.find(marker)
        if start < 0:
            continue
        # walk to matching closing brace of function
        i = text.find("{", start)
        depth = 0
        end = i
        for j in range(i, len(text)):
            if text[j] == "{":
                depth += 1
            elif text[j] == "}":
                depth -= 1
                if depth == 0:
                    end = j + 1
                    break
        # include docblock before function
        doc_start = start
        while doc_start > 0 and text[doc_start - 1] in " \t\n":
            doc_start -= 1
        if doc_start >= 3 and text[doc_start - 3 : doc_start] == "*/":
            p = text.rfind("/**", 0, start)
            if p >= 0 and start - p < 800:
                start = p
        text = text[:start] + text[end:]
    path.write_text(text, encoding="utf-8")


def main() -> None:
    print("Use manual assembler files; strip only after assembler exists.")

if __name__ == "__main__":
    main()
