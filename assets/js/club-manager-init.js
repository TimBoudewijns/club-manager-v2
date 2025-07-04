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
        importExportModule: null,
        
        // Initialize
        async init() {
            console.log('Club Manager initializing...');
            this.initializeModules();
            this.setInitialTab();
            await this.loadInitialData();
            this.$watch('activeTab', (value) => this.handleTabChange(value));
        },
        
        // Initialize all modules
        initializeModules() {
            if (typeof TeamModule !== 'undefined') this.teamModule = new TeamModule(this);
            if (typeof PlayerModule !== 'undefined') this.playerModule = new PlayerModule(this);
            if (typeof EvaluationModule !== 'undefined') this.evaluationModule = new EvaluationModule(this);
            if (typeof PlayerCardModule !== 'undefined') this.playerCardModule = new PlayerCardModule(this);
            if (this.userPermissions.can_view_club_teams && typeof ClubTeamsModule !== 'undefined') this.clubTeamsModule = new ClubTeamsModule(this);
            if (this.userPermissions.can_manage_teams && typeof TeamManagementModule !== 'undefined') this.teamManagementModule = new TeamManagementModule(this);
            if (this.userPermissions.can_manage_trainers && typeof TrainerModule !== 'undefined') this.trainerModule = new TrainerModule(this);
            if (this.userPermissions.can_import_export && typeof ImportExportModule !== 'undefined') this.importExportModule = new ImportExportModule(this);
        },
        
        // Set initial tab based on user permissions
        setInitialTab() {
            if (this.userPermissions.available_tabs && this.userPermissions.available_tabs.includes('player-management')) {
                this.activeTab = 'player-management';
            } else if (this.userPermissions.available_tabs && this.userPermissions.available_tabs.length > 0) {
                this.activeTab = this.userPermissions.available_tabs[0];
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
                    if (this.teamModule) await this.teamModule.loadMyTeams();
                    if (this.clubTeamsModule) await this.clubTeamsModule.loadClubTeams();
                    break;
                case 'team-management':
                    if (this.teamManagementModule) await this.teamManagementModule.loadManagedTeams();
                    break;
                case 'trainer-management':
                    if (this.trainerModule) await this.trainerModule.loadTrainerManagementData();
                    break;
            }
        },
        
        // API helper (shared across modules) - FINAL CORRECTED VERSION
        async apiPost(action, data = {}) {
            const formData = new FormData();
            formData.append('action', action);
            formData.append('nonce', window.clubManagerAjax.nonce);

            // This handles both simple objects and FormData objects correctly
            if (data instanceof FormData) {
                for (let [key, value] of data.entries()) {
                    formData.append(key, value);
                }
            } else {
                for (const key in data) {
                    if (Object.prototype.hasOwnProperty.call(data, key)) {
                        const value = data[key];
                        // Stringify complex data types (arrays, objects) for consistent handling in PHP
                        if (typeof value === 'object' && value !== null) {
                            formData.append(key, JSON.stringify(value));
                        } else {
                            formData.append(key, value);
                        }
                    }
                }
            }

            try {
                const response = await fetch(window.clubManagerAjax.ajax_url, {
                    method: 'POST',
                    body: formData
                });
                
                const resultText = await response.text();
                try {
                    const result = JSON.parse(resultText);
                    if (!result.success) {
                        const errorMessage = result.data?.message || result.data || 'An unknown error occurred.';
                        throw new Error(errorMessage);
                    }
                    return result.data;
                } catch (e) {
                    console.error("Failed to parse JSON. Server returned non-JSON response:", resultText);
                    throw new Error("An unexpected server error occurred. Please check the browser console and server logs.");
                }
            } catch (error) {
                console.error('API Error:', error);
                alert('Error: ' + error.message);
                throw error;
            }
        },
        
        // Season management
        async changeSeason() {
            await this.apiPost('cm_save_season_preference', { season: this.currentSeason });
            await this.handleTabChange(this.activeTab);
            if (this.teamModule) this.teamModule.resetSelections();
            if (this.teamManagementModule) this.teamManagementModule.resetSelections();
            if (this.clubTeamsModule) this.clubTeamsModule.resetSelections();
        },
        
        // Permission helpers
        hasPermission(permission) { return this.userPermissions[permission] === true; },
        isTabAvailable(tab) { return this.userPermissions.available_tabs?.includes(tab); }
    };
};
