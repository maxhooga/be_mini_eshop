CREATE TABLE orders (
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
