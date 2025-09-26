<?php
declare(strict_types=1);

require_once __DIR__ . '/../Model/Database.php';
require_once __DIR__ . '/../Model/BillModel.php';
require_once __DIR__ . '/../Model/UserModel.php';

class DashboardController {
    private BillModel $billModel;
    private UserModel $userModel;
    private PDO $db;

    public function __construct() {
        $this->billModel = new BillModel();
        $this->userModel = new UserModel();
        $this->db = Database::getInstance()->connect();
        session_start();
    }

    public function getDashboardData(): void {
        try {
            $this->checkAuthentication();
            $userId = $_SESSION['user']['id'];
            
            // Get user's bills
            $bills = $this->billModel->getUserBills($userId);
            
            // Get user's participation in other bills
            $participantBills = $this->getUserParticipantBills($userId);
            
            // Calculate user's overall balance
            $balance = $this->calculateUserBalance($userId);
            
            // Get recent activity
            $recentActivity = $this->getRecentActivity($userId);
            
            $this->sendResponse(true, 'Dashboard data retrieved successfully', [
                'user' => $_SESSION['user'],
                'bills' => $bills,
                'participant_bills' => $participantBills,
                'balance' => $balance,
                'recent_activity' => $recentActivity
            ]);
        } catch (Exception $e) {
            $this->sendResponse(false, $e->getMessage());
        }
    }

    public function getUserProfile(): void {
        try {
            $this->checkAuthentication();
            $userId = $_SESSION['user']['id'];
            
            $stmt = $this->db->prepare(
                "SELECT id, first_name, last_name, nickname, email, username, account_type, created_at 
                 FROM users WHERE id = :user_id"
            );
            $stmt->execute([':user_id' => $userId]);
            $user = $stmt->fetch();
            
            if (!$user) {
                throw new Exception('User not found');
            }
            
            $this->sendResponse(true, 'Profile retrieved successfully', ['profile' => $user]);
        } catch (Exception $e) {
            $this->sendResponse(false, $e->getMessage());
        }
    }

    public function updateProfile(): void {
        try {
            $this->checkAuthentication();
            $input = $this->getValidatedProfileInput();
            $userId = $_SESSION['user']['id'];
            
            // Check if nickname/username is unique (if changed)
            if (isset($input['nickname'])) {
                $stmt = $this->db->prepare(
                    "SELECT id FROM users WHERE nickname = :nickname AND id != :user_id"
                );
                $stmt->execute([':nickname' => $input['nickname'], ':user_id' => $userId]);
                if ($stmt->fetch()) {
                    throw new Exception('Nickname already taken');
                }
            }
            
            if (isset($input['username'])) {
                $stmt = $this->db->prepare(
                    "SELECT id FROM users WHERE username = :username AND id != :user_id"
                );
                $stmt->execute([':username' => $input['username'], ':user_id' => $userId]);
                if ($stmt->fetch()) {
                    throw new Exception('Username already taken');
                }
            }
            
            // Build update query
            $fields = [];
            $params = [':user_id' => $userId];
            
            foreach ($input as $field => $value) {
                $fields[] = "{$field} = :{$field}";
                $params[":{$field}"] = $value;
            }
            
            if (empty($fields)) {
                throw new Exception('No fields to update');
            }
            
            $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :user_id";
            $stmt = $this->db->prepare($sql);
            $success = $stmt->execute($params);
            
            if ($success) {
                // Update session data
                foreach ($input as $field => $value) {
                    $_SESSION['user'][$field] = $value;
                }
                
                $this->sendResponse(true, 'Profile updated successfully!');
            } else {
                throw new Exception('Failed to update profile');
            }
        } catch (Exception $e) {
            $this->sendResponse(false, $e->getMessage());
        }
    }

    public function getNotifications(): void {
        try {
            $this->checkAuthentication();
            $userId = $_SESSION['user']['id'];
            
            // Get notifications (simplified - you can expand this)
            $notifications = [
                [
                    'id' => 1,
                    'type' => 'bill_invite',
                    'message' => 'You have been invited to a new bill',
                    'created_at' => date('Y-m-d H:i:s'),
                    'read' => false
                ]
            ];
            
            $this->sendResponse(true, 'Notifications retrieved successfully', [
                'notifications' => $notifications,
                'unread_count' => count(array_filter($notifications, fn($n) => !$n['read']))
            ]);
        } catch (Exception $e) {
            $this->sendResponse(false, $e->getMessage());
        }
    }

    public function logout(): void {
        try {
            session_destroy();
            $this->sendResponse(true, 'Logged out successfully', [
                'redirect' => '../View/login.html'
            ]);
        } catch (Exception $e) {
            $this->sendResponse(false, $e->getMessage());
        }
    }

    private function getUserParticipantBills(int $userId): array {
        $stmt = $this->db->prepare(
            "SELECT b.*, bp.amount_owed, u.first_name, u.last_name, u.nickname as creator_name
             FROM bills b
             JOIN bill_participants bp ON b.id = bp.bill_id
             JOIN users u ON b.created_by = u.id
             WHERE bp.user_id = :user_id AND b.is_active = 1
             ORDER BY b.created_at DESC"
        );
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }

    private function calculateUserBalance(int $userId): array {
        // Calculate total owed by user
        $stmt = $this->db->prepare(
            "SELECT SUM(amount_owed) as total_owed 
             FROM bill_participants 
             WHERE user_id = :user_id"
        );
        $stmt->execute([':user_id' => $userId]);
        $owed = $stmt->fetch()['total_owed'] ?? 0;
        
        // Calculate total owed to user (from bills they created)
        $stmt = $this->db->prepare(
            "SELECT SUM(bp.amount_owed) as total_owed_to_user
             FROM bills b
             JOIN bill_participants bp ON b.id = bp.bill_id
             WHERE b.created_by = :user_id1 AND bp.user_id != :user_id2"
        );
        $stmt->execute([
            ':user_id1' => $userId,
            ':user_id2' => $userId
        ]);
        $owedToUser = $stmt->fetch()['total_owed_to_user'] ?? 0;
        
        $netBalance = $owedToUser - $owed;
        
        return [
            'total_owed' => (float)$owed,
            'total_owed_to_user' => (float)$owedToUser,
            'net_balance' => (float)$netBalance,
            'status' => $netBalance > 0 ? 'creditor' : ($netBalance < 0 ? 'debtor' : 'balanced')
        ];
    }

    private function getRecentActivity(int $userId): array {
        // Get recent bills created by user
        $stmt = $this->db->prepare(
            "SELECT 'bill_created' as type, b.title as description, b.created_at, b.id
             FROM bills b
             WHERE b.created_by = :user_id
             ORDER BY b.created_at DESC
             LIMIT 5"
        );
        $stmt->execute([':user_id' => $userId]);
        $activities = $stmt->fetchAll();
        
        // Get recent bills user participated in
        $stmt = $this->db->prepare(
            "SELECT 'bill_participated' as type, b.title as description, b.created_at, b.id
             FROM bills b
             JOIN bill_participants bp ON b.id = bp.bill_id
             WHERE bp.user_id = :user_id
             ORDER BY b.created_at DESC
             LIMIT 5"
        );
        $stmt->execute([':user_id' => $userId]);
        $participantActivities = $stmt->fetchAll();
        
        // Merge and sort by date
        $allActivities = array_merge($activities, $participantActivities);
        usort($allActivities, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));
        
        return array_slice($allActivities, 0, 10);
    }

    private function checkAuthentication(): void {
        if (!isset($_SESSION['user'])) {
            throw new Exception('Authentication required');
        }
    }

    private function getValidatedProfileInput(): array {
        $input = $this->getInputData();
        $allowedFields = ['first_name', 'last_name', 'nickname', 'username'];
        $validated = [];
        
        foreach ($allowedFields as $field) {
            if (isset($input[$field]) && !empty(trim($input[$field]))) {
                $validated[$field] = trim($input[$field]);
            }
        }
        
        // Validate email if provided
        if (isset($input['email']) && !empty($input['email'])) {
            if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid email format');
            }
            $validated['email'] = trim($input['email']);
        }
        
        return $validated;
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
    
    $controller = new DashboardController();
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'get_data':
            $controller->getDashboardData();
            break;
        case 'get_profile':
            $controller->getUserProfile();
            break;
        case 'update_profile':
            $controller->updateProfile();
            break;
        case 'get_notifications':
            $controller->getNotifications();
            break;
        case 'logout':
            $controller->logout();
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
