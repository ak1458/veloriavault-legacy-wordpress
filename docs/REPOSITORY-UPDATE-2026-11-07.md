# WordPress source import — 7 November 2026

The owner requested publishing the migrated WordPress implementation from the local `new wordpress` source directory into the existing WordPress repository. Its local handoff records a 23 October WordPress cutover and 24 October theme/plugin 1.3.1 deployment; those are historical records, not a new live verification.

The current branch now tracks the custom theme/plugin, their design assets and offline tests, build tools and deployment templates. The earlier WordPress-core/third-party-plugin snapshot remains recoverable in Git history and a verified local bundle. Production environment files, database/customer backups, uploads, local agent settings, dependency folders and old internal audit material are excluded. The local source worktree and its uncommitted work were not reset or staged.

Public cron template home paths were replaced with placeholders in the script and its matching offline contract. Runtime theme/plugin code was imported byte-for-byte. The existing repository LICENSE and real developer credits were retained. The repository is active again; its existing URL/name is preserved.

This operation does not connect to the hosting server, deploy ZIPs, change DNS, run migrations, or submit any payment/order/refund/courier action. See `VERIFICATION-2026-11-07.md` for the checks actually run for this import.
