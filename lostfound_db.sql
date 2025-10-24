-- Lost & Found Portal Database Export
-- This file contains the complete database structure and sample data
-- Import this file to set up the database on Server A

-- Create database
CREATE DATABASE IF NOT EXISTS lostfound_db;
USE lostfound_db;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    is_admin TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create items table
CREATE TABLE IF NOT EXISTS items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    type ENUM('lost', 'found') NOT NULL,
    location VARCHAR(100) NOT NULL,
    contact VARCHAR(100) NOT NULL,
    image VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert sample admin user (password: admin123)
INSERT INTO users (username, email, password, is_admin) VALUES 
('admin', 'admin@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1);

-- Insert sample regular user (password: user123)
INSERT INTO users (username, email, password, is_admin) VALUES 
('testuser', 'test@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0);

-- Insert sample items
INSERT INTO items (user_id, title, description, type, location, contact, image) VALUES 
(1, 'Black iPhone 13', 'Lost my iPhone 13 with a black case. Has a small crack on the screen. Last seen in the library.', 'lost', 'University Library, 2nd Floor', 'admin@university.edu', 'sample_phone.jpg'),
(2, 'Blue Backpack', 'Found a blue backpack near the cafeteria. Contains textbooks and a laptop.', 'found', 'Student Cafeteria', 'test@university.edu', 'sample_backpack.jpg'),
(1, 'Red Water Bottle', 'Lost my red water bottle with university logo. Has my name written on it.', 'lost', 'Gymnasium', 'admin@university.edu', 'sample_bottle.jpg'),
(2, 'Silver Watch', 'Found a silver watch in the parking lot. Looks expensive, want to return to owner.', 'found', 'Parking Lot B', 'test@university.edu', 'sample_watch.jpg');

-- Create indexes for better performance
CREATE INDEX idx_items_type ON items(type);
CREATE INDEX idx_items_created ON items(created_at);
CREATE INDEX idx_items_user ON items(user_id);
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_users_email ON users(email);

-- Show tables
SHOW TABLES;

-- Show sample data
SELECT 'Users Table:' as Table_Name;
SELECT * FROM users;

SELECT 'Items Table:' as Table_Name;
SELECT * FROM items;
