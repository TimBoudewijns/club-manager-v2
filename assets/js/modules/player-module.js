// Player Module - Handles player management functionality
class PlayerModule {
    constructor(app) {
        this.app = app;
        this.initializeData();
    }
    
    initializeData() {
        Object.assign(this.app, {
            // Player management data
            showAddPlayerModal: false,
            showAddExistingPlayerModal: false,
            showPlayerHistoryModal: false,
            playerSearch: '',
            searchResults: [],
            selectedExistingPlayer: null,
            playerHistory: [],
            historyPlayer: null,
            historyLoading: false,
            
            newPlayer: {
                first_name: '',
                last_name: '',
                birth_date: '',
                email: '',
                position: '',
                jersey_number: '',
                notes: ''
            },
            
            existingPlayerTeamData: {
                position: '',
                jersey_number: '',
                notes: ''
            }
        });
        
        this.bindMethods();
    }
    
    bindMethods() {
        this.app.createPlayer = this.createPlayer.bind(this);
        this.app.searchPlayers = this.searchPlayers.bind(this);
        this.app.selectExistingPlayer = this.selectExistingPlayer.bind(this);
        this.app.addExistingPlayerToTeam = this.addExistingPlayerToTeam.bind(this);
        this.app.closeAddExistingPlayerModal = this.closeAddExistingPlayerModal.bind(this);
        this.app.removePlayerFromTeam = this.removePlayerFromTeam.bind(this);
        this.app.viewPlayerHistory = this.viewPlayerHistory.bind(this);
        this.app.handleHistoryClick = this.handleHistoryClick.bind(this);
        this.app.handleRemoveClick = this.handleRemoveClick.bind(this);
        this.app.handlePlayerCardModalClick = this.handlePlayerCardModalClick.bind(this);
    }
    
    async createPlayer(event) {
        const button = event?.target?.closest('button');
        this.app.setButtonLoading(button, true, 'Add Player');
        
        try {
            const playerData = Object.assign({}, this.app.newPlayer, {
                team_id: this.app.selectedTeam.id,
                season: this.app.currentSeason
            });
            
            await this.app.apiPost('cm_create_player', playerData);
            
            this.app.showAddPlayerModal = false;
            this.app.newPlayer = {
                first_name: '',
                last_name: '',
                birth_date: '',
                email: '',
                position: '',
                jersey_number: '',
                notes: ''
            };
            
            await this.app.teamModule.loadTeamPlayers();
            
        } catch (error) {
            console.error('Error creating player');
        } finally {
            this.app.setButtonLoading(button, false, 'Add Player');
        }
    }
    
    async searchPlayers() {
        if (this.app.playerSearch.length < 2) {
            this.app.searchResults = [];
            return;
        }
        
        try {
            this.app.searchResults = await this.app.apiPost('cm_search_players', {
                search: this.app.playerSearch,
                team_id: this.app.selectedTeam.id,
                season: this.app.currentSeason
            });
        } catch (error) {
            console.error('Error searching players');
        }
    }
    
    selectExistingPlayer(player) {
        this.app.selectedExistingPlayer = player;
        this.app.searchResults = [];
        this.app.playerSearch = '';
    }
    
    async addExistingPlayerToTeam(event) {
        const button = event?.target?.closest('button');
        this.app.setButtonLoading(button, true, 'Add to Team');
        
        try {
            const data = Object.assign({}, this.app.existingPlayerTeamData, {
                team_id: this.app.selectedTeam.id,
                player_id: this.app.selectedExistingPlayer.id,
                season: this.app.currentSeason
            });
            
            await this.app.apiPost('cm_add_player_to_team', data);
            
            this.closeAddExistingPlayerModal();
            await this.app.teamModule.loadTeamPlayers();
            
        } catch (error) {
            console.error('Error adding player to team');
        } finally {
            this.app.setButtonLoading(button, false, 'Add to Team');
        }
    }
    
    closeAddExistingPlayerModal() {
        this.app.showAddExistingPlayerModal = false;
        this.app.selectedExistingPlayer = null;
        this.app.playerSearch = '';
        this.app.searchResults = [];
        this.app.existingPlayerTeamData = {
            position: '',
            jersey_number: '',
            notes: ''
        };
    }
    
    async removePlayerFromTeam(player) {
        if (!confirm('Are you sure you want to remove ' + player.first_name + ' ' + player.last_name + ' from this team?')) {
            return;
        }
        
        await this.app.withLoading(async () => {
            try {
                await this.app.apiPost('cm_remove_player_from_team', {
                    team_id: this.app.selectedTeam.id,
                    player_id: player.id,
                    season: this.app.currentSeason
                });
                
                // Hide player card if this player was being viewed
                if (this.app.viewingPlayer && this.app.viewingPlayer.id === player.id) {
                    this.app.viewingPlayer = null;
                    this.app.selectedPlayerCard = null;
                }
                
                await this.app.teamModule.loadTeamPlayers();
                
            } catch (error) {
                console.error('Error removing player from team');
            }
        }, 'Removing player...');
    }
    
    async viewPlayerHistory(playerId, isClubView = false) {
        const players = isClubView ? this.app.clubTeamPlayers : this.app.teamPlayers;
        const player = players.find(p => p.id == playerId);
        if (!player) return;
        
        this.app.showPlayerHistoryModal = true;
        this.app.historyLoading = true;
        this.app.playerHistory = [];
        
        // Set historyPlayer with isClubView flag for dynamic styling
        this.app.historyPlayer = {
            ...player,
            isClubView: isClubView
        };
        
        try {
            const data = await this.app.apiPost('cm_get_player_history', {
                player_id: playerId
            });
            
            this.app.playerHistory = data.history;
            // Update historyPlayer with complete data but keep isClubView flag
            this.app.historyPlayer = {
                ...data.player,
                isClubView: isClubView
            };
            
        } catch (error) {
            console.error('Error loading player history');
        } finally {
            this.app.historyLoading = false;
        }
    }
    
    handleHistoryClick(playerId, isClubView = false) {
        this.viewPlayerHistory(playerId, isClubView);
    }
    
    handleRemoveClick(playerId) {
        const player = this.app.teamPlayers.find(p => p.id == playerId);
        if (player) {
            this.removePlayerFromTeam(player);
        }
    }
    
    handlePlayerCardModalClick(playerId, isClubView = false) {
        const player = this.app.teamPlayers.find(p => p.id == playerId);
        if (player && this.app.viewPlayerCardInModal) {
            this.app.viewPlayerCardInModal(playerId, isClubView);
        }
    }
}