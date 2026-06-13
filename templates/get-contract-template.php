<?php
require_once __DIR__ . '/../includes/tenant_init.php';
header('Content-Type: application/json');

$tenant_id = getTenantId();
$pdo = getDB();

// Try default template first
$stmt = $pdo->prepare("SELECT id, name, content FROM contract_templates WHERE tenant_id = ? AND is_default = 1 LIMIT 1");
$stmt->execute([$tenant_id]);
$template = $stmt->fetch();

// Fallback to any template
if (!$template) {
    $stmt = $pdo->prepare("SELECT id, name, content FROM contract_templates WHERE tenant_id = ? ORDER BY created_at ASC LIMIT 1");
    $stmt->execute([$tenant_id]);
    $template = $stmt->fetch();
}

if ($template) {
    $rawContent   = $template['content'] ?? '';
    $contentJson  = json_decode($rawContent, true);
    $isVisual     = ($contentJson && isset($contentJson['html']));

    // Extract working HTML (visual templates wrap it in a JSON envelope)
    $html = $isVisual ? $contentJson['html'] : $rawContent;

    if ($isVisual) {
        // Strip editor-only controls (same logic as api/get-contract.php)
        $html = preg_replace('/<div[^>]*class="[^"]*absolute[^"]*-left-12[^"]*"[^>]*>.*?<\/div>/is', '', $html);
        $html = preg_replace('/<div[^>]*class="[^"]*no-print[^"]*"[^>]*>.*?<\/div>/is', '', $html);
        $html = preg_replace('/<button[^>]*onclick="(?:removeSection|moveSection)\([^)]*\)"[^>]*>.*?<\/button>/is', '', $html);
        $html = preg_replace('/\s*contenteditable(?:\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+))?/i', '', $html);

        // Remove the signature section entirely — signatures are only shown on the
        // signed document (contract-sign.php / api/get-contract.php).
        // The template builder wraps the signature block in <div class="px-8 pb-8">.
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML(
            '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body id="__croot__">' . $html . '</body></html>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        // Match the outer wrapper div that carries BOTH px-8 and pb-8 Tailwind classes
        $sigDivs = $xpath->query(
            '//div[contains(concat(" ",normalize-space(@class)," ")," px-8 ") and contains(concat(" ",normalize-space(@class)," ")," pb-8 ")]'
        );
        foreach ($sigDivs as $node) {
            $node->parentNode->removeChild($node);
        }

        // Extract just the body inner HTML, skipping the wrapper we added
        $body = $dom->getElementById('__croot__');
        if ($body) {
            $inner = '';
            foreach ($body->childNodes as $child) {
                $inner .= $dom->saveHTML($child);
            }
            $html = $inner;
        }
    }

    $isHtml = $isVisual || ($html !== strip_tags($html));

    echo json_encode([
        'success' => true,
        'id'      => $template['id'],
        'name'    => $template['name'],
        'content' => $html,
        'is_html' => $isHtml,
    ]);
} else {
    // Generic fallback when no template is configured
    echo json_encode([
        'success' => true,
        'id'      => null,
        'name'    => 'Rental Agreement',
        'content' => '<p><strong>Vehicle Rental Agreement</strong></p>
<p>By signing this agreement, the renter agrees to the following terms and conditions:</p>
<ol style="padding-left:1.25rem;margin:.5rem 0">
<li style="margin-bottom:.5rem">The renter will return the vehicle in the same condition as received, normal wear and tear excepted.</li>
<li style="margin-bottom:.5rem">The renter is responsible for all traffic fines and penalties incurred during the rental period.</li>
<li style="margin-bottom:.5rem">The renter must hold a valid driver\'s licence for the duration of the rental.</li>
<li style="margin-bottom:.5rem">The vehicle must not be driven by any person other than the named renter without prior written consent.</li>
<li style="margin-bottom:.5rem">The renter accepts liability for any damage, theft or loss of the vehicle during the rental period.</li>
<li style="margin-bottom:.5rem">Fuel must be returned at the same level as collected. A refuelling charge will apply otherwise.</li>
<li style="margin-bottom:.5rem">The vehicle must not be taken outside the agreed territory without prior written authorisation.</li>
<li style="margin-bottom:.5rem">Smoking is strictly prohibited in the vehicle. A cleaning fee will be charged if this rule is violated.</li>
<li style="margin-bottom:.5rem">The renter agrees to report any accidents or incidents to the rental company immediately.</li>
<li style="margin-bottom:.5rem">This agreement is governed by the laws of the jurisdiction in which the rental company operates.</li>
</ol>',
    ]);
}
?>
