CREATE TABLE product_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ref VARCHAR(60) NOT NULL,
    url VARCHAR(255) NOT NULL,
    alt TEXT,
    display_order INT,
    FOREIGN KEY (ref) REFERENCES products(ref),
    INDEX idx_ref (ref)
);
