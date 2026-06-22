CREATE TABLE product_translations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_ref VARCHAR(60) NOT NULL,
    lang VARCHAR(5) NOT NULL,
    slug VARCHAR(60) NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    dimensions VARCHAR(100),
    materials VARCHAR(100),
    UNIQUE KEY uk_product_lang (product_ref, lang),
    UNIQUE KEY uk_slug_lang (slug, lang),
    FOREIGN KEY (product_ref) REFERENCES products(ref) ON DELETE CASCADE
);
