<?php
declare(strict_types=1);

require_once __DIR__ . '/../Model/Database.php';
require_once __DIR__ . '/../Model/BillModel.php';
require_once __DIR__ . '/../Model/UserModel.php';

class BillController {
    private BillModel $billModel;
    private UserModel $userModel;

    public function __construct() {
        $this->billModel = new BillModel();
        $this->userModel = new UserModel();
        session_start();
    }

    public function createBill(): void {
        try {
            $this->checkAuthentication();
            $input = $this->getValidatedBillInput();
            
            $billId = $this->billModel->createBill(
                $input['title'],
                $input['description'],
                $input['total_amount'],
                $_SESSION['user']['id']
            );
            
            if ($billId) {
                $this->sendResponse(true, 'Bill created successfully!', ['bill_id' => $billId]);
            } else {
                throw new Exception('Failed to create bill');
            }
        } catch (Exception $e) {
            $this->sendResponse(false, $e->getMessage());
        }
    }

    public function getBills(): void {
        try {
            $this->checkAuthentication();
            $bills = $this->billModel->getUserBills($_SESSION['user']['id']);
            $this->sendResponse(true, 'Bills retrieved successfully', ['bills' => $bills]);
        } catch (Exception $e) {
            $this->sendResponse(false, $e->getMessage());
        }
    }

    public function getBillDetails(): void {
        try {
            $this->checkAuthentication();
            $billId = $_GET['bill_id'] ?? null;
            
            if (!$billId) {
                throw new Exception('Bill ID is required');
            }
            
            $bill = $this->billModel->getBillById((int)$billId);
            if (!$bill) {
                throw new Exception('Bill not found');
            }
            
            $participants = $this->billModel->getBillParticipants((int)$billId);
            $bill['participants'] = $participants;
            
            $this->sendResponse(true, 'Bill details retrieved successfully', ['bill' => $bill]);
        } catch (Exception $e) {
            $this->sendResponse(false, $e->getMessage());
        }
    }

    public function addParticipant(): void {
        try {
            $this->checkAuthentication();
            $input = $this->getValidatedParticipantInput();
            
            $success = $this->billModel->addParticipant(
                $input['bill_id'],
                $input['user_id'] ?? null,
                $input['guest_name'] ?? null,
                $input['guest_email'] ?? null,
                $input['amount_owed']
            );
            
            if ($success) {
                $this->sendResponse(true, 'Participant added successfully!');
            } else {
                throw new Exception('Failed to add participant');
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

    private function getValidatedBillInput(): array {
        $input = $this->getInputData();
        
        $required = ['title'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                throw new Exception(ucfirst(str_replace('_', ' ', $field)) . ' is required');
            }
        }
        
        // Check total_amount separately to allow 0
        if (!isset($input['total_amount'])) {
            throw new Exception('Total amount is required');
        }

        if (!is_numeric($input['total_amount']) || $input['total_amount'] < 0) {
            throw new Exception('Total amount must be a non-negative number');
        }

        return [
            'title' => trim($input['title']),
            'description' => trim($input['description'] ?? ''),
            'total_amount' => (float)$input['total_amount']
        ];
    }

    private function getValidatedParticipantInput(): array {
        $input = $this->getInputData();
        
        $required = ['bill_id', 'amount_owed'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                throw new Exception(ucfirst(str_replace('_', ' ', $field)) . ' is required');
            }
        }

        if (!is_numeric($input['amount_owed']) || $input['amount_owed'] < 0) {
            throw new Exception('Amount owed must be a non-negative number');
        }

        return [
            'bill_id' => (int)$input['bill_id'],
            'user_id' => !empty($input['user_id']) ? (int)$input['user_id'] : null,
            'guest_name' => !empty($input['guest_name']) ? trim($input['guest_name']) : null,
            'guest_email' => !empty($input['guest_email']) ? trim($input['guest_email']) : null,
            'amount_owed' => (float)$input['amount_owed']
        ];
    }

    private function getInputData(): array {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (strpos($contentType, 'application/json') !== false) {
            $input = json_decode(file_get_contents('php://input'), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON input');
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
    $controller = new BillController();
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'create':
            $controller->createBill();
            break;
        case 'list':
            $controller->getBills();
            break;
        case 'details':
            $controller->getBillDetails();
            break;
        case 'add_participant':
            $controller->addParticipant();
            break;
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
