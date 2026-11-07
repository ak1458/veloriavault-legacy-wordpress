SHELL := /bin/sh
THEME := wp-content/themes/veloria
PARITY := wp-content/plugins/veloria-commerce/tests/parity

.PHONY: setup test build-release

setup:
	cd $(THEME) && npm ci
	cd $(PARITY) && npm ci

test:
	cd $(THEME) && node --test tools/verify-css.test.mjs tools/asset-parity.test.mjs tools/review-form.test.mjs tools/order-actions.test.mjs
	cd $(THEME) && npm run build
	cd $(THEME) && node tools/verify-css.mjs
	node tools/verify-project.mjs
	node tools/cron-gate-contract.mjs
	php tools/asset-resolver-contract.php
	php tools/client-fields-contract.php
	php tools/merchant-controls-contract.php
	php tools/order-received-contract.php
	php tools/rest-hardening-contract.php
	php tools/cancellation-rest-contract.php
	php tools/business-rules-contract.php
	php tools/chat-shipping-contract.php
	php wp-content/plugins/veloria-commerce/tests/shipping-delivery-regression.php
	php wp-content/plugins/veloria-commerce/tests/refund-cancellation-regression.php
	php wp-content/plugins/veloria-commerce/tests/migration-regression.php
	@set -e; for file in $$(find wp-content/themes/veloria wp-content/plugins/veloria-commerce -type f -name '*.php' -print); do php -l "$$file" >/dev/null; done
	php $(PARITY)/run-parity.php $(PARITY)/vectors.json

build-release:
	sh tools/build-release.sh
