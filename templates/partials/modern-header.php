<?php
/**
 * Modern header with mobile sidebar trigger
 * Optimized for both desktop and mobile usage
 */
?>
<!-- Mobile Header with Sidebar Toggle -->
<div class="lg:hidden sticky top-0 z-30 bg-white border-b border-gray-200 shadow-sm">
    <div class="flex items-center justify-between px-4 py-3">
        <!-- Left side - Menu button and Logo -->
        <div class="flex items-center space-x-3">
            <!-- Mobile Menu Button -->
            <button @click="toggleMobileSidebar()" 
                    class="p-2 rounded-lg hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-orange-500 transition-colors">
                <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
            
            <!-- Mobile Logo -->
            <div class="flex items-center">
                <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-lg p-2 shadow-md">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <div class="ml-2">
                    <h1 class="text-lg font-bold text-gray-900">Club Manager</h1>
                </div>
            </div>
        </div>
        
        <!-- Right side - Season selector -->
        <div class="flex items-center space-x-2">
            <select x-model="currentSeason" @change="changeSeason" 
                    class="text-sm bg-white border border-gray-300 rounded-lg px-3 py-1.5 focus:border-orange-500 focus:ring-1 focus:ring-orange-500">
                <option value="2024-2025">2024-2025</option>
                <option value="2023-2024">2023-2024</option>
                <option value="2025-2026">2025-2026</option>
            </select>
        </div>
    </div>
    
    <!-- Mobile Tab Title -->
    <div class="px-4 py-2 bg-gray-50 border-t border-gray-100">
        <h2 class="text-sm font-semibold text-gray-900">
            <span x-show="activeTab === 'player-management'">Player Management</span>
            <span x-show="activeTab === 'team-management'">Team Management</span>
            <span x-show="activeTab === 'trainer-management'">Trainer Management</span>
            <span x-show="activeTab === 'import-export'">Import & Export</span>
        </h2>
    </div>
</div>

<!-- Desktop Header (simplified since we have sidebar now) -->
<div class="hidden lg:block mb-8">
    <div class="bg-white rounded-2xl shadow-xl border border-orange-100 overflow-hidden">
        <!-- Header Content -->
        <div class="p-6 md:p-8">
            <div class="flex items-center justify-between">
                <!-- Left side - Current tab info -->
                <div class="flex items-center space-x-4">
                    <!-- Dynamic Icon based on active tab -->
                    <div class="rounded-2xl p-4 shadow-lg" 
                         :class="{
                            'bg-gradient-to-br from-orange-500 to-orange-600': activeTab === 'player-management',
                            'bg-gradient-to-br from-blue-500 to-blue-600': activeTab === 'team-management',
                            'bg-gradient-to-br from-green-500 to-green-600': activeTab === 'trainer-management',
                            'bg-gradient-to-br from-purple-500 to-purple-600': activeTab === 'import-export'
                         }">
                        <!-- Player Management Icon -->
                        <svg x-show="activeTab === 'player-management'" class="w-8 h-8 md:w-10 md:h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        <!-- Team Management Icon -->
                        <svg x-show="activeTab === 'team-management'" class="w-8 h-8 md:w-10 md:h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                        </svg>
                        <!-- Trainer Management Icon -->
                        <svg x-show="activeTab === 'trainer-management'" class="w-8 h-8 md:w-10 md:h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        <!-- Import/Export Icon -->
                        <svg x-show="activeTab === 'import-export'" class="w-8 h-8 md:w-10 md:h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
                        </svg>
                    </div>
                    
                    <div>
                        <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-1">
                            <span x-show="activeTab === 'player-management'">Player Management</span>
                            <span x-show="activeTab === 'team-management'">Team Management</span>
                            <span x-show="activeTab === 'trainer-management'">Trainer Management</span>
                            <span x-show="activeTab === 'import-export'">Import & Export</span>
                        </h1>
                        <p class="text-gray-600 text-sm md:text-base">
                            <span x-show="activeTab === 'player-management'">Manage players across your teams</span>
                            <span x-show="activeTab === 'team-management'">Organize and manage your teams</span>
                            <span x-show="activeTab === 'trainer-management'">Manage trainers and permissions</span>
                            <span x-show="activeTab === 'import-export'">Import and export team data</span>
                        </p>
                    </div>
                </div>
                
                <!-- Right side - Actions -->
                <div class="flex items-center space-x-4">
                    <!-- Quick Action Button based on active tab -->
                    <button x-show="activeTab === 'player-management' && hasPermission('can_add_teams_player_mgmt')"
                            @click="showCreateTeamModal = true" 
                            class="bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white font-semibold py-2 px-4 rounded-lg shadow-lg transform transition-all duration-200 hover:scale-105">
                        <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Add Team
                    </button>
                    
                    <button x-show="activeTab === 'team-management' && hasPermission('can_create_teams')"
                            @click="showCreateTeamModal = true" 
                            class="bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-semibold py-2 px-4 rounded-lg shadow-lg transform transition-all duration-200 hover:scale-105">
                        <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Create Team
                    </button>
                    
                    <button x-show="activeTab === 'trainer-management' && hasPermission('can_manage_trainers')"
                            @click="showInviteTrainerModal = true" 
                            class="bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white font-semibold py-2 px-4 rounded-lg shadow-lg transform transition-all duration-200 hover:scale-105">
                        <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                        </svg>
                        Invite Trainer
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Loading State -->
        <div x-show="globalLoading" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             class="absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center rounded-2xl">
            <div class="flex items-center space-x-3">
                <svg class="animate-spin h-8 w-8 text-orange-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-orange-600 font-medium" x-text="loadingMessage">Loading...</span>
            </div>
        </div>
    </div>
</div>

