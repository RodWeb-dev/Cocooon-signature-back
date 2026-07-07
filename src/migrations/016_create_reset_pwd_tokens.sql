CREATE TABLE reset_pwd_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner VARCHAR(60) NOT NULL,
    value VARCHAR(64) NOT NULL UNIQUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    FOREIGN KEY (owner) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_owner (owner),
    INDEX idx_value (value)
);
