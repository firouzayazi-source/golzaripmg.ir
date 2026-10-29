#!/usr/bin/env bash
# وارسیِ پیش از هر پوش: bash scripts/check.sh
set -euo pipefail
cd "$(dirname "$0")/.."

fail=0

echo "— نحوِ پی‌اچ‌پی"
while IFS= read -r -d '' f; do
  if ! out=$(php -l "$f" 2>&1); then
    echo "$out"; fail=1
  fi
done < <(find wp-content scripts -name '*.php' -print0)

echo "— برابریِ Version و ثابتِ نسخه"
php -r '
require "scripts/lib.php";
$bad = 0;
foreach (golzari_slugs(".") as $slug) {
  $src = file_get_contents(GOLZARI_PLUGINS_DIR . "/$slug/$slug.php");
  $v = golzari_header($src, "Version");
  $c = golzari_ver_const($src);
  if ($v === "") { golzari_fail("$slug: سرآیندِ Version ندارد"); $bad++; continue; }
  if ($c && $c[1] !== $v) { golzari_fail("$slug: Version=$v ولی {$c[0]}={$c[1]}"); $bad++; continue; }
  if (!preg_match("/^ABSPATH|defined\(\s*.ABSPATH.\s*\)/m", $src)) { golzari_fail("$slug: نگهبانِ ABSPATH ندارد"); $bad++; continue; }
  echo "✓ $slug $v\n";
}
exit($bad ? 1 : 0);
' || fail=1

echo "— ممنوعه‌ها"
# هیچ‌وقت .htaccess نمی‌نویسیم؛ هیچ‌وقت dangerous-clean-slate.
if grep -rnE "\.htaccess|dangerous-clean-slate" wp-content .github --include='*.php' --include='*.yml' >/dev/null 2>&1; then
  grep -rnE "\.htaccess|dangerous-clean-slate" wp-content .github --include='*.php' --include='*.yml'
  echo "✗ نوشتنِ .htaccess یا dangerous-clean-slate ممنوع است"; fail=1
fi
# vh به جای dvh
if grep -rnE "[0-9]vh\b" wp-content --include='*.css' >/dev/null 2>&1; then
  grep -rnE "[0-9]vh\b" wp-content --include='*.css'
  echo "✗ ارتفاع با dvh، نه vh"; fail=1
fi

echo "— نسخه نسبت به main"
base=""
if git rev-parse --verify --quiet origin/main >/dev/null; then
  base=$(git merge-base HEAD origin/main)
  [ "$base" = "$(git rev-parse HEAD)" ] && base=$(git rev-parse --verify --quiet HEAD~1 || true)
fi
php scripts/verify-bump.php "$base" || fail=1

if [ "$fail" -ne 0 ]; then
  echo "✗ وارسی ناموفق"; exit 1
fi
echo "✓ همه‌چیز درست است"
