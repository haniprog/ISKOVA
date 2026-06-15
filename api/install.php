<?php
$host = getenv('DB_HOST') ?: 'localhost';
$port = getenv('DB_PORT') ?: '3306';
$dbName = getenv('DB_NAME') ?: 'iskova_db';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';

$connection = new mysqli($host, $user, $pass, '', (int) $port);
if ($connection->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Could not connect to MySQL: ' . $connection->connect_error]);
    exit;
}

$connection->query("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$connection->select_db($dbName);

$connection->query("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    user_id VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$connection->query("CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    lab VARCHAR(100) NOT NULL,
    date VARCHAR(20) NOT NULL,
    time VARCHAR(20) NOT NULL,
    time_out VARCHAR(20) NOT NULL,
    system VARCHAR(255) DEFAULT NULL,
    status VARCHAR(30) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_bookings_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$connection->query("CREATE TABLE IF NOT EXISTS labs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    capacity INT NOT NULL,
    computers INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    status VARCHAR(30) DEFAULT 'available',
    building VARCHAR(100) NOT NULL,
    floor VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$connection->query("INSERT IGNORE INTO labs (name, capacity, computers, type, status, building, floor) VALUES
    ('Lab A', 15, 15, 'small', 'available', 'Southwing', '5th Floor'),
    ('Lab B', 20, 20, 'small', 'available', 'Southwing', '5th Floor'),
    ('Lab C', 25, 25, 'medium', 'available', 'Southwing', '5th Floor'),
    ('Lab D', 30, 30, 'medium', 'available', 'Southwing', '5th Floor'),
    ('Lab E', 35, 35, 'medium', 'available', 'Southwing', '5th Floor'),
    ('Lab F', 40, 40, 'medium', 'available', 'Southwing', '5th Floor'),
    ('Lab G', 45, 45, 'large', 'available', 'Southwing', '5th Floor'),
    ('Lab H', 50, 50, 'large', 'available', 'Southwing', '5th Floor'),
    ('Lab I', 60, 60, 'large', 'available', 'Southwing', '5th Floor'),
    ('Lab J', 80, 80, 'large', 'available', 'Southwing', '5th Floor')");

echo json_encode(['success' => true, 'message' => 'MySQL schema initialized successfully.']);
