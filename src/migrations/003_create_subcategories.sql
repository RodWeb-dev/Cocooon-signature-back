CREATE TABLE subcategories (
    id INT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    name_en VARCHAR(100) UNIQUE,
    synced_at DATETIME
);
