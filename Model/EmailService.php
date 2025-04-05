<?php
declare(strict_types=1);

class EmailService {
    public function sendWelcomeEmail(string $to, string $name): bool {
        $subject = 'Welcome to Bill Splitter';
        $message = $this->buildWelcomeEmail($name);
        $headers = $this->buildEmailHeaders();
        
        return mail($to, $subject, $message, $headers);
    }

    public function sendPasswordResetEmail(string $to, string $token): bool {
        $subject = 'Password Reset Request';
        $message = $this->buildResetEmail($token);
        $headers = $this->buildEmailHeaders();
        
        return mail($to, $subject, $message, $headers);
    }

    private function buildWelcomeEmail(string $name): string {
        return "
            <html>
            <body>
                <h2>Welcome, {$name}!</h2>
                <p>Thank you for registering with Bill Splitter.</p>
                <p><a href='http://localhost/bs/View/login.php'>Click here to login</a></p>
            </body>
            </html>
        ";
    }

    private function buildResetEmail(string $token): string {
        $resetLink = "http://localhost/bs/reset-password.php?token={$token}";
        return "
            <html>
            <body>
                <h2>Password Reset</h2>
                <p>Click this link to reset your password:</p>
                <p><a href='{$resetLink}'>{$resetLink}</a></p>
                <p>This link will expire in 1 hour.</p>
            </body>
            </html>
        ";
    }

    private function buildEmailHeaders(): string {
        return "MIME-Version: 1.0\r\n" .
               "Content-type: text/html; charset=UTF-8\r\n" .
               "From: no-reply@billsplitter.com\r\n";
    }
}