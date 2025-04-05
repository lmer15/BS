<?php
declare(strict_types=1);

class UserModel {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->connect();
    }

    public function checkUserExists(string $value, string $field = 'email'): bool {
        $allowedFields = ['email', 'username', 'nickname'];
        if (!in_array($field, $allowedFields)) {
            throw new InvalidArgumentException("Invalid field for user check");
        }

        $stmt = $this->db->prepare("SELECT id FROM users WHERE {$field} = :value");
        $stmt->execute([':value' => $value]);
        return (bool)$stmt->fetch();
    }

    public function register(
        string $firstName,
        string $lastName,
        string $nickname,
        string $email,
        string $username,
        string $password
    ): bool {
        $this->validateRegistrationInput($firstName, $lastName, $nickname, $email, $username, $password);

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        
        $stmt = $this->db->prepare(
            "INSERT INTO users 
            (first_name, last_name, nickname, email, username, password) 
            VALUES 
            (:first_name, :last_name, :nickname, :email, :username, :password)"
        );
        
        return $stmt->execute([
            ':first_name' => $firstName,
            ':last_name' => $lastName,
            ':nickname' => $nickname,
            ':email' => $email,
            ':username' => $username,
            ':password' => $hashedPassword
        ]);
    }

    private function validateRegistrationInput(
        string $firstName,
        string $lastName,
        string $nickname,
        string $email,
        string $username,
        string $password
    ): void {
        // Validate first name
        if (empty($firstName) || strlen(trim($firstName)) < 2 || strlen(trim($firstName)) > 100) {
            throw new InvalidArgumentException("First name must be between 2-100 characters");
        }

        // Validate last name
        if (empty($lastName) || strlen(trim($lastName)) < 2 || strlen(trim($lastName)) > 100) {
            throw new InvalidArgumentException("Last name must be between 2-100 characters");
        }

        // Validate nickname
        if (empty($nickname) || strlen(trim($nickname)) < 3 || strlen(trim($nickname)) > 100) {
            throw new InvalidArgumentException("Nickname must be between 3-100 characters");
        }
        if ($this->checkUserExists($nickname, 'nickname')) {
            throw new InvalidArgumentException("Nickname already taken");
        }

        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid email format");
        }
        if ($this->checkUserExists($email)) {
            throw new InvalidArgumentException("Email already registered");
        }

        // Validate username
        if (empty($username) || strlen(trim($username)) < 4 || strlen(trim($username)) > 100) {
            throw new InvalidArgumentException("Username must be between 4-100 characters");
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            throw new InvalidArgumentException("Username can only contain letters, numbers and underscores");
        }
        if ($this->checkUserExists($username, 'username')) {
            throw new InvalidArgumentException("Username already taken");
        }

        // Validate password
        $this->validatePassword($password);
    }

    private function validatePassword(string $password): void {
        if (strlen($password) < 8 || strlen($password) > 16) {
            throw new InvalidArgumentException("Password must be 8-16 characters");
        }
        if (!preg_match('/[A-Z]/', $password)) {
            throw new InvalidArgumentException("Password needs at least one uppercase letter");
        }
        if (!preg_match('/[a-z]/', $password)) {
            throw new InvalidArgumentException("Password needs at least one lowercase letter");
        }
        if (!preg_match('/[0-9]/', $password)) {
            throw new InvalidArgumentException("Password needs at least one number");
        }
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            throw new InvalidArgumentException("Password needs at least one special character");
        }
    }

    public function login(string $login, string $password): ?array {
        $stmt = $this->db->prepare(
            "SELECT id, first_name, last_name, nickname, email, username, account_type 
             FROM users 
             WHERE (username = :login OR email = :login)
             AND password IS NOT NULL"
        );
        $stmt->execute([':login' => $login]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            unset($user['password']); // Never return password hash
            return $user;
        }
        return null;
    }

    public function createGuestAccess(
        string $firstName,
        string $lastName,
        string $email,
        string $billCode
    ): bool {
        // Set access to expire in 6 hours
        $expiresAt = (new DateTime())->add(new DateInterval('PT6H'))->format('Y-m-d H:i:s');

        $stmt = $this->db->prepare(
            "INSERT INTO guest_access 
            (first_name, last_name, email, bill_code, access_expires_at) 
            VALUES 
            (:first_name, :last_name, :email, :bill_code, :access_expires_at)"
        );
        
        return $stmt->execute([
            ':first_name' => $firstName,
            ':last_name' => $lastName,
            ':email' => $email,
            ':bill_code' => $billCode,
            ':access_expires_at' => $expiresAt
        ]);
    }

    public function validateGuestAccess(string $email, string $billCode): bool {
        $stmt = $this->db->prepare(
            "SELECT 1 FROM guest_access 
             WHERE email = :email 
             AND bill_code = :bill_code 
             AND (access_expires_at IS NULL OR access_expires_at > NOW())"
        );
        $stmt->execute([':email' => $email, ':bill_code' => $billCode]);
        return (bool)$stmt->fetch();
    }
}