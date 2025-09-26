<?php
declare(strict_types=1);

require_once __DIR__ . '/../Model/Database.php';
require_once __DIR__ . '/../Model/UserModel.php';
require_once __DIR__ . '/../Model/EmailService.php';

class PasswordController {
    private UserModel $userModel;
    private EmailService $emailService;
    private PDO $db;

    public function __construct() {
        $this->userModel = new UserModel();
        $this->emailService = new EmailService();
        $this->db = Database::getInstance()->connect();
        session_start();
    }

    public function requestPasswordReset(): void {
        try {
            $input = $this->getValidatedEmailInput();
            $email = $input['email'];
            
            // Check if user exists
            if (!$this->userModel->checkUserExists($email)) {
                // Don't reveal if email exists or not for security
                $this->sendResponse(true, 'If the email exists, a reset link has been sent.');
                return;
            }

            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expiresAt = (new DateTime())->add(new DateInterval('PT1H'))->format('Y-m-d H:i:s');

            // Store or update reset token
            $stmt = $this->db->prepare(
                "INSERT INTO password_resets (email, token, expires_at) 
                 VALUES (:email, :token, :expires_at)
                 ON DUPLICATE KEY UPDATE 
                 token = VALUES(token), 
                 expires_at = VALUES(expires_at)"
            );
            
            $success = $stmt->execute([
                ':email' => $email,
                ':token' => $token,
                ':expires_at' => $expiresAt
            ]);

            if ($success) {
                // Send reset email
                $this->emailService->sendPasswordResetEmail($email, $token);
                $this->sendResponse(true, 'If the email exists, a reset link has been sent.');
            } else {
                throw new Exception('Failed to process reset request');
            }
        } catch (Exception $e) {
            $this->sendResponse(false, $e->getMessage());
        }
    }

    public function validateResetToken(): void {
        try {
            $token = $_GET['token'] ?? '';
            
            if (empty($token)) {
                throw new Exception('Reset token is required');
            }

            $stmt = $this->db->prepare(
                "SELECT email FROM password_resets 
                 WHERE token = :token AND expires_at > NOW()"
            );
            $stmt->execute([':token' => $token]);
            $reset = $stmt->fetch();

            if (!$reset) {
                throw new Exception('Invalid or expired reset token');
            }

            $this->sendResponse(true, 'Token is valid', ['email' => $reset['email']]);
        } catch (Exception $e) {
            $this->sendResponse(false, $e->getMessage());
        }
    }

    public function resetPassword(): void {
        try {
            $input = $this->getValidatedResetInput();
            
            // Validate token
            $stmt = $this->db->prepare(
                "SELECT email FROM password_resets 
                 WHERE token = :token AND expires_at > NOW()"
            );
            $stmt->execute([':token' => $input['token']]);
            $reset = $stmt->fetch();

            if (!$reset) {
                throw new Exception('Invalid or expired reset token');
            }

            // Update password
            $hashedPassword = password_hash($input['password'], PASSWORD_BCRYPT, ['cost' => 12]);
            
            $stmt = $this->db->prepare(
                "UPDATE users SET password = :password WHERE email = :email"
            );
            $success = $stmt->execute([
                ':password' => $hashedPassword,
                ':email' => $reset['email']
            ]);

            if ($success) {
                // Delete the reset token
                $stmt = $this->db->prepare("DELETE FROM password_resets WHERE token = :token");
                $stmt->execute([':token' => $input['token']]);
                
                $this->sendResponse(true, 'Password reset successfully!', [
                    'redirect' => '../View/login.html'
                ]);
            } else {
                throw new Exception('Failed to reset password');
            }
        } catch (Exception $e) {
            $this->sendResponse(false, $e->getMessage());
        }
    }

    public function changePassword(): void {
        try {
            $this->checkAuthentication();
            $input = $this->getValidatedChangePasswordInput();
            
            $userId = $_SESSION['user']['id'];
            
            // Verify current password
            $stmt = $this->db->prepare("SELECT password FROM users WHERE id = :user_id");
            $stmt->execute([':user_id' => $userId]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($input['current_password'], $user['password'])) {
                throw new Exception('Current password is incorrect');
            }

            // Update password
            $hashedPassword = password_hash($input['new_password'], PASSWORD_BCRYPT, ['cost' => 12]);
            
            $stmt = $this->db->prepare(
                "UPDATE users SET password = :password WHERE id = :user_id"
            );
            $success = $stmt->execute([
                ':password' => $hashedPassword,
                ':user_id' => $userId
            ]);

            if ($success) {
                $this->sendResponse(true, 'Password changed successfully!');
            } else {
                throw new Exception('Failed to change password');
            }
        } catch (Exception $e) {
            $this->sendResponse(false, $e->getMessage());
        }
    }

    private function checkAuthentication(): void {
        if (!isset($_SESSION['user'])) {
            throw new Exception('Authentication required');
        }
    }

    private function getValidatedEmailInput(): array {
        $input = $this->getInputData();
        
        if (empty($input['email'])) {
            throw new Exception('Email is required');
        }

        if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format');
        }

        return ['email' => trim($input['email'])];
    }

    private function getValidatedResetInput(): array {
        $input = $this->getInputData();
        
        $required = ['token', 'password', 'confirm_password'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                throw new Exception(ucfirst(str_replace('_', ' ', $field)) . ' is required');
            }
        }

        if ($input['password'] !== $input['confirm_password']) {
            throw new Exception('Passwords do not match');
        }

        // Validate password strength
        $this->validatePassword($input['password']);

        return [
            'token' => $input['token'],
            'password' => $input['password']
        ];
    }

    private function getValidatedChangePasswordInput(): array {
        $input = $this->getInputData();
        
        $required = ['current_password', 'new_password', 'confirm_password'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                throw new Exception(ucfirst(str_replace('_', ' ', $field)) . ' is required');
            }
        }

        if ($input['new_password'] !== $input['confirm_password']) {
            throw new Exception('New passwords do not match');
        }

        // Validate password strength
        $this->validatePassword($input['new_password']);

        return [
            'current_password' => $input['current_password'],
            'new_password' => $input['new_password']
        ];
    }

    private function validatePassword(string $password): void {
        if (strlen($password) < 8 || strlen($password) > 16) {
            throw new Exception('Password must be 8-16 characters');
        }
        if (!preg_match('/[A-Z]/', $password)) {
            throw new Exception('Password needs at least one uppercase letter');
        }
        if (!preg_match('/[a-z]/', $password)) {
            throw new Exception('Password needs at least one lowercase letter');
        }
        if (!preg_match('/[0-9]/', $password)) {
            throw new Exception('Password needs at least one number');
        }
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            throw new Exception('Password needs at least one special character');
        }
    }

    private function getInputData(): array {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (strpos($contentType, 'application/json') !== false) {
            $rawInput = file_get_contents('php://input');
            if (empty($rawInput)) {
                throw new Exception('No input data received');
            }
            
            $input = json_decode($rawInput, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON input: ' . json_last_error_msg());
            }
            return $input ?? [];
        }
        
        return $_POST;
    }

    private function sendResponse(bool $success, string $message, ?array $data = null): void {
        $response = ['success' => $success, 'message' => $message];
        if ($data) {
            $response = array_merge($response, $data);
        }
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}

// Main execution
try {
    header('Content-Type: application/json');
    
    $controller = new PasswordController();
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'request_reset':
            $controller->requestPasswordReset();
            break;
        case 'validate_token':
            $controller->validateResetToken();
            break;
        case 'reset_password':
            $controller->resetPassword();
            break;
        case 'change_password':
            $controller->changePassword();
            break;
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
} catch (Error $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error'
    ]);
    exit;
}
