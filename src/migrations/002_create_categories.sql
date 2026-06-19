CREATE TABLE categories (
    id INT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    synced_at DATETIME
);
