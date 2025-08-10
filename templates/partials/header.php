<?php
/**
 * Header partial template with integrated tabs
 */
?>
<!-- Integrated Header Section -->
<div class="bg-white rounded-t-2xl shadow-xl border border-gray-200 overflow-hidden">
    <!-- Navigation and Season Header -->
    <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-3 sm:px-4 md:px-8 py-4 border-b border-gray-200">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <!-- Navigation Tabs - Desktop -->
            <div class="hidden sm:flex items-center space-x-2">
                <button class="py-3 px-5 rounded-lg font-semibold text-base transition-all duration-200"
                        :class="activeTab === 'player-management' ? 'bg-orange-500 text-white shadow-md' : 'text-gray-600 hover:text-orange-600 hover:bg-white/70'"
                        @click="activeTab = 'player-management'"
                        x-show="isTabAvailable('player-management')">
                    <span class="flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        <span>Players</span>
                    </span>
                </button>
                
                <button x-show="isTabAvailable('team-management')"
                        x-cloak
                        class="py-3 px-5 rounded-lg font-semibold text-base transition-all duration-200"
                        :class="activeTab === 'team-management' ? 'bg-orange-500 text-white shadow-md' : 'text-gray-600 hover:text-orange-600 hover:bg-white/70'"
                        @click="activeTab = 'team-management'">
                    <span class="flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                        </svg>
                        <span>Teams</span>
                    </span>
                </button>
                
                <button x-show="isTabAvailable('trainer-management')"
                        x-cloak
                        class="py-3 px-5 rounded-lg font-semibold text-base transition-all duration-200"
                        :class="activeTab === 'trainer-management' ? 'bg-orange-500 text-white shadow-md' : 'text-gray-600 hover:text-orange-600 hover:bg-white/70'"
                        @click="activeTab = 'trainer-management'">
                    <span class="flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        <span>Trainers</span>
                    </span>
                </button>
                
                <button x-show="isTabAvailable('import-export')"
                        x-cloak
                        class="py-3 px-5 rounded-lg font-semibold text-base transition-all duration-200"
                        :class="activeTab === 'import-export' ? 'bg-orange-500 text-white shadow-md' : 'text-gray-600 hover:text-orange-600 hover:bg-white/70'"
                        @click="activeTab = 'import-export'">
                    <span class="flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                        <span>Import/Export</span>
                    </span>
                </button>
                
                <button class="py-3 px-5 rounded-lg font-semibold text-base transition-all duration-200"
                        :class="activeTab === 'help' ? 'bg-green-500 text-white shadow-md' : 'text-gray-600 hover:text-green-600 hover:bg-white/70'"
                        @click="activeTab = 'help'">
                    <span class="flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>Help</span>
                    </span>
                </button>
            </div>
            
            <!-- Season Selector -->
            <div class="flex items-center space-x-3">
                <div class="flex items-center space-x-2 text-sm text-gray-600">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <span class="font-medium">Season:</span>
                </div>
                <div class="relative">
                    <select x-model="currentSeason" @change="changeSeason" 
                        class="bg-white border border-gray-300 focus:border-orange-500 focus:ring-2 focus:ring-orange-200 rounded-lg px-3 py-2 pr-8 text-sm appearance-none cursor-pointer shadow-sm hover:shadow-md transition-all">
                        <option value="2024-2025">2024-2025</option>
                        <option value="2025-2026">2025-2026</option>
                    </select>
                    <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                        <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Mobile Navigation -->
        <div class="sm:hidden mt-4">
            <div class="flex space-x-2 overflow-x-auto scrollbar-hide">
                <button class="flex-shrink-0 py-3 px-4 rounded-lg font-semibold text-sm transition-all duration-200 whitespace-nowrap"
                        :class="activeTab === 'player-management' ? 'bg-orange-500 text-white shadow-md' : 'text-gray-600 hover:text-orange-600 hover:bg-white/70'"
                        @click="activeTab = 'player-management'"
                        x-show="isTabAvailable('player-management')">
                    <span class="flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        <span>Players</span>
                    </span>
                </button>
                
                <button x-show="isTabAvailable('team-management')"
                        x-cloak
                        class="flex-shrink-0 py-3 px-4 rounded-lg font-semibold text-sm transition-all duration-200 whitespace-nowrap"
                        :class="activeTab === 'team-management' ? 'bg-orange-500 text-white shadow-md' : 'text-gray-600 hover:text-orange-600 hover:bg-white/70'"
                        @click="activeTab = 'team-management'">
                    <span class="flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                        </svg>
                        <span>Teams</span>
                    </span>
                </button>
                
                <button x-show="isTabAvailable('trainer-management')"
                        x-cloak
                        class="flex-shrink-0 py-3 px-4 rounded-lg font-semibold text-sm transition-all duration-200 whitespace-nowrap"
                        :class="activeTab === 'trainer-management' ? 'bg-orange-500 text-white shadow-md' : 'text-gray-600 hover:text-orange-600 hover:bg-white/70'"
                        @click="activeTab = 'trainer-management'">
                    <span class="flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        <span>Trainers</span>
                    </span>
                </button>
                
                <button x-show="isTabAvailable('import-export')"
                        x-cloak
                        class="flex-shrink-0 py-3 px-4 rounded-lg font-semibold text-sm transition-all duration-200 whitespace-nowrap"
                        :class="activeTab === 'import-export' ? 'bg-orange-500 text-white shadow-md' : 'text-gray-600 hover:text-orange-600 hover:bg-white/70'"
                        @click="activeTab = 'import-export'">
                    <span class="flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                        <span>Import/Export</span>
                    </span>
                </button>
                
                <button class="flex-shrink-0 py-3 px-4 rounded-lg font-semibold text-sm transition-all duration-200 whitespace-nowrap"
                        :class="activeTab === 'help' ? 'bg-green-500 text-white shadow-md' : 'text-gray-600 hover:text-green-600 hover:bg-white/70'"
                        @click="activeTab = 'help'">
                    <span class="flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>Help</span>
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Custom scrollbar hiding for mobile tabs */
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
</style>