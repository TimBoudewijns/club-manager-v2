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
            availableTrainersLoading: false,
            
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
        this.app.openAssignTrainerModal = this.openAssignTrainerModal.bind(this);
    }
    
    async loadManagedTeams() {
        try {
            console.log('Loading managed teams...');
            // Load all teams created by club members
            this.app.managedTeams = await this.app.apiPost('cm_get_all_club_teams', {
                season: this.app.currentSeason
            });
            console.log('Managed teams loaded:', this.app.managedTeams);
            
            // Also load available trainers when loading teams
            await this.loadAvailableTrainers();
        } catch (error) {
            console.error('Error loading managed teams:', error);
            this.app.managedTeams = [];
        }
    }
    
    async loadAvailableTrainers() {
        try {
            console.log('Loading available trainers...');
            this.app.availableTrainersLoading = true;
            
            const response = await this.app.apiPost('cm_get_available_trainers', {
                season: this.app.currentSeason
            });
            
            console.log('Available trainers response:', response);
            
            // Ensure we have an array
            if (Array.isArray(response)) {
                this.app.availableTrainers = response;
            } else if (response && Array.isArray(response.data)) {
                this.app.availableTrainers = response.data;
            } else {
                console.warn('Unexpected response format for available trainers:', response);
                this.app.availableTrainers = [];
            }
            
            console.log('Available trainers set to:', this.app.availableTrainers);
            
        } catch (error) {
            console.error('Error loading available trainers:', error);
            this.app.availableTrainers = [];
        } finally {
            this.app.availableTrainersLoading = false;
        }
    }
    
    async createClubTeam(event) {
        const button = event?.target?.closest('button');
        this.app.setButtonLoading(button, true, 'Create Team');
        
        await this.app.withLoading(async () => {
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
            } finally {
                this.app.setButtonLoading(button, false, 'Create Team');
            }
        }, 'Creating club team...');
    }
    
    async selectManagedTeam(team) {
        await this.app.withLoading(async () => {
            this.app.selectedManagedTeam = team;
            await this.loadTeamTrainers(team.id);
        }, `Loading ${team.name} details...`);
    }
    
    async loadTeamTrainers(teamId) {
        try {
            this.app.teamTrainers = await this.app.apiPost('cm_get_team_trainers', {
                team_id: teamId
            });
        } catch (error) {
            console.error('Error loading team trainers:', error);
            this.app.teamTrainers = [];
        }
    }
    
    // Update the openAssignTrainerModal method
    async openAssignTrainerModal(team) {
        console.log('Opening assign trainer modal for team:', team);
        
        // Set the selected team
        this.app.selectedManagedTeam = team;
        
        // Load team trainers FIRST
        await this.loadTeamTrainers(team.id);
        
        // Force reload available trainers to ensure we have fresh data
        await this.loadAvailableTrainers();
        
        // Wait a tick to ensure data is propagated
        await this.app.$nextTick();
        
        // Reset assignment data
        this.app.trainerAssignment = {
            teamId: team.id,
            trainerId: null
        };
        
        console.log('Available trainers before showing modal:', this.app.availableTrainers);
        console.log('Team trainers:', this.app.teamTrainers);
        
        // Show the modal
        this.app.showAssignTrainerModal = true;
    }
    
    async assignTrainerToTeam(event) {
        const button = event?.target?.closest('button');
        this.app.setButtonLoading(button, true, 'Assign Trainer');
        
        await this.app.withLoading(async () => {
            try {
                // Ensure we have the team ID
                if (!this.app.trainerAssignment.teamId && this.app.selectedManagedTeam) {
                    this.app.trainerAssignment.teamId = this.app.selectedManagedTeam.id;
                }
                
                if (!this.app.trainerAssignment.teamId || !this.app.trainerAssignment.trainerId) {
                    throw new Error('Please select a trainer');
                }
                
                await this.app.apiPost('cm_assign_trainer_to_team', {
                    team_id: this.app.trainerAssignment.teamId,
                    trainer_id: this.app.trainerAssignment.trainerId
                });
                
                this.app.showAssignTrainerModal = false;
                this.app.trainerAssignment = {
                    teamId: null,
                    trainerId: null
                };
                
                // Reload the team trainers
                if (this.app.selectedManagedTeam) {
                    await this.loadTeamTrainers(this.app.selectedManagedTeam.id);
                }
                
                // Reload available trainers to update the list
                await this.loadAvailableTrainers();
                
                // BELANGRIJK: Reload de managed teams om de trainer count te updaten
                await this.loadManagedTeams();
                
                alert('Trainer assigned successfully!');
                
            } catch (error) {
                alert('Error assigning trainer: ' + (error.message || 'Unknown error'));
            } finally {
                this.app.setButtonLoading(button, false, 'Assign Trainer');
            }
        }, 'Assigning trainer...');
    }
    
    async removeTrainerFromTeam(teamId, trainerId) {
        if (!confirm('Are you sure you want to remove this trainer from the team?')) {
            return;
        }
        
        await this.app.withLoading(async () => {
            try {
                await this.app.apiPost('cm_remove_trainer_from_team', {
                    team_id: teamId,
                    trainer_id: trainerId
                });
                
                if (this.app.selectedManagedTeam) {
                    await this.loadTeamTrainers(this.app.selectedManagedTeam.id);
                }
                
                // Reload available trainers
                await this.loadAvailableTrainers();
                
            } catch (error) {
                alert('Error removing trainer: ' + (error.message || 'Unknown error'));
            }
        }, 'Removing trainer...');
    }
    
    async editManagedTeam(team) {
        this.app.editingTeam = team;
        this.app.editTeamData = {
            name: team.name,
            coach: team.coach
        };
        // Use nextTick to ensure DOM is updated
        this.app.$nextTick(() => {
            this.app.showEditTeamModal = true;
        });
    }
    
    async updateManagedTeam(event) {
        if (!this.app.editingTeam || !this.app.editTeamData.name || !this.app.editTeamData.coach) {
            alert('Please fill in all fields');
            return;
        }
        
        const button = event?.target?.closest('button');
        this.app.setButtonLoading(button, true, 'Update Team');
        
        await this.app.withLoading(async () => {
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
            } finally {
                this.app.setButtonLoading(button, false, 'Update Team');
            }
        }, 'Updating team...');
    }
    
    async deleteManagedTeam(teamId) {
        if (!confirm('Are you sure you want to delete this team? This action cannot be undone.')) {
            return;
        }
        
        await this.app.withLoading(async () => {
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
        }, 'Deleting team...');
    }
    
    resetSelections() {
        this.app.selectedManagedTeam = null;
        this.app.teamTrainers = [];
        this.app.availableTrainers = [];
        this.app.availableTrainersLoading = false;
    }
}