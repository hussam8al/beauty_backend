-- Performance Optimization: Adding Indexes for faster JOINs and WHERE clauses

-- Products table: index for category filtering
CREATE INDEX IF NOT EXISTS idx_products_category_id ON products(category_id);
-- Products table: index for featured product filtering
CREATE INDEX IF NOT EXISTS idx_products_is_featured ON products(is_featured) WHERE is_featured = TRUE;

-- Favorites table: indexes for user and product lookups
CREATE INDEX IF NOT EXISTS idx_favorites_user_id ON favorites(user_id);
CREATE INDEX IF NOT EXISTS idx_favorites_product_id ON favorites(product_id);

-- Orders table: index for user order history
CREATE INDEX IF NOT EXISTS idx_orders_user_id ON orders(user_id);
-- Orders table: index for status filtering (e.g., pending orders)
CREATE INDEX IF NOT EXISTS idx_orders_status ON orders(status);

-- Order Items table: index for fetching items belonging to an order
CREATE INDEX IF NOT EXISTS idx_order_items_order_id ON order_items(order_id);
CREATE INDEX IF NOT EXISTS idx_order_items_product_id ON order_items(product_id);

-- Notifications table: index for fetching unread notifications for a user
CREATE INDEX IF NOT EXISTS idx_notifications_user_id_is_read ON notifications(user_id, is_read);

-- Ratings table: index for product rating summaries
CREATE INDEX IF NOT EXISTS idx_ratings_product_id ON ratings(product_id);
