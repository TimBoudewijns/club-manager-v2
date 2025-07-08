<!-- Global Loading Overlay -->
<div x-show="globalLoading"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-[9999] flex items-center justify-center bg-black bg-opacity-50 loading-backdrop"
     style="display: none;">
    <div class="bg-white rounded-xl shadow-2xl p-8 max-w-sm mx-4">
        <div class="flex flex-col items-center">
            <!-- Animated spinner -->
            <svg class="animate-spin h-12 w-12 text-orange-500 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="text-gray-700 font-medium text-center" x-text="loadingMessage || 'Loading...'"></p>
        </div>
    </div>
</div>