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
    
    return $mail;
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
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;
        $mail->AltBody = strip_tags($message);
        
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
    $primaryColor = $tenant['primary_color'] ?? '#3B82F6';
    $tenantName = htmlspecialchars($tenant['name']);
    
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
    $primaryColor = $tenant['primary_color'] ?? '#3B82F6';
    $tenantName = htmlspecialchars($tenant['name']);
    
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $loginUrl = $protocol . $host . '/auth/login.php';
    
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
