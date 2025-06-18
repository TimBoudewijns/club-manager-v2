// Club Manager Initialization

// Main Alpine component
window.clubManager = function() {
    return {
        // Core data
        activeTab: 'player-management',
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
            // Check if modules exist in global scope
            if (typeof TeamModule !== 'undefined') {
                this.teamModule = new TeamModule(this);
            }
            
            if (typeof PlayerModule !== 'undefined') {
                this.playerModule = new PlayerModule(this);
            }
            
            if (typeof EvaluationModule !== 'undefined') {
                this.evaluationModule = new EvaluationModule(this);
            }
            
            if (typeof PlayerCardModule !== 'undefined') {
                this.playerCardModule = new PlayerCardModule(this);
            }
            
            // Always initialize club teams module for owners/managers
            if (this.userPermissions.can_view_club_teams && typeof ClubTeamsModule !== 'undefined') {
                this.clubTeamsModule = new ClubTeamsModule(this);
            }
            
            // Only initialize modules user has access to
            if (this.userPermissions.can_manage_teams && typeof TeamManagementModule !== 'undefined') {
                this.teamManagementModule = new TeamManagementModule(this);
            }
            
            if (this.userPermissions.can_manage_trainers && typeof TrainerModule !== 'undefined') {
                this.trainerModule = new TrainerModule(this);
            }
        },
        
        // Set initial tab based on user permissions
        setInitialTab() {
            if (this.userPermissions.available_tabs && this.userPermissions.available_tabs.length > 0) {
                // Default to player-management if available
                if (this.userPermissions.available_tabs.includes('player-management')) {
                    this.activeTab = 'player-management';
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
                case 'player-management':
                    // Load both my teams and club teams
                    if (this.teamModule) {
                        await this.teamModule.loadMyTeams();
                    }
                    if (this.clubTeamsModule && this.userPermissions.can_view_club_teams) {
                        await this.clubTeamsModule.loadClubTeams();
                    }
                    break;
                    
                case 'team-management':
                    if (this.teamManagementModule) {
                        await this.teamManagementModule.loadManagedTeams();
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

// Module base class - andere modules kunnen dit gebruiken
window.ClubManagerModule = class {
    constructor(manager) {
        this.manager = manager;
    }
    
    // Shared API helper
    async apiPost(action, data = {}) {
        return this.manager.apiPost(action, data);
    }
    
    // Get current season
    get currentSeason() {
        return this.manager.currentSeason;
    }
    
    // Check permission
    hasPermission(permission) {
        return this.manager.hasPermission(permission);
    }
};