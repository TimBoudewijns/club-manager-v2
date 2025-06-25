<?php
/**
 * Main dashboard template
 */
?>
<style>
    /* Enhanced gradient animations */
    .gradient-animation {
        background: linear-gradient(-45deg, #f97316, #ea580c, #fb923c, #fdba74);
        background-size: 400% 400%;
        animation: gradientShift 8s ease infinite;
    }
    
    @keyframes gradientShift {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }
    
    /* Glassmorphism enhancements */
    .glass-effect {
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(20px) saturate(120%);
        border: 1px solid rgba(255, 255, 255, 0.2);
        box-shadow: 
            0 8px 32px rgba(31, 38, 135, 0.37),
            inset 0 1px 0 rgba(255, 255, 255, 0.5);
    }
    
    /* Section flow improvements */
    .section-container {
        margin-bottom: 2rem;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .section-container:hover {
        transform: translateY(-2px);
    }
    
    /* Hide elements with x-cloak until Alpine loads */
    [x-cloak] { display: none !important; }
    
    /* Floating elements */
    .floating-element {
        animation: float 6s ease-in-out infinite;
    }
    
    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-10px); }
    }
</style>

<div class="club-manager-app min-h-screen" x-data="clubManager()" data-theme="light">
    <div class="w-full px-4 md:px-6 lg:px-8 py-6 md:py-8 space-y-6 md:space-y-8">
        
        <?php include 'partials/header.php'; ?>
        
        <!-- Player Management Tab (Combined My Teams + Club Teams) -->
        <div x-show="activeTab === 'player-management'" 
             x-transition:enter="transition ease-out duration-500"
             x-transition:enter-start="opacity-0 transform translate-y-8 scale-95"
             x-transition:enter-end="opacity-100 transform translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-300"
             x-transition:leave-start="opacity-100 transform translate-y-0 scale-100"
             x-transition:leave-end="opacity-0 transform translate-y-4 scale-95"
             class="space-y-8">
            
            <!-- Player Management Header -->
            <div class="glass-effect rounded-2xl p-6 md:p-8 border border-orange-200/30 shadow-2xl">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="gradient-animation rounded-2xl p-4 shadow-xl floating-element">
                            <svg class="w-8 h-8 md:w-10 md:h-10 text-white drop-shadow-lg" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2 bg-gradient-to-r from-orange-600 to-orange-800 bg-clip-text text-transparent">
                                Player Management
                            </h2>
                            <p class="text-gray-600 text-sm md:text-base">Manage players across your teams and view club roster</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- My Teams Section -->
            <div class="section-container space-y-6">
                <!-- Section Header -->
                <div class="flex items-center justify-between">
                    <div class="space-y-2">
                        <h3 class="text-xl md:text-2xl font-bold text-gray-900 flex items-center">
                            <div class="bg-gradient-to-br from-orange-100 to-orange-200 rounded-xl p-3 mr-4 shadow-lg">
                                <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                            </div>
                            My Teams
                        </h3>
                        <p class="text-gray-600 ml-16">Teams you manage directly</p>
                    </div>
                    
                    <!-- Add Team Button -->
                    <button x-show="canCreateTeam()" 
                            @click="showCreateTeamModal = true" 
                            class="bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white font-bold py-3 px-6 md:px-8 rounded-xl shadow-lg hover:shadow-xl transform transition-all duration-300 hover:scale-105 flex items-center space-x-2 group">
                        <svg class="w-5 h-5 group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        <span>Create New Team</span>
                    </button>
                </div>
                
                <!-- Notice for trainers -->
                <div x-show="userPermissions.is_trainer" 
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 transform scale-95"
                     x-transition:enter-end="opacity-100 transform scale-100"
                     class="notification-info rounded-xl p-4 md:p-6">
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0 mt-1">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-semibold text-blue-900 mb-1">Trainer Access</h4>
                            <p class="text-blue-800 text-sm">
                                These are teams assigned to you where you can manage and evaluate players.
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- My Teams Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 md:gap-8">
                    <template x-for="team in myTeams" :key="team.id">
                        <div @click="selectTeam(team)" 
                             class="team-card group bg-white rounded-2xl shadow-lg hover:shadow-2xl cursor-pointer overflow-hidden transition-all duration-400 hover:scale-102 border border-white/50">
                            <!-- Orange gradient bar with animation -->
                            <div class="h-2 bg-gradient-to-r from-orange-400 via-orange-500 to-orange-600 group-hover:h-3 transition-all duration-300"></div>
                            
                            <!-- My Team Badge -->
                            <div class="absolute top-4 right-4 bg-gradient-to-r from-orange-100 to-orange-200 text-orange-700 px-3 py-1 rounded-full text-xs font-semibold shadow-sm border border-orange-300/50">
                                My Team
                            </div>
                            
                            <div class="p-6 md:p-8">
                                <h4 class="text-xl md:text-2xl font-bold text-gray-900 group-hover:text-orange-600 transition-colors duration-300 mb-4" x-text="team.name"></h4>
                                
                                <div class="space-y-3">
                                    <div class="flex items-center text-gray-600">
                                        <div class="bg-orange-100 rounded-lg p-2 mr-3">
                                            <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <span class="font-medium text-gray-700">Coach:</span>
                                            <span class="ml-2 text-gray-900 font-semibold" x-text="team.coach"></span>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center text-gray-600">
                                        <div class="bg-orange-100 rounded-lg p-2 mr-3">
                                            <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <span class="font-medium text-gray-700">Season:</span>
                                            <span class="ml-2 text-gray-900 font-semibold" x-text="team.season"></span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mt-6 pt-4 border-t border-gray-100">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-500">Click to manage players</span>
                                        <svg class="w-5 h-5 text-orange-500 group-hover:translate-x-1 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
                
                <!-- Empty State for My Teams -->
                <div x-show="myTeams.length === 0" 
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 transform scale-95"
                     x-transition:enter-end="opacity-100 transform scale-100"
                     class="empty-state">
                    <div class="bg-gradient-to-br from-orange-100 to-orange-200 rounded-full w-24 h-24 mx-auto mb-6 flex items-center justify-center shadow-lg">
                        <svg class="w-12 h-12 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <h4 class="text-2xl font-bold text-gray-900 mb-3">No teams yet</h4>
                    <p class="text-gray-600 mb-8 text-lg" x-show="userPermissions.is_trainer">
                        Your club manager will assign teams to you.
                    </p>
                    <p class="text-gray-600 mb-8 text-lg" x-show="!userPermissions.is_trainer">
                        Create your first team to get started managing your players.
                    </p>
                    <button x-show="canCreateTeam()" 
                            @click="showCreateTeamModal = true" 
                            class="bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white font-bold py-4 px-8 rounded-xl shadow-lg hover:shadow-xl transform transition-all duration-300 hover:scale-105">
                        Create Your First Team
                    </button>
                </div>
            </div>
            
            <!-- Club Teams Section - Only show if user has permission -->
            <div x-show="hasPermission('can_view_club_teams')" 
                 x-cloak
                 x-transition:enter="transition ease-out duration-500 delay-200"
                 x-transition:enter-start="opacity-0 transform translate-y-8"
                 x-transition:enter-end="opacity-100 transform translate-y-0"
                 class="section-container space-y-6">
                
                <!-- Section Header -->
                <div class="space-y-2">
                    <h3 class="text-xl md:text-2xl font-bold text-gray-900 flex items-center">
                        <div class="bg-gradient-to-br from-blue-100 to-blue-200 rounded-xl p-3 mr-4 shadow-lg">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                        </div>
                        Club Teams
                    </h3>
                    <p class="text-gray-600 ml-16">View-only access to other teams in your club</p>
                </div>
                
                <!-- Club Teams Notice -->
                <div class="notification-info rounded-xl p-4 md:p-6">
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0 mt-1">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-semibold text-blue-900 mb-1">Read-only mode</h4>
                            <p class="text-blue-800 text-sm">
                                You can view all teams and players in your club, but cannot make changes.
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Club Teams Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 md:gap-8">
                    <template x-for="team in clubTeams" :key="team.id">
                        <div @click="selectClubTeam(team)" 
                             class="team-card group bg-white rounded-2xl shadow-lg hover:shadow-2xl cursor-pointer overflow-hidden transition-all duration-400 hover:scale-102 border border-white/50">
                            <!-- Blue gradient bar -->
                            <div class="h-2 bg-gradient-to-r from-blue-400 via-blue-500 to-blue-600 group-hover:h-3 transition-all duration-300"></div>
                            
                            <!-- Club Team Badge -->
                            <div class="absolute top-4 right-4 bg-gradient-to-r from-blue-100 to-blue-200 text-blue-700 px-3 py-1 rounded-full text-xs font-semibold shadow-sm border border-blue-300/50">
                                Club Team
                            </div>
                            
                            <div class="p-6 md:p-8">
                                <h4 class="text-xl md:text-2xl font-bold text-gray-900 group-hover:text-blue-600 transition-colors duration-300 mb-4" x-text="team.name"></h4>
                                
                                <div class="space-y-3">
                                    <div class="flex items-center text-gray-600">
                                        <div class="bg-blue-100 rounded-lg p-2 mr-3">
                                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <span class="font-medium text-gray-700">Coach:</span>
                                            <span class="ml-2 text-gray-900 font-semibold" x-text="team.coach"></span>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center text-gray-600" x-show="team.trainer_names">
                                        <div class="bg-blue-100 rounded-lg p-2 mr-3">
                                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                            </svg>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <span class="font-medium text-gray-700">Trainer:</span>
                                            <span class="ml-2 text-gray-900 font-semibold text-sm break-words" x-text="team.trainer_names || 'No trainer assigned'"></span>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center text-gray-600">
                                        <div class="bg-blue-100 rounded-lg p-2 mr-3">
                                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <span class="font-medium text-gray-700">Season:</span>
                                            <span class="ml-2 text-gray-900 font-semibold" x-text="team.season"></span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mt-6 pt-4 border-t border-gray-100">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-500">Click to view players</span>
                                        <svg class="w-5 h-5 text-blue-500 group-hover:translate-x-1 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
                
                <!-- Empty State for Club Teams -->
                <div x-show="clubTeams.length === 0" 
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 transform scale-95"
                     x-transition:enter-end="opacity-100 transform scale-100"
                     class="empty-state">
                    <div class="bg-gradient-to-br from-blue-100 to-blue-200 rounded-full w-24 h-24 mx-auto mb-6 flex items-center justify-center shadow-lg">
                        <svg class="w-12 h-12 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                    </div>
                    <h4 class="text-2xl font-bold text-gray-900 mb-3">No club teams found</h4>
                    <p class="text-gray-600 text-lg">There are no other teams in your club yet.</p>
                </div>
            </div>
            
            <!-- Player Card Section -->
            <?php include 'partials/player-card.php'; ?>
        </div>
        
        <!-- Team Management Tab - Only for owners/managers -->
        <div x-show="activeTab === 'team-management' && isTabAvailable('team-management')" 
             x-cloak
             x-transition:enter="transition ease-out duration-500"
             x-transition:enter-start="opacity-0 transform translate-y-8 scale-95"
             x-transition:enter-end="opacity-100 transform translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-300"
             x-transition:leave-start="opacity-100 transform translate-y-0 scale-100"
             x-transition:leave-end="opacity-0 transform translate-y-4 scale-95">
            <?php include 'partials/team-management.php'; ?>
        </div>
        
        <!-- Trainer Management Tab - Only show if user has permission -->
        <div x-show="activeTab === 'trainer-management' && isTabAvailable('trainer-management')" 
             x-cloak
             x-transition:enter="transition ease-out duration-500"
             x-transition:enter-start="opacity-0 transform translate-y-8 scale-95"
             x-transition:enter-end="opacity-100 transform translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-300"
             x-transition:leave-start="opacity-100 transform translate-y-0 scale-100"
             x-transition:leave-end="opacity-0 transform translate-y-4 scale-95">
            <?php include 'partials/trainer-management.php'; ?>
        </div>
    </div>
    
    <!-- Include ALL Modals OUTSIDE the tab content divs -->
    <?php 
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
    ?>
</div>