<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/email.php';

// Instantiate PHPMailer directly to send with SMTP debug enabled
try {
    $mail = createPHPMailer();
    $mail->SMTPDebug = 2; // Output verbose debug log
    $mail->Debugoutput = 'echo';
    
    $mail->setFrom('info@fleetrentalpro.com', 'Test Debugger');
    $mail->addAddress('info@fleetrentalpro.com');
    
    $html_body = "<!DOCTYPE html><html><head><meta charset=\"UTF-8\"></head><body><h1>Hello SMTP Debug</h1><p>Test body with checkmark ✓ and pound £350.00.</p></body></html>";
    
    $mail->isHTML(true);
    $mail->Subject = 'Test SMTP Debug ' . time();
    $mail->Body    = $html_body;
    $mail->AltBody = getPlainTextFallback($html_body);
    
    echo "--- SENDING EMAIL WITH SMTP DEBUG ---\n";
    $ok = $mail->send();
    echo "\nResult: " . ($ok ? "Success" : "Failed") . "\n";
} catch (Exception $e) {
    echo "\nException: " . $e->getMessage() . "\n";
}
