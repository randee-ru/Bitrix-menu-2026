#!/usr/bin/env bash
# Сборка randee.menu-<версия>.zip из каталога randee.menu/ (корень репозитория Bitrix-menu-2026).
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
MODULE="$ROOT/randee.menu"
DIST="$ROOT/dist"
VERSION_LINE="$(grep "'VERSION'" "$MODULE/install/version.php" | head -1 | sed -E "s/.*'VERSION'[[:space:]]*=>[[:space:]]*'([^']+)'.*/\1/")"
OUT="$DIST/randee.menu-${VERSION_LINE}.zip"

if [[ ! -d "$MODULE" ]]; then
  echo "Не найден модуль: $MODULE" >&2
  exit 1
fi

mkdir -p "$DIST"
rm -f "$OUT"
(
  cd "$ROOT"
  zip -r -q "$OUT" randee.menu \
    -x "*.DS_Store" -x "*__MACOSX*" -x "*.git/*"
)
echo "Готово: $OUT ($(du -h "$OUT" | awk '{print $1}'))"
