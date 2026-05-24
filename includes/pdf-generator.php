<?php
/**
 * PDF Generator for Signed Contracts
 * Uses TCPDF to generate a professional PDF from the signed contract
 */

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Sanitize HTML content for TCPDF compatibility
 * TCPDF only supports a limited subset of HTML/CSS.
 * This strips unsupported attributes, classes, and CSS properties.
 */
function sanitizeHtmlForTcpdf($html) {
    if (empty($html)) return '';
    
    // Remove all designer floating toolbars (no-print)
    $html = preg_replace('/<div[^>]*class="[^"]*no-print[^"]*"[^>]*>.*?<\/div>\s*/is', '', $html);

    // Remove editing tools (handles and delete buttons) as a fallback
    $html = preg_replace('/<button[^>]*>.*?<\/button>/is', '', $html);
    $html = preg_replace('/<div[^>]*class="[^"]*section-handle[^"]*"[^>]*>.*?<\/div>/is', '', $html);
    
    // Replace SVGs (TCPDF doesn't support them well)
    $html = preg_replace('/<svg[^>]*>.*?<\/svg>/is', '', $html);

    // Specific fix for header text color and alignment (convert H1 to block with inline style)
    $html = preg_replace('/<h1[^>]*class="[^"]*text-center[^"]*"[^>]*>(.*?)<\/h1>/i', '<h1 style="color: white; font-size: 22pt; text-align: center;">$1</h1>', $html);
    $html = preg_replace('/<h1[^>]*>(.*?)<\/h1>/i', '<h1 style="color: white; font-size: 22pt; margin: 0; padding: 0;">$1</h1>', $html);

    // Handle contract designer header specifically (full width background support for TCPDF)
    $html = preg_replace_callback('/<div[^>]*id="contractHeader"[^>]*style="[^"]*background-color:\s*([^;"]+)[^"]*"[^>]*>(.*?)<\/div>/is', function($matches) {
        $bgColor = $matches[1];
        $content = $matches[2];
        return '<table width="100%" cellpadding="15" cellspacing="0" style="background-color: ' . $bgColor . ';"><tr><td>' . $content . '</td></tr></table>';
    }, $html);

    // Handle contact bar layout (side-by-side icons/text)
    $html = preg_replace_callback('/<div[^>]*id="contactBar"[^>]*>(.*?)<\/div>/is', function($matches) {
        $content = $matches[1];
        $parts = preg_split('/<\/div>\s*<div[^>]*>/i', $content);
        $cells = array_map(function($p) { 
            $p = strip_tags($p, '<span><strong><b><i><em>');
            return '<td align="center" style="color: #6b7280; font-size: 9pt;">' . trim($p) . '</td>'; 
        }, array_filter($parts));
        if (empty($cells)) return '';
        return '<table width="100%" cellpadding="10" cellspacing="0" style="border-bottom: 1px solid #e5e7eb; background-color: #f9fafb;"><tr>' . implode('', $cells) . '</tr></table><br>';
    }, $html);

    // Ensure logo images inside the header retain their alignment
    $html = preg_replace('/<div[^>]*id="logoContainer"[^>]*>(.*?)<\/div>/is', 
        '<table width="100%"><tr><td align="right" style="background-color: #ffffff; padding: 5px;">$1</td></tr></table>', $html);

    // Handle section headers (marker + title) - IMPORTANT FIX FOR BLUE LINE
    $html = preg_replace_callback('/<div[^>]*flex[^"]*items-center[^"]*space-x-2[^>]*>\s*<div[^>]*style="[^"]*background-color:\s*([^;"]+)[^"]*"[^>]*><\/div>\s*<h2[^>]*>(.*?)<\/h2>\s*<\/div>/is', function($matches) {
        $bgColor = $matches[1];
        $title = preg_replace('/<\/?h2[^>]*>/i', '', $matches[2]); // Strip nested h2 tags just in case
        return '<table width="100%" cellpadding="0" cellspacing="0"><tr><td width="6" style="background-color: ' . $bgColor . ';"></td><td width="10"></td><td><h2 style="margin: 0; padding: 0; color: #111827; font-size: 14pt;">' . trim($title) . '</h2></td></tr></table><br>';
    }, $html);

    // Map common Tailwind spacing classes to inline styles for TCPDF
    $spacingMap = [
        'p-8' => 'padding: 30px;',
        'p-6' => 'padding: 20px;',
        'p-4' => 'padding: 15px;',
        'p-3' => 'padding: 10px;',
        'pt-4' => 'padding-top: 15px;',
        'pt-6' => 'padding-top: 20px;',
        'pb-8' => 'padding-bottom: 30px;',
        'pb-6' => 'padding-bottom: 20px;',
        'mb-8' => 'margin-bottom: 30px;',
        'mb-6' => 'margin-bottom: 20px;',
        'mb-4' => 'margin-bottom: 15px;',
        'mb-3' => 'margin-bottom: 10px;',
        'mb-2' => 'margin-bottom: 8px;',
        'gap-8' => 'margin-bottom: 30px;',
    ];
    foreach ($spacingMap as $class => $style) {
        $html = preg_replace('/(class="[^"]*)\b' . preg_quote($class) . '\b([^"]*")/i', '$1$2 style="' . $style . '"', $html);
    }
    
    // Fix inner tables (Leased Vehicle) by targeting specific Tailwind borders instead of all tables
    $html = preg_replace('/<table[^>]*class="[^"]*border[^"]*"[^>]*>/i', '<table width="100%" cellpadding="8" style="border: 1px solid #d1d5db;">', $html);
    $html = preg_replace('/<td[^>]*class="[^"]*border-[br][^"]*"[^>]*>/i', '<td style="border: 1px solid #d1d5db;">', $html);
    $html = preg_replace('/<td([^>]*)>/i', '<td$1>', $html); // Just in case
    $html = preg_replace('/<tr[^>]*border-b[^>]*>/i', '<tr>', $html);

    // Handle signature grid (grid-cols-2)
    $html = preg_replace('/<div[^>]*grid[^"]*grid-cols-2[^>]*>\s*(<div[^>]*>.*?<\/div>)\s*(<div[^>]*>.*?<\/div>)\s*<\/div>/is', 
        '<table width="100%" cellpadding="5" cellspacing="0"><tr><td width="50%">$1</td><td width="50%">$2</td></tr></table>', $html);

    // Final clean up of any remaining div wrappers inside table cells
    $html = preg_replace('/<td>\s*<div[^>]*>(.*?)<\/div>\s*<\/td>/is', '<td>$1</td>', $html);

    // Convert section containers to divs
    $html = preg_replace('/<(section|article|main|header|footer|nav|aside)(\s[^>]*)?>/', '<div$2>', $html);
    $html = preg_replace('/<\/(section|article|main|header|footer|nav|aside)>/', '</div>', $html);
    
    // Strip pure structural divs that are breaking PDF flows
    $html = preg_replace('/<div class="contract-section[^"]*"[^>]*>(.*?)<\/div>\s*(?=<div|$)/is', '<div style="margin-bottom: 15px;">$1</div>', $html);

    // Remove empty <p>
    $html = preg_replace('/<p(\s[^>]*)?>\s*<\/p>/i', '', $html);
    
    // Clean up empty lines
    $html = preg_replace('/\n{3,}/', "\n\n", $html);
    
    return trim($html);
}

function generateSignedContractPDF($contract, $booking, $tenant, $vehicle) {
    // Create upload directory if it doesn't exist
    $uploadDir = __DIR__ . '/../uploads/contracts/' . $tenant['id'];
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $pdfPath = $uploadDir . '/contract_' . $booking['id'] . '_' . time() . '.pdf';
    
    // Create PDF
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('Fleet Rental Pro');
    $pdf->SetAuthor($tenant['name']);
    $pdf->SetTitle('Signed Rental Contract - Booking #' . str_pad($booking['id'], 5, '0', STR_PAD_LEFT));
    
    // Render the contract
    renderContractToPDF($pdf, $contract, $booking, $tenant, $vehicle, true);
    
    // Output PDF
    $pdf->Output($pdfPath, 'F');
    
    return $pdfPath;
}

/**
 * Shared rendering logic for both preview and final signed contract
 */
function renderContractToPDF($pdf, $contractData, $booking, $tenant, $vehicle, $isSigned = false) {
    $primaryColor = $tenant['primary_color'] ?? '#3B82F6';
    
    // Parse hex color to RGB
    $r = hexdec(substr($primaryColor, 1, 2));
    $g = hexdec(substr($primaryColor, 3, 2));
    $b = hexdec(substr($primaryColor, 5, 2));
    
    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // Set margins
    $pdf->SetMargins(20, 20, 20);
    $pdf->SetAutoPageBreak(true, 25);
    
    // Add a page
    $pdf->AddPage();
    
    // Determine if it's a visual template (JSON content)
    $rawContent = $contractData['content'] ?? '';
    $contentJson = json_decode($rawContent, true);
    $isVisualTemplate = ($contentJson && isset($contentJson['html']));
    
    // Extract the working content
    $contractText = $isVisualTemplate ? $contentJson['html'] : $rawContent;
    
    // ONLY add the branded headers if NOT using the new visual designer
    // (Visual designer templates have their own built-in headers)
    if (!$isVisualTemplate) {
        // Branded Header (Full width color block)
        $pdf->SetFillColor($r, $g, $b);
        $pdf->Rect(0, 0, 210, 45, 'F'); // Full width of A4
        
        $pdf->SetY(15);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 22);
        
        // Logo or Text Name
        if (!empty($tenant['logo_path'])) {
            $logoPath = BASE_PATH . $tenant['logo_path'];
            if (file_exists($logoPath)) {
                $pdf->Image($logoPath, 15, 12, 35); // 35mm wide
                $pdf->SetX(55);
            }
        }
        $pdf->Cell(110, 15, ' ' . $tenant['name'], 0, 0, 'L');
        
        // Header Label on the right
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 15, 'RENTAL CONTRACT   ', 0, 1, 'R');
        
        // Contact bar (Website | Email | Phone)
        $pdf->SetY(45);
        $pdf->SetFillColor(243, 244, 246);
        $pdf->Rect(0, 45, 210, 10, 'F');
        $pdf->SetY(47);
        $pdf->SetTextColor(107, 114, 128);
        $pdf->SetFont('helvetica', '', 8);
        
        $contactText = ($tenant['website'] ?? 'www.fleetrentalpro.com') . '  |  ' . ($tenant['email'] ?? 'info@fleetrentalpro.com') . '  |  ' . ($tenant['phone'] ?? '+44 000 000 000');
        $pdf->Cell(0, 6, $contactText, 0, 1, 'C');
        
        $pdf->Ln(10);
        
        // Verification Badge
        $pdf->SetFillColor(209, 250, 229);
        $pdf->SetTextColor(6, 95, 70);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell(35, 6, ' ✓ ID VERIFIED BY DIDIT ', 0, 1, 'L', true);
        $pdf->Ln(2);
        
        // Booking Summary (Structured grid)
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetDrawColor(229, 231, 235);
        $pdf->SetLineWidth(0.2);
        
        $vehicleNameStr = trim(($vehicle['brand'] ?? '') . ' ' . ($vehicle['model'] ?? ''));
        
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetTextColor(75, 85, 99);
        $pdf->Cell(25, 8, 'CUSTOMER:', 'B', 0); $pdf->SetTextColor(17, 24, 39); $pdf->SetFont('helvetica', '', 10); $pdf->Cell(60, 8, $booking['customer_name'], 'B', 0);
        $pdf->SetTextColor(75, 85, 99); $pdf->SetFont('helvetica', 'B', 9); $pdf->Cell(25, 8, 'DATES:', 'B', 0); $pdf->SetTextColor(17, 24, 39); $pdf->SetFont('helvetica', '', 10); $pdf->Cell(0, 8, date('M d', strtotime($booking['pickup_date'])) . ' - ' . date('M d, Y', strtotime($booking['return_date'])), 'B', 1);
        
        $pdf->SetTextColor(75, 85, 99); $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(25, 8, 'VEHICLE:', 'B', 0); $pdf->SetTextColor(17, 24, 39); $pdf->SetFont('helvetica', '', 10); $pdf->Cell(60, 8, trim($vehicleNameStr) ?: 'NOT SPECIFIED', 'B', 0);
        $pdf->SetTextColor(75, 85, 99); $pdf->SetFont('helvetica', 'B', 9); $pdf->Cell(25, 8, 'TOTAL:', 'B', 0); $pdf->SetTextColor($r, $g, $b); $pdf->SetFont('helvetica', 'B', 10); $pdf->Cell(0, 8, '£' . number_format($booking['total_price'], 2), 'B', 1);
        
        $pdf->Ln(8);
        
        // Terms section title
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetTextColor($r, $g, $b);
        $pdf->Cell(0, 10, 'Agreement Terms', 0, 1);
        $pdf->Ln(2);
        $pdf->SetTextColor(51, 51, 51);
    } else {
        $pdf->SetY(20);
    }

    // Process replacements
    $vehicleNameStr = trim(($vehicle['brand'] ?? '') . ' ' . ($vehicle['model'] ?? ''));
    $signatureHtml = '';
    
    if ($isSigned) {
        $sigImagePath = $contractData['signature_image_path'] ?? null;
        if ($sigImagePath && file_exists($sigImagePath)) {
            $imgData = base64_encode(file_get_contents($sigImagePath));
            $signatureHtml = '<img src="@' . $imgData . '" height="40" />';
        } elseif (!empty($contractData['signature_typed']) && strpos($contractData['signature_typed'], 'data:image/') === 0) {
            $imgParts = explode(',', $contractData['signature_typed'], 2);
            if (count($imgParts) === 2) {
                $signatureHtml = '<img src="@' . $imgParts[1] . '" height="40" />';
            }
        } else {
            $signatureHtml = '<i>' . htmlspecialchars($contractData['signature_typed'] ?? 'N/A') . '</i>';
        }
    } else {
        $signatureHtml = '<span style="color:#999;font-style:italic;font-weight:bold;">DIGITAL PREVIEW</span>';
    }

    $replacements = [
        '{{vehicle_name}}' => trim($vehicleNameStr) ?: 'NOT SPECIFIED',
        '{{vehicle_registration}}' => $vehicle['registration'] ?? 'N/A',
        '{{renter_full_name}}' => $booking['customer_name'] ?? 'PREVIEW CUSTOMER',
        '{{tenant_name}}' => $tenant['name'],
        '{{booking_reference}}' => '#' . str_pad($booking['id'] ?? 0, 5, '0', STR_PAD_LEFT),
        '{{pickup_datetime}}' => date('M d, Y', strtotime($booking['pickup_date'] ?? 'now')),
        '{{return_datetime}}' => date('M d, Y', strtotime($booking['return_date'] ?? 'now')),
        '{{booking_total_price}}' => '£' . number_format($booking['total_price'] ?? 0, 2),
        '{{security_deposit}}' => '£' . number_format($booking['security_deposit'] ?? 0, 2),
        '{{included_distance}}' => ($vehicle['mileage_limit'] ?? 'Unlimited') . ' miles',
        '{{excess_distance_fee}}' => '£0.50',
        '{{deductible_amount}}' => '£500',
        '{{current_datetime}}' => date('F j, Y h:i A'),
        '{{signature}}' => $signatureHtml,
    ];
    
    // Robust replacements logic
    foreach ($replacements as $key => $value) {
        // Handle variants like {{ tag }}, {{tag}}, and even <strong>{{tag}}</strong>
        // The pattern matches {{ followed by possible HTML tags, the key, possible HTML tags, and }}
        $pattern = '/\{\{\s*(?:<[^>]+>)*' . preg_quote(trim($key, '{} '), '/') . '(?:\s*<[^>]+>)*\s*\}\}/i';
        $contractText = preg_replace($pattern, $value, $contractText);
        
        // Fallback for simple exact match
        $contractText = str_ireplace($key, $value, $contractText);
    }
    
    if (empty(trim(strip_tags($contractText, '<img>')))) {
        // If content is somehow lost, provide a fallback message
        $contractText = '<p style="color:red;text-align:center;">Error: Contract content is empty or could not be rendered.</p>';
    }
    
    // Render as HTML or Plain Text
    if ($isVisualTemplate) {
        $htmlContent = sanitizeHtmlForTcpdf($contractText);
        $styledHtml = '
        <style>
            body { font-family: helvetica; font-size: 10pt; line-height: 1.6; color: #374151; }
            h1 { font-size: 18pt; font-weight: bold; margin-bottom: 12px; color: #111827; }
            h2 { font-size: 14pt; font-weight: bold; margin-bottom: 8px; color: #111827; }
            h3 { font-size: 12pt; font-weight: bold; margin-bottom: 6px; color: #111827; }
            p { margin-bottom: 10px; text-align: justify; }
            ul, ol { margin-bottom: 10px; padding-left: 20px; }
            li { margin-bottom: 4px; }
            table { border-collapse: collapse; width: 100%; margin-bottom: 15px; }
            td, th { border: 1px solid #e5e7eb; padding: 10px; font-size: 10pt; }
            th { background-color: #f9fafb; font-weight: bold; color: #111827; }
            strong, b { font-weight: bold; color: #111827; }
            em, i { font-style: italic; }
            .text-center { text-align: center; }
            .text-right { text-align: right; }
        </style>' . $htmlContent;
        $pdf->writeHTML($styledHtml, true, false, true, false, '');
    } else {
        $decodedText = html_entity_decode($contractText, ENT_QUOTES, 'UTF-8');
        if ($decodedText !== strip_tags($decodedText)) {
            $cleanedHtml = sanitizeHtmlForTcpdf($decodedText);
            $styledHtml = '<style>
                body { font-family: helvetica; font-size: 10pt; line-height: 1.6; color: #374151; }
                p { margin-bottom: 10px; }
                strong, b { font-weight: bold; }
            </style>' . $cleanedHtml;
            $pdf->writeHTML($styledHtml, true, false, true, false, '');
        } else {
            $pdf->SetFont('helvetica', '', 10);
            $pdf->MultiCell(0, 5, trim($decodedText), 0, 'L');
        }
    }

    
    $pdf->Ln(10);
    
    // Digital Signature Section (Only for legacy templates where signature isn't inline)
    if (!$isVisualTemplate && $isSigned) {
        $pdf->SetFillColor(249, 250, 251);
        $pdf->SetTextColor($r, $g, $b);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, '   Digital Signature', 0, 1, 'L', true);
        $pdf->Ln(4);
        
        $signatureY = $pdf->GetY();
        $pdf->SetFillColor(249, 250, 251);
        $pdf->Rect(20, $signatureY, 170, 30, 'F');
        
        if (!empty($contractData['signature_image_path']) && file_exists($contractData['signature_image_path'])) {
            $pdf->Image($contractData['signature_image_path'], 30, $signatureY + 2, 80, 26, 'PNG', '', '', false, 300, '', false, false, 0, 'CM');
        } elseif (!empty($contractData['signature_typed']) && strpos($contractData['signature_typed'], 'data:image/') === 0) {
            $tmpSig = tempnam(sys_get_temp_dir(), 'sig_');
            $imgParts = explode(',', $contractData['signature_typed'], 2);
            if (count($imgParts) === 2) {
                file_put_contents($tmpSig, base64_decode($imgParts[1]));
                $pdf->Image($tmpSig, 30, $signatureY + 2, 80, 26, 'PNG', '', '', false, 300, '', false, false, 0, 'CM');
                @unlink($tmpSig);
            }
        } else {
            $pdf->SetFont('helvetica', 'I', 16);
            $pdf->SetXY(25, $signatureY + 8);
            $pdf->Cell(160, 10, $contractData['signature_typed'] ?? 'N/A', 0, 1, 'L');
        }
    }
    
    if ($isSigned) {
        $pdf->SetY($pdf->GetY() + 5);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->Cell(0, 6, 'Signed electronically on: ' . date('F j, Y \a\t h:i:s A', strtotime($contractData['signed_at'] ?? 'now')), 0, 1);
        $pdf->Cell(0, 6, 'Signing token: ' . substr($contractData['signing_token'] ?? '', 0, 16) . '...', 0, 1);
    }
    
    // Footer
    $pdf->SetY(-30);
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetTextColor(150, 150, 150);
    $pdf->Cell(0, 5, 'This document was generated electronically by Fleet Rental Pro on behalf of ' . $tenant['name'] . '.', 0, 1, 'C');
    $pdf->Cell(0, 5, 'Document ID: CONTRACT-' . ($booking['id'] ?? 'PREVIEW') . '-' . date('Ymd'), 0, 1, 'C');
}
