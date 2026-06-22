CREATE TABLE products (
    ref VARCHAR(60) PRIMARY KEY,
    slug VARCHAR(60) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    dimensions VARCHAR(100),
    materials VARCHAR(100),
    price DECIMAL(10, 2) NOT NULL,
    delay INT DEFAULT 1,
    stock INT DEFAULT 0,
    category_id INT,
    subcategory_id INT,
    collection_id INT,
    synced_at DATETIME,
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (subcategory_id) REFERENCES subcategories(id),
    FOREIGN KEY (collection_id) REFERENCES collections(id),
    INDEX idx_slug (slug)
);
