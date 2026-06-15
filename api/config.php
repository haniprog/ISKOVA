<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

function getDbConnection()
{
    static $conn = null;
    if ($conn !== null) {
        return $conn;
    }

    $host = getenv('DB_HOST') ?: 'localhost';
    $port = getenv('DB_PORT') ?: '3306';
    $dbName = getenv('DB_NAME') ?: 'iskova_db';
    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASS') ?: '';

    $conn = new mysqli($host, $user, $pass, '', (int) $port);
    if ($conn->connect_error) {
        throw new RuntimeException('MySQL connection failed: ' . $conn->connect_error);
    }

    $conn->query("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    if (!$conn->select_db($dbName)) {
        throw new RuntimeException('Could not select database: ' . $dbName);
    }

    $conn->set_charset('utf8mb4');
    return $conn;
}

function bootDatabase()
{
    $conn = getDbConnection();

    $conn->query("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        full_name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        user_id VARCHAR(100) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("CREATE TABLE IF NOT EXISTS bookings (
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

    $conn->query("CREATE TABLE IF NOT EXISTS labs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        capacity INT NOT NULL,
        computers INT NOT NULL,
        type VARCHAR(50) NOT NULL,
        status VARCHAR(30) DEFAULT 'available',
        building VARCHAR(100) NOT NULL,
        floor VARCHAR(100) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("INSERT IGNORE INTO labs (name, capacity, computers, type, status, building, floor) VALUES
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

    return $conn;
}

function getCurrentUserId($conn)
{
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
}

function readJsonInput()
{
    $raw = file_get_contents('php://input');
    if (empty($raw)) {
        return [];
    }

    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function sendJson($payload, $statusCode = 200)
{
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

function requireLogin($conn)
{
    $userId = getCurrentUserId($conn);
    if (!$userId) {
        sendJson(['success' => false, 'error' => 'Authentication required'], 401);
    }

    return $userId;
}

try {
    $conn = bootDatabase();
} catch (Throwable $e) {
    sendJson(['success' => false, 'error' => $e->getMessage()], 500);
}
