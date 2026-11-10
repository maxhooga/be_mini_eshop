CREATE TABLE order_items (
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

CREATE INDEX order_items_order_id_idx ON order_items (order_id);
CREATE INDEX order_items_product_id_idx ON order_items (product_id);
