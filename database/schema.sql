CREATE DATABASE IF NOT EXISTS pagecraft;
USE pagecraft;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50),
    password VARCHAR(255)
);

CREATE TABLE sites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    site_name VARCHAR(100),
    template_type VARCHAR(50) DEFAULT 'blank',
    is_published TINYINT(1) DEFAULT 0,
    is_archived TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE pages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    site_id INT,
    title VARCHAR(100),
    slug VARCHAR(100)
);

CREATE TABLE sections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    page_id INT,
    type VARCHAR(50),
    content TEXT,
    style TEXT,
    position INT DEFAULT 0,
    height INT DEFAULT 0,
    is_archived TINYINT(1) DEFAULT 0
);

CREATE TABLE visitor_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    site_id INT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    visited_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);