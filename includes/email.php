<?php
/**
 * Email Helper Functions
 * Handles email sending with SMTP configuration using PHPMailer
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Internal helper to create a configured PHPMailer instance
 */
function createPHPMailer() {
    $mail = new PHPMailer(true);
    
    // Server settings - centralized config
    $mail->isSMTP();
    $mail->Host       = 'fleetrentalpro.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'info@fleetrentalpro.com';
    $mail->Password   = 'FProfit_2026!';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;
    $mail->Timeout    = 5; // Prevent FastCGI from timing out
    $mail->CharSet    = 'UTF-8';
    $mail->Encoding   = 'quoted-printable';
    
    return $mail;
}

function getCustomEmailTemplate($tenant_id, $template_key) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM email_templates WHERE tenant_id = ? AND template_key = ? AND enabled = 1");
        $stmt->execute([$tenant_id, $template_key]);
        return $stmt->fetch();
    } catch (\Exception $e) {
        error_log("Error fetching custom email template: " . $e->getMessage());
        return null;
    }
}

function getPlainTextFallback($html) {
    // Replace <br>, <p>, and table rows with newlines to keep structure
    $text = preg_replace('/<br\s*\/?>/i', "\n", $html);
    $text = preg_replace('/<\/p>/i', "\n\n", $text);
    $text = preg_replace('/<\/tr>/i', "\n", $text);
    $text = preg_replace('/<\/td>/i', " ", $text);
    
    // Strip all remaining tags
    $text = strip_tags($text);
    
    // Decode HTML entities (e.g. &nbsp;, &amp;)
    $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
    
    // Normalize and trim extra spaces/newlines
    $text = preg_replace("/[ \t]+/", " ", $text);
    $text = preg_replace("/\n\s*\n\s*\n+/", "\n\n", $text);
    return trim($text);
}

function sendEmail($to, $subject, $message, $from_name = null) {
    if ($from_name === null) {
        $from_name = SITE_NAME;
    }
    
    try {
        $mail = createPHPMailer();
        
        // Recipients
        $mail->setFrom('info@fleetrentalpro.com', $from_name);
        $mail->addAddress($to);
        $mail->addReplyTo('info@fleetrentalpro.com', $from_name);
        
        // Clean and decode the subject line
        $clean_subject = html_entity_decode(strip_tags($subject), ENT_QUOTES, 'UTF-8');
        
        // Ensure any HTML entity escaped characters (like &lt; or &gt;) are fully decoded to raw HTML tags
        $html_message = htmlspecialchars_decode($message, ENT_QUOTES);
        if (strpos($html_message, '&lt;') !== false) {
            $html_message = html_entity_decode($html_message, ENT_QUOTES, 'UTF-8');
        }
        
        // Strip DOCTYPE declarations which can confuse some legacy/local email client parsers into plain-text rendering
        $html_message = preg_replace('/<!DOCTYPE[^>]*>/i', '', $html_message);
        
        // Ensure the HTML has proper structural wrappers if not present
        if (strpos($html_message, '<html') === false) {
            $html_message = '<html><head>'
                 . '<meta charset="UTF-8">'
                 . '<meta name="viewport" content="width=device-width,initial-scale=1">'
                 . '</head>'
                 . '<body style="margin:0;padding:0;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Arial,sans-serif;background:#f5f5f5;">'
                 . $html_message
                 . '</body></html>';
        }
        
        // Format the HTML string by inserting newlines between tags to keep line lengths short.
        // This prevents quoted-printable soft-wraps (= \r\n) from breaking inline CSS or HTML tags.
        $html_message = str_replace('><', ">\n<", $html_message);
        
        // Content settings
        $mail->isHTML(true);
        $mail->Subject = $clean_subject;
        $mail->Body    = $html_message;
        $mail->AltBody = getPlainTextFallback($html_message);
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed: {$e->getMessage()}");
        return false;
    }
}

function sendPasswordResetEmail($email, $name, $token) {
    $reset_link = SITE_URL . "/auth/reset-password.php?token=" . $token;
    
    $subject = "Password Reset Request - " . SITE_NAME;
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #000; color: #fff; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
            .content { background-color: #f9f9f9; padding: 30px; border-radius: 0 0 5px 5px; }
            .button { display: inline-block; padding: 12px 30px; background-color: #000; color: #fff !important; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
            .warning { background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 12px; margin: 15px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>" . SITE_NAME . "</h1>
            </div>
            <div class='content'>
                <h2>Password Reset Request</h2>
                <p>Hello " . htmlspecialchars($name) . ",</p>
                <p>We received a request to reset your password for your " . SITE_NAME . " account. Click the button below to create a new password:</p>
                <p style='text-align: center;'>
                    <a href='" . $reset_link . "' class='button' style='color: #fff;'>Reset Password</a>
                </p>
                <p>Or copy and paste this link into your browser:</p>
                <p style='word-break: break-all; color: #666; background: #fff; padding: 10px; border-radius: 3px;'>" . $reset_link . "</p>
                <div class='warning'>
                    <strong>⏰ This link will expire in 1 hour.</strong>
                </div>
                <p>If you didn't request a password reset, you can safely ignore this email. Your password will remain unchanged.</p>
                <p>For security reasons, we never send your password via email.</p>
            </div>
            <div class='footer'>
                <p>&copy; " . date('Y') . " " . SITE_NAME . ". All rights reserved.</p>
                <p>This is an automated message, please do not reply to this email.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($email, $subject, $message);
}

function sendWelcomeEmail($email, $name) {
    $subject = "Welcome to " . SITE_NAME;
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #000; color: #fff; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
            .content { background-color: #f9f9f9; padding: 30px; border-radius: 0 0 5px 5px; }
            .button { display: inline-block; padding: 12px 30px; background-color: #000; color: #fff !important; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Welcome to " . SITE_NAME . "!</h1>
            </div>
            <div class='content'>
                <h2>Hello " . htmlspecialchars($name) . "!</h2>
                <p>Thank you for joining " . SITE_NAME . ". We're excited to have you on board!</p>
                <p>You can now access your dashboard and start managing your fleet rental business.</p>
                <p style='text-align: center;'>
                    <a href='" . SITE_URL . "/auth/login.php' class='button' style='color: #fff;'>Login to Dashboard</a>
                </p>
                <p>If you have any questions, feel free to reach out to our support team.</p>
            </div>
            <div class='footer'>
                <p>&copy; " . date('Y') . " " . SITE_NAME . ". All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($email, $subject, $message);
}

/**
 * Send "Welcome & Sign Your Contract" email after booking is created
 */
function sendContractWelcomeEmail($to, $customerName, $contractUrl, $tenant) {
    $tenantId = $tenant['id'] ?? null;
    $primaryColor = $tenant['primary_color'] ?? '#3B82F6';
    $tenantName = htmlspecialchars($tenant['name']);

    // Check for custom email template in the database
    if ($tenantId) {
        $customTpl = getCustomEmailTemplate($tenantId, 'contract_welcome');
        if ($customTpl) {
            $logoUrl = !empty($tenant['logo']) ? (strpos($tenant['logo'], 'http') === 0 ? $tenant['logo'] : SITE_URL . $tenant['logo']) : '';
            $logoHtml = $logoUrl 
                ? '<img src="' . $logoUrl . '" alt="' . $tenantName . '" style="max-height:38px;vertical-align:middle;display:inline-block;" />'
                : '<span style="font-size:18px;font-weight:700;color:#111111;">' . $tenantName . '</span>';

            $sample = [
                '{{company_logo}}'   => $logoHtml,
                '{{customer_name}}'  => $customerName,
                '{{contract_url}}'   => $contractUrl,
                '{{company_name}}'   => $tenantName,
                '{{vehicle_name}}'   => 'your booked vehicle', // Generic/fallback since vehicle isn't directly passed here
                '{{pickup_date}}'    => 'your pickup date',
            ];
            $renderedSubject = str_replace(array_keys($sample), array_values($sample), $customTpl['subject']);
            $renderedBody    = str_replace(array_keys($sample), array_values($sample), $customTpl['body']);
            return sendEmail($to, $renderedSubject, $renderedBody, $tenantName);
        }
    }
    
    $subject = "Welcome! Please Sign Your Rental Contract - " . $tenantName;
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: {$primaryColor}; color: #fff; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
            .content { background-color: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
            .button { display: inline-block; padding: 14px 36px; background-color: {$primaryColor}; color: #fff !important; text-decoration: none; border-radius: 8px; margin: 20px 0; font-weight: bold; font-size: 16px; }
            .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
            .step { display: flex; align-items: flex-start; margin-bottom: 16px; }
            .step-num { background-color: {$primaryColor}; color: #fff; width: 28px; height: 28px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-weight: bold; margin-right: 12px; flex-shrink: 0; font-size: 14px; }
            .highlight { background-color: #fff; border-left: 4px solid {$primaryColor}; padding: 16px; margin: 20px 0; border-radius: 4px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1 style='margin:0; font-size: 24px;'>{$tenantName}</h1>
                <p style='margin:8px 0 0; opacity: 0.9; font-size: 14px;'>Rental Contract Ready for Signing</p>
            </div>
            <div class='content'>
                <h2 style='margin-top:0;'>Welcome, " . htmlspecialchars($customerName) . "!</h2>
                <p>Thank you for your booking with <strong>{$tenantName}</strong>. Before your rental begins, please review and sign your rental contract.</p>
                
                <div class='highlight'>
                    <p style='margin:0;'><strong>Here's what you'll need to do:</strong></p>
                </div>
                
                <div style='margin: 20px 0;'>
                    <div class='step'>
                        <span class='step-num'>1</span>
                        <div><strong>Verify Your ID</strong><br><span style='color:#666;'>Quick identity check for your security</span></div>
                    </div>
                    <div class='step'>
                        <span class='step-num'>2</span>
                        <div><strong>Review Your Contract</strong><br><span style='color:#666;'>Read through the rental terms and conditions</span></div>
                    </div>
                    <div class='step'>
                        <span class='step-num'>3</span>
                        <div><strong>Sign Digitally</strong><br><span style='color:#666;'>Sign your contract electronically to confirm your booking</span></div>
                    </div>
                    <div class='step'>
                        <span class='step-num'>4</span>
                        <div><strong>Manage Online</strong><br><span style='color:#666;'>Log in to your account to view bookings and signed documents</span></div>
                    </div>
                </div>
                
                <p style='text-align: center;'>
                    <a href='" . htmlspecialchars($contractUrl) . "' class='button' style='color: #fff;'>Review & Sign Contract</a>
                </p>
                <p style='color: #666; font-size: 13px;'>If the button above doesn't work, copy and paste this link into your browser:</p>
                <p style='word-break: break-all; color: #666; background: #fff; padding: 10px; border-radius: 4px; font-size: 12px;'>" . htmlspecialchars($contractUrl) . "</p>
            </div>
            <div class='footer'>
                <p>&copy; " . date('Y') . " {$tenantName}. All rights reserved.</p>
                <p>This is an automated message. Please do not reply to this email.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($to, $subject, $message, $tenantName);
}

/**
 * Send signed contract PDF to customer
 */
function sendSignedContractEmail($to, $customerName, $pdfPath, $tenant) {
    $primaryColor = $tenant['primary_color'] ?? '#3B82F6';
    $tenantName = htmlspecialchars($tenant['name']);
    
    $subject = "Your Signed Rental Contract - " . $tenantName;
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: {$primaryColor}; color: #fff; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
            .content { background-color: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
            .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
            .success-badge { background-color: #d1fae5; color: #065f46; padding: 12px 20px; border-radius: 8px; text-align: center; margin: 20px 0; font-weight: 600; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1 style='margin:0; font-size: 24px;'>{$tenantName}</h1>
                <p style='margin:8px 0 0; opacity: 0.9; font-size: 14px;'>Contract Signed Successfully</p>
            </div>
            <div class='content'>
                <h2 style='margin-top:0;'>All Set, " . htmlspecialchars($customerName) . "!</h2>
                <div class='success-badge'>✓ Your rental contract has been signed successfully</div>
                <p>Your signed rental contract is attached to this email as a PDF. Please keep it for your records.</p>
                <p>If you have any questions about your rental, don't hesitate to contact us.</p>
                <p>We look forward to serving you!</p>
            </div>
            <div class='footer'>
                <p>&copy; " . date('Y') . " {$tenantName}. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    try {
        $mail = createPHPMailer();
        
        $mail->setFrom('info@fleetrentalpro.com', $tenantName);
        $mail->addAddress($to);
        $mail->addReplyTo('info@fleetrentalpro.com', $tenantName);
        
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;
        $mail->AltBody = strip_tags($message);
        
        // Attach the signed PDF
        if (file_exists($pdfPath)) {
            $mail->addAttachment($pdfPath, 'Signed_Rental_Contract.pdf');
        }
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Signed contract email sending failed: {$e->getMessage()}");
        return false;
    }
}

/**
 * Send customer account credentials email after booking
 */
function sendCustomerAccountEmail($to, $customerName, $password, $tenant, $bookingId) {
    $tenantId = $tenant['id'] ?? null;
    $primaryColor = $tenant['primary_color'] ?? '#3B82F6';
    $tenantName = htmlspecialchars($tenant['name']);
    
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $loginUrl = $protocol . $host . '/auth/login.php';

    // Check for custom email template in the database
    if ($tenantId) {
        $customTpl = getCustomEmailTemplate($tenantId, 'account_created');
        if ($customTpl) {
            $logoUrl = !empty($tenant['logo']) ? (strpos($tenant['logo'], 'http') === 0 ? $tenant['logo'] : SITE_URL . $tenant['logo']) : '';
            $logoHtml = $logoUrl 
                ? '<img src="' . $logoUrl . '" alt="' . $tenantName . '" style="max-height:38px;vertical-align:middle;display:inline-block;" />'
                : '<span style="font-size:18px;font-weight:700;color:#111111;">' . $tenantName . '</span>';

            $sample = [
                '{{company_logo}}'   => $logoHtml,
                '{{customer_name}}'  => $customerName,
                '{{customer_email}}' => $to,
                '{{password}}'       => $password,
                '{{booking_ref}}'    => str_pad($bookingId, 5, '0', STR_PAD_LEFT),
                '{{company_name}}'   => $tenantName,
                '{{login_url}}'      => $loginUrl,
            ];
            $renderedSubject = str_replace(array_keys($sample), array_values($sample), $customTpl['subject']);
            $renderedBody    = str_replace(array_keys($sample), array_values($sample), $customTpl['body']);
            return sendEmail($to, $renderedSubject, $renderedBody, $tenantName);
        }
    }
    
    $subject = "Your Account Details - " . $tenantName;
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: {$primaryColor}; color: #fff; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
            .content { background-color: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
            .button { display: inline-block; padding: 14px 36px; background-color: {$primaryColor}; color: #fff !important; text-decoration: none; border-radius: 8px; margin: 20px 0; font-weight: bold; font-size: 16px; }
            .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
            .credentials { background-color: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; margin: 20px 0; }
            .credentials p { margin: 8px 0; }
            .credentials strong { display: inline-block; min-width: 80px; }
            .warning { background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 12px; margin: 15px 0; border-radius: 4px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1 style='margin:0; font-size: 24px;'>{$tenantName}</h1>
                <p style='margin:8px 0 0; opacity: 0.9; font-size: 14px;'>Your Account Has Been Created</p>
            </div>
            <div class='content'>
                <h2 style='margin-top:0;'>Welcome, " . htmlspecialchars($customerName) . "!</h2>
                <p>Your booking <strong>#" . htmlspecialchars($bookingId) . "</strong> has been confirmed and an account has been created for you so you can manage your bookings online.</p>
                
                <div class='credentials'>
                    <p style='margin-top:0; font-weight: 600; font-size: 15px;'>Your Login Details:</p>
                    <p><strong>Email:</strong> " . htmlspecialchars($to) . "</p>
                    <p><strong>Password:</strong> " . htmlspecialchars($password) . "</p>
                </div>
                
                <div class='warning'>
                    <strong>🔒 For your security</strong>, we recommend changing your password after your first login.
                </div>
                
                <p style='text-align: center;'>
                    <a href='" . htmlspecialchars($loginUrl) . "' class='button' style='color: #fff;'>Login to Your Account</a>
                </p>
                
                <p style='color: #666; font-size: 13px;'>If the button above doesn't work, copy and paste this link into your browser:</p>
                <p style='word-break: break-all; color: #666; background: #fff; padding: 10px; border-radius: 4px; font-size: 12px;'>" . htmlspecialchars($loginUrl) . "</p>
            </div>
            <div class='footer'>
                <p>&copy; " . date('Y') . " {$tenantName}. All rights reserved.</p>
                <p>This is an automated message. Please do not reply to this email.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($to, $subject, $message, $tenantName);
}

/**
 * Send contract signed notification to tenant admin
 */
function sendContractSignedNotification($adminEmail, $customerName, $bookingRef, $tenant) {
    $primaryColor = $tenant['primary_color'] ?? '#3B82F6';
    $tenantName = htmlspecialchars($tenant['name']);
    
    $subject = "Contract Signed - Booking #{$bookingRef} - " . $tenantName;
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #1a1f2b; color: #fff; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
            .content { background-color: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
            .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
            .info-box { background-color: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; margin: 16px 0; }
            .badge { display: inline-block; background-color: #d1fae5; color: #065f46; padding: 4px 12px; border-radius: 20px; font-size: 13px; font-weight: 600; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1 style='margin:0; font-size: 24px;'>{$tenantName}</h1>
                <p style='margin:8px 0 0; opacity: 0.7; font-size: 14px;'>Admin Notification</p>
            </div>
            <div class='content'>
                <h2 style='margin-top:0;'>Contract Signed ✓</h2>
                <p>A customer has signed their rental contract.</p>
                <div class='info-box'>
                    <p style='margin:0 0 8px;'><strong>Customer:</strong> " . htmlspecialchars($customerName) . "</p>
                    <p style='margin:0 0 8px;'><strong>Booking:</strong> #{$bookingRef}</p>
                    <p style='margin:0;'><strong>Status:</strong> <span class='badge'>Signed</span></p>
                </div>
                <p>You can view the full booking details and signed contract in your dashboard.</p>
            </div>
            <div class='footer'>
                <p>&copy; " . date('Y') . " " . SITE_NAME . ". All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($adminEmail, $subject, $message, $tenantName);
}

/**
 * Send booking confirmation email to customer
 */
function sendBookingConfirmationEmail($to, $bookingData, $tenant, $vehicle) {
    $tenantId = $tenant['id'] ?? null;
    $tenantName = htmlspecialchars($tenant['name'] ?? 'Fleet Rental');
    $primaryColor = $tenant['primary_color'] ?? '#000000';
    $bookingRef = str_pad($bookingData['id'], 5, '0', STR_PAD_LEFT);
    $customerName = htmlspecialchars($bookingData['customer_name']);
    $pickupDate = date('D, M j, Y', strtotime($bookingData['pickup_date']));
    $returnDate = date('D, M j, Y', strtotime($bookingData['return_date']));
    $pickupTime = date('g:i A', strtotime($bookingData['pickup_time']));
    $returnTime = date('g:i A', strtotime($bookingData['return_time']));
    $rentalAmount = number_format($bookingData['total_price'], 2);
    $depositAmount = number_format($bookingData['security_deposit'] ?? 0, 2);
    $currency = strtoupper($bookingData['currency'] ?? 'GBP');
    $currencySymbol = $currency === 'GBP' ? '£' : ($currency === 'USD' ? '$' : ($currency === 'EUR' ? '€' : $currency));
    
    $vehicleName = htmlspecialchars(($vehicle['brand'] ?? '') . ' ' . ($vehicle['model'] ?? ''));
    $vehicleImage = !empty($vehicle['image']) ? htmlspecialchars($vehicle['image']) : '';
    
    // Build login URL
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $loginUrl = $protocol . $host . '/auth/login.php';

    // Check for custom email template in the database
    if ($tenantId) {
        $customTpl = getCustomEmailTemplate($tenantId, 'booking_confirmation');
        if ($customTpl) {
            $logoUrl = !empty($tenant['logo']) ? (strpos($tenant['logo'], 'http') === 0 ? $tenant['logo'] : SITE_URL . $tenant['logo']) : '';
            $logoHtml = $logoUrl 
                ? '<img src="' . $logoUrl . '" alt="' . $tenantName . '" style="max-height:38px;vertical-align:middle;display:inline-block;" />'
                : '<span style="font-size:18px;font-weight:700;color:#111111;">' . $tenantName . '</span>';

            $sample = [
                '{{company_logo}}'   => $logoHtml,
                '{{customer_name}}'  => $customerName,
                '{{customer_email}}' => $to,
                '{{booking_ref}}'    => $bookingRef,
                '{{vehicle_name}}'   => $vehicleName,
                '{{pickup_date}}'    => $pickupDate . ' ' . $pickupTime,
                '{{return_date}}'    => $returnDate . ' ' . $returnTime,
                '{{total_price}}'    => $rentalAmount,
                '{{deposit}}'        => $depositAmount,
                '{{currency}}'       => $currencySymbol,
                '{{company_name}}'   => $tenantName,
                '{{company_email}}'  => $tenant['email'] ?? 'info@fleetrentalpro.com',
                '{{login_url}}'      => $loginUrl,
            ];
            $renderedSubject = str_replace(array_keys($sample), array_values($sample), $customTpl['subject']);
            $renderedBody    = str_replace(array_keys($sample), array_values($sample), $customTpl['body']);
            return sendEmail($to, $renderedSubject, $renderedBody, $tenantName);
        }
    }

    // Build tenant logo HTML
    $logoHtml = '';
    if (!empty($tenant['logo'])) {
        $logoUrl = (strpos($tenant['logo'], 'http') === 0) ? $tenant['logo'] : SITE_URL . $tenant['logo'];
        $logoHtml = '<img src="' . $logoUrl . '" alt="' . $tenantName . '" style="height: 32px; max-width: 120px; object-fit: contain;">';
    } else {
        $logoHtml = '<div style="font-size: 24px; font-weight: 800; color: #000; letter-spacing: -1px;">' . $tenantName . '</div>';
    }
    
    $subject = "Your Booking is Confirmed - #{$bookingRef} - {$tenantName}";
    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset=\"UTF-8\">
        <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
        <style>
            body { margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f5f5f5; color: #333; }
            .email-wrapper { max-width: 600px; margin: 0 auto; background-color: #ffffff; }
            .email-header { padding: 24px 32px; background: #ffffff; text-align: center; border-bottom: 1px solid #111111; }
            .booking-ref { font-size: 13px; color: #666; text-align: center; padding: 12px 0 0; }
            .booking-ref strong { color: #000; }
            .content-card { margin: 0 16px 16px; border: 1px solid #e5e5e5; border-radius: 16px; padding: 32px; background: #fff; }
            .greeting { font-size: 14px; color: #333; margin: 0 0 8px; }
            .heading { font-size: 24px; font-weight: 700; color: #1a1a1a; margin: 0 0 24px; }
            .hero-image { width: 100%; height: 200px; object-fit: cover; border-radius: 12px; margin-bottom: 24px; display: block; background-color: #f0f0f0; }
            .section-title { font-size: 18px; font-weight: 700; color: #1a1a1a; margin: 0 0 12px; }
            .section-text { font-size: 14px; color: #555; line-height: 1.5; margin: 0 0 20px; }
            .btn { display: inline-block; padding: 14px 32px; background-color: #1a1a1a; color: #ffffff !important; text-decoration: none; border-radius: 10px; font-weight: 600; font-size: 14px; }
            .divider { border: none; border-top: 1px solid #e5e5e5; margin: 24px 0; }
            .price-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
            .price-label { font-size: 14px; color: #555; }
            .price-value { font-size: 14px; color: #1a1a1a; font-weight: 500; }
            .info-text { font-size: 13px; color: #666; line-height: 1.5; margin: 16px 0; }
            .see-more { font-size: 14px; color: #1a1a1a; text-decoration: underline; font-weight: 600; }
            .footer-logo { font-size: 20px; font-weight: 800; color: #000; margin-bottom: 12px; }
            .footer-text { font-size: 14px; color: #333; margin: 0 0 4px; }
            .footer-team { font-size: 14px; color: #555; margin: 0; }
            .alert-box { background-color: #fffbeb; border: 1px solid #fbbf24; border-radius: 8px; padding: 16px; margin: 20px 0; }
            .alert-title { font-size: 14px; font-weight: 700; color: #92400e; margin: 0 0 8px; }
            .alert-text { font-size: 13px; color: #92400e; margin: 0; line-height: 1.5; }
        </style>
    </head>
    <body>
        <div class=\"email-wrapper\">
            <div class=\"email-header\">
                <div class=\"logo\">{$logoHtml}</div>
                <div class=\"booking-ref\">Booking: <strong>#{$bookingRef}</strong></div>
            </div>
            
            <div class=\"content-card\">
                <p class=\"greeting\">Hello {$customerName},</p>
                <h1 class=\"heading\">Your booking is confirmed!</h1>
                
                " . ($vehicleImage ? "<img src=\"{$vehicleImage}\" alt=\"{$vehicleName}\" class=\"hero-image\">" : "") . "
                
                <h2 class=\"section-title\">We are almost there</h2>
                <p class=\"section-text\">Thank you for your booking with {$tenantName}. Before you collect your vehicle, you need to complete the required documents and provide your signature.</p>
                
                <div class=\"alert-box\">
                    <p class=\"alert-title\">Action Required</p>
                    <p class=\"alert-text\">Please log in to your account to review and sign your rental agreement. This must be completed before your pickup date.</p>
                </div>
                
                <a href=\"{$loginUrl}\" class=\"btn\">Login</a>
                
                <hr class=\"divider\">
                
                <h2 class=\"section-title\">Booking Summary</h2>
                <div class=\"price-row\">
                    <span class=\"price-label\">Rental amount: </span>
                    <span class=\"price-value\">{$currencySymbol}{$rentalAmount}</span>
                </div>
                <div class=\"price-row\">
                    <span class=\"price-label\">Deposit: </span>
                    <span class=\"price-value\">{$currencySymbol}{$depositAmount}</span>
                </div>
                <p class=\"info-text\">Your deposit will be refunded a few days after the vehicle is returned in good condition.</p>
                <a href=\"{$loginUrl}\" class=\"see-more\">See more</a>
                
                <hr class=\"divider\">
                
                <div class=\"footer-logo\">{$tenantName}</div>
                <p class=\"footer-text\"><strong>We look forward to seeing you,</strong></p>
                <p class=\"footer-team\">Your {$tenantName} team</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($to, $subject, $message, $tenantName);
}

/**
 * Send team invitation email
 */
function sendTeamInvitationEmail($email, $tenant, $invitedByName, $token) {
    $tenantName = htmlspecialchars($tenant['name']);
    $invite_link = SITE_URL . "/auth/join.php?token=" . $token;
    
    // Logo block
    $logoHtml = '';
    if (!empty($tenant['logo'])) {
        $logoUrl = (strpos($tenant['logo'], 'http') === 0) ? $tenant['logo'] : SITE_URL . $tenant['logo'];
        $logoHtml = "<img src='{$logoUrl}' alt='{$tenantName}' style='max-height: 50px; margin-bottom: 20px;'>";
    } else {
        $logoHtml = "<h1 style='margin:0; font-size: 24px; font-weight: 800; color: #000;'>{$tenantName}</h1>";
    }

    $subject = "Join the team at " . $tenantName;
    $message = "
    <html>
    <head>
        <style>
            body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, Arial, sans-serif; line-height: 1.6; color: #000; background-color: #f6f6f6; margin: 0; padding: 0; }
            .wrapper { width: 100%; table-layout: fixed; background-color: #f6f6f6; padding-bottom: 40px; }
            .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; margin-top: 40px; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
            .header { padding: 40px 40px 20px; text-align: center; }
            .content { padding: 0 40px 40px; }
            .button { display: inline-block; padding: 14px 30px; background-color: #000000; color: #ffffff !important; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 15px; margin: 20px 0; }
            .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
            .divider { height: 1px; background-color: #eee; margin: 30px 0; }
            p { margin: 0 0 15px; font-size: 15px; color: #333; }
        </style>
    </head>
    <body>
        <div class='wrapper'>
            <div class='container'>
                <div class='header'>
                    {$logoHtml}
                </div>
                <div class='content'>
                    <h2 style='margin:0 0 20px; font-size: 20px; font-weight: 700; color: #000;'>Team Invitation</h2>
                    <p>Hello,</p>
                    <p><strong>" . htmlspecialchars($invitedByName) . "</strong> has invited you to join the dashboard team for <strong>{$tenantName}</strong>.</p>
                    
                    <p>As a team member, you'll be able to manage vehicles, bookings, and customers for the dealership.</p>
                    
                    <div style='text-align: center;'>
                        <a href='{$invite_link}' class='button'>Accept Invitation</a>
                    </div>
                    
                    <div class='divider'></div>
                    
                    <p style='font-size: 13px; color: #888;'>If the button above doesn't work, copy and paste this link into your browser:</p>
                    <p style='word-break: break-all; color: #666; font-size: 12px; font-family: monospace; background: #fafafa; padding: 10px; border-radius: 4px;'>{$invite_link}</p>
                </div>
            </div>
            <div class='footer'>
                <p>&copy; " . date('Y') . " " . SITE_NAME . ". All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($email, $subject, $message, $tenantName);
}
