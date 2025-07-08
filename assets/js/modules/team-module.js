// Team Module - Handles My Teams functionality
class TeamModule {
    constructor(app) {
        this.app = app;
        this.initializeData();
    }
    
    initializeData() {
        Object.assign(this.app, {
            // My Teams data
            myTeams: [],
            selectedTeam: null,
            teamPlayers: [],
            viewingPlayer: null,
            selectedPlayerCard: null,
            
            // Create team modal
            showCreateTeamModal: false,
            newTeam: {
                name: '',
                coach: ''
            },
            
            // Team details modal
            showTeamDetailsModal: false
        });
        
        // Bind methods to app
        this.bindMethods();
    }
    
    bindMethods() {
        this.app.loadMyTeams = this.loadMyTeams.bind(this);
        this.app.createTeam = this.createTeam.bind(this);
        this.app.selectTeam = this.selectTeam.bind(this);
        this.app.loadTeamPlayers = this.loadTeamPlayers.bind(this);
        this.app.canCreateTeam = this.canCreateTeam.bind(this);
        this.app.closeTeamDetailsModal = this.closeTeamDetailsModal.bind(this);
    }
    
    async loadMyTeams() {
        try {
            this.app.myTeams = await this.app.apiPost('cm_get_my_teams', {
                season: this.app.currentSeason
            });
        } catch (error) {
            console.error('Error loading teams:', error);
            alert('Error loading teams');
        }
    }
    
    canCreateTeam() {
        // Check if user can create teams
        return this.app.userPermissions.can_create_teams === true;
    }
    
    async createTeam(event) {
        if (!this.canCreateTeam()) {
            alert('You do not have permission to create teams');
            return;
        }
        
        const button = event?.target?.closest('button');
        this.app.setButtonLoading(button, true, 'Create Team');
        
        try {
            await this.app.apiPost('cm_create_team', {
                name: this.app.newTeam.name,
                coach: this.app.newTeam.coach,
                season: this.app.currentSeason
            });
            
            this.app.showCreateTeamModal = false;
            this.app.newTeam = { name: '', coach: '' };
            await this.loadMyTeams();
            
        } catch (error) {
            alert('Error creating team: ' + (error.message || 'Unknown error'));
        } finally {
            this.app.setButtonLoading(button, false, 'Create Team');
        }
    }
    
    async selectTeam(team) {
        await this.app.withLoading(async () => {
            // Reset club team selection when selecting a personal team
            this.app.selectedClubTeam = null;
            this.app.isViewingClubTeam = false;
            this.app.viewingClubPlayer = null;
            this.app.selectedClubPlayerCard = null;
            
            // Set personal team selection
            this.app.selectedTeam = team;
            this.app.viewingPlayer = null;
            this.app.selectedPlayerCard = null;
            
            // Load team players
            await this.loadTeamPlayers();
            
            // Show team details modal after loading players
            // Use nextTick to ensure DOM is updated
            this.app.$nextTick(() => {
                this.app.showTeamDetailsModal = true;
            });
        }, `Loading ${team.name}...`);
    }
    
    async loadTeamPlayers() {
        if (!this.app.selectedTeam) return;
        
        try {
            this.app.teamPlayers = await this.app.apiPost('cm_get_team_players', {
                team_id: this.app.selectedTeam.id,
                season: this.app.currentSeason
            });
        } catch (error) {
            alert('Error loading players');
        }
    }
    
    closeTeamDetailsModal() {
        this.app.showTeamDetailsModal = false;
        
        // Als we een club team bekijken, reset dan niet de selectie
        if (!this.app.isViewingClubTeam) {
            // Optionally reset selection for my teams
            // this.app.selectedTeam = null;
            // this.app.teamPlayers = [];
        }
    }
    
    resetSelections() {
        this.app.selectedTeam = null;
        this.app.teamPlayers = [];
        this.app.viewingPlayer = null;
        this.app.selectedPlayerCard = null;
        this.app.showTeamDetailsModal = false;
    }
}