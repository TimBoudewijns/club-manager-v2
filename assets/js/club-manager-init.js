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
        
        // Data properties (initialized for all users to prevent Alpine errors)
        clubTeams: [],
        managedTeams: [],
        myTeams: [],
        
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
            // Always initialize core modules that all users need
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
            if (this.userPermissions.can_view_all_club_teams && typeof ClubTeamsModule !== 'undefined') {
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
            
            // Add fallback functions for missing modules/methods
            this.initializeFallbackMethods();
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
                        if (this.clubTeamsModule && this.userPermissions.can_view_all_club_teams) {
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
                    console.error('Error changing season: ', error.message);
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
        },
        
        // Initialize fallback methods for missing functionality
        initializeFallbackMethods() {
            // Fallback for handlePlayerCardModalClick if PlayerModule doesn't have it
            if (!this.handlePlayerCardModalClick) {
                this.handlePlayerCardModalClick = (playerId, isClubView = false) => {
                    console.warn('handlePlayerCardModalClick called but not available for this user type');
                    if (this.playerCardModule && typeof this.playerCardModule.viewPlayerCardInModal === 'function') {
                        this.playerCardModule.viewPlayerCardInModal(playerId, isClubView);
                    }
                };
            }
            
            // Fallback for other potentially missing methods
            const fallbackMethods = [
                'openCreateTeamModal',
                'openEditTeamModal',
                'openTeamDetailsModal',
                'openPlayerModal',
                'openTrainerModal',
                'deletePlayer',
                'deleteTeam',
                'deleteTrainer',
                'createTeam',
                'editTeam',
                'importExportData'
            ];
            
            fallbackMethods.forEach(methodName => {
                if (!this[methodName]) {
                    this[methodName] = (...args) => {
                        console.warn(`${methodName} called but not available for this user type`);
                    };
                }
            });
            
            // Initialize safe data properties to prevent undefined errors
            
            // Team Module variables (always needed for all users)
            if (!this.myTeams) {
                this.myTeams = [];
            }
            
            if (!this.selectedTeam) {
                this.selectedTeam = null;
            }
            
            if (!this.teamPlayers) {
                this.teamPlayers = [];
            }
            
            if (!this.viewingPlayer) {
                this.viewingPlayer = null;
            }
            
            if (!this.selectedPlayerCard) {
                this.selectedPlayerCard = null;
            }
            
            if (!this.showCreateTeamModal) {
                this.showCreateTeamModal = false;
            }
            
            if (!this.newTeam) {
                this.newTeam = {
                    name: '',
                    coach: ''
                };
            }
            
            if (!this.showTeamDetailsModal) {
                this.showTeamDetailsModal = false;
            }
            
            // Club Teams Module variables
            if (!this.clubTeams) {
                this.clubTeams = [];
            }
            
            if (!this.selectedClubTeam) {
                this.selectedClubTeam = null;
            }
            
            if (!this.clubTeamPlayers) {
                this.clubTeamPlayers = [];
            }
            
            if (!this.viewingClubPlayer) {
                this.viewingClubPlayer = null;
            }
            
            if (!this.selectedClubPlayerCard) {
                this.selectedClubPlayerCard = null;
            }
            
            if (!this.isViewingClubTeam) {
                this.isViewingClubTeam = false;
            }
            
            // Evaluation Module variables
            if (!this.evaluationCategories) {
                this.evaluationCategories = [];
            }
            
            if (!this.evaluations) {
                this.evaluations = {};
            }
            
            if (!this.showEvaluationModal) {
                this.showEvaluationModal = false;
            }
            
            if (!this.evaluatingPlayer) {
                this.evaluatingPlayer = null;
            }
            
            if (!this.evaluatingPlayerFrom) {
                this.evaluatingPlayerFrom = 'myTeam';
            }
            
            if (!this.evaluationScores) {
                this.evaluationScores = {};
            }
            
            if (!this.evaluationDate) {
                this.evaluationDate = new Date().toISOString().split('T')[0];
            }
            
            // Player Module variables
            if (!this.showAddPlayerModal) {
                this.showAddPlayerModal = false;
            }
            
            if (!this.showAddExistingPlayerModal) {
                this.showAddExistingPlayerModal = false;
            }
            
            if (!this.showPlayerHistoryModal) {
                this.showPlayerHistoryModal = false;
            }
            
            if (!this.playerSearch) {
                this.playerSearch = '';
            }
            
            if (!this.searchResults) {
                this.searchResults = [];
            }
            
            if (!this.selectedExistingPlayer) {
                this.selectedExistingPlayer = null;
            }
            
            if (!this.playerHistory) {
                this.playerHistory = [];
            }
            
            if (!this.historyPlayer) {
                this.historyPlayer = null;
            }
            
            if (!this.historyLoading) {
                this.historyLoading = false;
            }
            
            if (!this.newPlayer) {
                this.newPlayer = {
                    first_name: '',
                    last_name: '',
                    birth_date: '',
                    email: '',
                    position: '',
                    jersey_number: '',
                    notes: ''
                };
            }
            
            if (!this.existingPlayerTeamData) {
                this.existingPlayerTeamData = {
                    position: '',
                    jersey_number: '',
                    notes: ''
                };
            }
            
            // Player Card Module variables
            if (!this.showPlayerCardModal) {
                this.showPlayerCardModal = false;
            }
            
            if (!this.playerCardChart) {
                this.playerCardChart = null;
            }
            
            if (!this.modalPlayerCardChart) {
                this.modalPlayerCardChart = null;
            }
            
            if (!this.playerEvaluationHistory) {
                this.playerEvaluationHistory = [];
            }
            
            if (!this.availableEvaluationDates) {
                this.availableEvaluationDates = [];
            }
            
            if (!this.selectedEvaluationDate) {
                this.selectedEvaluationDate = 'all';
            }
            
            if (!this.playerAdvice) {
                this.playerAdvice = null;
            }
            
            if (!this.adviceLoading) {
                this.adviceLoading = false;
            }
            
            if (!this.adviceStatus) {
                this.adviceStatus = 'no_evaluations';
            }
            
            if (!this.lastAdviceTimestamp) {
                this.lastAdviceTimestamp = null;
            }
            
            if (!this.modalViewingPlayer) {
                this.modalViewingPlayer = null;
            }
            
            if (!this.modalIsClubView) {
                this.modalIsClubView = false;
            }
            
            if (!this.playerCardLoading) {
                this.playerCardLoading = false;
            }
            
            if (!this.playerCardLoadingMessage) {
                this.playerCardLoadingMessage = '';
            }
            
            // Team Management Module variables (not available for club trainers)
            if (!this.managedTeams) {
                this.managedTeams = [];
            }
            
            if (!this.selectedManagedTeam) {
                this.selectedManagedTeam = null;
            }
            
            if (!this.teamTrainers) {
                this.teamTrainers = [];
            }
            
            if (!this.availableTrainers) {
                this.availableTrainers = [];
            }
            
            // Trainer Module variables
            if (!this.pendingInvitations) {
                this.pendingInvitations = [];
            }
            
            if (!this.activeTrainers) {
                this.activeTrainers = [];
            }
            
            // Modal states
            if (!this.showCreateClubTeamModal) {
                this.showCreateClubTeamModal = false;
            }
            
            if (!this.showEditTeamModal) {
                this.showEditTeamModal = false;
            }
            
            if (!this.showAssignTrainerModal) {
                this.showAssignTrainerModal = false;
            }
            
            if (!this.showInviteTrainerModal) {
                this.showInviteTrainerModal = false;
            }
            
            if (!this.showEditTrainerModal) {
                this.showEditTrainerModal = false;
            }
            
            // Form data objects
            if (!this.newClubTeam) {
                this.newClubTeam = {
                    name: '',
                    coach: '',
                    trainers: []
                };
            }
            
            if (!this.editingTeam) {
                this.editingTeam = null;
            }
            
            if (!this.editTeamData) {
                this.editTeamData = {
                    name: '',
                    coach: ''
                };
            }
            
            if (!this.trainerAssignment) {
                this.trainerAssignment = {
                    teamId: null,
                    trainerId: null
                };
            }
            
            if (!this.newTrainerInvite) {
                this.newTrainerInvite = {
                    email: '',
                    selectedTeams: [],
                    role: 'trainer',
                    message: ''
                };
            }
            
            if (!this.editingTrainer) {
                this.editingTrainer = null;
            }
            
            if (!this.editTrainerData) {
                this.editTrainerData = {
                    selectedTeams: [],
                    role: 'trainer'
                };
            }
            
            // Import/Export Module variables
            if (!this.showImportExportModal) {
                this.showImportExportModal = false;
            }
            
            if (!this.importExportMode) {
                this.importExportMode = 'import';
            }
            
            if (!this.importWizardStep) {
                this.importWizardStep = 1;
            }
            
            if (!this.importType) {
                this.importType = '';
            }
            
            if (!this.importFile) {
                this.importFile = null;
            }
            
            if (!this.importFileData) {
                this.importFileData = null;
            }
            
            if (!this.importTempKey) {
                this.importTempKey = null;
            }
            
            if (!this.importMapping) {
                this.importMapping = {};
            }
            
            if (!this.importPreviewData) {
                this.importPreviewData = [];
            }
            
            if (!this.importOptions) {
                this.importOptions = {
                    duplicateHandling: 'skip',
                    sendInvitations: true,
                    validateEmails: true,
                    dateFormat: 'DD-MM-YYYY'
                };
            }
            
            if (!this.importProgress) {
                this.importProgress = {
                    total: 0,
                    processed: 0,
                    successful: 0,
                    failed: 0,
                    errors: [],
                    isProcessing: false,
                    isPaused: false,
                    sessionId: null
                };
            }
            
            if (!this.importResults) {
                this.importResults = {
                    created: 0,
                    updated: 0,
                    skipped: 0,
                    failed: 0,
                    errors: []
                };
            }
            
            if (!this.exportType) {
                this.exportType = 'teams';
            }
            
            if (!this.exportFilters) {
                this.exportFilters = {
                    season: '',
                    teamIds: [],
                    includeEvaluations: false
                };
            }
            
            if (!this.exportFormat) {
                this.exportFormat = 'csv';
            }
            
            if (!this.fieldMappings) {
                this.fieldMappings = {
                    'name': ['name', 'team_name', 'teamname', 'naam'],
                    'coach': ['coach', 'coach_name', 'trainer', 'head_coach'],
                    'season': ['season', 'year', 'seizoen'],
                    'first_name': ['first_name', 'firstname', 'voornaam', 'fname'],
                    'last_name': ['last_name', 'lastname', 'achternaam', 'lname'],
                    'email': ['email', 'email_address', 'e-mail', 'emailadres'],
                    'birth_date': ['birth_date', 'birthdate', 'date_of_birth', 'dob', 'geboortedatum'],
                    'position': ['position', 'pos', 'positie'],
                    'jersey_number': ['jersey_number', 'jersey', 'number', 'shirt_number', 'rugnummer'],
                    'team_name': ['team_name', 'team', 'teamname', 'ploeg'],
                    'team_names': ['team_names', 'teams', 'assigned_teams', 'ploegen']
                };
            }
            
            if (!this.availableFields) {
                this.availableFields = {
                    teams: [
                        { key: 'name', label: 'Team Name', required: true },
                        { key: 'coach', label: 'Coach', required: true },
                        { key: 'season', label: 'Season', required: true }
                    ],
                    players: [
                        { key: 'first_name', label: 'First Name', required: true },
                        { key: 'last_name', label: 'Last Name', required: true },
                        { key: 'email', label: 'Email', required: true },
                        { key: 'birth_date', label: 'Birth Date', required: true },
                        { key: 'position', label: 'Position', required: false },
                        { key: 'jersey_number', label: 'Jersey Number', required: false },
                        { key: 'team_name', label: 'Team Name', required: false }
                    ],
                    trainers: [
                        { key: 'email', label: 'Email', required: true },
                        { key: 'team_names', label: 'Team Names (semicolon separated)', required: false }
                    ]
                };
            }
            
            if (!this.importTemplates) {
                this.importTemplates = {
                    teams: 'name,coach,season\nHockey Team Alpha,John Doe,2024-2025\nHockey Team Beta,Jane Smith,2024-2025\nHockey Team Gamma,Bob Wilson,2024-2025',
                    players: 'first_name,last_name,email,birth_date,position,jersey_number,team_name\nJohn,Doe,john.doe@email.com,15-03-2005,Forward,10,Hockey Team Alpha\nJane,Smith,jane.smith@email.com,22-07-2006,Defense,5,Hockey Team Alpha\nBob,Johnson,bob.j@email.com,01-01-2005,Goalkeeper,1,Hockey Team Beta\nAlice,Wilson,alice.w@email.com,30-09-2005,Midfield,8,Hockey Team Beta',
                    trainers: 'email,team_names\ntrainer1@club.com,Hockey Team Alpha\ntrainer2@club.com,Hockey Team Alpha;Hockey Team Beta\nheadcoach@club.com,Hockey Team Gamma'
                };
            }
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