CREATE TABLE rate_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    action VARCHAR(60) NOT NULL,
    identifier VARCHAR(64) NOT NULL,
    attempts INT DEFAULT 1,
    first_attempt DATETIME DEFAULT CURRENT_TIMESTAMP,
    blocked_until DATETIME,
    CONSTRAINT uniq_action_identifier UNIQUE KEY (action, identifier)
);
