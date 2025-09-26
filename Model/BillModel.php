<?php
declare(strict_types=1);

require_once __DIR__ . '/Database.php';

class BillModel {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->connect();
    }

    public function createBill(string $title, string $description, float $totalAmount, int $createdBy): ?int {
        $code = $this->generateBillCode();
        
        $stmt = $this->db->prepare(
            "INSERT INTO bills (code, title, description, total_amount, created_by) 
             VALUES (:code, :title, :description, :total_amount, :created_by)"
        );
        
        $success = $stmt->execute([
            ':code' => $code,
            ':title' => $title,
            ':description' => $description,
            ':total_amount' => $totalAmount,
            ':created_by' => $createdBy
        ]);
        
        return $success ? (int)$this->db->lastInsertId() : null;
    }

    public function getBillById(int $billId): ?array {
        $stmt = $this->db->prepare(
            "SELECT b.*, u.first_name, u.last_name, u.email as creator_email 
             FROM bills b 
             JOIN users u ON b.created_by = u.id 
             WHERE b.id = :bill_id AND b.is_active = 1"
        );
        $stmt->execute([':bill_id' => $billId]);
        return $stmt->fetch() ?: null;
    }

    public function getBillByCode(string $code): ?array {
        $stmt = $this->db->prepare(
            "SELECT b.*, u.first_name, u.last_name, u.email as creator_email 
             FROM bills b 
             JOIN users u ON b.created_by = u.id 
             WHERE b.code = :code AND b.is_active = 1"
        );
        $stmt->execute([':code' => $code]);
        return $stmt->fetch() ?: null;
    }

    public function getUserBills(int $userId): array {
        $stmt = $this->db->prepare(
            "SELECT b.*, 
             COUNT(bp.id) as participant_count,
             SUM(bp.amount_owed) as total_owed
             FROM bills b 
             LEFT JOIN bill_participants bp ON b.id = bp.bill_id
             WHERE b.created_by = :user_id AND b.is_active = 1
             GROUP BY b.id
             ORDER BY b.created_at DESC"
        );
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function getBillParticipants(int $billId): array {
        $stmt = $this->db->prepare(
            "SELECT bp.*, u.first_name, u.last_name, u.email, u.nickname
             FROM bill_participants bp
             LEFT JOIN users u ON bp.user_id = u.id
             WHERE bp.bill_id = :bill_id
             ORDER BY bp.id"
        );
        $stmt->execute([':bill_id' => $billId]);
        return $stmt->fetchAll();
    }

    public function addParticipant(
        int $billId, 
        ?int $userId, 
        ?string $guestName, 
        ?string $guestEmail, 
        float $amountOwed
    ): bool {
        // Validate that either user_id or guest info is provided
        if (!$userId && (!$guestName || !$guestEmail)) {
            throw new InvalidArgumentException('Either user ID or guest information is required');
        }

        // Check if bill exists and is active
        $bill = $this->getBillById($billId);
        if (!$bill) {
            throw new InvalidArgumentException('Bill not found or inactive');
        }

        // Check if user exists (if user_id provided)
        if ($userId) {
            $userExists = $this->db->prepare("SELECT id FROM users WHERE id = :user_id");
            $userExists->execute([':user_id' => $userId]);
            if (!$userExists->fetch()) {
                throw new InvalidArgumentException('User not found');
            }
        }

        // Check if participant already exists
        if ($userId) {
            $existingParticipant = $this->db->prepare(
                "SELECT id FROM bill_participants WHERE bill_id = :bill_id AND user_id = :user_id"
            );
            $existingParticipant->execute([':bill_id' => $billId, ':user_id' => $userId]);
            if ($existingParticipant->fetch()) {
                throw new InvalidArgumentException('User is already a participant in this bill');
            }
        }

        $stmt = $this->db->prepare(
            "INSERT INTO bill_participants (bill_id, user_id, guest_name, guest_email, amount_owed) 
             VALUES (:bill_id, :user_id, :guest_name, :guest_email, :amount_owed)"
        );
        
        return $stmt->execute([
            ':bill_id' => $billId,
            ':user_id' => $userId,
            ':guest_name' => $guestName,
            ':guest_email' => $guestEmail,
            ':amount_owed' => $amountOwed
        ]);
    }

    public function updateParticipantAmount(int $participantId, float $newAmount): bool {
        $stmt = $this->db->prepare(
            "UPDATE bill_participants SET amount_owed = :amount_owed WHERE id = :participant_id"
        );
        
        return $stmt->execute([
            ':participant_id' => $participantId,
            ':amount_owed' => $newAmount
        ]);
    }

    public function removeParticipant(int $participantId): bool {
        $stmt = $this->db->prepare("DELETE FROM bill_participants WHERE id = :participant_id");
        return $stmt->execute([':participant_id' => $participantId]);
    }

    public function deactivateBill(int $billId): bool {
        $stmt = $this->db->prepare(
            "UPDATE bills SET is_active = 0 WHERE id = :bill_id"
        );
        return $stmt->execute([':bill_id' => $billId]);
    }

    public function getBillSummary(int $billId): ?array {
        $bill = $this->getBillById($billId);
        if (!$bill) {
            return null;
        }

        $participants = $this->getBillParticipants($billId);
        $totalOwed = array_sum(array_column($participants, 'amount_owed'));
        
        return [
            'bill' => $bill,
            'participants' => $participants,
            'total_owed' => $totalOwed,
            'remaining_amount' => $bill['total_amount'] - $totalOwed,
            'is_fully_allocated' => abs($bill['total_amount'] - $totalOwed) < 0.01
        ];
    }

    private function generateBillCode(): string {
        do {
            $code = strtoupper(substr(md5(uniqid('', true)), 0, 8));
            $stmt = $this->db->prepare("SELECT id FROM bills WHERE code = :code");
            $stmt->execute([':code' => $code]);
        } while ($stmt->fetch());
        
        return $code;
    }
}
