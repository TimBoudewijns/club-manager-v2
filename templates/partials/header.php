<?php
/**
 * Header partial template with integrated tabs
 */
?>
<!-- Integrated Header Section -->
<div class="bg-white border border-gray-200 overflow-hidden">
    <!-- Header Content -->
    <div class="px-4 sm:px-6 md:px-8 py-6 md:py-8 border-b border-gray-100">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
            <div class="flex items-center space-x-4">
                <!-- Icon -->
                <div class="bg-gray-900 rounded-xl p-3 shadow-sm">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl md:text-3xl font-semibold text-gray-900 tracking-tight">
                        Club Manager
                    </h1>
                    <p class="text-gray-500 text-sm md:text-base font-medium mt-1">
                        Professional team management
                    </p>
                </div>
            </div>
            
            <div class="flex items-center space-x-4">
                <!-- Season Selector -->
                <div class="relative">
                    <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2 block">Season</label>
                    <div class="relative">
                        <select x-model="currentSeason" @change="changeSeason" 
                            class="bg-white border border-gray-300 focus:border-gray-900 focus:ring-1 focus:ring-gray-900 rounded-lg px-4 py-2.5 pr-10 appearance-none cursor-pointer text-sm font-medium text-gray-900 transition-all duration-200">
                            <option value="2024-2025">2024-2025</option>
                            <option value="2025-2026">2025-2026</option>
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                            <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Integrated Tabs Section -->
    <div class="bg-gray-50/50 px-3 sm:px-4 md:px-8 py-0 border-t border-gray-100">
        <!-- Mobile tabs with scroll -->
        <div class="md:hidden">
            <div class="flex space-x-1 overflow-x-auto scrollbar-hide py-3">
                <button class="flex-shrink-0 py-2.5 px-4 rounded-lg font-medium transition-all duration-200 whitespace-nowrap text-sm"
                        :class="activeTab === 'player-management' ? 'bg-gray-900 text-white shadow-sm' : 'text-gray-600 hover:text-gray-900 hover:bg-white'"
                        @click="activeTab = 'player-management'"
                        x-show="isTabAvailable('player-management')">
                    <span class="flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        <span>Players</span>
                    </span>
                </button>
                
                <button x-show="isTabAvailable('team-management')"
                        x-cloak
                        class="flex-shrink-0 py-2.5 px-4 rounded-lg font-medium transition-all duration-200 whitespace-nowrap text-sm"
                        :class="activeTab === 'team-management' ? 'bg-gray-900 text-white shadow-sm' : 'text-gray-600 hover:text-gray-900 hover:bg-white'"
                        @click="activeTab = 'team-management'">
                    <span class="flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                        </svg>
                        <span>Teams</span>
                    </span>
                </button>
                
                <button x-show="isTabAvailable('trainer-management')"
                        x-cloak
                        class="flex-shrink-0 py-2.5 px-4 rounded-lg font-medium transition-all duration-200 whitespace-nowrap text-sm"
                        :class="activeTab === 'trainer-management' ? 'bg-gray-900 text-white shadow-sm' : 'text-gray-600 hover:text-gray-900 hover:bg-white'"
                        @click="activeTab = 'trainer-management'">
                    <span class="flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        <span>Trainers</span>
                    </span>
                </button>
                
                <button x-show="isTabAvailable('import-export')"
                        x-cloak
                        class="flex-shrink-0 py-2.5 px-4 rounded-lg font-medium transition-all duration-200 whitespace-nowrap text-sm"
                        :class="activeTab === 'import-export' ? 'bg-gray-900 text-white shadow-sm' : 'text-gray-600 hover:text-gray-900 hover:bg-white'"
                        @click="activeTab = 'import-export'">
                    <span class="flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                        <span>Import/Export</span>
                    </span>
                </button>
            </div>
        </div>
        
        <!-- Desktop tabs -->
        <div class="hidden md:flex items-center space-x-1">
            <button class="py-4 px-6 font-medium transition-all duration-200 text-sm border-b-2 border-transparent relative"
                    :class="activeTab === 'player-management' ? 'text-gray-900 border-gray-900 bg-white' : 'text-gray-500 hover:text-gray-900 hover:bg-white/50'"
                    @click="activeTab = 'player-management'"
                    x-show="isTabAvailable('player-management')">
                <span class="flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <span>Player Management</span>
                </span>
            </button>
            
            <button x-show="isTabAvailable('team-management')"
                    x-cloak
                    class="py-4 px-6 font-medium transition-all duration-200 text-sm border-b-2 border-transparent relative"
                    :class="activeTab === 'team-management' ? 'text-gray-900 border-gray-900 bg-white' : 'text-gray-500 hover:text-gray-900 hover:bg-white/50'"
                    @click="activeTab = 'team-management'">
                <span class="flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                    </svg>
                    <span>Team Management</span>
                </span>
            </button>
            
            <button x-show="isTabAvailable('trainer-management')"
                    x-cloak
                    class="py-4 px-6 font-medium transition-all duration-200 text-sm border-b-2 border-transparent relative"
                    :class="activeTab === 'trainer-management' ? 'text-gray-900 border-gray-900 bg-white' : 'text-gray-500 hover:text-gray-900 hover:bg-white/50'"
                    @click="activeTab = 'trainer-management'">
                <span class="flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    <span>Trainer Management</span>
                </span>
            </button>
            
            <button x-show="isTabAvailable('import-export')"
                    x-cloak
                    class="py-4 px-6 font-medium transition-all duration-200 text-sm border-b-2 border-transparent relative"
                    :class="activeTab === 'import-export' ? 'text-gray-900 border-gray-900 bg-white' : 'text-gray-500 hover:text-gray-900 hover:bg-white/50'"
                    @click="activeTab = 'import-export'">
                <span class="flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                    </svg>
                    <span>Import/Export</span>
                </span>
            </button>
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