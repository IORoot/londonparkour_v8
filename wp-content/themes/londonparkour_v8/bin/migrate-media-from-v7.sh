#!/usr/bin/env bash
#
# Migrate media files + Real Media Library from v7 to v8.
#
# Usage:
#   themes/londonparkour_v8/bin/migrate-media-from-v7.sh
#
set -euo pipefail

# Repo root = four levels up (bin/ -> theme -> themes -> wp-content -> root).
ROOT="$(cd "$(dirname "$0")/../../../.." && pwd)"
V7_ROOT="/Users/andypearson/Sites/londonparkour_v7"
V7_UPLOADS="${V7_ROOT}/application/wp-content/uploads"
V8_UPLOADS="${ROOT}/wp-content/uploads"
TMP="/tmp/lp-v7-media-migrate-$$"

V7_DB_CONTAINER="londonparkourV7com_mariadb"
V7_DB="live_londonparkourV7_com"
V7_DB_USER="root"
V7_DB_PASS="nl175PjdyZm4pmCKeyER"

V8_DB_CONTAINER="londonparkour_v8-db-1"
V8_DB="wordpress"
V8_DB_USER="wordpress"
V8_DB_PASS="your_mysql_password"

V8_URL="http://localhost:8102"
V7_URLS=("http://localhost:83" "https://localhost:83" "http://localhost:8443" "https://localhost:8443")

say() { printf '\n\033[1m▸ %s\033[0m\n' "$1"; }

v7_mysql() {
  docker exec "$V7_DB_CONTAINER" mysql -u"$V7_DB_USER" -p"$V7_DB_PASS" "$V7_DB" -N -e "$1"
}

v8_mysql() {
  docker exec "$V8_DB_CONTAINER" mysql -u"$V8_DB_USER" -p"$V8_DB_PASS" "$V8_DB" -e "$1"
}

v8_mysql_file() {
  docker exec -i "$V8_DB_CONTAINER" mysql -u"$V8_DB_USER" -p"$V8_DB_PASS" "$V8_DB" < "$1"
}

[[ -d "$V7_UPLOADS" ]] || { echo "v7 uploads not found: $V7_UPLOADS"; exit 1; }

mkdir -p "$TMP" "$V8_UPLOADS"

say "Syncing uploads from v7 → v8"
rsync -a --delete --stats \
  --exclude '.DS_Store' \
  "$V7_UPLOADS/" "$V8_UPLOADS/"

say "Copying Real Media Library Pro + physical upload folder plugin"
rm -rf "${ROOT}/wp-content/plugins/real-media-library-lite"
cp -R "${V7_ROOT}/application/wp-content/plugins/real-media-library" "${ROOT}/wp-content/plugins/"
cp -R "${V7_ROOT}/application/wp-content/plugins/physical-custom-upload-folder" "${ROOT}/wp-content/plugins/"

say "Exporting v7 media database tables"
docker exec "$V7_DB_CONTAINER" mysqldump -u"$V7_DB_USER" -p"$V7_DB_PASS" "$V7_DB" \
  --no-create-info --skip-add-locks --complete-insert \
  wp_realmedialibrary wp_realmedialibrary_posts \
  > "$TMP/rml.sql"

docker exec "$V7_DB_CONTAINER" mysqldump -u"$V7_DB_USER" -p"$V7_DB_PASS" "$V7_DB" \
  --no-create-info --skip-add-locks --complete-insert \
  wp_posts --where="post_type='attachment'" \
  > "$TMP/attachments.sql"

v7_mysql "CREATE TABLE IF NOT EXISTS _lp_migrate_attachment_meta (
  meta_id BIGINT UNSIGNED NOT NULL,
  post_id BIGINT UNSIGNED NOT NULL,
  meta_key VARCHAR(255) DEFAULT NULL,
  meta_value LONGTEXT,
  PRIMARY KEY (meta_id)
) ENGINE=InnoDB;
TRUNCATE TABLE _lp_migrate_attachment_meta;
INSERT INTO _lp_migrate_attachment_meta (meta_id, post_id, meta_key, meta_value)
SELECT pm.meta_id, pm.post_id, pm.meta_key, pm.meta_value
FROM wp_postmeta pm
INNER JOIN wp_posts p ON p.ID = pm.post_id
WHERE p.post_type = 'attachment';"

docker exec "$V7_DB_CONTAINER" mysqldump -u"$V7_DB_USER" -p"$V7_DB_PASS" "$V7_DB" \
  --skip-add-locks --complete-insert \
  _lp_migrate_attachment_meta \
  > "$TMP/attachment-meta-staging.sql"

v7_mysql "DROP TABLE IF EXISTS _lp_migrate_attachment_meta;"

docker exec "$V7_DB_CONTAINER" mysql -u"$V7_DB_USER" -p"$V7_DB_PASS" "$V7_DB" -N -e "
SELECT CONCAT(
  'INSERT INTO wp_options (option_name, option_value, autoload) VALUES (',
  QUOTE(option_name), ',', QUOTE(option_value), ',', QUOTE(autoload),
  ') ON DUPLICATE KEY UPDATE option_value=VALUES(option_value), autoload=VALUES(autoload);'
)
FROM wp_options
WHERE option_name IN (
  'rml_cqs','rml_importTaxNotice','rml_importTaxNotice-expire',
  'rml_licenseActivated','rml_licenseActivated-expire',
  'rml_load_frontend',
  'external_updates-real-media-library',
  'wpls_activation_id_real-media-library',
  'wpls_license_real-media-library'
);" > "$TMP/rml-options.sql"

say "Importing into v8 database"
v8_mysql "SET FOREIGN_KEY_CHECKS=0;
DELETE FROM wp_realmedialibrary_posts;
DELETE FROM wp_realmedialibrary_meta;
DELETE FROM wp_realmedialibrary;
DELETE pm FROM wp_postmeta pm
  INNER JOIN wp_posts p ON p.ID = pm.post_id
  WHERE p.post_type = 'attachment';
DELETE FROM wp_posts WHERE post_type = 'attachment';
SET FOREIGN_KEY_CHECKS=1;"

v8_mysql_file "$TMP/rml.sql"
v8_mysql_file "$TMP/attachments.sql"
v8_mysql_file "$TMP/attachment-meta-staging.sql"
v8_mysql "INSERT INTO wp_postmeta (post_id, meta_key, meta_value)
  SELECT post_id, meta_key, meta_value FROM _lp_migrate_attachment_meta;
DROP TABLE IF EXISTS _lp_migrate_attachment_meta;"
[[ -s "$TMP/rml-options.sql" ]] && v8_mysql_file "$TMP/rml-options.sql" || true
v8_mysql "DELETE FROM wp_options WHERE option_name IN ('rml_db_version','rml_db_previous_version','rml_db_migration');"

say "Updating attachment URLs for v8"
for old in "${V7_URLS[@]}"; do
  v8_mysql "UPDATE wp_posts
    SET guid = REPLACE(guid, '${old}', '${V8_URL}')
    WHERE post_type = 'attachment' AND guid LIKE '%${old}%';"
done

v8_mysql "SET @max_id = (SELECT IFNULL(MAX(ID), 0) FROM wp_posts);
SET @sql = CONCAT('ALTER TABLE wp_posts AUTO_INCREMENT = ', @max_id + 1);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @max_meta = (SELECT IFNULL(MAX(meta_id), 0) FROM wp_postmeta);
SET @sql = CONCAT('ALTER TABLE wp_postmeta AUTO_INCREMENT = ', @max_meta + 1);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;"

say "Activating plugins"
docker compose -f "${ROOT}/docker-compose.yml" exec -T cli wp plugin deactivate real-media-library-lite 2>/dev/null || true
docker compose -f "${ROOT}/docker-compose.yml" exec -T cli wp plugin activate real-media-library physical-custom-upload-folder

say "Resetting RML structure cache"
docker compose -f "${ROOT}/docker-compose.yml" exec -T cli wp eval 'if (function_exists("wp_rml_structure_reset")) { wp_rml_structure_reset(); echo "RML cache reset\n"; }'

say "Verification"
v8_mysql "SELECT COUNT(*) AS attachments FROM wp_posts WHERE post_type='attachment';
SELECT COUNT(*) AS folders FROM wp_realmedialibrary;
SELECT COUNT(*) AS mappings FROM wp_realmedialibrary_posts;"

MISSING=$(docker compose -f "${ROOT}/docker-compose.yml" exec -T cli wp eval '
$upload = wp_upload_dir();
$base = trailingslashit($upload["basedir"]);
$missing = 0;
$checked = 0;
$q = new WP_Query(["post_type" => "attachment", "posts_per_page" => -1, "fields" => "ids"]);
foreach ($q->posts as $id) {
  $file = get_post_meta($id, "_wp_attached_file", true);
  if (!$file) { continue; }
  $checked++;
  if (!file_exists($base . $file)) { $missing++; }
}
echo $missing . " missing of " . $checked;
')

echo "  Files on disk missing from uploads: ${MISSING}"

rm -rf "$TMP"

say "Done — open ${V8_URL}/wp-admin/upload.php"
