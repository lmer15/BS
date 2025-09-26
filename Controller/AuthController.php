<?php
declare(strict_types=1);

require_once __DIR__ . '/../Model/Database.php';
require_once __DIR__ . '/../Model/UserModel.php';

class AuthController {
    private UserModel $userModel;

    public function __construct() {
        $this->userModel = new UserModel();
        session_start();
    }

    public function register(): void {
        try {
            $input = $this->getValidatedRegisterInput();
            
            if ($this->userModel->register(
                $input['first_name'],
                $input['last_name'],
                $input['nickname'],
                $input['email'],
                $input['username'],
                $input['password']
            )) {
                $this->sendResponse(true, 'Registration successful!', 'login.html');
            } else {
                throw new Exception('Registration failed. Please try again.');
            }
        } catch (Exception $e) {
            $this->sendResponse(false, $e->getMessage());
        }
    }

    public function login(): void {
        try {
            $input = $this->getValidatedLoginInput();
            $user = $this->userModel->login($input['login'], $input['password']);
            
            if ($user) {
                $_SESSION['user'] = $user;
                $redirect = $user['account_type'] === 'premium' 
                    ? 'premium_dashboard.html' 
                    : 'dashboard.html';
                $this->sendResponse(true, 'Login successful!', $redirect);
            } else {
                throw new Exception('Invalid login credentials');
            }
        } catch (Exception $e) {
            $this->sendResponse(false, $e->getMessage());
        }
    }

    public function guestAccess(): void {
        try {
            $input = $this->getValidatedGuestInput();
            
            if ($this->userModel->validateGuestAccess($input['email'], $input['bill_code'])) {
                $_SESSION['guest'] = [
                    'first_name' => $input['first_name'],
                    'last_name' => $input['last_name'],
                    'email' => $input['email'],
                    'access_type' => 'guest'
                ];
                $this->sendResponse(true, 'Guest access granted!', 'guest_dashboard.html');
            } else {
                throw new Exception('Invalid bill code or access has expired');
            }
        } catch (Exception $e) {
            $this->sendResponse(false, $e->getMessage());
        }
    }

    private function getValidatedRegisterInput(): array {
        $input = $this->getInputData();
        
        $required = ['first_name', 'last_name', 'nickname', 'email', 'username', 'password', 'confirm_password'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                throw new Exception(ucfirst(str_replace('_', ' ', $field)) . ' is required');
            }
            $input[$field] = trim($input[$field]);
        }

        if ($input['password'] !== $input['confirm_password']) {
            throw new Exception('Passwords do not match');
        }

        return $input;
    }

    private function getValidatedLoginInput(): array {
        $input = $this->getInputData();
        
        if (empty($input['login'])) {
            throw new Exception('Email/Username is required');
        }
        if (empty($input['password'])) {
            throw new Exception('Password is required');
        }

        return [
            'login' => trim($input['login']),
            'password' => $input['password']
        ];
    }

    private function getValidatedGuestInput(): array {
        $input = $this->getInputData();
        
        $required = ['first_name', 'last_name', 'email', 'bill_code'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                throw new Exception(ucfirst(str_replace('_', ' ', $field)) . ' is required');
            }
            $input[$field] = trim($input[$field]);
        }

        if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format');
        }

        return $input;
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

    private function sendResponse(bool $success, string $message, ?string $redirect = null): void {
        $response = ['success' => $success, 'message' => $message];
        if ($redirect) {
            $response['redirect'] = $redirect;
        }
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}

// Main execution
try {
    // Ensure we always return JSON
    header('Content-Type: application/json');
    
    $controller = new AuthController();
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'register':
            $controller->register();
            break;
        case 'login':
            $controller->login();
            break;
        case 'guest':
            $controller->guestAccess();
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