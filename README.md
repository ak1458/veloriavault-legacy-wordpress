# Veloria Vault Website

This repository contains the source code for the **Veloria Vault** WordPress website.

## Project Overview
- **Domain**: [veloriavault.com](https://veloriavault.com)
- **Tech Stack**: WordPress, PHP, MySQL.
- **Goal**: Professional version control for themes, plugins, and core logic.

## Repository Management
- **Media (uploads/)**: Excluded from Git to keep the repository lightweight. Media is managed directly on the server.
- **Config (wp-config.php)**: Excluded for security. Use the existing server configuration.
- **Backups**: Database and site backups are stored in the server's \BACKUPS_AND_LOGS\ directory.

## Deployment Notes
Version control implemented for professional project management.

## Database Management
- **Backup**: A compressed SQL dump is included in the \database/\ directory (\database_dump.sql.gz\).
- **Restoration**:
    1. Unzip the file: \gunzip database/database_dump.sql.gz    2. Import to MySQL: \mysql -u [user] -p [database_name] < database/database_dump.sqlEOF
