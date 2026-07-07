CREATE TABLE collection_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    collection_id INT NOT NULL,
    url VARCHAR(255) NOT NULL,
    alt TEXT,
    display_order INT,
    FOREIGN KEY (collection_id) REFERENCES collections(id) ON DELETE CASCADE,
    INDEX idx_collection_id (collection_id)
);
