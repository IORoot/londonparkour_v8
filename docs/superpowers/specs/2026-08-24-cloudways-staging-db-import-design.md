# Design — Cloudways staging database import on Git Pull

Date: 2026-08-24
Status: draft, pending review

## Why

The Cloudways staging app already pulls this repo into `public_html` via the
Git GUI **Pull** button. That updates files only. Staging should also load
`database/backup.sql` into the **staging** database when that dump actually
changed — never the live app.

## Decisions

1. **Two Cloudways applications.** Git Pull runs on staging only.
2. **Import only when `database/backup.sql` changed**, detected by a SHA stamp,
   not by “every Pull”.
3. **Cloudways copies git → `public_html`; that tree is not a git repo.**
   GUI Pull does not run git hooks. Import is `database/cloudways_load.sh`
   from `public_html` (SSH after Pull, or a staging-only cron). Hooks in
   `config/githooks` are unused on Cloudways.
4. **Keep `wp search-replace`.** `WP_HOME` / `WP_SITEURL` override `home` and
   `siteurl` only. This dump contains ~3,400 `http://localhost:8102` URLs in
   `wp_posts` (permalinks, GUIDs, content). Those stay localhost unless
   rewritten. Constants are the *target* of the replace, not a substitute.
5. **Staging lock is a marker file** on the server, not a hostname allowlist
   in git. Live never gets the file.

## Architecture

```
Cloudways GUI "Pull"
        │
        ▼
   git fetch in git_repo  →  copy into public_html
        │
        ▼
   SSH or staging cron runs database/cloudways_load.sh
        │
        ├─ no public_html/.staging-import-enabled?  exit (refuse)
        ├─ dump hash == stamp?                      exit 0 (unless --force)
        ├─ read WP_HOME from public_html/wp-config
        ├─ strip DROP/CREATE DATABASE + USE from dump
        ├─ wp --path=public_html db import
        ├─ wp search-replace http://localhost:8102 → WP_HOME
        └─ write dump hash to public_html/.staging-import-sha
```

Marker and stamp live in `public_html` (Master SSH cannot write the
root-owned application directory). Cloudways Git copy leaves extra files
alone — same as `wp-config.php`.

## Components

| Path | Role |
|---|---|
| `database/cloudways_load.sh` | Import. Safe from any cwd (uses `--path` + `cd` into WordPress). `--force` ignores the SHA stamp. |
| `public_html/.staging-import-enabled` | Created once on staging. Absent → no import. Not in git. |
| `public_html/.staging-import-sha` | SHA of last successfully imported dump. |

`database/database_load.sh` stays Docker-only. Do not reuse it on Cloudways.

## Import behaviour

1. Abort unless `.staging-import-enabled` exists in `public_html`.
2. Abort unless wp-cli can see WordPress at that path (`wp --path=… core is-installed`).
3. Skip if SHA of `database/backup.sql` matches `.staging-import-sha`, unless
   `--force`.
4. Target URL: `WP_HOME` from wp-config. If unset, `wp option get home`
   **before** import. Abort if empty or still `localhost`.
5. Copy dump to a temp file; strip `DROP DATABASE`, `CREATE DATABASE`, and
   `USE` (dump targets Docker’s `wordpress` database, not Cloudways’).
6. `wp db import` the temp file into the database named in wp-config. Never
   create or drop databases.
7. `wp search-replace 'http://localhost:8102' "$WP_HOME"` across tables
   (serialized-safe). Do not use the constants as a replacement for this step.
8. `wp cache flush` and `wp rewrite flush`.
9. Write the dump SHA to `.staging-import-sha`.
10. Delete the temp file.

On any failure: non-zero exit, do not write the stamp, so the next Pull retries.
A mid-import MySQL failure can leave staging half-loaded; re-run with `--force`.

## One-time staging SSH

Not committed. Run once on the staging application:

```bash
cd ~/applications/londonparkour_staging/public_html
touch .staging-import-enabled
# cwd no longer matters after the lock exists:
bash ~/applications/londonparkour_staging/public_html/database/cloudways_load.sh
```

If `touch` is denied, SSH as the application user from Cloudways Access
Details (`rswhxpawjz`), not the Master `master_*` user.

Day-to-day: press Pull, then run the script (or a staging-only cron every
minute so Pull is enough). `--force` re-imports the same dump.

## Out of scope

- Live application (no marker file, no Git Pull on live).
- Syncing `wp-content/uploads` (gitignored; staging media 404s until copied).
- Purging Cloudways Varnish.
- Changing the local Docker dump/load path.

## Testing

Cannot hit Cloudways from this machine without credentials. Verify locally:

- Script refuses to run without `.staging-import-enabled`.
- SQL stripper removes `DROP`/`CREATE DATABASE`/`USE` from `backup.sql` and
  leaves table statements intact.
- `--force` is documented and parsed.

On staging, after a Pull that copies a new dump: run the script (or wait for
cron) and confirm `wp option get home` is the staging URL and a post permalink
does not contain `localhost:8102`.
