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
    
    async createTeam() {
        if (!this.canCreateTeam()) {
            alert('You do not have permission to create teams');
            return;
        }
        
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
        }
    }
    
    async selectTeam(team) {
        // Reset club team selection when selecting a personal team
        this.app.selectedClubTeam = null;
        this.app.isViewingClubTeam = false;
        this.app.viewingClubPlayer = null;
        this.app.selectedClubPlayerCard = null;
        
        // Set personal team selection
        this.app.selectedTeam = team;
        this.app.viewingPlayer = null;
        this.app.selectedPlayerCard = null;
        
        // Show team details modal
        this.app.showTeamDetailsModal = true;
        
        await this.loadTeamPlayers();
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
        // Optionally reset selection
        // this.app.selectedTeam = null;
        // this.app.teamPlayers = [];
    }
    
    resetSelections() {
        this.app.selectedTeam = null;
        this.app.teamPlayers = [];
        this.app.viewingPlayer = null;
        this.app.selectedPlayerCard = null;
        this.app.showTeamDetailsModal = false;
    }
}