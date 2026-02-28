-- MySQL Schema

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'admin'
);

-- Categories Table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    icon_class VARCHAR(255)
);

-- Tutorials Table
CREATE TABLE IF NOT EXISTS tutorials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    difficulty VARCHAR(50),
    duration INT,
    category_id INT,
    author_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    view_count INT DEFAULT 0,
    image_path VARCHAR(255),
    video_url VARCHAR(255),
    pdf_path VARCHAR(255),
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Steps Table
CREATE TABLE IF NOT EXISTS steps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tutorial_id INT NOT NULL,
    step_number INT NOT NULL,
    title VARCHAR(255),
    content TEXT,
    image_path VARCHAR(255),
    FOREIGN KEY (tutorial_id) REFERENCES tutorials(id) ON DELETE CASCADE
);

-- Materials Table
CREATE TABLE IF NOT EXISTS materials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tutorial_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    quantity VARCHAR(255),
    local_source VARCHAR(255),
    FOREIGN KEY (tutorial_id) REFERENCES tutorials(id) ON DELETE CASCADE
);

-- Indexes for performance
CREATE INDEX idx_tutorial_category ON tutorials(category_id);
CREATE INDEX idx_step_tutorial ON steps(tutorial_id);
CREATE INDEX idx_material_tutorial ON materials(tutorial_id);
