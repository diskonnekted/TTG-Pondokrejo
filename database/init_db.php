<?php
// database/init_db.php

require_once __DIR__ . '/../includes/config.php';

try {
    $pdo = connectDB();
    
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    
    if ($driver === 'sqlite') {
        // SQLite Schema
        $queries = [
            "CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT UNIQUE NOT NULL,
                password_hash TEXT NOT NULL,
                role TEXT DEFAULT 'admin'
            )",
            "CREATE TABLE IF NOT EXISTS categories (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                icon_class TEXT
            )",
            "CREATE TABLE IF NOT EXISTS tutorials (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL,
                slug TEXT UNIQUE NOT NULL,
                description TEXT,
                difficulty TEXT,
                duration INTEGER,
                category_id INTEGER,
                author_id INTEGER,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                view_count INTEGER DEFAULT 0,
                image_path TEXT,
                FOREIGN KEY(category_id) REFERENCES categories(id),
                FOREIGN KEY(author_id) REFERENCES users(id)
            )",
            "CREATE TABLE IF NOT EXISTS steps (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                tutorial_id INTEGER NOT NULL,
                step_number INTEGER NOT NULL,
                title TEXT,
                content TEXT,
                image_path TEXT,
                FOREIGN KEY(tutorial_id) REFERENCES tutorials(id) ON DELETE CASCADE
            )",
            "CREATE TABLE IF NOT EXISTS materials (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                tutorial_id INTEGER NOT NULL,
                name TEXT NOT NULL,
                quantity TEXT,
                local_source TEXT,
                FOREIGN KEY(tutorial_id) REFERENCES tutorials(id) ON DELETE CASCADE
            )"
        ];
    } else {
        // MySQL / PostgreSQL Schema (Simplified for MySQL)
        $queries = [
            "CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(255) UNIQUE NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                role VARCHAR(50) DEFAULT 'admin'
            )",
            "CREATE TABLE IF NOT EXISTS categories (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                icon_class VARCHAR(255)
            )",
            "CREATE TABLE IF NOT EXISTS tutorials (
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
                FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
                FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL
            )",
            "CREATE TABLE IF NOT EXISTS steps (
                id INT AUTO_INCREMENT PRIMARY KEY,
                tutorial_id INT NOT NULL,
                step_number INT NOT NULL,
                title VARCHAR(255),
                content TEXT,
                image_path VARCHAR(255),
                FOREIGN KEY (tutorial_id) REFERENCES tutorials(id) ON DELETE CASCADE
            )",
            "CREATE TABLE IF NOT EXISTS materials (
                id INT AUTO_INCREMENT PRIMARY KEY,
                tutorial_id INT NOT NULL,
                name VARCHAR(255) NOT NULL,
                quantity VARCHAR(255),
                local_source VARCHAR(255),
                FOREIGN KEY (tutorial_id) REFERENCES tutorials(id) ON DELETE CASCADE
            )"
        ];
    }

    foreach ($queries as $query) {
        $pdo->exec($query);
    }
    
    echo "Database tables created successfully.\n";

    // Seed Data
    // Check if admin exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt->execute(['admin']);
    if ($stmt->fetchColumn() == 0) {
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)");
        $stmt->execute(['admin', $password, 'admin']);
        echo "Admin user created (user: admin, pass: admin123).\n";
    }

    // Check if categories exist
    $stmt = $pdo->query("SELECT COUNT(*) FROM categories");
    if ($stmt->fetchColumn() == 0) {
        $cats = [
            ['Pertanian', 'fas fa-seedling'],
            ['Peternakan', 'fas fa-cow'],
            ['Perikanan', 'fas fa-fish'],
            ['Pengolahan Limbah', 'fas fa-recycle'],
            ['Energi', 'fas fa-bolt'],
            ['Kerajinan', 'fas fa-tools']
        ];
        $stmt = $pdo->prepare("INSERT INTO categories (name, icon_class) VALUES (?, ?)");
        foreach ($cats as $cat) {
            $stmt->execute($cat);
        }
        echo "Categories seeded.\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
