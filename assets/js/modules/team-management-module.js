// Team Management Module - For owners/managers to manage club teams
class TeamManagementModule {
    constructor(app) {
        this.app = app;
        this.initializeData();
    }
    
    initializeData() {
        Object.assign(this.app, {
            // Team management data
            managedTeams: [],
            selectedManagedTeam: null,
            teamTrainers: [],
            availableTrainers: [],
            
            // Create team for club
            showCreateClubTeamModal: false,
            newClubTeam: {
                name: '',
                coach: '',
                trainers: []
            },
            
            // Edit team modal
            showEditTeamModal: false,
            editingTeam: null,
            editTeamData: {
                name: '',
                coach: ''
            },
            
            // Assign trainer modal
            showAssignTrainerModal: false,
            trainerAssignment: {
                teamId: null,
                trainerId: null
            }
        });
        
        this.bindMethods();
    }
    
    bindMethods() {
        this.app.loadManagedTeams = this.loadManagedTeams.bind(this);
        this.app.createClubTeam = this.createClubTeam.bind(this);
        this.app.selectManagedTeam = this.selectManagedTeam.bind(this);
        this.app.assignTrainerToTeam = this.assignTrainerToTeam.bind(this);
        this.app.removeTrainerFromTeam = this.removeTrainerFromTeam.bind(this);
        this.app.loadAvailableTrainers = this.loadAvailableTrainers.bind(this);
        this.app.editManagedTeam = this.editManagedTeam.bind(this);
        this.app.updateManagedTeam = this.updateManagedTeam.bind(this);
        this.app.deleteManagedTeam = this.deleteManagedTeam.bind(this);
    }
    
    async loadManagedTeams() {
        try {
            // Load all teams created by club members
            this.app.managedTeams = await this.app.apiPost('cm_get_all_club_teams', {
                season: this.app.currentSeason
            });
            
            // Also load available trainers
            await this.loadAvailableTrainers();
        } catch (error) {
            console.error('Error loading managed teams:', error);
        }
    }
    
    async loadAvailableTrainers() {
        try {
            this.app.availableTrainers = await this.app.apiPost('cm_get_available_trainers', {
                season: this.app.currentSeason
            });
        } catch (error) {
            console.error('Error loading available trainers:', error);
        }
    }
    
    async createClubTeam() {
        try {
            const result = await this.app.apiPost('cm_create_club_team', {
                name: this.app.newClubTeam.name,
                coach: this.app.newClubTeam.coach,
                season: this.app.currentSeason,
                trainers: this.app.newClubTeam.trainers
            });
            
            this.app.showCreateClubTeamModal = false;
            this.app.newClubTeam = {
                name: '',
                coach: '',
                trainers: []
            };
            
            await this.loadManagedTeams();
            alert('Team created successfully!');
            
        } catch (error) {
            alert('Error creating team: ' + (error.message || 'Unknown error'));
        }
    }
    
    async selectManagedTeam(team) {
        this.app.selectedManagedTeam = team;
        await this.loadTeamTrainers(team.id);
    }
    
    async loadTeamTrainers(teamId) {
        try {
            this.app.teamTrainers = await this.app.apiPost('cm_get_team_trainers', {
                team_id: teamId
            });
        } catch (error) {
            console.error('Error loading team trainers:', error);
        }
    }
    
    async assignTrainerToTeam() {
        try {
            await this.app.apiPost('cm_assign_trainer_to_team', {
                team_id: this.app.trainerAssignment.teamId,
                trainer_id: this.app.trainerAssignment.trainerId
            });
            
            this.app.showAssignTrainerModal = false;
            this.app.trainerAssignment = {
                teamId: null,
                trainerId: null
            };
            
            if (this.app.selectedManagedTeam) {
                await this.loadTeamTrainers(this.app.selectedManagedTeam.id);
            }
            
            alert('Trainer assigned successfully!');
            
        } catch (error) {
            alert('Error assigning trainer: ' + (error.message || 'Unknown error'));
        }
    }
    
    async removeTrainerFromTeam(teamId, trainerId) {
        if (!confirm('Are you sure you want to remove this trainer from the team?')) {
            return;
        }
        
        try {
            await this.app.apiPost('cm_remove_trainer_from_team', {
                team_id: teamId,
                trainer_id: trainerId
            });
            
            if (this.app.selectedManagedTeam) {
                await this.loadTeamTrainers(this.app.selectedManagedTeam.id);
            }
            
        } catch (error) {
            alert('Error removing trainer: ' + (error.message || 'Unknown error'));
        }
    }
    
    async editManagedTeam(team) {
        this.app.editingTeam = team;
        this.app.editTeamData = {
            name: team.name,
            coach: team.coach
        };
        this.app.showEditTeamModal = true;
    }
    
    async updateManagedTeam() {
        if (!this.app.editingTeam || !this.app.editTeamData.name || !this.app.editTeamData.coach) {
            alert('Please fill in all fields');
            return;
        }
        
        try {
            await this.app.apiPost('cm_update_team', {
                team_id: this.app.editingTeam.id,
                name: this.app.editTeamData.name,
                coach: this.app.editTeamData.coach
            });
            
            this.app.showEditTeamModal = false;
            this.app.editingTeam = null;
            this.app.editTeamData = {
                name: '',
                coach: ''
            };
            
            await this.loadManagedTeams();
            alert('Team updated successfully!');
            
        } catch (error) {
            alert('Error updating team: ' + (error.message || 'Unknown error'));
        }
    }
    
    async deleteManagedTeam(teamId) {
        if (!confirm('Are you sure you want to delete this team? This action cannot be undone.')) {
            return;
        }
        
        try {
            await this.app.apiPost('cm_delete_team', {
                team_id: teamId
            });
            
            await this.loadManagedTeams();
            
            if (this.app.selectedManagedTeam && this.app.selectedManagedTeam.id === teamId) {
                this.app.selectedManagedTeam = null;
                this.app.teamTrainers = [];
            }
            
        } catch (error) {
            alert('Error deleting team: ' + (error.message || 'Unknown error'));
        }
    }
    
    resetSelections() {
        this.app.selectedManagedTeam = null;
        this.app.teamTrainers = [];
    }
}