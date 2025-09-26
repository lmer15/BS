<?php
declare(strict_types=1);

require_once __DIR__ . '/../Model/Database.php';
require_once __DIR__ . '/../Model/BillModel.php';
require_once __DIR__ . '/../Model/UserModel.php';

class GuestController {
    private BillModel $billModel;
    private UserModel $userModel;

    public function __construct() {
        $this->billModel = new BillModel();
        $this->userModel = new UserModel();
        session_start();
    }

    public function createGuestAccess(): void {
        try {
            $input = $this->getValidatedGuestInput();
            
            // Check if bill exists
            $bill = $this->billModel->getBillByCode($input['bill_code']);
            if (!$bill) {
                throw new Exception('Invalid bill code');
            }

            // Create guest access record
            $success = $this->userModel->createGuestAccess(
                $input['first_name'],
                $input['last_name'],
                $input['email'],
                $input['bill_code']
            );

            if ($success) {
                $this->sendResponse(true, 'Guest access created successfully!', [
                    'bill_id' => $bill['id'],
                    'bill_title' => $bill['title']
                ]);
            } else {
                throw new Exception('Failed to create guest access');
            }
        } catch (Exception $e) {
            $this->sendResponse(false, $e->getMessage());
        }
    }

    public function validateGuestAccess(): void {
        try {
            $input = $this->getValidatedGuestInput();
            
            if ($this->userModel->validateGuestAccess($input['email'], $input['bill_code'])) {
                // Set guest session
                $_SESSION['guest'] = [
                    'first_name' => $input['first_name'],
                    'last_name' => $input['last_name'],
                    'email' => $input['email'],
                    'bill_code' => $input['bill_code'],
                    'access_type' => 'guest'
                ];
                
                $this->sendResponse(true, 'Guest access granted!', [
                    'redirect' => '../View/guest_dashboard.html'
                ]);
            } else {
                throw new Exception('Invalid bill code or access has expired');
            }
        } catch (Exception $e) {
            $this->sendResponse(false, $e->getMessage());
        }
    }

    public function getGuestBillDetails(): void {
        try {
            $this->checkGuestSession();
            
            $billCode = $_SESSION['guest']['bill_code'];
            $bill = $this->billModel->getBillByCode($billCode);
            
            if (!$bill) {
                throw new Exception('Bill not found');
            }

            $participants = $this->billModel->getBillParticipants($bill['id']);
            $bill['participants'] = $participants;
            
            $this->sendResponse(true, 'Bill details retrieved successfully', ['bill' => $bill]);
        } catch (Exception $e) {
            $this->sendResponse(false, $e->getMessage());
        }
    }

    public function updateGuestParticipant(): void {
        try {
            $this->checkGuestSession();
            
            $input = $this->getValidatedParticipantInput();
            $billCode = $_SESSION['guest']['bill_code'];
            $bill = $this->billModel->getBillByCode($billCode);
            
            if (!$bill) {
                throw new Exception('Bill not found');
            }

            // Find the guest participant
            $participants = $this->billModel->getBillParticipants($bill['id']);
            $guestParticipant = null;
            
            foreach ($participants as $participant) {
                if ($participant['guest_email'] === $_SESSION['guest']['email']) {
                    $guestParticipant = $participant;
                    break;
                }
            }

            if (!$guestParticipant) {
                throw new Exception('Guest participant not found');
            }

            $success = $this->billModel->updateParticipantAmount(
                $guestParticipant['id'], 
                $input['amount_owed']
            );

            if ($success) {
                $this->sendResponse(true, 'Amount updated successfully!');
            } else {
                throw new Exception('Failed to update amount');
            }
        } catch (Exception $e) {
            $this->sendResponse(false, $e->getMessage());
        }
    }

    private function checkGuestSession(): void {
        if (!isset($_SESSION['guest']) || $_SESSION['guest']['access_type'] !== 'guest') {
            throw new Exception('Guest session required');
        }
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

    private function getValidatedParticipantInput(): array {
        $input = $this->getInputData();
        
        if (empty($input['amount_owed']) || !is_numeric($input['amount_owed'])) {
            throw new Exception('Valid amount is required');
        }

        return [
            'amount_owed' => (float)$input['amount_owed']
        ];
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
    
    $controller = new GuestController();
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'create_access':
            $controller->createGuestAccess();
            break;
        case 'validate_access':
            $controller->validateGuestAccess();
            break;
        case 'get_bill_details':
            $controller->getGuestBillDetails();
            break;
        case 'update_participant':
            $controller->updateGuestParticipant();
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
