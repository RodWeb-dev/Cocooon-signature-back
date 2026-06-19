CREATE TABLE newsletter_subscribers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner VARCHAR(60) NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    subscribed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    synced_at DATETIME NULL,
    FOREIGN KEY (owner) REFERENCES users(id),
    INDEX idx_owner (owner)
);
