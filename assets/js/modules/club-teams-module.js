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
            isViewingClubTeam: false,
            showClubTeamDetailsModal: false
        });
        
        this.bindMethods();
    }
    
    bindMethods() {
        this.app.loadClubTeams = this.loadClubTeams.bind(this);
        this.app.selectClubTeam = this.selectClubTeam.bind(this);
        this.app.loadClubTeamPlayers = this.loadClubTeamPlayers.bind(this);
        this.app.viewClubPlayerCard = this.viewClubPlayerCard.bind(this);
        this.app.closeClubTeamDetailsModal = this.closeClubTeamDetailsModal.bind(this);
        this.app.viewClubPlayerCardInModal = this.viewClubPlayerCardInModal.bind(this);
        this.app.handleClubPlayerCardClick = this.handleClubPlayerCardClick.bind(this);
        this.app.handleClubHistoryClick = this.handleClubHistoryClick.bind(this);
    }
    
    async loadClubTeams() {
        try {
            this.app.clubTeams = await this.app.apiPost('cm_get_club_teams', {
                season: this.app.currentSeason
            });
            console.log('Club teams loaded:', this.app.clubTeams);
        } catch (error) {
            console.error('Error loading club teams:', error);
        }
    }
    
    async selectClubTeam(team) {
        console.log('Selecting club team:', team);
        
        // Reset personal team selection when selecting a club team
        this.app.selectedTeam = null;
        this.app.viewingPlayer = null;
        this.app.selectedPlayerCard = null;
        
        // Set club team selection
        this.app.selectedClubTeam = team;
        this.app.viewingClubPlayer = null;
        this.app.selectedClubPlayerCard = null;
        this.app.isViewingClubTeam = true;
        
        // Load team players
        await this.loadClubTeamPlayers();
        
        // Show team details modal - make sure this is set to true
        this.app.showClubTeamDetailsModal = true;
        
        // Force Alpine to update
        this.app.$nextTick(() => {
            console.log('Modal should be visible now:', this.app.showClubTeamDetailsModal);
            console.log('Selected club team:', this.app.selectedClubTeam);
            console.log('Club team players:', this.app.clubTeamPlayers);
        });
    }
    
    async loadClubTeamPlayers() {
        if (!this.app.selectedClubTeam) return;
        
        try {
            console.log('Loading players for club team:', this.app.selectedClubTeam.id);
            this.app.clubTeamPlayers = await this.app.apiPost('cm_get_club_team_players', {
                team_id: this.app.selectedClubTeam.id,
                season: this.app.currentSeason
            });
            console.log('Club team players loaded:', this.app.clubTeamPlayers);
        } catch (error) {
            console.error('Error loading club team players:', error);
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
        const player = this.app.clubTeamPlayers.find(p => p.id == playerId);
        if (!player) {
            console.error('Player not found:', playerId);
            return;
        }
        
        // Use player card module to show in modal
        if (this.app.playerCardModule) {
            await this.app.playerCardModule.viewPlayerCardInModal(playerId, true);
        }
    }
    
    handleClubPlayerCardClick(playerId) {
        console.log('Handle club player card click:', playerId);
        this.viewClubPlayerCardInModal(playerId);
    }
    
    handleClubHistoryClick(playerId) {
        console.log('Handle club history click:', playerId);
        if (this.app.playerModule) {
            this.app.playerModule.viewPlayerHistory(playerId, true);
        }
    }
    
    closeClubTeamDetailsModal() {
        console.log('Closing club team details modal');
        this.app.showClubTeamDetailsModal = false;
        // Don't reset selection here if you want to keep it
        // this.app.selectedClubTeam = null;
        // this.app.clubTeamPlayers = [];
    }
    
    resetSelections() {
        this.app.selectedClubTeam = null;
        this.app.clubTeamPlayers = [];
        this.app.viewingClubPlayer = null;
        this.app.selectedClubPlayerCard = null;
        this.app.isViewingClubTeam = false;
        this.app.showClubTeamDetailsModal = false;
    }
}