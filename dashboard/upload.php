<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    redirect('/auth/login.php');
}

// Check if user has a tenant
if (!$_SESSION['tenant_id']) {
    die('Error: No tenant associated with this account.');
}

$pdo = getDB();

// Get tenant information
$stmt = $pdo->prepare("SELECT * FROM tenants WHERE id = ?");
$stmt->execute([$_SESSION['tenant_id']]);
$tenant = $stmt->fetch();

$upload_message = '';
$upload_success = false;

// Handle image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['images'])) {
    $upload_dir = __DIR__ . '/../uploads/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $uploaded_files = $_FILES['images'];
    $successful_uploads = 0;
    
    // Handle single or multiple files
    if (is_array($uploaded_files['name'])) {
        $file_count = count($uploaded_files['name']);
        
        for ($i = 0; $i < $file_count; $i++) {
            if ($uploaded_files['error'][$i] === UPLOAD_ERR_OK) {
                $file_name = basename($uploaded_files['name'][$i]);
                $file_tmp = $uploaded_files['tmp_name'][$i];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                
                // Validate file type
                $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (in_array($file_ext, $allowed_types)) {
                    $new_file_name = uniqid() . '_' . time() . '.' . $file_ext;
                    $destination = $upload_dir . $new_file_name;
                    
                    if (move_uploaded_file($file_tmp, $destination)) {
                        $successful_uploads++;
                    }
                }
            }
        }
    } else {
        // Single file upload
        if ($uploaded_files['error'] === UPLOAD_ERR_OK) {
            $file_name = basename($uploaded_files['name']);
            $file_tmp = $uploaded_files['tmp_name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array($file_ext, $allowed_types)) {
                $new_file_name = uniqid() . '_' . time() . '.' . $file_ext;
                $destination = $upload_dir . $new_file_name;
                
                if (move_uploaded_file($file_tmp, $destination)) {
                    $successful_uploads = 1;
                }
            }
        }
    }
    
    if ($successful_uploads > 0) {
        $upload_success = true;
        $upload_message = "Successfully uploaded $successful_uploads image(s)!";
    } else {
        $upload_message = "No images were uploaded. Please check file types (jpg, jpeg, png, gif, webp only).";
    }
}

// Get uploaded images
$uploaded_images = [];
$upload_dir = __DIR__ . '/../uploads/';
if (file_exists($upload_dir)) {
    $files = scandir($upload_dir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && !is_dir($upload_dir . $file)) {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $uploaded_images[] = [
                    'name' => $file,
                    'path' => '/uploads/' . $file,
                    'size' => filesize($upload_dir . $file),
                    'date' => filemtime($upload_dir . $file)
                ];
            }
        }
    }
    // Sort by date, newest first
    usort($uploaded_images, function($a, $b) {
        return $b['date'] - $a['date'];
    });
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Images - <?= htmlspecialchars($tenant['name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" href="/assets/images/fleet-logo-black-small.png" type="image/png">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen p-6">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="mb-6">
                <a href="/dashboard/" class="text-blue-600 hover:text-blue-700 text-sm mb-2 inline-block">← Back to Dashboard</a>
                <h1 class="text-3xl font-bold text-gray-900">Upload Images</h1>
                <p class="text-gray-600 mt-1">Upload images for your vehicles and listings</p>
            </div>

            <!-- Upload Message -->
            <?php if ($upload_message): ?>
            <div class="mb-6 p-4 rounded-lg <?= $upload_success ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200' ?>">
                <p class="<?= $upload_success ? 'text-green-800' : 'text-red-800' ?>"><?= htmlspecialchars($upload_message) ?></p>
            </div>
            <?php endif; ?>

            <!-- Upload Form -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                <form method="POST" enctype="multipart/form-data" id="uploadForm">
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-12 text-center hover:border-blue-500 transition">
                        <div class="flex justify-center mb-4">
                            <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                            </svg>
                        </div>
                        <label for="fileInput" class="cursor-pointer">
                            <span class="text-lg font-semibold text-gray-900 block mb-2">Click to upload images</span>
                            <span class="text-sm text-gray-600 block mb-4">or drag and drop</span>
                            <span class="text-xs text-gray-500">PNG, JPG, GIF, WEBP up to 10MB each</span>
                        </label>
                        <input type="file" 
                               id="fileInput" 
                               name="images[]" 
                               multiple 
                               accept="image/*" 
                               class="hidden"
                               onchange="handleFileSelect(this)">
                    </div>
                    
                    <div id="filePreview" class="mt-4 hidden">
                        <p class="text-sm font-medium text-gray-700 mb-2">Selected files:</p>
                        <div id="fileList" class="space-y-2"></div>
                    </div>
                    
                    <button type="submit" 
                            id="uploadBtn"
                            class="mt-6 w-full bg-blue-600 text-white py-3 px-6 rounded-lg font-semibold hover:bg-blue-700 transition disabled:bg-gray-400 disabled:cursor-not-allowed">
                        Upload Images
                    </button>
                </form>
            </div>

            <!-- Uploaded Images -->
            <?php if (!empty($uploaded_images)): ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Uploaded Images (<?= count($uploaded_images) ?>)</h2>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <?php foreach ($uploaded_images as $image): ?>
                    <div class="group relative bg-gray-100 rounded-lg overflow-hidden aspect-square">
                        <img src="<?= htmlspecialchars($image['path']) ?>" 
                             alt="<?= htmlspecialchars($image['name']) ?>"
                             class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-50 transition flex items-center justify-center">
                            <div class="opacity-0 group-hover:opacity-100 transition">
                                <button onclick="copyPath('<?= htmlspecialchars($image['path']) ?>')" 
                                        class="px-3 py-1 bg-white text-gray-900 rounded text-sm font-medium hover:bg-gray-100">
                                    Copy Path
                                </button>
                            </div>
                        </div>
                        <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black to-transparent p-2">
                            <p class="text-white text-xs truncate"><?= htmlspecialchars($image['name']) ?></p>
                            <p class="text-white text-xs opacity-75"><?= number_format($image['size'] / 1024, 1) ?> KB</p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function handleFileSelect(input) {
            const filePreview = document.getElementById('filePreview');
            const fileList = document.getElementById('fileList');
            const uploadBtn = document.getElementById('uploadBtn');
            
            if (input.files.length > 0) {
                filePreview.classList.remove('hidden');
                fileList.innerHTML = '';
                
                Array.from(input.files).forEach((file, index) => {
                    const fileItem = document.createElement('div');
                    fileItem.className = 'flex items-center justify-between p-2 bg-gray-50 rounded';
                    fileItem.innerHTML = `
                        <span class="text-sm text-gray-700 truncate">${file.name}</span>
                        <span class="text-xs text-gray-500 ml-2">${(file.size / 1024).toFixed(1)} KB</span>
                    `;
                    fileList.appendChild(fileItem);
                });
                
                uploadBtn.disabled = false;
            } else {
                filePreview.classList.add('hidden');
                uploadBtn.disabled = true;
            }
        }
        
        function copyPath(path) {
            navigator.clipboard.writeText(path).then(() => {
                showSuccessModal('Path copied to clipboard!', path);
            });
        }
        
        function showSuccessModal(title, message) {
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
            modal.innerHTML = `
                <div class="bg-white rounded-lg p-6 max-w-sm mx-4 shadow-xl">
                    <div class="flex items-center justify-center w-12 h-12 mx-auto mb-4 bg-green-100 rounded-full">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 text-center mb-2">${title}</h3>
                    <p class="text-sm text-gray-600 text-center mb-4">${message}</p>
                    <button onclick="this.closest('.fixed').remove()" class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium">
                        OK
                    </button>
                </div>
            `;
            document.body.appendChild(modal);
            modal.addEventListener('click', (e) => {
                if (e.target === modal) modal.remove();
            });
        }
        
        // Drag and drop
        const dropZone = document.querySelector('form');
        
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => {
                dropZone.classList.add('border-blue-500');
            }, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => {
                dropZone.classList.remove('border-blue-500');
            }, false);
        });
        
        dropZone.addEventListener('drop', (e) => {
            const dt = e.dataTransfer;
            const files = dt.files;
            document.getElementById('fileInput').files = files;
            handleFileSelect(document.getElementById('fileInput'));
        }, false);
    </script>
</body>
</html>
