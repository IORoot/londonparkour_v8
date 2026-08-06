# githooks

These scripts are used for deploying the site correctly via git. They include various tasks that enable proper deployments.

## pre-commit

This script runs before any files are committed and do the following tasks:

-   Extract the database from the database container using mysqldump. This is so the database SQL file can be committed into the repository automatically.
