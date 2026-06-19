----------
-- Create the cocoon database and tables
----------
--
-- Create the cocoon database
DROP DATABASE IF EXISTS cocoon;
CREATE DATABASE cocoon CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cocoon;
-- Create the users table
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
--
-- Create the addresses table
CREATE TABLE addresses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner VARCHAR(60) NOT NULL,
    name VARCHAR(100) NOT NULL,
    address VARCHAR(255) NOT NULL,
    postal_code VARCHAR(10) NOT NULL,
    city VARCHAR(100) NOT NULL,
    country VARCHAR(100) NOT NULL DEFAULT 'France',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_owner (owner)
);
--
-- Create the collections table
CREATE TABLE collections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(60) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    synced_at DATETIME
);
--
-- Create the categories table
CREATE TABLE categories (
    id INT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    synced_at DATETIME
);
--
-- Create the subcategories table
CREATE TABLE subcategories (
    id INT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    synced_at DATETIME
);
--
-- Create the products table
CREATE TABLE products (
    ref VARCHAR(60) PRIMARY KEY,
    slug VARCHAR(60) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    dimensions VARCHAR(100),
    materials VARCHAR(100),
    price DECIMAL(10, 2) NOT NULL,
    availability ENUM('in_stock', 'made_to_order') DEFAULT 'in_stock',
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
--
-- Create the carts table
CREATE TABLE carts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner VARCHAR(60) NOT NULL,
    status ENUM('pending', 'saved', 'validated', 'cancelled') DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner) REFERENCES users(id),
    INDEX idx_owner (owner)
);
--
-- Create the orders table
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner VARCHAR(60) NOT NULL,
    address_id INT NOT NULL,
    status ENUM(
        'pending',
        'validated',
        'shipped',
        'delivered',
        'cancelled'
    ) DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner) REFERENCES users(id),
    FOREIGN KEY (address_id) REFERENCES addresses(id),
    INDEX idx_owner (owner)
);
--
-- Create the cart_items table
CREATE TABLE cart_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cart_id INT NOT NULL,
    ref VARCHAR(60) NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (cart_id) REFERENCES carts(id),
    FOREIGN KEY (ref) REFERENCES products(ref),
    CONSTRAINT uniq_ref UNIQUE KEY(cart_id, ref),
    INDEX idx_cart_id (cart_id),
    INDEX idx_ref (ref)
);
--
-- Create the order_items table
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    ref VARCHAR(60) NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (ref) REFERENCES products(ref),
    INDEX idx_order_id (order_id),
    INDEX idx_ref (ref)
);
-- Create the reviews table
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ref VARCHAR(60) NOT NULL,
    owner VARCHAR(60) NOT NULL,
    rating INT NOT NULL CHECK (
        rating >= 1
        AND rating <= 5
    ),
    comment TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ref) REFERENCES products(ref),
    FOREIGN KEY (owner) REFERENCES users(id),
    INDEX idx_ref (ref),
    INDEX idx_owner (owner)
);
--
-- Create the collection_images table
CREATE TABLE collection_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    collection_id INT NOT NULL,
    url VARCHAR(255) NOT NULL,
    alt TEXT,
    display_order INT,
    FOREIGN KEY (collection_id) REFERENCES collections(id),
    INDEX idx_collection_id (collection_id)
);
--
-- Create the product_images table
CREATE TABLE product_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ref VARCHAR(60) NOT NULL,
    url VARCHAR(255) NOT NULL,
    alt TEXT,
    display_order INT,
    FOREIGN KEY (ref) REFERENCES products(ref),
    INDEX idx_ref (ref)
);
--
-- Create the reset_pwd_tokens table
CREATE TABLE reset_pwd_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner VARCHAR(60) NOT NULL,
    value VARCHAR(60) NOT NULL UNIQUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    FOREIGN KEY (owner) REFERENCES users(id),
    INDEX idx_owner (owner),
    INDEX idx_value (value)
);
--
-- Create the refresh_tokens table
CREATE TABLE refresh_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner VARCHAR(60) NOT NULL,
    value VARCHAR(60) NOT NULL UNIQUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    FOREIGN KEY (owner) REFERENCES users(id),
    INDEX idx_owner (owner),
    INDEX idx_value (value)
);
--
-- Create the verify_email_tokens
CREATE TABLE verify_email_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner VARCHAR(60) NOT NULL,
    value VARCHAR(60) NOT NULL UNIQUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    FOREIGN KEY (owner) REFERENCES users(id),
    INDEX idx_owner (owner),
    INDEX idx_value (value)
);
--
-- Create the rate_limits table
CREATE TABLE rate_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    action VARCHAR(60) NOT NULL,
    identifier VARCHAR(64) NOT NULL,
    attempts INT DEFAULT 1,
    first_attempt DATETIME DEFAULT CURRENT_TIMESTAMP,
    blocked_until DATETIME,
    CONSTRAINT uniq_action_identifier UNIQUE KEY (action, identifier)
);
--
-- Create the contact_messages table
CREATE TABLE contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    mail VARCHAR(100) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
--
-- Create the newletter table
CREATE TABLE newsletter_subscribers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner VARCHAR(60) NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    subscribed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    synced_at DATETIME NULL,
    FOREIGN KEY (owner) REFERENCES users(id),
    INDEX idx_owner (owner)
);
--
-- Create table materials for price coefficient
CREATE TABLE materials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    coefficient DECIMAL(5, 4) NOT NULL,
    synced_at DATETIME
);
--
-- Create table product_variants
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
