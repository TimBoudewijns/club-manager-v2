// Teams module
import { API } from '../utils/api.js';

export class TeamsModule {
    constructor(app) {
        this.app = app;
    }
    
    init() {
        // Initialize team-specific data
        Object.assign(this.app, {
            showCreateTeamModal: false,
            newTeam: {
                name: '',
                coach: ''
            }
        });
    }
    
    async loadTeams() {
        try {
            this.app.teams = await API.post('cm_get_teams', {
                season: this.app.currentSeason
            });
        } catch (error) {
            API.handleError(error);
        }
    }
    
    async createTeam() {
        try {
            const result = await API.post('cm_create_team', {
                name: this.app.newTeam.name,
                coach: this.app.newTeam.coach,
                season: this.app.currentSeason
            });
            
            this.app.showCreateTeamModal = false;
            this.app.newTeam = { name: '', coach: '' };
            await this.loadTeams();
            
        } catch (error) {
            API.handleError(error);
        }
    }
} 
