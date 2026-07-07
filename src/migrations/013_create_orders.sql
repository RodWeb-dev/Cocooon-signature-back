CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner VARCHAR(60) NOT NULL,
    address_id INT NOT NULL,
    status ENUM(
        'pending',
        'validated',
        'shipped',
        'delivered',
        'cancelled'
    ) DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (address_id) REFERENCES addresses(id) ON DELETE CASCADE,
    INDEX idx_owner (owner)
);
