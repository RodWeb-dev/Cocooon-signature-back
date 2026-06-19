CREATE TABLE materials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    coefficient DECIMAL(5, 4) NOT NULL,
    synced_at DATETIME
);
