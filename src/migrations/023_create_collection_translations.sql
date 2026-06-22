CREATE TABLE collection_translations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    collection_id INT NOT NULL,
    lang VARCHAR(5) NOT NULL,
    slug VARCHAR(60) NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    UNIQUE KEY uk_collection_lang (collection_id, lang),
    UNIQUE KEY uk_slug_lang (slug, lang),
    FOREIGN KEY (collection_id) REFERENCES collections(id) ON DELETE CASCADE
);
