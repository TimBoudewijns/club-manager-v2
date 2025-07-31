<?php
/**
 * Unified Dashboard Template - Complete Redesign
 * Modern card-based interface with integrated header and tabs
 */
?>
<style>
    /* Modern Unified Dashboard Styles */
    .unified-dashboard {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        min-height: 100vh;
    }
    
    /* Main Container Card */
    .dashboard-card {
        background: white;
        border-radius: 24px;
        box-shadow: 
            0 4px 6px -1px rgba(0, 0, 0, 0.1),
            0 2px 4px -1px rgba(0, 0, 0, 0.06),
            0 0 0 1px rgba(0, 0, 0, 0.05);
        overflow: hidden;
        transition: all 0.3s ease;
    }
    
    .dashboard-card:hover {
        box-shadow: 
            0 20px 25px -5px rgba(0, 0, 0, 0.1),
            0 10px 10px -5px rgba(0, 0, 0, 0.04),
            0 0 0 1px rgba(0, 0, 0, 0.05);
    }
    
    /* Integrated Header */
    .dashboard-header {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border-bottom: 1px solid #e2e8f0;
        padding: 2rem;
    }
    
    /* Tab Navigation */
    .tab-navigation {
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        padding: 0;
        position: relative;
    }
    
    .tab-list {
        display: flex;
        overflow-x: auto;
        scrollbar-width: none; /* Firefox */
        -ms-overflow-style: none; /* Internet Explorer 10+ */
    }
    
    .tab-list::-webkit-scrollbar { /* WebKit */
        width: 0;
        height: 0;
    }
    
    .tab-item {
        flex: none;
        padding: 1.25rem 2rem;
        font-weight: 600;
        font-size: 0.95rem;
        transition: all 0.3s ease;
        border-bottom: 3px solid transparent;
        cursor: pointer;
        position: relative;
        white-space: nowrap;
    }
    
    .tab-item:hover {
        background: rgba(248, 250, 252, 0.8);
        color: #374151;
    }
    
    .tab-item.active {
        background: white;
        border-bottom-color: currentColor;
        color: var(--tab-color);
        box-shadow: 0 -2px 0 white;
    }
    
    .tab-item.active::after {
        content: '';
        position: absolute;
        bottom: -1px;
        left: 0;
        right: 0;
        height: 1px;
        background: white;
    }
    
    /* Tab Colors */
    .tab-player-management.active { --tab-color: #f97316; }
    .tab-team-management.active { --tab-color: #3b82f6; }
    .tab-trainer-management.active { --tab-color: #10b981; }
    .tab-import-export.active { --tab-color: #8b5cf6; }
    
    /* Content Area */
    .dashboard-content {
        background: white;
        min-height: 600px;
    }
    
    .content-section {
        padding: 2.5rem;
    }
    
    /* Section Headers */
    .section-header {
        margin-bottom: 2rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid #f1f5f9;
    }
    
    .section-title {
        display: flex;
        align-items: center;
        gap: 1rem;
        font-size: 1.75rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 0.5rem;
    }
    
    .section-subtitle {
        color: #6b7280;
        font-size: 1rem;
    }
    
    .section-icon {
        padding: 0.75rem;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    /* Quick Actions */
    .quick-actions {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
    }
    
    .quick-action-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1.5rem;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.2s ease;
        cursor: pointer;
        border: none;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }
    
    .quick-action-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
    
    /* Responsive Design */
    @media (max-width: 768px) {
        .dashboard-header {
            padding: 1.5rem;
        }
        
        .tab-item {
            padding: 1rem 1.5rem;
            font-size: 0.9rem;
        }
        
        .content-section {
            padding: 1.5rem;
        }
        
        .section-title {
            font-size: 1.5rem;
        }
        
        .quick-actions {
            flex-direction: column;
        }
        
        .quick-action-btn {
            justify-content: center;
        }
    }
    
    @media (max-width: 640px) {
        .dashboard-card {
            border-radius: 0;
            margin: 0;
        }
        
        .dashboard-header {
            padding: 1rem;
        }
        
        .content-section {
            padding: 1rem;
        }
        
        .section-header {
            margin-bottom: 1.5rem;
        }
    }
    
    /* Loading States */
    .loading-overlay {
        position: relative;
    }
    
    .loading-overlay::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(2px);
        z-index: 10;
    }
    
    .loading-spinner {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 11;
    }
    
    /* Animations */
    .fade-in {
        animation: fadeIn 0.5s ease-out;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .slide-in {
        animation: slideIn 0.3s ease-out;
    }
    
    @keyframes slideIn {
        from { opacity: 0; transform: translateX(-20px); }
        to { opacity: 1; transform: translateX(0); }
    }
</style>

<div class="unified-dashboard club-manager-app" x-data="clubManager()" data-theme="light">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-6 lg:py-12">
        
        <!-- Main Dashboard Card -->
        <div class="dashboard-card">
            
            <!-- Integrated Header -->
            <div class="dashboard-header">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                    
                    <!-- Left Side - Brand & Context -->
                    <div class="flex items-center gap-4">
                        <!-- Dynamic Icon based on active tab -->
                        <div class="section-icon" 
                             :class="{
                                'bg-gradient-to-br from-orange-100 to-orange-200 text-orange-600': activeTab === 'player-management',
                                'bg-gradient-to-br from-blue-100 to-blue-200 text-blue-600': activeTab === 'team-management', 
                                'bg-gradient-to-br from-green-100 to-green-200 text-green-600': activeTab === 'trainer-management',
                                'bg-gradient-to-br from-purple-100 to-purple-200 text-purple-600': activeTab === 'import-export'
                             }">
                            <!-- Player Management Icon -->
                            <svg x-show="activeTab === 'player-management'" class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            <!-- Team Management Icon -->
                            <svg x-show="activeTab === 'team-management'" class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                            </svg>
                            <!-- Trainer Management Icon -->
                            <svg x-show="activeTab === 'trainer-management'" class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                            <!-- Import/Export Icon -->
                            <svg x-show="activeTab === 'import-export'" class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
                            </svg>
                        </div>
                        
                        <div>
                            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900 mb-1">
                                Club Manager
                            </h1>
                            <p class="text-gray-600 text-sm lg:text-base">
                                <span x-show="activeTab === 'player-management'">Manage players across your teams</span>
                                <span x-show="activeTab === 'team-management'">Organize and manage your teams</span>
                                <span x-show="activeTab === 'trainer-management'">Manage trainers and permissions</span>
                                <span x-show="activeTab === 'import-export'">Import and export team data</span>
                            </p>
                        </div>
                    </div>
                    
                    <!-- Right Side - Controls -->
                    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                        
                        <!-- Season Selector -->
                        <div class="flex flex-col">
                            <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Season</label>
                            <select x-model="currentSeason" @change="changeSeason" 
                                    class="bg-white border border-gray-300 rounded-lg px-3 py-2 text-sm font-medium focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all">
                                <option value="2024-2025">2024-2025</option>
                                <option value="2023-2024">2023-2024</option>
                                <option value="2025-2026">2025-2026</option>
                            </select>
                        </div>
                        
                        <!-- Quick Actions -->
                        <div class="quick-actions">
                            <!-- Player Management Quick Action -->
                            <button x-show="activeTab === 'player-management' && hasPermission('can_add_teams_player_mgmt')"
                                    @click="showCreateTeamModal = true" 
                                    class="quick-action-btn bg-gradient-to-r from-orange-500 to-orange-600 text-white hover:from-orange-600 hover:to-orange-700">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Add Team
                            </button>
                            
                            <!-- Team Management Quick Action -->
                            <button x-show="activeTab === 'team-management' && hasPermission('can_create_teams')"
                                    @click="showCreateTeamModal = true" 
                                    class="quick-action-btn bg-gradient-to-r from-blue-500 to-blue-600 text-white hover:from-blue-600 hover:to-blue-700">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Create Team
                            </button>
                            
                            <!-- Trainer Management Quick Action -->
                            <button x-show="activeTab === 'trainer-management' && hasPermission('can_manage_trainers')"
                                    @click="showInviteTrainerModal = true" 
                                    class="quick-action-btn bg-gradient-to-r from-green-500 to-green-600 text-white hover:from-green-600 hover:to-green-700">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                                </svg>
                                Invite Trainer
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Integrated Tab Navigation -->
            <div class="tab-navigation">
                <div class="tab-list">
                    
                    <!-- Player Management Tab -->
                    <button x-show="isTabAvailable('player-management')"
                            @click="activeTab = 'player-management'"
                            class="tab-item tab-player-management"
                            :class="{ 'active': activeTab === 'player-management' }">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            <span>Player Management</span>
                        </div>
                    </button>
                    
                    <!-- Team Management Tab -->
                    <button x-show="isTabAvailable('team-management')" x-cloak
                            @click="activeTab = 'team-management'"
                            class="tab-item tab-team-management"
                            :class="{ 'active': activeTab === 'team-management' }">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                            </svg>
                            <span>Team Management</span>
                        </div>
                    </button>
                    
                    <!-- Trainer Management Tab -->
                    <button x-show="isTabAvailable('trainer-management')" x-cloak
                            @click="activeTab = 'trainer-management'"
                            class="tab-item tab-trainer-management"
                            :class="{ 'active': activeTab === 'trainer-management' }">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                            <span>Trainer Management</span>
                        </div>
                    </button>
                    
                    <!-- Import/Export Tab -->
                    <button x-show="isTabAvailable('import-export')" x-cloak
                            @click="activeTab = 'import-export'"
                            class="tab-item tab-import-export"
                            :class="{ 'active': activeTab === 'import-export' }">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
                            </svg>
                            <span>Import & Export</span>
                        </div>
                    </button>
                </div>
            </div>
            
            <!-- Dashboard Content -->
            <div class="dashboard-content" 
                 :class="{ 'loading-overlay': globalLoading }">
                
                <!-- Loading Spinner -->
                <div x-show="globalLoading" class="loading-spinner">
                    <div class="flex items-center space-x-3">
                        <svg class="animate-spin h-8 w-8 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-blue-600 font-medium" x-text="loadingMessage">Loading...</span>
                    </div>
                </div>
                
                <!-- Tab Content Areas -->
                
                <!-- Player Management Tab Content -->
                <div x-show="activeTab === 'player-management'" 
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 transform translate-y-4"
                     x-transition:enter-end="opacity-100 transform translate-y-0"
                     class="content-section fade-in">
                    
                    <?php include 'partials/content/player-management-content.php'; ?>
                </div>
                
                <!-- Team Management Tab Content -->
                <div x-show="activeTab === 'team-management'" 
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 transform translate-y-4"
                     x-transition:enter-end="opacity-100 transform translate-y-0"
                     class="content-section fade-in">
                    
                    <?php include 'partials/content/team-management-content.php'; ?>
                </div>
                
                <!-- Trainer Management Tab Content -->
                <div x-show="activeTab === 'trainer-management'" 
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 transform translate-y-4"
                     x-transition:enter-end="opacity-100 transform translate-y-0"
                     class="content-section fade-in">
                    
                    <?php include 'partials/content/trainer-management-content.php'; ?>
                </div>
                
                <!-- Import/Export Tab Content -->
                <div x-show="activeTab === 'import-export'" 
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 transform translate-y-4"
                     x-transition:enter-end="opacity-100 transform translate-y-0"
                     class="content-section fade-in">
                    
                    <?php include 'partials/content/import-export-content.php'; ?>
                </div>
                
            </div>
        </div>
    </div>
    
    <!-- Include all modals -->
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
    include CLUB_MANAGER_PLUGIN_DIR . 'templates/modals/import-export-modal.php';
    ?>
</div>