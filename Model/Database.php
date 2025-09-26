<?php
declare(strict_types=1);

class Database {
    private PDO $connection;
    private static ?Database $instance = null;
    
    private function __construct() {
        require_once __DIR__ . '/../config.php';
        
        $host = DB_HOST;
        $dbname = DB_NAME;
        $username = DB_USER;
        $password = DB_PASS;
        
        try {
            $this->connection = new PDO(
                "mysql:host=$host;dbname=$dbname;charset=" . DB_CHARSET,
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
            
            // Initialize database tables if they don't exist
            $this->initializeDatabase();
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    private function initializeDatabase(): void {
        $sql = [
            "CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                first_name VARCHAR(100) NOT NULL,
                last_name VARCHAR(100) NOT NULL,
                nickname VARCHAR(100) NOT NULL UNIQUE,
                email VARCHAR(255) NOT NULL UNIQUE,
                username VARCHAR(100) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                account_type ENUM('standard', 'premium') DEFAULT 'standard',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )",
            
            "CREATE TABLE IF NOT EXISTS bills (
                id INT AUTO_INCREMENT PRIMARY KEY,
                code VARCHAR(10) NOT NULL UNIQUE,
                title VARCHAR(100) NOT NULL,
                description TEXT DEFAULT NULL,
                total_amount DECIMAL(10,2) NOT NULL,
                created_by INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                is_active TINYINT(1) DEFAULT 1,
                FOREIGN KEY (created_by) REFERENCES users(id)
            )",
            
            "CREATE TABLE IF NOT EXISTS bill_participants (
                id INT AUTO_INCREMENT PRIMARY KEY,
                bill_id INT NOT NULL,
                user_id INT DEFAULT NULL,
                guest_name VARCHAR(100) DEFAULT NULL,
                guest_email VARCHAR(100) DEFAULT NULL,
                amount_owed DECIMAL(10,2) NOT NULL,
                FOREIGN KEY (bill_id) REFERENCES bills(id),
                FOREIGN KEY (user_id) REFERENCES users(id)
            )",
            
            "CREATE TABLE IF NOT EXISTS guest_access (
                id INT AUTO_INCREMENT PRIMARY KEY,
                first_name VARCHAR(100) NOT NULL,
                last_name VARCHAR(100) NOT NULL,
                email VARCHAR(255) NOT NULL,
                bill_code VARCHAR(50) NOT NULL,
                access_expires_at TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
            
            "CREATE TABLE IF NOT EXISTS password_resets (
                email VARCHAR(100) NOT NULL PRIMARY KEY,
                token VARCHAR(64) NOT NULL,
                expires_at DATETIME NOT NULL,
                FOREIGN KEY (email) REFERENCES users(email) ON DELETE CASCADE
            )"
        ];
        
        foreach ($sql as $query) {
            try {
                $this->connection->exec($query);
            } catch (PDOException $e) {
                // Log error but continue with other tables
                error_log("Database initialization error: " . $e->getMessage());
            }
        }
    }
    
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function connect(): PDO {
        return $this->connection;
    }
    
    private function __clone() {}
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}