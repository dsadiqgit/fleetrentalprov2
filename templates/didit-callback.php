<?php
// This file is loaded inside the Didit iframe when verification completes.
// It sends a message to the parent window to proceed to the payment step.
$raw_status = $_GET['status'] ?? 'approved';
$status = strtolower(str_replace([' ', '+'], '_', $raw_status));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification Complete</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            background-color: #f9fafb;
            color: #111827;
            padding: 20px;
            box-sizing: border-box;
        }
        .success-icon {
            color: #10b981;
            width: 80px;
            height: 80px;
            margin-bottom: 24px;
        }
        .review-icon {
            color: #f59e0b;
            width: 80px;
            height: 80px;
            margin-bottom: 24px;
        }
        h2 { margin: 0 0 12px 0; font-size: 28px; text-align: center; }
        p { margin: 0 0 24px 0; color: #6b7280; text-align: center; font-size: 16px; line-height: 1.5; max-width: 400px; }
        .close-btn {
            background-color: #2563eb;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            transition: background-color 0.2s;
        }
        .close-btn:hover { background-color: #1d4ed8; }
    </style>
    <link rel="icon" href="/assets/images/fleet-logo-black-small.png" type="image/png">
</head>
<body>
    <?php if ($status === 'in_review'): ?>
        <svg class="review-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <h2>Verification In Review</h2>
        <p>Your identity document has been submitted and is currently being reviewed.</p>
        <p><strong>If you are on your mobile phone:</strong> You can now close this tab and return to your computer to finish booking.</p>
    <?php else: ?>
        <svg class="success-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <h2>Verification Complete!</h2>
        <p>Your identity has been successfully verified.</p>
        <p><strong>If you used your phone to scan:</strong> You can now close this tab. Please return to your computer to continue to the payment step and complete your booking.</p>
    <?php endif; ?>

    <script>
        // Send a message to the parent window if we are in an iframe
        if (window.self !== window.top) {
            window.parent.postMessage({
                type: 'didit_verification_complete',
                status: '<?php echo htmlspecialchars($status); ?>'
            }, '*');
        }
    </script>
</body>
</html>
