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
        });
        
        this.bindMethods();
    }
    
    bindMethods() {
        this.app.loadClubTeams = this.loadClubTeams.bind(this);
        this.app.selectClubTeam = this.selectClubTeam.bind(this);
        this.app.loadClubTeamPlayers = this.loadClubTeamPlayers.bind(this);
        this.app.viewClubPlayerCard = this.viewClubPlayerCard.bind(this);
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
        this.app.selectedClubTeam = team;
        this.app.viewingClubPlayer = null;
        this.app.selectedClubPlayerCard = null;
        this.app.isViewingClubTeam = true;
        await this.loadClubTeamPlayers();
    }
    
    async loadClubTeamPlayers() {
        if (!this.app.selectedClubTeam) return;
        
        try {
            this.app.clubTeamPlayers = await this.app.apiPost('cm_get_club_team_players', {
                team_id: this.app.selectedClubTeam.id,
                season: this.app.currentSeason
            });
        } catch (error) {
            console.error('Error loading club team players:', error);
        }
    }
    
    async viewClubPlayerCard(player) {
        // This is handled by PlayerCardModule
        if (this.app.playerCardModule) {
            await this.app.playerCardModule.viewPlayerCard(player, true);
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