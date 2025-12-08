CREATE DATABASE IF NOT EXISTS dolphin_crm;
USE dolphin_crm;

CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    firstname VARCHAR(50) NOT NULL,
    lastname VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    role ENUM('Admin', 'Member') DEFAULT 'Member',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS contacts (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    title ENUM('Mr', 'Mrs', 'Ms', 'Dr', 'Prof') NOT NULL,
    firstname VARCHAR(50) NOT NULL,
    lastname VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    telephone VARCHAR(20),
    company VARCHAR(100),
    type ENUM('Sales Lead', 'Support') NOT NULL,
    assigned_to INTEGER,
    created_by INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_to) REFERENCES users(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS notes (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    contact_id INTEGER NOT NULL,
    comment TEXT NOT NULL,
    created_by INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id)
);


INSERT INTO users (firstname, lastname, email, password, role) 
SELECT 'Johnathan', 'Donut', 'admin@project2.com', '$2y$10$ueGdTsEm2JY2oWQ4fQfH2eYSEVti5Gp4DLgok91nJCZvLw35uF8lO', 'Admin'
WHERE NOT EXISTS (
    SELECT 1 
    FROM users
);