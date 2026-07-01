CREATE TABLE contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    mail VARCHAR(100) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    status ENUM ('Envoyé', 'Lu', 'Traité') DEFAULT 'Envoyé',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
