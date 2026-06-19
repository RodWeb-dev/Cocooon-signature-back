CREATE TABLE users (
    id VARCHAR(60) DEFAULT (UUID_V7()) PRIMARY KEY,
    firstname VARCHAR(100) NOT NULL,
    lastname VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    hash_pwd VARCHAR(255) NOT NULL,
    phone_nbr VARCHAR(20),
    role ENUM('admin', 'editor', 'pro', 'user') DEFAULT 'user',
    email_verified TINYINT(1) DEFAULT 0,
    actif TINYINT(1) DEFAULT 1,
    birthday DATE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role)
);
