#!/bin/bash
# Riverside demo instance — one-shot setup on the startperformance VPS
# (cPanel account "riverside", main domain riverside.startperformance.com).
# Run ON THE SERVER after uploading the plugin/theme zips + seed to /tmp/riverside/:
#   bash /tmp/riverside/riverside-setup.sh
# Idempotent-ish: skips steps whose result already exists.
set -e

DOMAIN="riverside.startperformance.com"
DIR="/home/riverside/public_html"
DB="riverside_wp"
DBUSER="riverside_wp"
UP="/tmp/riverside"
DBPASS=$(openssl rand -base64 18 | tr -d '/+=' | cut -c1-20)
WPPASS=$(openssl rand -base64 18 | tr -d '/+=' | cut -c1-20)
VENDOR_PIN=$(shuf -i 100000-999999 -n 1)

echo "== 1. Database =="
if ! uapi Mysql list_databases 2>/dev/null | grep -q "database: $DB"; then
  uapi --output=jsonpretty Mysql create_database name="$DB" | grep -E '"status"|"reason"'
  uapi --output=jsonpretty Mysql create_user name="$DBUSER" password="$DBPASS" | grep -E '"status"|"reason"'
  uapi --output=jsonpretty Mysql set_privileges_on_database user="$DBUSER" database="$DB" privileges="ALL PRIVILEGES" | grep -E '"status"|"reason"'
  NEWDB=1
else
  echo "database exists, keeping (wp-config will not be rewritten)"
fi

echo "== 2. WordPress =="
mkdir -p "$DIR"
cd "$DIR"
if [ ! -f wp-load.php ]; then
  wp core download --skip-content --quiet
fi
if [ ! -f wp-config.php ]; then
  wp config create --dbname="$DB" --dbuser="$DBUSER" --dbpass="$DBPASS" --quiet \
    --extra-php <<PHP
define( 'SP_SUPER_ADMIN_PIN', '$VENDOR_PIN' );
define( 'DISALLOW_FILE_EDIT', true );
define( 'WP_AUTO_UPDATE_CORE', 'minor' );
PHP
fi
if ! wp core is-installed --quiet 2>/dev/null; then
  wp core install --url="https://$DOMAIN" --title="City of Riverside" \
    --admin_user="riverside_admin" --admin_password="$WPPASS" --admin_email="richard@grouprb.com" --skip-email --quiet
  echo "WordPress installed"
fi
mkdir -p wp-content/plugins wp-content/themes wp-content/uploads

echo "== 3. Plugins + theme =="
for z in "$UP"/start-performance-2.*.zip "$UP"/government-service-core-*.zip "$UP"/start-performance-ai-*.zip; do
  [ -f "$z" ] && unzip -o -q "$z" -d wp-content/plugins/
done
[ -f "$UP/city-core-theme.zip" ] && unzip -o -q "$UP/city-core-theme.zip" -d wp-content/themes/
wp plugin activate start-performance government-service-core start-performance-ai --quiet
wp theme activate city-core-theme --quiet 2>/dev/null || echo "theme activate skipped"
wp rewrite structure '/%postname%/' --quiet
wp option update blogdescription "Utility Service Request Portal" --quiet
wp option update timezone_string "America/New_York" --quiet

echo "== 4. Seed demo data =="
wp eval-file "$UP/riverside-seed.php"

echo "== 5. Versions =="
wp plugin list --fields=name,status,version

echo
echo "=================== SAVE THESE ==================="
echo "Site:        https://$DOMAIN/"
echo "App:         https://$DOMAIN/sp-app/"
echo "Vendor:      https://$DOMAIN/sp-login/?vendor=1   PIN: $VENDOR_PIN"
echo "WP admin:    https://$DOMAIN/wp-admin/   user riverside_admin  pass $WPPASS"
[ -n "$NEWDB" ] && echo "DB:          $DB / $DBUSER / $DBPASS"
echo "=================================================="
