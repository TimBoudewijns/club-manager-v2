// Trainer Module - Handles trainer management functionality
export class TrainerModule {
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
        if (!this.app.newTrainerInvite.selectedTeams) {
            this.app.newTrainerInvite.selectedTeams = [];
        }
        const index = this.app.newTrainerInvite.selectedTeams.indexOf(teamId);
        if (index > -1) {
            this.app.newTrainerInvite.selectedTeams.splice(index, 1);
        } else {
            this.app.newTrainerInvite.selectedTeams.push(teamId);
        }
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
            alert(`You have reached your trainer limit of ${this.app.trainerLimit}. Please upgrade your membership to invite more trainers.`);
            return false;
        }
        return true;
    }
    
    async inviteTrainer() {
        if (!this.app.newTrainerInvite.selectedTeams || this.app.newTrainerInvite.selectedTeams.length === 0) {
            alert('Please select at least one team for the trainer');
            return;
        }
        
        try {
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
            alert('Invitation sent successfully!');
            
        } catch (error) {
            alert('Error sending invitation: ' + (error.message || 'Unknown error'));
        }
    }
    
    async cancelInvitation(invitationId) {
        if (!confirm('Are you sure you want to cancel this invitation?')) {
            return;
        }
        
        try {
            await this.app.apiPost('cm_cancel_invitation', {
                invitation_id: invitationId
            });
            
            await this.loadTrainerManagementData();
            
        } catch (error) {
            alert('Error canceling invitation');
        }
    }
    
    async editTrainer(trainer) {
        this.app.editingTrainer = trainer;
        this.app.editTrainerData = {
            selectedTeams: trainer.teams ? trainer.teams.map(t => t.id) : [],
            role: trainer.role || 'trainer'
        };
        this.app.showEditTrainerModal = true;
    }
    
    async updateTrainer() {
        if (!this.app.editTrainerData.selectedTeams || this.app.editTrainerData.selectedTeams.length === 0) {
            alert('Please select at least one team for the trainer');
            return;
        }
        
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
            alert('Trainer updated successfully!');
            
        } catch (error) {
            alert('Error updating trainer: ' + (error.message || 'Unknown error'));
        }
    }
    
    async removeTrainer(trainer) {
        if (!confirm(`Are you sure you want to remove ${trainer.display_name} as a trainer?`)) {
            return;
        }
        
        try {
            await this.app.apiPost('cm_remove_trainer', {
                trainer_id: trainer.id
            });
            
            await this.loadTrainerManagementData();
            
        } catch (error) {
            alert('Error removing trainer');
        }
    }
}