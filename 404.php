<?php
require_once __DIR__ . '/config/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100;300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/app/custom.css">
    <link rel="icon" href="/assets/images/fleet-logo-black-small.png" type="image/png">
</head>
<body class="bg-white min-h-screen flex flex-col items-center justify-center p-4" style="font-family: 'Inter', sans-serif;">
    
    <div class="flex items-center gap-6 mb-8">
        <h1 class="text-3xl font-bold text-blue-600">404</h1>
        <div class="h-10 w-px bg-blue-100"></div>
        <p class="text-sm text-gray-500 tracking-tight">This page could not be found.</p>
    </div>

    <a href="/" class="px-6 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium text-sm">
        Go Home
    </a>

</body>
</html>
