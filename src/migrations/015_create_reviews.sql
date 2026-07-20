CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ref VARCHAR(60) NOT NULL,
    owner VARCHAR(60) NOT NULL,
    rating INT NOT NULL CHECK (
        rating >= 1
        AND rating <= 5
    ),
    comment TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ref) REFERENCES products(ref) ON DELETE CASCADE,
    FOREIGN KEY (owner) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_ref (ref),
    INDEX idx_owner (owner)
);
