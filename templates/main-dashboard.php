<?php
/**
 * Main dashboard template
 */
?>
<style>
    /* Fix gradient buttons specifically */
    .club-manager-app .bg-gradient-to-r.from-orange-500.to-orange-600 {
        background-image: linear-gradient(to right, #f97316, #ea580c) !important;
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
    
    /* Hide elements with x-cloak until Alpine loads */
    [x-cloak] { display: none !important; }
</style>

<div class="club-manager-app min-h-screen bg-white" x-data="clubManager()" data-theme="light">
    <div class="w-full px-4 md:px-6 lg:px-8 py-8">
        
        <?php include 'partials/header.php'; ?>
        
        <?php include 'partials/tabs.php'; ?>
        
        <!-- My Teams Tab -->
        <div x-show="activeTab === 'my-teams'" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform scale-95"
             x-transition:enter-end="opacity-100 transform scale-100">
            
            <!-- Add Team Button - Only show if user can create teams -->
            <div class="mb-8" x-show="canCreateTeam()">
                <button @click="showCreateTeamModal = true" 
                        class="bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white font-bold py-3 px-8 rounded-xl shadow-lg transform transition-all duration-200 hover:scale-105 flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    <span>Create New Team</span>
                </button>
            </div>
            
            <!-- Notice for trainers -->
            <div x-show="userPermissions.is_trainer" class="mb-8 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-blue-600 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div>
                        <p class="text-blue-800 text-sm">
                            <span class="font-semibold">Trainer mode:</span> You can view and evaluate players in teams assigned to you by your club manager.
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- My Teams Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                <template x-for="team in myTeams" :key="team.id">
                    <div @click="selectTeam(team)" 
                         class="bg-white rounded-xl shadow-lg hover:shadow-2xl transform transition-all duration-300 hover:scale-105 cursor-pointer overflow-hidden group">
                        <div class="bg-gradient-to-r from-orange-400 to-orange-500 h-2 group-hover:h-3 transition-all duration-300"></div>
                        <div class="p-6">
                            <div class="flex items-start justify-between mb-4">
                                <h3 class="text-2xl font-bold text-gray-900 group-hover:text-orange-600 transition-colors" x-text="team.name"></h3>
                                <div class="bg-orange-100 text-orange-600 px-3 py-1 rounded-full text-sm font-semibold">
                                    <span x-text="team.season"></span>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <div class="flex items-center text-gray-600">
                                    <svg class="w-5 h-5 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    <span class="font-medium">Coach:</span>
                                    <span class="ml-2" x-text="team.coach"></span>
                                </div>
                            </div>
                            <div class="mt-4 pt-4 border-t border-gray-100">
                                <span class="text-sm text-gray-500">Click to manage players →</span>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
            
            <!-- Empty State -->
            <div x-show="myTeams.length === 0" class="text-center py-16">
                <div class="bg-orange-50 rounded-full w-24 h-24 mx-auto mb-6 flex items-center justify-center">
                    <svg class="w-12 h-12 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-2">No teams yet</h3>
                <p class="text-gray-600 mb-6" x-show="userPermissions.is_trainer">
                    Your club manager will assign teams to you.
                </p>
                <p class="text-gray-600 mb-6" x-show="!userPermissions.is_trainer">
                    Create your first team to get started managing your players.
                </p>
                <button x-show="canCreateTeam()" 
                        @click="showCreateTeamModal = true" 
                        class="bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white font-bold py-3 px-8 rounded-xl shadow-lg transform transition-all duration-200 hover:scale-105">
                    Create Your First Team
                </button>
            </div>
            
            <?php include 'partials/team-details.php'; ?>
            
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
        
        <!-- Club Teams Tab - Only show if user has permission -->
        <div x-show="activeTab === 'club-teams' && isTabAvailable('club-teams')" 
             x-cloak
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform scale-95"
             x-transition:enter-end="opacity-100 transform scale-100">
            <?php include 'partials/club-teams.php'; ?>
        </div>
        
        <!-- Trainer Management Tab - Only show if user has permission -->
        <div x-show="activeTab === 'trainer-management' && isTabAvailable('trainer-management')" 
             x-cloak
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform scale-95"
             x-transition:enter-end="opacity-100 transform scale-100">
            <?php include 'partials/trainer-management.php'; ?>
        </div>
        
        <!-- Include Modals -->
        <?php 
        include CLUB_MANAGER_PLUGIN_DIR . 'templates/modals/create-team-modal.php';
        include CLUB_MANAGER_PLUGIN_DIR . 'templates/modals/add-player-modal.php';
        include CLUB_MANAGER_PLUGIN_DIR . 'templates/modals/add-existing-player-modal.php';
        include CLUB_MANAGER_PLUGIN_DIR . 'templates/modals/evaluation-modal.php';
        include CLUB_MANAGER_PLUGIN_DIR . 'templates/modals/player-history-modal.php';
        include CLUB_MANAGER_PLUGIN_DIR . 'templates/modals/invite-trainer-modal.php';
        include CLUB_MANAGER_PLUGIN_DIR . 'templates/modals/edit-trainer-modal.php';
        ?>
    </div>
</div>