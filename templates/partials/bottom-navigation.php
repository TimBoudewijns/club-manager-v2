<?php
/**
 * Bottom Navigation for Mobile
 * Fixed bottom navigation with main tabs for mobile devices
 */
?>
<!-- Mobile Bottom Navigation -->
<div class="lg:hidden fixed bottom-0 left-0 right-0 z-40 bg-white border-t border-gray-200 shadow-lg">
    <div class="grid grid-cols-4 h-16" :class="isTabAvailable('import-export') ? 'grid-cols-4' : 'grid-cols-3'">
        
        <!-- Player Management -->
        <button x-show="isTabAvailable('player-management')"
                @click="activeTab = 'player-management'"
                class="flex flex-col items-center justify-center p-2 transition-all duration-200"
                :class="activeTab === 'player-management' ? 
                    'text-orange-600 bg-orange-50' : 
                    'text-gray-500 hover:text-orange-600 hover:bg-orange-50'">
            <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
            <span class="text-xs font-medium">Players</span>
            <!-- Active indicator -->
            <div x-show="activeTab === 'player-management'" 
                 class="absolute bottom-0 left-1/2 transform -translate-x-1/2 w-12 h-1 bg-orange-600 rounded-t-full"></div>
        </button>
        
        <!-- Team Management -->
        <button x-show="isTabAvailable('team-management')" 
                x-cloak
                @click="activeTab = 'team-management'"
                class="flex flex-col items-center justify-center p-2 transition-all duration-200 relative"
                :class="activeTab === 'team-management' ? 
                    'text-blue-600 bg-blue-50' : 
                    'text-gray-500 hover:text-blue-600 hover:bg-blue-50'">
            <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
            </svg>
            <span class="text-xs font-medium">Teams</span>
            <!-- Active indicator -->
            <div x-show="activeTab === 'team-management'" 
                 class="absolute bottom-0 left-1/2 transform -translate-x-1/2 w-12 h-1 bg-blue-600 rounded-t-full"></div>
        </button>
        
        <!-- Trainer Management -->
        <button x-show="isTabAvailable('trainer-management')" 
                x-cloak
                @click="activeTab = 'trainer-management'"
                class="flex flex-col items-center justify-center p-2 transition-all duration-200 relative"
                :class="activeTab === 'trainer-management' ? 
                    'text-green-600 bg-green-50' : 
                    'text-gray-500 hover:text-green-600 hover:bg-green-50'">
            <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
            </svg>
            <span class="text-xs font-medium">Trainers</span>
            <!-- Active indicator -->
            <div x-show="activeTab === 'trainer-management'" 
                 class="absolute bottom-0 left-1/2 transform -translate-x-1/2 w-12 h-1 bg-green-600 rounded-t-full"></div>
        </button>
        
        <!-- Import/Export -->
        <button x-show="isTabAvailable('import-export')" 
                x-cloak
                @click="activeTab = 'import-export'"
                class="flex flex-col items-center justify-center p-2 transition-all duration-200 relative"
                :class="activeTab === 'import-export' ? 
                    'text-purple-600 bg-purple-50' : 
                    'text-gray-500 hover:text-purple-600 hover:bg-purple-50'">
            <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
            </svg>
            <span class="text-xs font-medium">Data</span>
            <!-- Active indicator -->
            <div x-show="activeTab === 'import-export'" 
                 class="absolute bottom-0 left-1/2 transform -translate-x-1/2 w-12 h-1 bg-purple-600 rounded-t-full"></div>
        </button>
    </div>
</div>

<!-- Bottom padding for mobile content to avoid overlap with bottom navigation -->
<div class="lg:hidden h-16"></div>