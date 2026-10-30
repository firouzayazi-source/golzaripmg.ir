#!/usr/bin/env bash
# وردپرسِ کوچک با SQLite برای آزمونِ دیداری: bash tests/visual/setup-wp.sh <dir>
set -euo pipefail
repo="$(cd "$(dirname "$0")/../.." && pwd)"
dir="$1"
mkdir -p "$dir" && cd "$dir"

wp core download --quiet --allow-root
wp config create --dbname=wp --dbuser=wp --dbpass=wp --skip-check --quiet --allow-root
wp config set WP_DEBUG true --raw --allow-root
wp config set WP_DEBUG_DISPLAY true --raw --allow-root

curl -sSL -o /tmp/sqlite.zip https://downloads.wordpress.org/plugin/sqlite-database-integration.zip
unzip -q /tmp/sqlite.zip -d wp-content/plugins/
sed -e "s#{SQLITE_IMPLEMENTATION_FOLDER_PATH}#$dir/wp-content/plugins/sqlite-database-integration#" \
    -e "s#{SQLITE_PLUGIN}#sqlite-database-integration/load.php#" \
    wp-content/plugins/sqlite-database-integration/db.copy > wp-content/db.php

wp core install --url=http://127.0.0.1:8080 --title="گلزاری" --admin_user=admin --admin_password=admin \
  --admin_email=admin@example.com --skip-email --quiet --allow-root
wp language core install fa_IR --activate --allow-root || echo "بسته‌ی فارسی نصب نشد؛ ادامه."

for p in "$repo"/wp-content/plugins/*/; do
  cp -r "$p" wp-content/plugins/
  wp plugin activate "$(basename "$p")" --allow-root
done

id=$(wp post create --porcelain --post_type=page --post_status=publish --post_title="خدمات آرایش دائم" \
  --post_content="<p>میکروبلیدینگ ابرو، تاتوی خط چشم و رژ لب دائم. نوبت: ۰۹۱۲۰۰۰۰۰۰۰</p><h2>چه ویژگی هایی زیبانو را متمایز می سازد؟</h2><h3>آرامش و اسپا</h3><p>لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است.</p><p><a href='http://instagram.com/somayehgolzari_acamedy'>اینستاگرام</a> <a href='https://smdesign.ir/zibanoo/booking'>رزرو نوبت</a> <a href='tel:02100000000'>تماس</a></p><p>حق چاپ © 2026 - قالب وردپرس توسط راست چین</p><img class='hero-sample attachment-full size-full wp-image-2094' width='1200' height='800' style='max-width:100%;height:auto' src='/wp-content/uploads/2023/08/003.webp' alt=''>" --allow-root)
wp option update show_on_front page --allow-root
wp option update page_on_front "$id" --allow-root
