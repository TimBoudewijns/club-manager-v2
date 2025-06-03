<?php
/**
 * Main dashboard template
 */

// Check if user can view club teams
$can_view_club_teams = Club_Manager_Teams_Helper::can_view_club_teams();
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
            
            <!-- Add Team Button -->
            <div class="mb-8">
                <button @click="showCreateTeamModal = true" 
                        class="bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white font-bold py-3 px-8 rounded-xl shadow-lg transform transition-all duration-200 hover:scale-105 flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    <span>Create New Team</span>
                </button>
            </div>
            
            <?php include 'partials/teams-grid.php'; ?>
            
            <?php include 'partials/team-details.php'; ?>
            
            <?php include 'partials/player-card.php'; ?>
        </div>
        
        <!-- Club Teams Tab -->
        <div x-show="activeTab === 'club-teams' && canViewClubTeams" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform scale-95"
             x-transition:enter-end="opacity-100 transform scale-100">
            
            <?php if ($can_view_club_teams): ?>
                <?php include 'partials/club-teams.php'; ?>
            <?php else: ?>
                <div class="bg-white rounded-2xl shadow-xl p-16 text-center">
                    <div class="bg-orange-50 rounded-full w-24 h-24 mx-auto mb-6 flex items-center justify-center">
                        <svg class="w-12 h-12 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">Access Restricted</h3>
                    <p class="text-gray-600">You need to be a team owner or manager to access club teams.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Include Modals -->
        <?php 
        include CLUB_MANAGER_PLUGIN_DIR . 'templates/modals/create-team-modal.php';
        include CLUB_MANAGER_PLUGIN_DIR . 'templates/modals/add-player-modal.php';
        include CLUB_MANAGER_PLUGIN_DIR . 'templates/modals/add-existing-player-modal.php';
        include CLUB_MANAGER_PLUGIN_DIR . 'templates/modals/evaluation-modal.php';
        include CLUB_MANAGER_PLUGIN_DIR . 'templates/modals/player-history-modal.php';
        ?>
    </div>
</div>