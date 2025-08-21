// Trainer Module - Handles trainer management functionality
class TrainerModule {
    constructor(app) {
        this.app = app;
        this.initializeData();
    }
    
    initializeData() {
        Object.assign(this.app, {
            // Trainer management data
            showInviteTrainerModal: false,
            showEditTrainerModal: false,
            pendingInvitations: [],
            activeTrainers: [],
            managedTeams: [],
            trainerLimit: window.clubManagerAjax?.trainer_limit || null,
            newTrainerInvite: {
                email: '',
                selectedTeams: [],
                role: 'trainer',
                message: ''
            },
            editingTrainer: null,
            editTrainerData: {
                selectedTeams: [],
                role: 'trainer'
            }
        });
        
        this.bindMethods();
    }
    
    bindMethods() {
        this.app.loadTrainerManagementData = this.loadTrainerManagementData.bind(this);
        this.app.toggleTeamSelection = this.toggleTeamSelection.bind(this);
        this.app.toggleEditTeamSelection = this.toggleEditTeamSelection.bind(this);
        this.app.canInviteMoreTrainers = this.canInviteMoreTrainers.bind(this);
        this.app.checkTrainerLimit = this.checkTrainerLimit.bind(this);
        this.app.inviteTrainer = this.inviteTrainer.bind(this);
        this.app.cancelInvitation = this.cancelInvitation.bind(this);
        this.app.editTrainer = this.editTrainer.bind(this);
        this.app.updateTrainer = this.updateTrainer.bind(this);
        this.app.removeTrainer = this.removeTrainer.bind(this);
    }
    
    async loadTrainerManagementData() {
        try {
            // Load managed teams for the invitation form
            const teamsData = await this.app.apiPost('cm_get_managed_teams', {
                season: this.app.currentSeason
            });
            this.app.managedTeams = teamsData || [];
            
            // Debug logging
            console.log('Club Manager Debug - loadTrainerManagementData:');
            console.log('Season:', this.app.currentSeason);
            console.log('Managed teams loaded:', this.app.managedTeams);
            
            // Load pending invitations
            const invitationsData = await this.app.apiPost('cm_get_pending_invitations');
            this.app.pendingInvitations = invitationsData || [];
            
            // Load active trainers
            const trainersData = await this.app.apiPost('cm_get_active_trainers');
            this.app.activeTrainers = trainersData || [];
            
        } catch (error) {
            console.error('Error loading trainer management data:', error);
            // Initialize with empty arrays on error
            this.app.managedTeams = [];
            this.app.pendingInvitations = [];
            this.app.activeTrainers = [];
        }
    }
    
    toggleTeamSelection(teamId) {
        // Ensure teamId is an integer
        teamId = parseInt(teamId);
        console.log('Club Manager Debug - toggleTeamSelection called with teamId:', teamId, 'type:', typeof teamId);
        
        if (!this.app.newTrainerInvite.selectedTeams) {
            this.app.newTrainerInvite.selectedTeams = [];
        }
        
        console.log('Current selectedTeams before toggle:', this.app.newTrainerInvite.selectedTeams);
        
        const index = this.app.newTrainerInvite.selectedTeams.indexOf(teamId);
        if (index > -1) {
            this.app.newTrainerInvite.selectedTeams.splice(index, 1);
            console.log('Removed teamId from selection');
        } else {
            this.app.newTrainerInvite.selectedTeams.push(teamId);
            console.log('Added teamId to selection');
        }
        
        console.log('Current selectedTeams after toggle:', this.app.newTrainerInvite.selectedTeams);
    }
    
    toggleEditTeamSelection(teamId) {
        if (!this.app.editTrainerData.selectedTeams) {
            this.app.editTrainerData.selectedTeams = [];
        }
        const index = this.app.editTrainerData.selectedTeams.indexOf(teamId);
        if (index > -1) {
            this.app.editTrainerData.selectedTeams.splice(index, 1);
        } else {
            this.app.editTrainerData.selectedTeams.push(teamId);
        }
    }
    
    // Check trainer limit
    canInviteMoreTrainers() {
        if (!this.app.trainerLimit || this.app.trainerLimit === 999) {
            return true; // No limit set or unlimited
        }
        return this.app.activeTrainers.length < this.app.trainerLimit;
    }
    
    checkTrainerLimit() {
        if (!this.canInviteMoreTrainers()) {
            console.error(`You have reached your trainer limit of ${this.app.trainerLimit}. Please upgrade your membership to invite more trainers.`);
            return false;
        }
        return true;
    }
    
    async inviteTrainer(event) {
        // Teams are optional - trainer can be invited to club without specific team assignment
        // Teams can be assigned later by the club administrator
        
        const button = event?.target?.closest('button');
        this.app.setButtonLoading(button, true, 'Send Invitation');
        
        await this.app.withLoading(async () => {
            try {
                // Debug logging
                console.log('Club Manager Debug - Sending invite data:', {
                    email: this.app.newTrainerInvite.email,
                    teams: this.app.newTrainerInvite.selectedTeams,
                    role: this.app.newTrainerInvite.role,
                    message: this.app.newTrainerInvite.message
                });
                console.log('All managed teams:', this.app.managedTeams);
                
                await this.app.apiPost('cm_invite_trainer', {
                    email: this.app.newTrainerInvite.email,
                    teams: this.app.newTrainerInvite.selectedTeams,
                    role: this.app.newTrainerInvite.role,
                    message: this.app.newTrainerInvite.message
                });
                
                this.app.showInviteTrainerModal = false;
                this.app.newTrainerInvite = {
                    email: '',
                    selectedTeams: [],
                    role: 'trainer',
                    message: ''
                };
                
                await this.loadTrainerManagementData();
                console.log('Invitation sent successfully!');
                
            } catch (error) {
                console.error('Error sending invitation: ', error.message || 'Unknown error');
            } finally {
                this.app.setButtonLoading(button, false, 'Send Invitation');
            }
        }, `Sending invitation to ${this.app.newTrainerInvite.email}...`);
    }
    
    async cancelInvitation(invitationId) {
        if (!confirm('Are you sure you want to cancel this invitation?')) {
            return;
        }
        
        await this.app.withLoading(async () => {
            try {
                await this.app.apiPost('cm_cancel_invitation', {
                    invitation_id: invitationId
                });
                
                await this.loadTrainerManagementData();
                
            } catch (error) {
                console.error('Error canceling invitation');
            }
        }, 'Canceling invitation...');
    }
    
    async editTrainer(trainer) {
        this.app.editingTrainer = trainer;
        this.app.editTrainerData = {
            selectedTeams: trainer.teams ? trainer.teams.map(t => t.id) : [],
            role: trainer.role || 'trainer'
        };
        this.app.showEditTrainerModal = true;
    }
    
    async updateTrainer(event) {
        if (!this.app.editTrainerData.selectedTeams || this.app.editTrainerData.selectedTeams.length === 0) {
            console.error('Please select at least one team for the trainer');
            return;
        }
        
        const button = event?.target?.closest('button');
        this.app.setButtonLoading(button, true, 'Update Trainer');
        
        await this.app.withLoading(async () => {
            try {
                await this.app.apiPost('cm_update_trainer', {
                    trainer_id: this.app.editingTrainer.id,
                    teams: this.app.editTrainerData.selectedTeams,
                    role: this.app.editTrainerData.role
                });
                
                this.app.showEditTrainerModal = false;
                this.app.editingTrainer = null;
                this.app.editTrainerData = {
                    selectedTeams: [],
                    role: 'trainer'
                };
                
                await this.loadTrainerManagementData();
                console.log('Trainer updated successfully!');
                
            } catch (error) {
                console.error('Error updating trainer: ', error.message || 'Unknown error');
            } finally {
                this.app.setButtonLoading(button, false, 'Update Trainer');
            }
        }, 'Updating trainer...');
    }
    
    async removeTrainer(trainer) {
        if (!confirm(`Are you sure you want to remove ${trainer.display_name} as a trainer?`)) {
            return;
        }
        
        await this.app.withLoading(async () => {
            try {
                await this.app.apiPost('cm_remove_trainer', {
                    trainer_id: trainer.id
                });
                
                await this.loadTrainerManagementData();
                
            } catch (error) {
                console.error('Error removing trainer');
            }
        }, `Removing ${trainer.display_name}...`);
    }
}