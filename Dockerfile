FROM composer:2 AS vendor

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
	--no-dev \
	--no-interaction \
	--no-scripts \
	--optimize-autoloader \
	--ignore-platform-reqs

FROM php:8.4-cli

RUN apt-get update \
	&& apt-get install -y --no-install-recommends libpq-dev \
	&& docker-php-ext-install pdo_pgsql \
	&& rm -rf /var/lib/apt/lists/*

WORKDIR /app

COPY . .
COPY --from=vendor /app/vendor ./vendor

RUN mkdir -p temp log \
	&& chmod -R 777 temp log \
	&& chmod +x bin/docker-entrypoint.sh

ENV PORT=10000
EXPOSE 10000

ENTRYPOINT ["/app/bin/docker-entrypoint.sh"]
