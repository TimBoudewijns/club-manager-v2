<?php
/**
 * Tabs partial template
 * Only show Club Teams tab if user has permission (controlled by Alpine.js)
 */
?>
<!-- Tabs Section -->
<div class="bg-white rounded-xl shadow-md p-1 md:p-2 mb-8 overflow-x-auto">
    <div class="flex items-center">
        <div class="flex space-x-1 md:space-x-2 min-w-fit">
            <button class="flex-1 md:flex-none py-2 md:py-3 px-3 md:px-6 rounded-lg font-semibold transition-all duration-200 whitespace-nowrap text-sm md:text-base"
                    :class="activeTab === 'my-teams' ? 'bg-gradient-to-r from-orange-500 to-orange-600 text-white shadow-lg' : 'text-gray-600 hover:text-orange-600 hover:bg-orange-50'"
                    @click="activeTab = 'my-teams'">
                <span class="flex items-center justify-center space-x-1 md:space-x-2">
                    <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <span>My Teams</span>
                </span>
            </button>
            
            <!-- Club Teams tab - only show if user has permission -->
            <button x-show="canViewClubTeams"
                    x-cloak
                    class="flex-1 md:flex-none py-2 md:py-3 px-3 md:px-6 rounded-lg font-semibold transition-all duration-200 whitespace-nowrap text-sm md:text-base"
                    :class="activeTab === 'club-teams' ? 'bg-gradient-to-r from-orange-500 to-orange-600 text-white shadow-lg' : 'text-gray-600 hover:text-orange-600 hover:bg-orange-50'"
                    @click="activeTab = 'club-teams'">
                <span class="flex items-center justify-center space-x-1 md:space-x-2">
                    <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                    <span>Club Teams</span>
                </span>
            </button>
        </div>
    </div>
</div>