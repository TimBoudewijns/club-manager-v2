<?php
/**
 * Tabs partial template
 * Shows tabs based on user permissions
 */
?>
<!-- Tabs Section -->
<div class="bg-white rounded-xl shadow-md p-1 md:p-2 mb-8 overflow-x-auto">
    <div class="flex items-center">
        <div class="flex space-x-1 md:space-x-2 min-w-fit">
            <!-- Player Management tab - Everyone gets this -->
            <button class="flex-1 md:flex-none py-2 md:py-3 px-3 md:px-6 rounded-lg font-semibold transition-all duration-200 whitespace-nowrap text-sm md:text-base focus:outline-none focus:ring-2 focus:ring-orange-300 focus:ring-offset-2"
                    :class="activeTab === 'player-management' ? 'bg-gradient-to-r from-orange-500 to-orange-600 text-white shadow-lg' : 'text-gray-600 hover:text-orange-600 hover:bg-orange-50'"
                    @click="activeTab = 'player-management'"
                    x-show="isTabAvailable('player-management')">
                <span class="flex items-center justify-center space-x-1 md:space-x-2">
                    <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <span>Player Management</span>
                </span>
            </button>
            
            <!-- Team Management tab - only for owners/managers -->
            <button x-show="isTabAvailable('team-management')"
                    x-cloak
                    class="flex-1 md:flex-none py-2 md:py-3 px-3 md:px-6 rounded-lg font-semibold transition-all duration-200 whitespace-nowrap text-sm md:text-base focus:outline-none focus:ring-2 focus:ring-orange-300 focus:ring-offset-2"
                    :class="activeTab === 'team-management' ? 'bg-gradient-to-r from-orange-500 to-orange-600 text-white shadow-lg' : 'text-gray-600 hover:text-orange-600 hover:bg-orange-50'"
                    @click="activeTab = 'team-management'">
                <span class="flex items-center justify-center space-x-1 md:space-x-2">
                    <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                    </svg>
                    <span>Team Management</span>
                </span>
            </button>
            
            <!-- Trainer Management tab - only for owners/managers -->
            <button x-show="isTabAvailable('trainer-management')"
                    x-cloak
                    class="flex-1 md:flex-none py-2 md:py-3 px-3 md:px-6 rounded-lg font-semibold transition-all duration-200 whitespace-nowrap text-sm md:text-base focus:outline-none focus:ring-2 focus:ring-orange-300 focus:ring-offset-2"
                    :class="activeTab === 'trainer-management' ? 'bg-gradient-to-r from-orange-500 to-orange-600 text-white shadow-lg' : 'text-gray-600 hover:text-orange-600 hover:bg-orange-50'"
                    @click="activeTab = 'trainer-management'">
                <span class="flex items-center justify-center space-x-1 md:space-x-2">
                    <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    <span>Trainer Management</span>
                </span>
            </button>
            
            <!-- Help tab - Always available -->
            <button class="flex-1 md:flex-none py-2 md:py-3 px-3 md:px-6 rounded-lg font-semibold transition-all duration-200 whitespace-nowrap text-sm md:text-base focus:outline-none focus:ring-2 focus:ring-orange-300 focus:ring-offset-2"
                    :class="activeTab === 'help' ? 'bg-gradient-to-r from-green-500 to-green-600 text-white shadow-lg' : 'text-gray-600 hover:text-green-600 hover:bg-green-50'"
                    @click="activeTab = 'help'">
                <span class="flex items-center justify-center space-x-1 md:space-x-2">
                    <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>Help</span>
                </span>
            </button>
        </div>
    </div>
</div>