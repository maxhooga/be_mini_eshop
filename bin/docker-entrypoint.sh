#!/bin/sh
set -eu

if [ -z "${DATABASE_URL:-}" ]; then
	echo "DATABASE_URL is required." >&2
	exit 1
fi

php bin/migrate.php

port="${PORT:-8000}"
echo "Listening on 0.0.0.0:${port}"
exec php -S "0.0.0.0:${port}" -t www www/router.php
