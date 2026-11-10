-- Applied on every deploy. Safe to run again: existing orders are kept.
CREATE TABLE IF NOT EXISTS products (
	id SERIAL PRIMARY KEY,
	name VARCHAR(255) NOT NULL,
	description TEXT NULL,
	price_czk NUMERIC(12, 2) NOT NULL,
	vat_rate NUMERIC(5, 2) NOT NULL DEFAULT 21.00,
	is_active BOOLEAN NOT NULL DEFAULT TRUE,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS orders (
	id SERIAL PRIMARY KEY,
	customer_name VARCHAR(255) NOT NULL,
	customer_email VARCHAR(255) NOT NULL,
	customer_phone VARCHAR(32) NOT NULL,
	total_without_vat NUMERIC(12, 2) NOT NULL,
	total_vat NUMERIC(12, 2) NOT NULL,
	total_with_vat NUMERIC(12, 2) NOT NULL,
	currency VARCHAR(3) NOT NULL DEFAULT 'EUR',
	exchange_rate NUMERIC(12, 6) NOT NULL,
	exchange_rate_date DATE NOT NULL,
	total_foreign NUMERIC(12, 2) NOT NULL,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS order_items (
	id SERIAL PRIMARY KEY,
	order_id INTEGER NOT NULL REFERENCES orders (id) ON DELETE CASCADE,
	product_id INTEGER NOT NULL REFERENCES products (id),
	product_name VARCHAR(255) NOT NULL,
	quantity INTEGER NOT NULL CHECK (quantity >= 1),
	unit_price_czk NUMERIC(12, 2) NOT NULL,
	vat_rate NUMERIC(5, 2) NOT NULL,
	line_without_vat NUMERIC(12, 2) NOT NULL,
	line_vat NUMERIC(12, 2) NOT NULL,
	line_with_vat NUMERIC(12, 2) NOT NULL
);

CREATE INDEX IF NOT EXISTS order_items_order_id_idx ON order_items (order_id);
CREATE INDEX IF NOT EXISTS order_items_product_id_idx ON order_items (product_id);
