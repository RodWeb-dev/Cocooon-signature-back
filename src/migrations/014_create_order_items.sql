CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    ref VARCHAR(60) NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (ref) REFERENCES products(ref) ON DELETE CASCADE,
    INDEX idx_order_id (order_id),
    INDEX idx_ref (ref)
);
