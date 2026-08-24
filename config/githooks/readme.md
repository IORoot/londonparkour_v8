# githooks

These scripts are used for deploying the site correctly via git. They include various tasks that enable proper deployments.

## pre-commit

This script runs before any files are committed and do the following tasks:

-   Extract the database from the database container using mysqldump. This is so the database SQL file can be committed into the repository automatically.

## Cloudways staging import

Cloudways clones into `git_repo` and copies files into `public_html`.
`public_html` is **not** a git repo, so `git config core.hooksPath` there
will fail, and `post-merge` / `post-checkout` will not run on GUI Pull.

After Pull, import from `public_html` (this is the supported path):

```bash
cd ~/applications/londonparkour_staging/public_html
touch .staging-import-enabled
bash database/cloudways_load.sh
```

The application root is owned by root; Master SSH cannot write there.
If `touch` in `public_html` is also denied, SSH as the **application** user
(`rswhxpawjz` — Cloudways → Access Details), not `master_*`.

To make Pull automatic without git hooks, add a **staging-only** cron
(Application → Cron Job Management):

```
* * * * * /bin/bash /home/master/applications/londonparkour_staging/public_html/database/cloudways_load.sh
```

The script no-ops unless the marker file exists and `backup.sql` changed.
Do not add the marker or the cron on live.
