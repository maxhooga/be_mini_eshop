# be_mini_eshop

JSON API for a small order flow: product catalog → multi-item order → thank-you detail.  
Stack: **Nette** + **PostgreSQL**, prices with VAT (DPH), foreign total via **ČNB** daily rates (EUR).

## Requirements

- PHP 8.2+ with `pdo_pgsql`
- Composer
- PostgreSQL 14+
- Network access to ČNB rate list (for `POST /api/orders`)

## Quick start

```bash
make install
```

### 1. Database

```bash
createdb fapi_orders
# or: psql -c "CREATE DATABASE fapi_orders;"
```

### 2. Config

Copy credentials into `config/local.neon` (gitignored). Example:

```neon
database:
	dsn: 'pgsql:host=127.0.0.1;dbname=fapi_orders'
	user: your_user
	password: your_password
```

Defaults in `config/common.neon` are neutral placeholders (`user`/`password`: `fapi` / `fapi`, DB `fapi_orders` on `127.0.0.1`). Put real credentials only in `local.neon`.

CORS allows local Vite origins (`http://localhost:5173`, `:3000`, and `127.0.0.1` variants). Extend `corsAllowedOrigins` in `local.neon` if needed.

### 3. Schema + seed

Apply SQL files **in order**:

```bash
psql -d fapi_orders -f migrations/structures/2026-11-10-120000-products.sql
psql -d fapi_orders -f migrations/structures/2026-11-10-120100-orders.sql
psql -d fapi_orders -f migrations/structures/2026-11-10-120200-order-items.sql
psql -d fapi_orders -f seeds/products.sql
```

Seed loads three demo products: FAPI Starter, FAPI Pro, FAPI Consulting.

### 4. Run

```bash
make dev    # http://localhost:8000
```

### 5. Tests

```bash
make test
```

## API

| Method | Path | Description |
|--------|------|-------------|
| `GET` | `/api/products` | Active products |
| `POST` | `/api/orders` | Create order (customer + items) |
| `GET` | `/api/orders/{id}` | Order detail (thank-you) |

Successful responses use `{ "data": ... }`.  
Errors use `{ "error": { "message": "...", "errors"?: { field: message } } }` with HTTP 400 / 404 / 405 / 422 / 500.

### `POST /api/orders` body

```json
{
  "customerName": "Jan Novák",
  "customerEmail": "jan@example.com",
  "customerPhone": "+420777123456",
  "items": [
    { "productId": 1, "quantity": 2 },
    { "productId": 2, "quantity": 1 }
  ]
}
```

- Prices are snapshotted into `order_items` (no session cart).
- Totals include VAT; `totalForeign` is CZK→EUR via ČNB.

## curl examples

Base URL: `http://localhost:8000`

```bash
# List products
curl -s http://localhost:8000/api/products | jq

# Create order
curl -s -X POST http://localhost:8000/api/orders \
  -H 'Content-Type: application/json' \
  -d '{
    "customerName": "Jan Novák",
    "customerEmail": "jan@example.com",
    "customerPhone": "+420777123456",
    "items": [
      { "productId": 1, "quantity": 2 },
      { "productId": 2, "quantity": 1 }
    ]
  }' | jq

# Order detail (replace ID from create response)
curl -s http://localhost:8000/api/orders/1 | jq
```

Without `jq`, drop `| jq` — raw JSON is fine.

### Expected status codes (smoke)

| Call | Status |
|------|--------|
| `GET /api/products` | `200` |
| `POST /api/orders` (valid) | `201` |
| `POST /api/orders` (invalid body) | `422` |
| `POST /api/orders` (bad JSON) | `400` |
| `GET /api/orders/99999` | `404` |
| `GET /api/orders` | `405` |

## Makefile

| Target | What it does |
|--------|----------------|
| `make install` | `composer install` |
| `make dev` | PHP built-in server on `:8000` (`www/`) |
| `make test` | Nette Tester (`composer tester`) |
| `make up` | API + Postgres via Docker Compose (`http://localhost:8000`) |

## Deploy

The assignment asks for a public link. This repo deploys as a Docker web service plus Postgres.

On startup the container reads `DATABASE_URL`, applies `migrations/deploy/001_schema.sql` (existing orders are kept), and seeds products when the catalog is empty. `config/local.neon` is not used in the image. If `DATABASE_URL` is set locally, it overrides `local.neon`.

### Render (public URL)

Free tier, no credit card. The free database expires 30 days after creation, which is enough for the review.

1. Push this repository to a public GitHub repo.
2. Open [Render Blueprints](https://dashboard.render.com/blueprints) → **New Blueprint Instance** → select the repo.
3. Render reads `render.yaml`, creates the web service and Postgres in Frankfurt, and injects `DATABASE_URL`.
4. When the deploy is live, send this link:

```text
https://fapi-orders.onrender.com/api/products
```

The hostname is on the service page if Render adds a suffix. `GET /api/products` should return the three demo products. Creating an order needs a network call to ČNB, which the service can make.

The free web service sleeps after about 15 minutes. The first request after that can take around a minute.

To allow a browser frontend on another origin, set `CORS_ALLOWED_ORIGINS` to a comma-separated list (for example `https://your-frontend.example`). That list replaces the localhost defaults.

### Same image locally

```bash
make up
```

Postgres is published on `localhost:5433` so it does not clash with a database already running on `5432`. The API is `http://localhost:8000`.

## Project layout (short)

```
app/Model/           domain (products, orders, pricing, ČNB)
app/Presentation/Api JSON presenters
migrations/structures schema SQL for a local database
migrations/deploy    schema applied on every deploy (keeps data)
seeds/               demo products
tests/               unit + HTTP presenter tests
Dockerfile           image used by Render and docker compose
render.yaml          Render Blueprint (web service + Postgres)
```
