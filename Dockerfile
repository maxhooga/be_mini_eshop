FROM composer:2 AS vendor

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-scripts --optimize-autoloader

FROM php:8.3-cli

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

EXPOSE 8000

ENTRYPOINT ["bin/docker-entrypoint.sh"]
