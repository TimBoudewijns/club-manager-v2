// Club Manager Initialization

// Main Alpine component
window.clubManager = function() {
    return {
        // Core data
        activeTab: 'player-management',
        currentSeason: window.clubManagerAjax?.preferred_season || '2024-2025',
        userPermissions: window.clubManagerAjax?.permissions || {},
        
        // Global loading state
        globalLoading: false,
        loadingMessage: '',
        
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
            console.log('User permissions:', this.userPermissions);
            console.log('AJAX URL:', window.clubManagerAjax?.ajax_url);
            
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
            
            // Initialize import/export module for authorized users
            if (this.userPermissions.can_import_export && typeof ImportExportModule !== 'undefined') {
                this.importExportModule = new ImportExportModule(this);
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
            console.log('Tab changed to:', tab);
            
            await this.withLoading(async () => {
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
                        
                    case 'import-export':
                        // No initial data load needed for import/export
                        break;
                }
            }, 'Loading data...');
        },
        
        // Loading helper with message
        async withLoading(asyncFn, message = 'Loading...') {
            this.globalLoading = true;
            this.loadingMessage = message;
            try {
                return await asyncFn();
            } finally {
                this.globalLoading = false;
                this.loadingMessage = '';
            }
        },
        
        // Button loading state helper
        setButtonLoading(button, isLoading, originalText) {
            if (!button) return;
            
            if (isLoading) {
                button.disabled = true;
                button.dataset.originalText = originalText || button.innerHTML;
                button.innerHTML = `
                    <svg class="animate-spin h-5 w-5 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                `;
            } else {
                button.disabled = false;
                button.innerHTML = button.dataset.originalText || originalText;
            }
        },
        
        // API helper (shared across modules) with enhanced debugging
        async apiPost(action, data = {}) {
            console.log('AJAX Call:', action, data);
            
            // Validate action name
            if (!action.startsWith('cm_')) {
                console.warn('AJAX action should start with cm_:', action);
            }
            
            // Check if we have required data
            if (!window.clubManagerAjax) {
                console.error('clubManagerAjax not available');
                throw new Error('AJAX configuration not available');
            }
            
            if (!window.clubManagerAjax.ajax_url) {
                console.error('AJAX URL not configured');
                throw new Error('AJAX URL not configured');
            }
            
            if (!window.clubManagerAjax.nonce) {
                console.error('Security nonce not available');
                throw new Error('Security nonce not available');
            }
            
            // Handle FormData differently
            if (data instanceof FormData) {
                data.append('action', action);
                data.append('nonce', window.clubManagerAjax.nonce);
                
                try {
                    const response = await fetch(window.clubManagerAjax.ajax_url, {
                        method: 'POST',
                        body: data
                    });
                    
                    // Get response as text first for debugging
                    const responseText = await response.text();
                    console.log('Raw response for ' + action + ':', responseText);
                    
                    let result;
                    try {
                        result = JSON.parse(responseText);
                    } catch (e) {
                        console.error('JSON Parse error:', e);
                        console.error('Response was:', responseText);
                        
                        // Check for common WordPress errors
                        if (responseText.includes('<!DOCTYPE html>') || responseText.includes('<html')) {
                            throw new Error('Received HTML instead of JSON. Check if you are logged in.');
                        }
                        
                        throw new Error('Invalid JSON response from server');
                    }
                    
                    if (!result.success) {
                        console.error('AJAX Error:', result.data);
                        throw new Error(result.data || 'Request failed');
                    }
                    
                    console.log('AJAX Success for ' + action + ':', result.data);
                    return result.data;
                    
                } catch (error) {
                    console.error('API Error for ' + action + ':', error);
                    throw error;
                }
            } else {
                // Regular data handling
                const formData = new FormData();
                formData.append('action', action);
                formData.append('nonce', window.clubManagerAjax.nonce);
                
                Object.keys(data).forEach(key => {
                    if (Array.isArray(data[key])) {
                        data[key].forEach(value => {
                            formData.append(key + '[]', value);
                        });
                    } else if (typeof data[key] === 'object' && data[key] !== null) {
                        formData.append(key, JSON.stringify(data[key]));
                    } else {
                        formData.append(key, data[key]);
                    }
                });
                
                try {
                    const response = await fetch(window.clubManagerAjax.ajax_url, {
                        method: 'POST',
                        body: formData
                    });
                    
                    // Get response as text first for debugging
                    const responseText = await response.text();
                    console.log('Raw response for ' + action + ':', responseText);
                    
                    let result;
                    try {
                        result = JSON.parse(responseText);
                    } catch (e) {
                        console.error('JSON Parse error:', e);
                        console.error('Response was:', responseText);
                        
                        // Check for common WordPress errors
                        if (responseText.includes('<!DOCTYPE html>') || responseText.includes('<html')) {
                            throw new Error('Received HTML instead of JSON. Check if you are logged in.');
                        }
                        
                        throw new Error('Invalid JSON response from server');
                    }
                    
                    if (!result.success) {
                        console.error('AJAX Error:', result.data);
                        throw new Error(result.data || 'Request failed');
                    }
                    
                    console.log('AJAX Success for ' + action + ':', result.data);
                    return result.data;
                    
                } catch (error) {
                    console.error('API Error for ' + action + ':', error);
                    throw error;
                }
            }
        },
        
        // Season management
        async changeSeason() {
            await this.withLoading(async () => {
                try {
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
                } catch (error) {
                    console.error('Error changing season:', error);
                    alert('Error changing season: ' + error.message);
                }
            }, 'Changing season...');
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
    
    // Use loading wrapper
    async withLoading(asyncFn, message) {
        return this.manager.withLoading(asyncFn, message);
    }
    
    // Set button loading
    setButtonLoading(button, isLoading, originalText) {
        return this.manager.setButtonLoading(button, isLoading, originalText);
    }
};

// Debug helper for development
if (window.clubManagerAjax && window.clubManagerAjax.debug) {
    console.log('Club Manager Debug Mode Enabled');
    window.addEventListener('error', function(e) {
        console.error('JavaScript Error:', e.message, 'at', e.filename, ':', e.lineno);
    });
}