CREATE TABLE cart_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cart_id INT NOT NULL,
    ref VARCHAR(60) NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (cart_id) REFERENCES carts(id),
    FOREIGN KEY (ref) REFERENCES products(ref),
    CONSTRAINT uniq_ref UNIQUE KEY(cart_id, ref),
    INDEX idx_cart_id (cart_id),
    INDEX idx_ref (ref)
);
