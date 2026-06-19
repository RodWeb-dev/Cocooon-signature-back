CREATE TABLE product_variants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_ref VARCHAR(60) NOT NULL,
    variant_ref VARCHAR(60) NOT NULL UNIQUE,
    odoo_variant_id INT,
    dimension VARCHAR(50),
    material_id INT NOT NULL,
    base_price DECIMAL(10, 2) NOT NULL,
    stock INT DEFAULT 0,
    synced_at DATETIME,
    FOREIGN KEY (product_ref) REFERENCES products(ref),
    FOREIGN KEY (material_id) REFERENCES materials(id),
    INDEX idx_ref (product_ref)
);
