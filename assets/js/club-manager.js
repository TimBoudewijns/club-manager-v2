// Club Manager Main JavaScript - Module System

// Wait for the clubManagerAjax object to be available
if (typeof window.clubManagerAjax === 'undefined') {
    console.error('Club Manager: Ajax object not loaded');
}

// Get the module base URL
const moduleBase = window.clubManagerAjax?.plugin_url ? window.clubManagerAjax.plugin_url + 'assets/js/modules/' : '/wp-content/plugins/club-manager/assets/js/modules/';

// Import modules using full URLs
import { TeamModule } from './modules/team-module.js';
import { PlayerModule } from './modules/player-module.js';
import { EvaluationModule } from './modules/evaluation-module.js';
import { TrainerModule } from './modules/trainer-module.js';
import { TeamManagementModule } from './modules/team-management-module.js';
import { PlayerCardModule } from './modules/player-card-module.js';
import { ClubTeamsModule } from './modules/club-teams-module.js';

// Main Alpine component
window.clubManager = function() {
    return {
        // Core data
        activeTab: 'my-teams',
        currentSeason: window.clubManagerAjax?.preferred_season || '2024-2025',
        userPermissions: window.clubManagerAjax?.permissions || {},
        
        // Module instances
        teamModule: null,
        playerModule: null,
        evaluationModule: null,
        trainerModule: null,
        teamManagementModule: null,
        playerCardModule: null,
        clubTeamsModule: null,
        
        // Initialize
        async init() {
            console.log('Club Manager initializing...');
            console.log('User permissions:', this.userPermissions);
            console.log('Plugin URL:', window.clubManagerAjax?.plugin_url);
            
            // Initialize modules
            this.initializeModules();
            
            // Set initial tab based on permissions
            this.setInitialTab();
            
            // Load initial data
            await this.loadInitialData();
            
            // Watch for tab changes
            this.$watch('activeTab', async (value) => {
                await this.handleTabChange(value);
            });
        },
        
        // Initialize all modules
        initializeModules() {
            this.teamModule = new TeamModule(this);
            this.playerModule = new PlayerModule(this);
            this.evaluationModule = new EvaluationModule(this);
            this.playerCardModule = new PlayerCardModule(this);
            
            // Only initialize modules user has access to
            if (this.userPermissions.can_manage_teams) {
                this.teamManagementModule = new TeamManagementModule(this);
            }
            
            if (this.userPermissions.can_manage_trainers) {
                this.trainerModule = new TrainerModule(this);
            }
            
            if (this.userPermissions.can_view_club_teams) {
                this.clubTeamsModule = new ClubTeamsModule(this);
            }
        },
        
        // Set initial tab based on user permissions
        setInitialTab() {
            if (this.userPermissions.available_tabs && this.userPermissions.available_tabs.length > 0) {
                // Default to my-teams if available
                if (this.userPermissions.available_tabs.includes('my-teams')) {
                    this.activeTab = 'my-teams';
                } else {
                    // Otherwise use first available tab
                    this.activeTab = this.userPermissions.available_tabs[0];
                }
            }
        },
        
        // Load initial data based on active tab
        async loadInitialData() {
            await this.handleTabChange(this.activeTab);
        },
        
        // Handle tab changes
        async handleTabChange(tab) {
            switch (tab) {
                case 'my-teams':
                    await this.teamModule.loadMyTeams();
                    break;
                    
                case 'team-management':
                    if (this.teamManagementModule) {
                        await this.teamManagementModule.loadManagedTeams();
                    }
                    break;
                    
                case 'club-teams':
                    if (this.clubTeamsModule) {
                        await this.clubTeamsModule.loadClubTeams();
                    }
                    break;
                    
                case 'trainer-management':
                    if (this.trainerModule) {
                        await this.trainerModule.loadTrainerManagementData();
                    }
                    break;
            }
        },
        
        // API helper (shared across modules)
        async apiPost(action, data = {}) {
            const formData = new FormData();
            formData.append('action', action);
            formData.append('nonce', window.clubManagerAjax.nonce);
            
            Object.keys(data).forEach(key => {
                if (Array.isArray(data[key])) {
                    data[key].forEach(value => {
                        formData.append(key + '[]', value);
                    });
                } else {
                    formData.append(key, data[key]);
                }
            });
            
            try {
                const response = await fetch(window.clubManagerAjax.ajax_url, {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (!result.success) {
                    throw new Error(result.data || 'Request failed');
                }
                
                return result.data;
            } catch (error) {
                console.error('API Error:', error);
                throw error;
            }
        },
        
        // Season management
        async changeSeason() {
            await this.apiPost('cm_save_season_preference', {
                season: this.currentSeason
            });
            
            // Reload data for current tab
            await this.handleTabChange(this.activeTab);
            
            // Reset selections
            if (this.teamModule) {
                this.teamModule.resetSelections();
            }
            if (this.teamManagementModule) {
                this.teamManagementModule.resetSelections();
            }
            if (this.clubTeamsModule) {
                this.clubTeamsModule.resetSelections();
            }
        },
        
        // Helper method to check if user has permission for a feature
        hasPermission(permission) {
            return this.userPermissions[permission] === true;
        },
        
        // Helper method to check if tab is available
        isTabAvailable(tab) {
            return this.userPermissions.available_tabs && 
                   this.userPermissions.available_tabs.includes(tab);
        }
    };
};