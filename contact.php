<?php require_once __DIR__ . '/config/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - <?= SITE_NAME ?></title>
    <link rel="preload" href="/public/font/Inter_Regular.ttf" as="font" type="font/ttf" crossorigin>
    <link rel="preload" href="/public/font/Inter_SemiBold.ttf" as="font" type="font/ttf" crossorigin>
    <link rel="preload" href="/public/font/Inter_Bold.ttf" as="font" type="font/ttf" crossorigin>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/app/custom.css">
    <link rel="stylesheet" href="/public/css/blog.css">
    <link rel="icon" href="/assets/images/fleet-logo-black-small.png" type="image/png">
</head>
<body class="bg-gray-50 overflow-hidden h-screen" style="font-family: 'Inter', sans-serif;">
    <?php include __DIR__ . '/includes/header.php'; ?>

    <div class="h-[calc(100vh-64px)] overflow-y-auto py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl mx-auto">
            <!-- Progress Bar -->
            <div class="mb-8">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-gray-600"><span id="currentStep">1</span> / 3</span>
                    <span class="text-sm text-gray-500">press <kbd class="px-2 py-1 bg-gray-200 rounded text-xs">Enter ↵</kbd></span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-1.5">
                    <div id="progressBar" class="bg-blue-600 h-1.5 rounded-full transition-all duration-300" style="width: 33.33%"></div>
                </div>
            </div>

            <!-- Multi-step Form -->
            <form id="contactForm" action="/api/contact.php" method="POST" class="bg-white rounded-2xl shadow-lg p-8 md:p-12">
                <!-- Step 1: Name and Company -->
                <div id="step1" class="step-content">
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-8">What's your name and company?</h2>
                    
                    <div class="space-y-6">
                        <div>
                            <label for="fullName" class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                            <input type="text" id="fullName" name="fullName" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Jane Doe">
                        </div>
                        <div>
                            <label for="companyName" class="block text-sm font-medium text-gray-700 mb-2">Company Name *</label>
                            <input type="text" id="companyName" name="companyName" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Acme Inc.">
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-between mt-8">
                        <button type="button" disabled class="px-6 py-3 border border-gray-300 text-gray-400 rounded-lg font-medium inline-flex items-center cursor-not-allowed">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                            Back
                        </button>
                        <button type="button" onclick="nextStep(2)" class="px-6 py-3 bg-black text-white rounded-lg font-medium hover:bg-gray-800 inline-flex items-center">
                            Continue
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <div class="mt-8 text-center">
                        <a href="https://wa.me/1234567890" class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900">
                            <svg class="w-4 h-4 mr-2 text-green-500" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                            </svg>
                            Need help? Chat with us on WhatsApp
                        </a>
                    </div>
                </div>

                <!-- Step 2: Business Details -->
                <div id="step2" class="step-content hidden">
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-8">Tell us about your business</h2>
                    
                    <div class="space-y-6">
                        <div>
                            <label for="businessAddress" class="block text-sm font-medium text-gray-700 mb-2">Business Address *</label>
                            <input type="text" id="businessAddress" name="businessAddress" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="123 Main St, City, State">
                        </div>
                        <div>
                            <label for="numberOfCars" class="block text-sm font-medium text-gray-700 mb-2">Number of Cars *</label>
                            <input type="number" id="numberOfCars" name="numberOfCars" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="10">
                        </div>
                        <div>
                            <label for="phoneNumber" class="block text-sm font-medium text-gray-700 mb-2">Phone Number *</label>
                            <input type="tel" id="phoneNumber" name="phoneNumber" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="+1 (555) 123-4567">
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-between mt-8">
                        <button type="button" onclick="prevStep(1)" class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 inline-flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                            Back
                        </button>
                        <button type="button" onclick="nextStep(3)" class="px-6 py-3 bg-black text-white rounded-lg font-medium hover:bg-gray-800 inline-flex items-center">
                            Continue
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <div class="mt-8 text-center">
                        <a href="https://wa.me/1234567890" class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900">
                            <svg class="w-4 h-4 mr-2 text-green-500" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                            </svg>
                            Need help? Chat with us on WhatsApp
                        </a>
                    </div>
                </div>

                <!-- Step 3: Message (Optional) -->
                <div id="step3" class="step-content hidden">
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Message (optional)</h2>
                    <p class="text-gray-600 mb-8">e.g., specific compliance challenges, desired go-live date, current pain points... We love to hear it, so we can prepare better for the meeting!</p>
                    
                    <div class="space-y-6">
                        <div>
                            <textarea id="message" name="message" rows="6" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none" placeholder="e.g., specific compliance challenges, desired go-live date, current pain points... We love to hear it, so we can prepare better for the meeting!"></textarea>
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-between mt-8">
                        <button type="button" onclick="prevStep(2)" class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 inline-flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                            Back
                        </button>
                        <button type="submit" class="px-6 py-3 bg-black text-white rounded-lg font-medium hover:bg-gray-800 inline-flex items-center">
                            Continue
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <div class="mt-8 text-center">
                        <a href="https://wa.me/1234567890" class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900">
                            <svg class="w-4 h-4 mr-2 text-green-500" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                            </svg>
                            Need help? Chat with us on WhatsApp
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        let currentStepNum = 1;
        
        function nextStep(step) {
            // Hide current step
            document.getElementById('step' + currentStepNum).classList.add('hidden');
            
            // Show next step
            document.getElementById('step' + step).classList.remove('hidden');
            
            // Update progress
            currentStepNum = step;
            document.getElementById('currentStep').textContent = step;
            document.getElementById('progressBar').style.width = (step / 3 * 100) + '%';
        }
        
        function prevStep(step) {
            // Hide current step
            document.getElementById('step' + currentStepNum).classList.add('hidden');
            
            // Show previous step
            document.getElementById('step' + step).classList.remove('hidden');
            
            // Update progress
            currentStepNum = step;
            document.getElementById('currentStep').textContent = step;
            document.getElementById('progressBar').style.width = (step / 3 * 100) + '%';
        }
        
        // Allow Enter key to progress
        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
                e.preventDefault();
                if (currentStepNum < 3) {
                    nextStep(currentStepNum + 1);
                }
            }
        });
    </script>
</body>
</html>
