// Players module
import { API } from '../utils/api.js';

export class PlayersModule {
    constructor(app) {
        this.app = app;
    }
    
    init() {
        // Initialize player-specific data
        Object.assign(this.app, {
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
        
        // Bind methods to app context
        this.app.createPlayer = this.createPlayer.bind(this);
        this.app.searchPlayers = this.searchPlayers.bind(this);
        this.app.selectExistingPlayer = this.selectExistingPlayer.bind(this);
        this.app.addExistingPlayerToTeam = this.addExistingPlayerToTeam.bind(this);
        this.app.closeAddExistingPlayerModal = this.closeAddExistingPlayerModal.bind(this);
        this.app.removePlayerFromTeam = this.removePlayerFromTeam.bind(this);
        this.app.viewPlayerHistory = this.viewPlayerHistory.bind(this);
        this.app.handleHistoryClick = this.handleHistoryClick.bind(this);
        this.app.handleRemoveClick = this.handleRemoveClick.bind(this);
    }
    
    async loadTeamPlayers() {
        if (!this.app.selectedTeam) return;
        
        try {
            this.app.teamPlayers = await API.post('cm_get_team_players', {
                team_id: this.app.selectedTeam.id,
                season: this.app.currentSeason
            });
        } catch (error) {
            API.handleError(error);
        }
    }
    
    async createPlayer() {
        try {
            await API.post('cm_create_player', {
                ...this.app.newPlayer,
                team_id: this.app.selectedTeam.id,
                season: this.app.currentSeason
            });
            
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
            
            await this.loadTeamPlayers();
            
        } catch (error) {
            API.handleError(error);
        }
    }
    
    async searchPlayers() {
        if (this.app.playerSearch.length < 2) {
            this.app.searchResults = [];
            return;
        }
        
        try {
            this.app.searchResults = await API.post('cm_search_players', {
                search: this.app.playerSearch,
                team_id: this.app.selectedTeam.id,
                season: this.app.currentSeason
            });
        } catch (error) {
            API.handleError(error);
        }
    }
    
    selectExistingPlayer(player) {
        this.app.selectedExistingPlayer = player;
        this.app.searchResults = [];
        this.app.playerSearch = '';
    }
    
    async addExistingPlayerToTeam() {
        try {
            await API.post('cm_add_player_to_team', {
                team_id: this.app.selectedTeam.id,
                player_id: this.app.selectedExistingPlayer.id,
                ...this.app.existingPlayerTeamData,
                season: this.app.currentSeason
            });
            
            this.closeAddExistingPlayerModal();
            await this.loadTeamPlayers();
            
        } catch (error) {
            API.handleError(error);
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
        if (!confirm(`Are you sure you want to remove ${player.first_name} ${player.last_name} from this team?`)) {
            return;
        }
        
        try {
            await API.post('cm_remove_player_from_team', {
                team_id: this.app.selectedTeam.id,
                player_id: player.id,
                season: this.app.currentSeason
            });
            
            // Hide player card if this player was being viewed
            if (this.app.viewingPlayer && this.app.viewingPlayer.id === player.id) {
                this.app.viewingPlayer = null;
                this.app.selectedPlayerCard = null;
            }
            
            await this.loadTeamPlayers();
            
        } catch (error) {
            API.handleError(error);
        }
    }
    
    async viewPlayerHistory(playerId) {
        const player = this.app.teamPlayers.find(p => p.id == playerId);
        if (!player) return;
        
        this.app.showPlayerHistoryModal = true;
        this.app.historyLoading = true;
        this.app.playerHistory = [];
        this.app.historyPlayer = player;
        
        try {
            const data = await API.post('cm_get_player_history', {
                player_id: playerId
            });
            
            this.app.playerHistory = data.history;
            this.app.historyPlayer = data.player;
            
        } catch (error) {
            API.handleError(error);
        } finally {
            this.app.historyLoading = false;
        }
    }
    
    handleHistoryClick(playerId) {
        this.viewPlayerHistory(playerId);
    }
    
    handleRemoveClick(playerId) {
        const player = this.app.teamPlayers.find(p => p.id == playerId);
        if (player) {
            this.removePlayerFromTeam(player);
        }
    }
} 
