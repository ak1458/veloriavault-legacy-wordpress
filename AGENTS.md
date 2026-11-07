# Veloria WordPress source

This repository contains the custom theme/plugin and offline build contract. Never commit credentials, WordPress configuration, uploads, database dumps, customer data or local production backups. Use synthetic data for tests. Production actions need explicit owner authorization and verified backups. Preserve WooCommerce order/customer/product data and existing attribution. Run `make test` in the supported Unix/Docker environment before claiming runtime acceptance. Deployment is separate from a Git push.
