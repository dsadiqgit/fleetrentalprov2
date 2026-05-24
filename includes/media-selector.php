<?php
// Media Selector Modal Component
// Usage: include __DIR__ . '/../includes/media-selector.php';
// JS call: openMediaSelector(callback);
?>
<div id="mediaSelectorModal" class="fixed inset-0 bg-black bg-opacity-50 z-[10000] hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl w-full max-w-5xl max-h-[90vh] flex flex-col overflow-hidden shadow-2xl">
        <!-- Header -->
        <div class="p-6 border-b border-gray-100 flex items-center justify-between bg-white sticky top-0 z-10">
            <div>
                <h3 class="text-xl font-bold text-gray-900">Select Media</h3>
                <p class="text-sm text-gray-500">Choose an image from your library or upload a new one</p>
            </div>
            <div class="flex items-center gap-3">
                <button onclick="document.getElementById('mediaSelectorUpload').click()" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-bold hover:bg-blue-700 transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Upload New
                </button>
                <button onclick="closeMediaSelector()" class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
        </div>

        <!-- Library Grid -->
        <div class="flex-1 overflow-y-auto p-6 bg-gray-50">
            <div id="mediaSelectorGrid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                <!-- Loaded via JS -->
                <div class="col-span-full py-20 text-center">
                    <div class="animate-spin h-10 w-10 border-4 border-blue-600 border-t-transparent rounded-full mx-auto mb-4"></div>
                    <p class="text-gray-500 font-medium">Loading your media library...</p>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="p-6 border-t border-gray-100 bg-white flex justify-end gap-3">
            <button onclick="closeMediaSelector()" class="px-6 py-2 border border-gray-300 rounded-lg text-sm font-bold text-gray-700 hover:bg-gray-50 transition">
                Cancel
            </button>
            <button id="mediaSelectorConfirmBtn" onclick="confirmSelection()" class="hidden px-6 py-2 bg-blue-600 text-white rounded-lg text-sm font-bold hover:bg-blue-700 transition">
                Confirm Selection
            </button>
        </div>
    </div>
</div>

<input type="file" id="mediaSelectorUpload" class="hidden" accept="image/*">

<script>
let currentMediaCallback = null;
let isMultipleSelection = false;
let selectedUrls = [];

function openMediaSelector(callback, multiple = false) {
    currentMediaCallback = callback;
    isMultipleSelection = multiple;
    selectedUrls = [];
    document.getElementById('mediaSelectorModal').classList.remove('hidden');
    document.getElementById('mediaSelectorConfirmBtn').classList.toggle('hidden', !multiple);
    document.body.style.overflow = 'hidden';
    loadMediaLibrary();
}

function closeMediaSelector() {
    document.getElementById('mediaSelectorModal').classList.add('hidden');
    document.body.style.overflow = '';
}

function loadMediaLibrary() {
    const grid = document.getElementById('mediaSelectorGrid');
    
    fetch('/dashboard/website-builder.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get_media'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.media.length === 0) {
                grid.innerHTML = `
                    <div class="col-span-full py-20 text-center text-gray-500">
                        <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                        <p class="font-bold">No images found</p>
                        <p class="text-sm">Upload your first image to get started</p>
                    </div>
                `;
                return;
            }

            grid.innerHTML = data.media.map(item => `
                <div class="group relative aspect-square bg-white rounded-xl border border-gray-200 overflow-hidden cursor-pointer hover:shadow-xl transition-all duration-300 media-item" 
                     data-url="${item.file_path}"
                     onclick="toggleMediaItem(this, '${item.file_path}')">
                    <img src="${item.file_path}" alt="${item.file_name}" class="w-full h-full object-cover">
                    <div class="absolute inset-0 bg-blue-600/0 group-hover:bg-blue-600/10 transition-colors flex items-center justify-center selection-overlay">
                        <div class="w-10 h-10 bg-white rounded-full shadow-lg flex items-center justify-center opacity-0 group-hover:opacity-100 scale-90 group-hover:scale-100 transition-all check-icon">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                        </div>
                    </div>
                </div>
            `).join('');
        }
    });
}

function toggleMediaItem(el, url) {
    if (!isMultipleSelection) {
        selectMediaItem(url);
        return;
    }

    const index = selectedUrls.indexOf(url);
    if (index > -1) {
        selectedUrls.splice(index, 1);
        el.classList.remove('ring-4', 'ring-blue-600', 'ring-inset');
        el.querySelector('.selection-overlay').classList.remove('bg-blue-600/20');
        el.querySelector('.check-icon').classList.remove('opacity-100', 'scale-100');
    } else {
        selectedUrls.push(url);
        el.classList.add('ring-4', 'ring-blue-600', 'ring-inset');
        el.querySelector('.selection-overlay').classList.add('bg-blue-600/20');
        el.querySelector('.check-icon').classList.add('opacity-100', 'scale-100');
    }
}

function confirmSelection() {
    if (currentMediaCallback && selectedUrls.length > 0) {
        currentMediaCallback(selectedUrls);
    }
    closeMediaSelector();
}

function selectMediaItem(url) {
    if (currentMediaCallback) {
        currentMediaCallback(isMultipleSelection ? [url] : url);
    }
    closeMediaSelector();
}

document.getElementById('mediaSelectorUpload').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const formData = new FormData();
        formData.append('image', file);
        
        // Use website-builder.php's upload handler as it returns the URL directly
        fetch('/dashboard/website-builder.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                selectMediaItem(data.url);
            } else {
                alert('Upload failed: ' + data.error);
            }
        });
    }
});

// Close on click outside
document.getElementById('mediaSelectorModal').addEventListener('click', (e) => {
    if (e.target.id === 'mediaSelectorModal') closeMediaSelector();
});

// Close on escape
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeMediaSelector();
});
</script>
