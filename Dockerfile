FROM node:24.19.0-bookworm-slim

RUN apt-get update \
    && apt-get install -y --no-install-recommends php-cli php-mbstring php-xml zip make ca-certificates \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /workspace

COPY wp-content/themes/veloria/package.json wp-content/themes/veloria/package-lock.json wp-content/themes/veloria/
RUN cd wp-content/themes/veloria && npm ci

COPY wp-content/plugins/veloria-commerce/tests/parity/package.json wp-content/plugins/veloria-commerce/tests/parity/package-lock.json wp-content/plugins/veloria-commerce/tests/parity/
RUN cd wp-content/plugins/veloria-commerce/tests/parity && npm ci

COPY . .

CMD ["make", "test"]
