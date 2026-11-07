# Import verification — 7 November 2026

This is a source-repository update, not a deployment or fresh production acceptance test.

## Passed locally

- Node.js 24.19.0 lockfile installs and theme production build (Tailwind CSS, esbuild, five bundled font files).
- Generated asset byte hashes match the local source snapshot.
- CSS class verification and 35 theme tests; three project reproducibility contracts.
- All 108 PHP files pass syntax checks under PHP 8.3.28.
- 10,000 pricing vectors: zero mismatches; 1,453 business-rule checks.
- Asset resolver, 16 client-field, 15 receipt, four REST-hardening and nine cancellation-REST contracts.
- 18 shipping-assistant checks, 37 shipping/delivery regressions, 133 refund/cancellation regressions and 37 migration regressions.
- Runtime theme/plugin files compared byte-for-byte with the selected local source.
- Source exclusions, README links and code fences checked before commit. No environment/config/database backup files selected for publication.
- Existing repository LICENSE retained unchanged; full pre-update Git history saved in a verified local bundle.

## Environment limits

The first native Windows PHP run lacked the mbstring extension; the three affected suites passed when that existing extension was enabled for the test process. No global PHP or production configuration was changed.

The merchant-controls contract fails its exact `checkout/example.php` assertion on native Windows, where recursive filesystem paths contain backslashes. The existing cron contract also expects Unix executable/PATH semantics and fails under native Windows/Git Bash. Neither failure was hidden or marked passing. Their source and imported runtime code were preserved. Docker Desktop's Linux engine was unavailable for a full Linux `make test` run during this import. Consequently this report does not claim that the complete Unix/Docker contract passed in this session.

No live WordPress connection, deployment, DNS change, database import, email, payment, courier, or refund operation was performed. Provider/bank/mail/mobile/concurrency acceptance remains outside this repository sync.
