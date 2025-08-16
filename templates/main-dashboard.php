<?php
/**
 * Main dashboard template
 */
?>
<style>
    /* DFD Brand Gradient Buttons */
    .club-manager-app .bg-gradient-to-r.from-orange-500.to-orange-600 {
        background-image: linear-gradient(to right, #F77F00, #ea580c) !important;
    }
    
    .club-manager-app .bg-gradient-to-r.from-orange-600.to-orange-700 {
        background-image: linear-gradient(to right, #ea580c, #c2410c) !important;
    }
    
    .club-manager-app .hover\:from-orange-600:hover {
        --tw-gradient-from: #ea580c !important;
    }
    
    .club-manager-app .hover\:to-orange-700:hover {
        --tw-gradient-to: #c2410c !important;
    }
    
    /* DFD Blue gradients for club teams */
    .club-manager-app .bg-gradient-to-r.from-blue-500.to-blue-600 {
        background-image: linear-gradient(to right, #4169E1, #3457d5) !important;
    }
    
    .club-manager-app .bg-gradient-to-r.from-blue-600.to-blue-700 {
        background-image: linear-gradient(to right, #3457d5, #2847c7) !important;
    }
    
    /* Purple gradients for import/export */
    .club-manager-app .bg-gradient-to-r.from-purple-500.to-purple-600 {
        background-image: linear-gradient(to right, #a855f7, #9333ea) !important;
    }
    
    .club-manager-app .bg-gradient-to-r.from-purple-600.to-purple-700 {
        background-image: linear-gradient(to right, #9333ea, #7e22ce) !important;
    }
    
    /* Hide elements with x-cloak until Alpine loads */
    [x-cloak] { display: none !important; }
    
    /* Enhanced tab integration styles */
    .border-b-4 {
        border-bottom-width: 4px;
    }
    
    /* Smooth tab transitions */
    .tab-transition {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    /* Active tab styling enhancement */
    .tab-active {
        position: relative;
    }
    
    .tab-active::after {
        content: '';
        position: absolute;
        bottom: -1px;
        left: 0;
        right: 0;
        height: 1px;
        background: white;
        z-index: 1;
    }
    
    /* Z-INDEX HIERARCHY FIX FOR MODALS AND LOADING */
    /* Base modal z-indexes */
    .club-manager-app [x-show="showCreateTeamModal"] { z-index: 40 !important; }
    .club-manager-app [x-show="showTeamDetailsModal"] { z-index: 50 !important; }
    .club-manager-app [x-show="showAddPlayerModal"] { z-index: 55 !important; }
    .club-manager-app [x-show="showAddExistingPlayerModal"] { z-index: 55 !important; }
    .club-manager-app [x-show="showEvaluationModal"] { z-index: 55 !important; }
    .club-manager-app [x-show="showPlayerHistoryModal"] { z-index: 60 !important; }
    .club-manager-app [x-show="showPlayerCardModal"] { z-index: 65 !important; }
    .club-manager-app [x-show="showImportExportModal"] { z-index: 55 !important; }
    
    /* Loading overlay moet boven ALLES staan */
    .club-manager-app [x-show="globalLoading"] {
        z-index: 999999 !important;
        position: fixed !important;
        inset: 0 !important;
    }
    
    /* Extra specifiek voor de loading overlay */
    div[x-show="globalLoading"].fixed {
        z-index: 999999 !important;
    }
</style>

<div class="club-manager-app min-h-screen bg-white" x-data="clubManager()" data-theme="light">
    <div class="w-full py-6 md:py-8">
        
        <?php include 'partials/header.php'; ?>
        
        <!-- Player Management Tab (Combined My Teams + Club Teams) -->
        <div x-show="activeTab === 'player-management'" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform scale-95"
             x-transition:enter-end="opacity-100 transform scale-100">
            
            <!-- Player Management Content Container -->
            <div class="bg-white rounded-b-2xl shadow-xl border-x border-b border-gray-200 overflow-hidden">
                <!-- Player Management Header -->
                <div class="bg-gradient-to-r from-orange-50 to-amber-50 border-b border-orange-200 p-6 md:p-8">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="bg-gradient-to-br from-[#F77F00] to-orange-600 rounded-2xl p-3 shadow-lg">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-lg sm:text-xl font-bold text-slate-900 mb-1 tracking-tight">Player Management</h2>
                                <p class="text-slate-600 text-sm sm:text-base">Manage players across your teams and view club roster</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Content Area -->
                <div class="p-6 md:p-8">
                    <!-- My Teams Section -->
                    <div class="mb-12">
                        <!-- Section Header -->
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                            <div>
                                <h3 class="text-base sm:text-lg font-semibold text-gray-900 flex items-center">
                                    <span class="bg-orange-100 rounded-lg p-2 mr-3">
                                        <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                        </svg>
                                    </span>
                                    My Teams
                                </h3>
                                <p class="text-gray-600 mt-1 text-sm sm:text-base sm:ml-12">Teams you manage directly</p>
                            </div>
                            <!-- Add Team Button - Only show if user can create teams -->
                            <button x-show="hasPermission('can_add_teams_player_mgmt')" 
                                    @click="showCreateTeamModal = true" 
                                    class="bg-[#F77F00] hover:bg-[#ea580c] text-white font-semibold py-3 px-6 rounded-xl shadow-md transform transition-all duration-200 hover:-translate-y-0.5 hover:shadow-lg flex items-center justify-center space-x-2 w-full sm:w-auto">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                <span>Create New Team</span>
                            </button>
                        </div>
                        
                        <!-- Notice for trainers -->
                        <div x-show="userPermissions.is_trainer" class="mb-6 bg-orange-50 border border-orange-200 rounded-lg p-4">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-orange-600 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <div>
                                    <p class="text-orange-800 text-sm">
                                        These are teams assigned to you where you can manage and evaluate players.
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- My Teams Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <template x-for="team in myTeams" :key="team.id">
                                <div @click="selectTeam(team)" 
                                     class="bg-white rounded-xl shadow-lg hover:shadow-2xl transform transition-all duration-300 hover:scale-105 cursor-pointer overflow-hidden group relative border border-gray-100">
                                    <!-- My Team Badge -->
                                    <div class="absolute top-4 right-4 bg-orange-100 text-orange-700 px-3 py-1 rounded-full text-xs font-semibold">
                                        My Team
                                    </div>
                                    <div class="p-6">
                                        <div class="flex items-start justify-between mb-4">
                                            <h4 class="text-lg font-semibold text-gray-900 group-hover:text-orange-600 transition-colors" x-text="team.name"></h4>
                                        </div>
                                        <div class="space-y-2">
                                            <div class="flex items-center text-gray-600">
                                                <svg class="w-5 h-5 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                </svg>
                                                <span class="font-medium">Coach:</span>
                                                <span class="ml-2" x-text="team.coach"></span>
                                            </div>
                                            <div class="flex items-center text-gray-600">
                                                <svg class="w-5 h-5 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                                </svg>
                                                <span class="font-medium">Trainer:</span>
                                                <span class="ml-2 text-sm" x-text="team.trainer_names ? team.trainer_names : 'No trainer assigned'"></span>
                                            </div>
                                            <div class="flex items-center text-gray-600">
                                                <svg class="w-5 h-5 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                </svg>
                                                <span class="font-medium">Season:</span>
                                                <span class="ml-2" x-text="team.season"></span>
                                            </div>
                                        </div>
                                        <div class="mt-4 pt-4 border-t border-gray-100">
                                            <span class="text-sm text-gray-500">Click to manage players →</span>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                        
                        <!-- Empty State for My Teams -->
                        <div x-show="myTeams.length === 0" class="text-center py-16">
                            <div class="bg-orange-50 rounded-full w-24 h-24 mx-auto mb-6 flex items-center justify-center">
                                <svg class="w-12 h-12 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                            </div>
                            <h4 class="text-lg font-semibold text-gray-900 mb-2">No teams yet</h4>
                            <p class="text-gray-600 mb-6" x-show="userPermissions.is_trainer">
                                Your club manager will assign teams to you.
                            </p>
                            <p class="text-gray-600 mb-6" x-show="!userPermissions.is_trainer">
                                Create your first team to get started managing your players.
                            </p>
                            <button x-show="hasPermission('can_add_teams_player_mgmt')" 
                                    @click="showCreateTeamModal = true" 
                                    class="bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white font-bold py-3 px-8 rounded-xl shadow-lg transform transition-all duration-200 hover:scale-105">
                                Create Your First Team
                            </button>
                        </div>
                    </div>
                    
                    <!-- Club Teams Section - Only show if user has permission -->
                    <div x-show="hasPermission('can_see_club_teams_in_player_mgmt')" x-cloak>
                        <!-- Divider -->
                        <div class="flex items-center mb-8">
                            <div class="flex-grow border-t border-gray-300"></div>
                            <div class="flex-shrink-0 px-4">
                                <span class="bg-gray-100 text-gray-500 px-3 py-1 rounded-full text-sm font-medium">Club Overview</span>
                            </div>
                            <div class="flex-grow border-t border-gray-300"></div>
                        </div>
                        
                        <!-- Section Header -->
                        <div class="mb-6">
                            <h3 class="text-lg sm:text-xl font-bold text-gray-900 flex items-center">
                                <span class="bg-blue-100 rounded-lg p-2 mr-3">
                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                    </svg>
                                </span>
                                Club Teams
                            </h3>
                            <p class="text-gray-600 mt-1 text-sm sm:text-base sm:ml-12">View-only access to other teams in your club</p>
                        </div>
                        
                        <!-- Club Teams Notice -->
                        <div class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-blue-600 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <div>
                                    <p class="text-blue-800 text-sm">
                                        <span class="font-semibold">Read-only mode:</span> You can view all teams and players in your club, but cannot make changes.
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Club Teams Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                            <template x-for="team in clubTeams" :key="team.id">
                                <div @click="selectClubTeam(team)" 
                                     class="bg-white rounded-xl shadow-lg hover:shadow-2xl transform transition-all duration-300 hover:scale-105 cursor-pointer overflow-hidden group relative border border-gray-100">
                                    <!-- Club Team Badge -->
                                    <div class="absolute top-4 right-4 bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-xs font-semibold">
                                        Club Team
                                    </div>
                                    <div class="p-6">
                                        <div class="flex items-start justify-between mb-4">
                                            <h4 class="text-lg font-semibold text-gray-900 group-hover:text-blue-600 transition-colors" x-text="team.name"></h4>
                                        </div>
                                        <div class="space-y-2">
                                            <div class="flex items-center text-gray-600">
                                                <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                </svg>
                                                <span class="font-medium">Coach:</span>
                                                <span class="ml-2" x-text="team.coach"></span>
                                            </div>
                                            <div class="flex items-center text-gray-600">
                                                <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                                </svg>
                                                <span class="font-medium">Trainer:</span>
                                                <span class="ml-2 text-sm" x-text="team.trainer_names ? team.trainer_names : 'No trainer assigned'"></span>
                                            </div>
                                            <div class="flex items-center text-gray-600">
                                                <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                </svg>
                                                <span class="font-medium">Season:</span>
                                                <span class="ml-2" x-text="team.season"></span>
                                            </div>
                                        </div>
                                        <div class="mt-4 pt-4 border-t border-gray-100">
                                            <span class="text-sm text-gray-500">Click to view players →</span>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                        
                        <!-- Empty State for Club Teams -->
                        <div x-show="clubTeams.length === 0" class="text-center py-16">
                            <div class="bg-blue-50 rounded-full w-24 h-24 mx-auto mb-6 flex items-center justify-center">
                                <svg class="w-12 h-12 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                            </div>
                            <h4 class="text-lg font-semibold text-gray-900 mb-2">No club teams found</h4>
                            <p class="text-gray-600 mb-6">There are no other teams in your club yet.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Player Card Section - Outside main container for proper spacing -->
            <?php include 'partials/player-card.php'; ?>
        </div>
        
        <!-- Team Management Tab - Only for owners/managers -->
        <div x-show="activeTab === 'team-management' && isTabAvailable('team-management')" 
             x-cloak
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform scale-95"
             x-transition:enter-end="opacity-100 transform scale-100">
            <?php include 'partials/team-management.php'; ?>
        </div>
        
        <!-- Trainer Management Tab - Only show if user has permission -->
        <div x-show="activeTab === 'trainer-management' && isTabAvailable('trainer-management')" 
             x-cloak
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform scale-95"
             x-transition:enter-end="opacity-100 transform scale-100">
            <?php include 'partials/trainer-management.php'; ?>
        </div>
        
        <!-- Import/Export Tab - Only show if user has permission -->
        <div x-show="activeTab === 'import-export' && isTabAvailable('import-export')" 
             x-cloak
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform scale-95"
             x-transition:enter-end="opacity-100 transform scale-100">
            <?php include 'partials/import-export-tab.php'; ?>
        </div>
        
        <!-- Help Tab - Always available -->
        <div x-show="activeTab === 'help'" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform scale-95"
             x-transition:enter-end="opacity-100 transform scale-100">
            <?php include 'partials/help-tab.php'; ?>
        </div>
    </div>
    
    <!-- Include ALL Modals OUTSIDE the tab content divs -->
    <?php 
    include CLUB_MANAGER_PLUGIN_DIR . 'templates/components/loading-overlay.php';
    include CLUB_MANAGER_PLUGIN_DIR . 'templates/modals/create-team-modal.php';
    include CLUB_MANAGER_PLUGIN_DIR . 'templates/modals/team-details-modal.php';
    include CLUB_MANAGER_PLUGIN_DIR . 'templates/modals/add-player-modal.php';
    include CLUB_MANAGER_PLUGIN_DIR . 'templates/modals/add-existing-player-modal.php';
    include CLUB_MANAGER_PLUGIN_DIR . 'templates/modals/evaluation-modal.php';
    include CLUB_MANAGER_PLUGIN_DIR . 'templates/modals/player-history-modal.php';
    include CLUB_MANAGER_PLUGIN_DIR . 'templates/modals/invite-trainer-modal.php';
    include CLUB_MANAGER_PLUGIN_DIR . 'templates/modals/edit-trainer-modal.php';
    include CLUB_MANAGER_PLUGIN_DIR . 'templates/modals/create-club-team-modal.php';
    include CLUB_MANAGER_PLUGIN_DIR . 'templates/modals/assign-trainer-modal.php';
    include CLUB_MANAGER_PLUGIN_DIR . 'templates/modals/edit-team-modal.php';
    include CLUB_MANAGER_PLUGIN_DIR . 'templates/modals/player-card-modal.php';
    include CLUB_MANAGER_PLUGIN_DIR . 'templates/modals/import-export-modal.php';
    include CLUB_MANAGER_PLUGIN_DIR . 'templates/modals/season-management-modal.php';
    ?>
</div>