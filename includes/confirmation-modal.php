<!-- Confirmation Modal -->
<div id="confirmationModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-md w-full shadow-xl">
        <div class="p-6">
            <div class="flex items-center justify-center w-12 h-12 mx-auto mb-4 rounded-full bg-red-100">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
            </div>
            <h3 id="confirmationTitle" class="text-lg font-semibold text-gray-900 text-center mb-2">Confirm Action</h3>
            <p id="confirmationMessage" class="text-sm text-gray-600 text-center mb-6">Are you sure you want to proceed?</p>
            <div class="flex items-center justify-center space-x-3">
                <button onclick="closeConfirmationModal()" class="px-6 py-2.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium transition whitespace-nowrap">
                    Cancel
                </button>
                <button id="confirmationButton" class="min-w-[120px] px-6 py-2.5 bg-red-600 text-white rounded-lg font-medium transition whitespace-nowrap">
                    Confirm
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Success/Error Notification Toast -->
<div id="notificationToast" class="hidden fixed top-4 right-4 z-50 max-w-md">
    <div id="notificationContent" class="bg-white rounded-lg shadow-lg border-l-4 p-4 flex items-start space-x-3">
        <div id="notificationIcon" class="flex-shrink-0"></div>
        <div class="flex-1">
            <p id="notificationMessage" class="text-sm font-medium text-gray-900"></p>
        </div>
        <button onclick="closeNotification()" class="flex-shrink-0 text-gray-400 hover:text-gray-600">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>
</div>

<script>
let confirmationCallback = null;

function showConfirmation(title, message, onConfirm, confirmText = 'Confirm', confirmClass = 'bg-red-600 hover:bg-red-700') {
    document.getElementById('confirmationTitle').textContent = title;
    document.getElementById('confirmationMessage').textContent = message;
    const confirmBtn = document.getElementById('confirmationButton');
    confirmBtn.textContent = confirmText;
    confirmBtn.className = `min-w-[120px] px-6 py-2.5 text-white rounded-lg font-medium transition whitespace-nowrap ${confirmClass}`;
    confirmationCallback = onConfirm;
    document.getElementById('confirmationModal').classList.remove('hidden');
}

function closeConfirmationModal() {
    document.getElementById('confirmationModal').classList.add('hidden');
    confirmationCallback = null;
}

document.getElementById('confirmationButton').addEventListener('click', function() {
    if (confirmationCallback) {
        confirmationCallback();
    }
    closeConfirmationModal();
});

// Close modal when clicking outside
document.getElementById('confirmationModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeConfirmationModal();
    }
});

function showNotification(message, type = 'success') {
    const toast = document.getElementById('notificationToast');
    const content = document.getElementById('notificationContent');
    const icon = document.getElementById('notificationIcon');
    const messageEl = document.getElementById('notificationMessage');
    
    messageEl.textContent = message;
    
    if (type === 'success') {
        content.className = 'bg-white rounded-lg shadow-lg border-l-4 border-green-500 p-4 flex items-start space-x-3';
        icon.innerHTML = '<svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
    } else {
        content.className = 'bg-white rounded-lg shadow-lg border-l-4 border-red-500 p-4 flex items-start space-x-3';
        icon.innerHTML = '<svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
    }
    
    toast.classList.remove('hidden');
    
    setTimeout(() => {
        closeNotification();
    }, 3000);
}

function closeNotification() {
    document.getElementById('notificationToast').classList.add('hidden');
}
</script>
