<?php
$didit_session_id = 'b6819164-0722-4b6d-a102-392174301c1a';
$didit_api_key = 'lqcFsMLeyMP1c9_7mCT1zOZU-xAd1l-0qbQnasmjpEM';

// Try with trailing slash
$ch = curl_init("https://verification.didit.me/v1/session/{$didit_session_id}/");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'x-api-key: ' . $didit_api_key
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Trailing slash - HTTP Code: $http_code\n";
echo "Response: $response\n\n";

// Try without /v1/
$ch = curl_init("https://verification.didit.me/session/{$didit_session_id}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'x-api-key: ' . $didit_api_key
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "No v1 - HTTP Code: $http_code\n";
echo "Response: $response\n";
?>
