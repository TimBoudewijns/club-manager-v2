// Club Teams Module - Handles club teams viewing functionality
class ClubTeamsModule {
    constructor(app) {
        this.app = app;
        this.initializeData();
    }
    
    initializeData() {
        Object.assign(this.app, {
            // Club teams data
            clubTeams: [],
            selectedClubTeam: null,
            clubTeamPlayers: [],
            viewingClubPlayer: null,
            selectedClubPlayerCard: null,
            isViewingClubTeam: false
            // We hergebruiken showTeamDetailsModal in plaats van een aparte showClubTeamDetailsModal
        });
        
        this.bindMethods();
    }
    
    bindMethods() {
        this.app.loadClubTeams = this.loadClubTeams.bind(this);
        this.app.selectClubTeam = this.selectClubTeam.bind(this);
        this.app.loadClubTeamPlayers = this.loadClubTeamPlayers.bind(this);
        this.app.viewClubPlayerCard = this.viewClubPlayerCard.bind(this);
        this.app.viewClubPlayerCardInModal = this.viewClubPlayerCardInModal.bind(this);
        this.app.handleClubPlayerCardClick = this.handleClubPlayerCardClick.bind(this);
        this.app.handleClubHistoryClick = this.handleClubHistoryClick.bind(this);
    }
    
    async loadClubTeams() {
        try {
            this.app.clubTeams = await this.app.apiPost('cm_get_club_teams', {
                season: this.app.currentSeason
            });
        } catch (error) {
            console.error('Error loading club teams:', error);
        }
    }
    
    async selectClubTeam(team) {
        await this.app.withLoading(async () => {
            // Reset personal team selection when selecting a club team
            this.app.selectedTeam = null;
            this.app.viewingPlayer = null;
            this.app.selectedPlayerCard = null;
            this.app.teamPlayers = []; // Clear my team players
            
            // Set club team selection
            this.app.selectedClubTeam = team;
            this.app.viewingClubPlayer = null;
            this.app.selectedClubPlayerCard = null;
            this.app.isViewingClubTeam = true;
            
            // Load team players INTO teamPlayers array (hergebruik dezelfde array)
            await this.loadClubTeamPlayers();
            
            // Show the SAME team details modal
            this.app.$nextTick(() => {
                this.app.showTeamDetailsModal = true;
            });
        }, `Loading ${team.name}...`);
    }
    
    async loadClubTeamPlayers() {
        if (!this.app.selectedClubTeam) return;
        
        try {
            const players = await this.app.apiPost('cm_get_club_team_players', {
                team_id: this.app.selectedClubTeam.id,
                season: this.app.currentSeason
            });
            
            // Store in teamPlayers array (hergebruik dezelfde array als My Teams)
            this.app.teamPlayers = players;
            this.app.clubTeamPlayers = players; // Ook opslaan voor backwards compatibility
            
        } catch (error) {
            console.error('Error loading club team players:', error);
            this.app.teamPlayers = [];
            this.app.clubTeamPlayers = [];
        }
    }
    
    async viewClubPlayerCard(player) {
        // This is handled by PlayerCardModule
        if (this.app.playerCardModule) {
            await this.app.playerCardModule.viewPlayerCard(player, true);
        }
    }
    
    async viewClubPlayerCardInModal(playerId) {
        const player = this.app.teamPlayers.find(p => p.id == playerId);
        if (!player) return;
        
        // Use player card module to show in modal
        if (this.app.playerCardModule) {
            await this.app.playerCardModule.viewPlayerCardInModal(playerId, true);
        }
    }
    
    handleClubPlayerCardClick(playerId) {
        this.viewClubPlayerCardInModal(playerId);
    }
    
    handleClubHistoryClick(playerId) {
        if (this.app.playerModule) {
            this.app.playerModule.viewPlayerHistory(playerId, true);
        }
    }
    
    resetSelections() {
        this.app.selectedClubTeam = null;
        this.app.clubTeamPlayers = [];
        this.app.viewingClubPlayer = null;
        this.app.selectedClubPlayerCard = null;
        this.app.isViewingClubTeam = false;
    }
}