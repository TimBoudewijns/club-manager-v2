// Club Manager Main JavaScript
// Define the Alpine component in the global scope
window.clubManager = function() {
    return {
        // Core data
        activeTab: 'my-teams',
        currentSeason: window.clubManagerAjax?.preferred_season || '2024-2025',
        teams: [],
        selectedTeam: null,
        teamPlayers: [],
        viewingPlayer: null,
        selectedPlayerCard: null,
        canViewClubTeams: window.clubManagerAjax?.can_view_club_teams || false,
        
        // Club teams data
        clubTeams: [],
        selectedClubTeam: null,
        clubTeamPlayers: [],
        viewingClubPlayer: null,
        selectedClubPlayerCard: null,
        isViewingClubTeam: false,
        
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
        },
        
        // Team data
        showCreateTeamModal: false,
        newTeam: {
            name: '',
            coach: ''
        },
        
        // Player data
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
        },
        
        // Evaluation data
        showEvaluationModal: false,
        evaluatingPlayer: null,
        evaluationNotes: '',
        currentEvaluationScores: {},
        evaluations: {},
        
        // Player card data
        playerCardChart: null,
        playerEvaluationHistory: [],
        availableEvaluationDates: [],
        selectedEvaluationDate: 'all',
        playerAdvice: null,
        adviceLoading: false,
        adviceStatus: 'no_evaluations',
        lastAdviceTimestamp: null,
        
        // Evaluation categories
        evaluationCategories: [
            {
                name: 'Ball Control',
                key: 'ball_control',
                subcategories: [
                    { key: 'first_touch', name: 'First Touch', description: 'Clean reception' },
                    { key: 'ball_carry', name: 'Ball Carry', description: 'Control under pressure' }
                ]
            },
            {
                name: 'Passing & Receiving',
                key: 'passing_receiving',
                subcategories: [
                    { key: 'push_slap_hit', name: 'Push, Slap, Hit', description: 'Accuracy & power' },
                    { key: 'timing_communication', name: 'Timing & Communication', description: 'Timing and communication' }
                ]
            },
            {
                name: 'Dribbling Skills',
                key: 'dribbling_skills',
                subcategories: [
                    { key: '1v1_situations', name: '1v1 Situations', description: '1v1 situations' },
                    { key: 'lr_control', name: 'L/R Control', description: 'Left/right control at speed' }
                ]
            },
            {
                name: 'Defensive Skills',
                key: 'defensive_skills',
                subcategories: [
                    { key: 'jab_block', name: 'Jab & Block', description: 'Jab & block tackle' },
                    { key: 'marking_positioning', name: 'Marking & Positioning', description: 'Marking & positioning' }
                ]
            },
            {
                name: 'Finishing & Scoring',
                key: 'finishing_scoring',
                subcategories: [
                    { key: 'shot_variety', name: 'Shot Variety', description: 'Hit, deflection, rebound' },
                    { key: 'scoring_instinct', name: 'Scoring Instinct', description: 'Scoring instinct' }
                ]
            },
            {
                name: 'Tactical Understanding',
                key: 'tactical_understanding',
                subcategories: [
                    { key: 'spatial_awareness', name: 'Spatial Awareness', description: 'Spatial awareness' },
                    { key: 'game_intelligence', name: 'Game Intelligence', description: 'Making the right choices' }
                ]
            },
            {
                name: 'Physical Fitness',
                key: 'physical_fitness',
                subcategories: [
                    { key: 'speed_endurance', name: 'Speed & Endurance', description: 'Speed & endurance' },
                    { key: 'strength_agility', name: 'Strength & Agility', description: 'Strength, agility, balance' }
                ]
            },
            {
                name: 'Mental Toughness',
                key: 'mental_toughness',
                subcategories: [
                    { key: 'focus_resilience', name: 'Focus & Resilience', description: 'Focus and resilience' },
                    { key: 'confidence_pressure', name: 'Confidence Under Pressure', description: 'Performance under pressure' }
                ]
            },
            {
                name: 'Team Play & Communication',
                key: 'team_play',
                subcategories: [
                    { key: 'verbal_communication', name: 'Verbal Communication', description: 'Verbal communication' },
                    { key: 'supporting_teammates', name: 'Supporting Teammates', description: 'On and off the ball' }
                ]
            },
            {
                name: 'Coachability & Attitude',
                key: 'coachability',
                subcategories: [
                    { key: 'takes_feedback', name: 'Takes Feedback', description: 'Takes feedback seriously' },
                    { key: 'work_ethic', name: 'Work Ethic', description: 'Work ethic, drive, respect' }
                ]
            }
        ],
        
        // Initialize
        init() {
            console.log('Club Manager initializing...');
            console.log('Can view club teams:', this.canViewClubTeams);
            
            // Initialize data first
            this.initializeData();
            
            // Then load teams
            this.loadTeams();
            
            // Fix for modals on mobile
            this.$watch('showCreateTeamModal', (value) => {
                document.body.style.overflow = value ? 'hidden' : '';
            });
            this.$watch('showAddPlayerModal', (value) => {
                document.body.style.overflow = value ? 'hidden' : '';
            });
            this.$watch('showAddExistingPlayerModal', (value) => {
                document.body.style.overflow = value ? 'hidden' : '';
            });
            this.$watch('showEvaluationModal', (value) => {
                document.body.style.overflow = value ? 'hidden' : '';
            });
            this.$watch('showPlayerHistoryModal', (value) => {
                document.body.style.overflow = value ? 'hidden' : '';
            });
            this.$watch('showInviteTrainerModal', (value) => {
                document.body.style.overflow = value ? 'hidden' : '';
            });
            this.$watch('showEditTrainerModal', (value) => {
                document.body.style.overflow = value ? 'hidden' : '';
            });
            
            // Watch for tab changes
            this.$watch('activeTab', (value) => {
                if (value === 'club-teams' && this.canViewClubTeams) {
                    this.loadClubTeams();
                } else if (value === 'trainer-management' && this.canViewClubTeams) {
                    this.loadTrainerManagementData();
                }
            });
        },
        
        // Initialize all data structures
        initializeData() {
            // Ensure all arrays are initialized
            this.teams = this.teams || [];
            this.clubTeams = this.clubTeams || [];
            this.teamPlayers = this.teamPlayers || [];
            this.clubTeamPlayers = this.clubTeamPlayers || [];
            this.searchResults = this.searchResults || [];
            this.playerHistory = this.playerHistory || [];
            this.playerEvaluationHistory = this.playerEvaluationHistory || [];
            this.availableEvaluationDates = this.availableEvaluationDates || [];
            this.pendingInvitations = this.pendingInvitations || [];
            this.activeTrainers = this.activeTrainers || [];
            this.managedTeams = this.managedTeams || [];
            
            // Ensure all objects are initialized
            this.evaluations = this.evaluations || {};
            this.currentEvaluationScores = this.currentEvaluationScores || {};
            
            // Ensure newTrainerInvite has selectedTeams array
            if (!this.newTrainerInvite.selectedTeams) {
                this.newTrainerInvite.selectedTeams = [];
            }
            
            // Ensure editTrainerData has selectedTeams array
            if (!this.editTrainerData.selectedTeams) {
                this.editTrainerData.selectedTeams = [];
            }
        },
        
        // API helper
        async apiPost(action, data = {}) {
            const formData = new FormData();
            formData.append('action', action);
            formData.append('nonce', window.clubManagerAjax.nonce);
            
            Object.keys(data).forEach(key => {
                if (Array.isArray(data[key])) {
                    data[key].forEach(value => {
                        formData.append(key + '[]', value);
                    });
                } else {
                    formData.append(key, data[key]);
                }
            });
            
            try {
                const response = await fetch(window.clubManagerAjax.ajax_url, {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (!result.success) {
                    throw new Error(result.data || 'Request failed');
                }
                
                return result.data;
            } catch (error) {
                console.error('API Error:', error);
                throw error;
            }
        },
        
        // Team Methods
        async loadTeams() {
            try {
                this.teams = await this.apiPost('cm_get_teams', {
                    season: this.currentSeason
                });
            } catch (error) {
                alert('Error loading teams');
            }
        },
        
        async createTeam() {
            try {
                await this.apiPost('cm_create_team', {
                    name: this.newTeam.name,
                    coach: this.newTeam.coach,
                    season: this.currentSeason
                });
                
                this.showCreateTeamModal = false;
                this.newTeam = { name: '', coach: '' };
                await this.loadTeams();
                
            } catch (error) {
                alert('Error creating team');
            }
        },
        
        async selectTeam(team) {
            this.selectedTeam = team;
            this.viewingPlayer = null;
            this.selectedPlayerCard = null;
            this.isViewingClubTeam = false;
            await this.loadTeamPlayers();
        },
        
        // Club Team Methods
        async loadClubTeams() {
            try {
                this.clubTeams = await this.apiPost('cm_get_club_teams', {
                    season: this.currentSeason
                });
            } catch (error) {
                console.error('Error loading club teams:', error);
            }
        },
        
        async selectClubTeam(team) {
            this.selectedClubTeam = team;
            this.viewingClubPlayer = null;
            this.selectedClubPlayerCard = null;
            this.isViewingClubTeam = true;
            await this.loadClubTeamPlayers();
        },
        
        async loadClubTeamPlayers() {
            if (!this.selectedClubTeam) return;
            
            try {
                this.clubTeamPlayers = await this.apiPost('cm_get_club_team_players', {
                    team_id: this.selectedClubTeam.id,
                    season: this.currentSeason
                });
            } catch (error) {
                console.error('Error loading club team players:', error);
            }
        },
        
        // Trainer Management Methods
        async loadTrainerManagementData() {
            try {
                // Load managed teams for the invitation form
                const teamsData = await this.apiPost('cm_get_managed_teams', {
                    season: this.currentSeason
                });
                this.managedTeams = teamsData || [];
                
                // Load pending invitations
                const invitationsData = await this.apiPost('cm_get_pending_invitations');
                this.pendingInvitations = invitationsData || [];
                
                // Load active trainers
                const trainersData = await this.apiPost('cm_get_active_trainers');
                this.activeTrainers = trainersData || [];
                
            } catch (error) {
                console.error('Error loading trainer management data:', error);
                // Initialize with empty arrays on error
                this.managedTeams = [];
                this.pendingInvitations = [];
                this.activeTrainers = [];
            }
        },
        
        toggleTeamSelection(teamId) {
            if (!this.newTrainerInvite.selectedTeams) {
                this.newTrainerInvite.selectedTeams = [];
            }
            const index = this.newTrainerInvite.selectedTeams.indexOf(teamId);
            if (index > -1) {
                this.newTrainerInvite.selectedTeams.splice(index, 1);
            } else {
                this.newTrainerInvite.selectedTeams.push(teamId);
            }
        },
        
        toggleEditTeamSelection(teamId) {
            if (!this.editTrainerData.selectedTeams) {
                this.editTrainerData.selectedTeams = [];
            }
            const index = this.editTrainerData.selectedTeams.indexOf(teamId);
            if (index > -1) {
                this.editTrainerData.selectedTeams.splice(index, 1);
            } else {
                this.editTrainerData.selectedTeams.push(teamId);
            }
        },
        
        // Check trainer limit
        canInviteMoreTrainers() {
            if (!this.trainerLimit || this.trainerLimit === 999) {
                return true; // No limit set or unlimited
            }
            return this.activeTrainers.length < this.trainerLimit;
        },
        
        checkTrainerLimit() {
            if (!this.canInviteMoreTrainers()) {
                alert(`You have reached your trainer limit of ${this.trainerLimit}. Please upgrade your membership to invite more trainers.`);
                return false;
            }
            return true;
        },
        
        async inviteTrainer() {
            if (!this.newTrainerInvite.selectedTeams || this.newTrainerInvite.selectedTeams.length === 0) {
                alert('Please select at least one team for the trainer');
                return;
            }
            
            try {
                await this.apiPost('cm_invite_trainer', {
                    email: this.newTrainerInvite.email,
                    teams: this.newTrainerInvite.selectedTeams,
                    role: this.newTrainerInvite.role,
                    message: this.newTrainerInvite.message
                });
                
                this.showInviteTrainerModal = false;
                this.newTrainerInvite = {
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
        },
        
        async cancelInvitation(invitationId) {
            if (!confirm('Are you sure you want to cancel this invitation?')) {
                return;
            }
            
            try {
                await this.apiPost('cm_cancel_invitation', {
                    invitation_id: invitationId
                });
                
                await this.loadTrainerManagementData();
                
            } catch (error) {
                alert('Error canceling invitation');
            }
        },
        
        async editTrainer(trainer) {
            this.editingTrainer = trainer;
            this.editTrainerData = {
                selectedTeams: trainer.teams ? trainer.teams.map(t => t.id) : [],
                role: trainer.role || 'trainer'
            };
            this.showEditTrainerModal = true;
        },
        
        async updateTrainer() {
            if (!this.editTrainerData.selectedTeams || this.editTrainerData.selectedTeams.length === 0) {
                alert('Please select at least one team for the trainer');
                return;
            }
            
            try {
                await this.apiPost('cm_update_trainer', {
                    trainer_id: this.editingTrainer.id,
                    teams: this.editTrainerData.selectedTeams,
                    role: this.editTrainerData.role
                });
                
                this.showEditTrainerModal = false;
                this.editingTrainer = null;
                this.editTrainerData = {
                    selectedTeams: [],
                    role: 'trainer'
                };
                
                await this.loadTrainerManagementData();
                alert('Trainer updated successfully!');
                
            } catch (error) {
                alert('Error updating trainer: ' + (error.message || 'Unknown error'));
            }
        },
        
        async removeTrainer(trainer) {
            if (!confirm(`Are you sure you want to remove ${trainer.display_name} as a trainer?`)) {
                return;
            }
            
            try {
                await this.apiPost('cm_remove_trainer', {
                    trainer_id: trainer.id
                });
                
                await this.loadTrainerManagementData();
                
            } catch (error) {
                alert('Error removing trainer');
            }
        },
        
        // Player Methods
        async loadTeamPlayers() {
            if (!this.selectedTeam) return;
            
            try {
                this.teamPlayers = await this.apiPost('cm_get_team_players', {
                    team_id: this.selectedTeam.id,
                    season: this.currentSeason
                });
            } catch (error) {
                alert('Error loading players');
            }
        },
        
        async createPlayer() {
            try {
                const playerData = Object.assign({}, this.newPlayer, {
                    team_id: this.selectedTeam.id,
                    season: this.currentSeason
                });
                
                await this.apiPost('cm_create_player', playerData);
                
                this.showAddPlayerModal = false;
                this.newPlayer = {
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
                alert('Error creating player');
            }
        },
        
        async searchPlayers() {
            if (this.playerSearch.length < 2) {
                this.searchResults = [];
                return;
            }
            
            try {
                this.searchResults = await this.apiPost('cm_search_players', {
                    search: this.playerSearch,
                    team_id: this.selectedTeam.id,
                    season: this.currentSeason
                });
            } catch (error) {
                console.error('Error searching players');
            }
        },
        
        selectExistingPlayer(player) {
            this.selectedExistingPlayer = player;
            this.searchResults = [];
            this.playerSearch = '';
        },
        
        async addExistingPlayerToTeam() {
            try {
                const data = Object.assign({}, this.existingPlayerTeamData, {
                    team_id: this.selectedTeam.id,
                    player_id: this.selectedExistingPlayer.id,
                    season: this.currentSeason
                });
                
                await this.apiPost('cm_add_player_to_team', data);
                
                this.closeAddExistingPlayerModal();
                await this.loadTeamPlayers();
                
            } catch (error) {
                alert('Error adding player to team');
            }
        },
        
        closeAddExistingPlayerModal() {
            this.showAddExistingPlayerModal = false;
            this.selectedExistingPlayer = null;
            this.playerSearch = '';
            this.searchResults = [];
            this.existingPlayerTeamData = {
                position: '',
                jersey_number: '',
                notes: ''
            };
        },
        
        async removePlayerFromTeam(player) {
            if (!confirm('Are you sure you want to remove ' + player.first_name + ' ' + player.last_name + ' from this team?')) {
                return;
            }
            
            try {
                await this.apiPost('cm_remove_player_from_team', {
                    team_id: this.selectedTeam.id,
                    player_id: player.id,
                    season: this.currentSeason
                });
                
                // Hide player card if this player was being viewed
                if (this.viewingPlayer && this.viewingPlayer.id === player.id) {
                    this.viewingPlayer = null;
                    this.selectedPlayerCard = null;
                }
                
                await this.loadTeamPlayers();
                
            } catch (error) {
                alert('Error removing player from team');
            }
        },
        
        handleRemoveClick(playerId) {
            const player = this.teamPlayers.find(p => p.id == playerId);
            if (player) {
                this.removePlayerFromTeam(player);
            }
        },
        
        // Player History
        async viewPlayerHistory(playerId, isClubView = false) {
            const players = isClubView ? this.clubTeamPlayers : this.teamPlayers;
            const player = players.find(p => p.id == playerId);
            if (!player) return;
            
            this.showPlayerHistoryModal = true;
            this.historyLoading = true;
            this.playerHistory = [];
            this.historyPlayer = player;
            
            try {
                const data = await this.apiPost('cm_get_player_history', {
                    player_id: playerId
                });
                
                this.playerHistory = data.history;
                this.historyPlayer = data.player;
                
            } catch (error) {
                alert('Error loading player history');
            } finally {
                this.historyLoading = false;
            }
        },
        
        handleHistoryClick(playerId, isClubView = false) {
            this.viewPlayerHistory(playerId, isClubView);
        },
        
        // Evaluation Methods
        handleEvaluateClick(playerId) {
            const player = this.teamPlayers.find(p => p.id == playerId);
            if (player) {
                this.evaluatePlayer(player);
            }
        },
        
        evaluatePlayer(player) {
            this.evaluatingPlayer = player;
            this.currentEvaluationScores = {};
            this.showEvaluationModal = true;
            this.loadEvaluations(player);
            this.initializeCurrentEvaluationScores();
        },
        
        async loadEvaluations(player, isClubView = false) {
            try {
                const team = isClubView ? this.selectedClubTeam : this.selectedTeam;
                const action = isClubView ? 'cm_get_club_player_evaluations' : 'cm_get_evaluations';
                
                const data = await this.apiPost(action, {
                    player_id: player.id,
                    team_id: team.id,
                    season: this.currentSeason
                });
                
                this.evaluations[player.id] = data.evaluations || [];
                
            } catch (error) {
                console.error('Error loading evaluations:', error);
            }
        },
        
        initializeCurrentEvaluationScores() {
            this.evaluationCategories.forEach(category => {
                category.subcategories.forEach(sub => {
                    const key = category.key + '_' + sub.key;
                    this.currentEvaluationScores[key] = this.getLastSubcategoryScore(category.key, sub.key);
                });
            });
        },
        
        getLastSubcategoryScore(categoryKey, subcategoryKey) {
            if (!this.evaluatingPlayer || !this.evaluations[this.evaluatingPlayer.id]) return 5;
            
            const evaluations = this.evaluations[this.evaluatingPlayer.id]
                .filter(e => e.category === categoryKey && e.subcategory === subcategoryKey)
                .sort((a, b) => new Date(b.evaluated_at) - new Date(a.evaluated_at));
            
            return evaluations.length > 0 ? parseFloat(evaluations[0].score) : 5;
        },
        
        getSubcategoryScore(categoryKey, subcategoryKey) {
            const key = categoryKey + '_' + subcategoryKey;
            return this.currentEvaluationScores[key] || 5;
        },
        
        getCategoryAverage(categoryKey) {
            const category = this.evaluationCategories.find(c => c.key === categoryKey);
            if (!category) return '5.0';
            
            let scores = [];
            category.subcategories.forEach(sub => {
                const score = this.getSubcategoryScore(categoryKey, sub.key);
                scores.push(score);
            });
            
            if (scores.length === 0) return '5.0';
            
            const average = scores.reduce((a, b) => a + b, 0) / scores.length;
            return average.toFixed(1);
        },
        
        updateSubcategoryScore(categoryKey, subcategoryKey, score) {
            const key = categoryKey + '_' + subcategoryKey;
            this.currentEvaluationScores[key] = parseFloat(score);
        },
        
        async saveEvaluation() {
            const savePromises = [];
            
            for (const category of this.evaluationCategories) {
                // Calculate and save main category average
                const categoryScore = this.getCategoryAverage(category.key);
                savePromises.push(this.saveEvaluationScore(category.key, null, categoryScore));
                
                // Save subcategory scores
                for (const sub of category.subcategories) {
                    const subScore = this.getSubcategoryScore(category.key, sub.key);
                    savePromises.push(this.saveEvaluationScore(category.key, sub.key, subScore));
                }
            }
            
            try {
                await Promise.all(savePromises);
                
                // Reset current evaluation scores
                this.currentEvaluationScores = {};
                
                // Reload evaluations to get fresh data from database
                await this.loadEvaluations(this.evaluatingPlayer);
                
                // If player card is open for the same player, refresh that too
                if (this.viewingPlayer && this.viewingPlayer.id === this.evaluatingPlayer.id) {
                    await this.loadEvaluationHistory(this.viewingPlayer);
                    
                    // Force update spider chart with new data
                    this.forceUpdateSpiderChart();
                    
                    // Clear old advice and set status
                    this.playerAdvice = null;
                    this.adviceStatus = 'generating';
                    this.adviceLoading = false;
                    
                    // Force UI update
                    await this.$nextTick();
                    
                    // Start polling for new advice after delay
                    setTimeout(() => {
                        this.pollForAdvice(this.viewingPlayer);
                    }, 2000);
                }
                
                this.closeEvaluationModal();
                alert('Evaluation saved successfully! AI advice is being generated...');
                
            } catch (error) {
                alert('Error saving evaluation. Please try again.');
            }
        },
        
        async saveEvaluationScore(category, subcategory, score) {
            const data = {
                player_id: this.evaluatingPlayer.id,
                team_id: this.selectedTeam.id,
                season: this.currentSeason,
                category: category,
                score: score,
                notes: this.evaluationNotes
            };
            
            if (subcategory) {
                data.subcategory = subcategory;
            }
            
            const result = await this.apiPost('cm_save_evaluation', data);
            
            if (!result) {
                throw new Error('Failed to save evaluation');
            }
            
            return result;
        },
        
        closeEvaluationModal() {
            this.showEvaluationModal = false;
            this.evaluatingPlayer = null;
            this.evaluationNotes = '';
            this.currentEvaluationScores = {};
        },
        
        // Player Card Methods
        handlePlayerCardClick(playerId, isClubView = false) {
            const players = isClubView ? this.clubTeamPlayers : this.teamPlayers;
            const player = players.find(p => p.id == playerId);
            if (player) {
                if (isClubView) {
                    this.viewClubPlayerCard(player);
                } else {
                    this.viewPlayerCard(player);
                }
            }
        },
        
        async viewPlayerCard(player) {
            // If clicking same player, toggle card
            if (this.viewingPlayer && this.viewingPlayer.id === player.id) {
                this.viewingPlayer = null;
                this.selectedPlayerCard = null;
                if (this.playerCardChart) {
                    this.playerCardChart.destroy();
                    this.playerCardChart = null;
                }
                return;
            }
            
            // Destroy existing chart if any
            if (this.playerCardChart) {
                this.playerCardChart.destroy();
                this.playerCardChart = null;
            }
            
            this.viewingPlayer = player;
            this.selectedPlayerCard = this.selectedTeam;
            
            // Load evaluations first
            await this.loadEvaluations(player);
            await this.loadEvaluationHistory(player);
            
            // Load AI advice
            await this.loadPlayerAdvice(player);
            
            // Wait for Alpine to update the DOM
            await this.$nextTick();
            
            // Wait a bit more for the DOM to be ready and try to create chart
            setTimeout(() => {
                this.createSpiderChart();
            }, 500);
        },
        
        async viewClubPlayerCard(player) {
            // If clicking same player, toggle card
            if (this.viewingClubPlayer && this.viewingClubPlayer.id === player.id) {
                this.viewingClubPlayer = null;
                this.selectedClubPlayerCard = null;
                if (this.playerCardChart) {
                    this.playerCardChart.destroy();
                    this.playerCardChart = null;
                }
                return;
            }
            
            // Destroy existing chart if any
            if (this.playerCardChart) {
                this.playerCardChart.destroy();
                this.playerCardChart = null;
            }
            
            this.viewingClubPlayer = player;
            this.selectedClubPlayerCard = this.selectedClubTeam;
            
            // Load evaluations first
            await this.loadEvaluations(player, true);
            await this.loadEvaluationHistory(player, true);
            
            // Load AI advice
            await this.loadPlayerAdvice(player, true);
            
            // Wait for Alpine to update the DOM
            await this.$nextTick();
            
            // Wait a bit more for the DOM to be ready and try to create chart
            setTimeout(() => {
                this.createSpiderChart(true);
            }, 500);
        },
        
        async loadEvaluationHistory(player, isClubView = false) {
            try {
                const team = isClubView ? this.selectedClubTeam : this.selectedTeam;
                const action = isClubView ? 'cm_get_club_player_evaluations' : 'cm_get_evaluations';
                
                const data = await this.apiPost(action, {
                    player_id: player.id,
                    team_id: team.id,
                    season: this.currentSeason
                });
                
                // Store all evaluations
                const allEvaluations = data.evaluations || [];
                this.evaluations[player.id] = allEvaluations;
                
                // Filter for main category scores only (no subcategory)
                this.playerEvaluationHistory = allEvaluations.filter(e => !e.subcategory);
                
                // Get unique evaluation dates
                const dates = [];
                const dateSet = new Set();
                allEvaluations.forEach(e => {
                    const date = e.evaluated_at.split(' ')[0];
                    if (!dateSet.has(date)) {
                        dateSet.add(date);
                        dates.push(date);
                    }
                });
                this.availableEvaluationDates = dates.sort((a, b) => new Date(b) - new Date(a));
                
                // Sort by date descending
                this.playerEvaluationHistory.sort((a, b) => new Date(b.evaluated_at) - new Date(a.evaluated_at));
                
                // Reset selected date to 'all'
                this.selectedEvaluationDate = 'all';
                
            } catch (error) {
                this.playerEvaluationHistory = [];
                this.availableEvaluationDates = [];
            }
        },
        
        getFilteredEvaluationHistory() {
            if (this.selectedEvaluationDate === 'all') {
                return this.playerEvaluationHistory;
            }
            
            const selectedDate = this.selectedEvaluationDate;
            return this.playerEvaluationHistory.filter(e => 
                e.evaluated_at.startsWith(selectedDate)
            );
        },
        
        getSubcategoryEvaluations(category, evaluatedAt) {
            const viewingPlayer = this.isViewingClubTeam ? this.viewingClubPlayer : this.viewingPlayer;
            if (!viewingPlayer || !this.evaluations[viewingPlayer.id]) {
                return [];
            }
            
            const evaluationDate = evaluatedAt.split(' ')[0];
            
            return this.evaluations[viewingPlayer.id].filter(e => 
                e.category === category && 
                e.subcategory && 
                e.evaluated_at.startsWith(evaluationDate)
            );
        },
        
        formatSubcategoryName(subcategoryKey) {
            return subcategoryKey
                .replace(/_/g, ' ')
                .split(' ')
                .map(w => w.charAt(0).toUpperCase() + w.slice(1))
                .join(' ');
        },
        
        onEvaluationDateChange() {
            this.forceUpdateSpiderChart();
        },
        
        getPlayerCardCategoryAverage(categoryKey) {
            const category = this.evaluationCategories.find(c => c.key === categoryKey);
            const viewingPlayer = this.isViewingClubTeam ? this.viewingClubPlayer : this.viewingPlayer;
            
            if (!category || !viewingPlayer || !this.evaluations[viewingPlayer.id]) {
                return '5.0';
            }
            
            // Filter evaluations based on selected date
            let evaluationsToUse = this.evaluations[viewingPlayer.id];
            if (this.selectedEvaluationDate !== 'all') {
                const selectedDate = this.selectedEvaluationDate;
                evaluationsToUse = evaluationsToUse.filter(e => 
                    e.evaluated_at.startsWith(selectedDate)
                );
            }
            
            // Try to get main category evaluations first
            const categoryEvaluations = evaluationsToUse.filter(e => 
                e.category === categoryKey && !e.subcategory
            );
            
            if (categoryEvaluations.length > 0) {
                const sum = categoryEvaluations.reduce((acc, eval) => acc + parseFloat(eval.score), 0);
                const average = sum / categoryEvaluations.length;
                return average.toFixed(1);
            }
            
            // If no main category evaluations, calculate from subcategories
            let scores = [];
            category.subcategories.forEach(sub => {
                const subEvaluations = evaluationsToUse.filter(e => 
                    e.category === categoryKey && e.subcategory === sub.key
                );
                
                if (subEvaluations.length > 0) {
                    if (this.selectedEvaluationDate !== 'all') {
                        const sum = subEvaluations.reduce((acc, eval) => acc + parseFloat(eval.score), 0);
                        scores.push(sum / subEvaluations.length);
                    } else {
                        subEvaluations.sort((a, b) => new Date(b.evaluated_at) - new Date(a.evaluated_at));
                        scores.push(parseFloat(subEvaluations[0].score));
                    }
                }
            });
            
            if (scores.length === 0) {
                return '5.0';
            }
            
            const average = scores.reduce((a, b) => a + b, 0) / scores.length;
            return average.toFixed(1);
        },
        
        createSpiderChart(isClubView = false) {
            const canvasId = isClubView ? 'clubPlayerCardSpiderChart' : 'playerCardSpiderChart';
            const canvas = document.getElementById(canvasId);
            const viewingPlayer = isClubView ? this.viewingClubPlayer : this.viewingPlayer;
            
            if (!canvas || !viewingPlayer) {
                setTimeout(() => this.createSpiderChart(isClubView), 200);
                return;
            }
            
            if (canvas.offsetParent === null) {
                setTimeout(() => this.createSpiderChart(isClubView), 200);
                return;
            }
            
            if (typeof Chart === 'undefined') {
                setTimeout(() => this.createSpiderChart(isClubView), 200);
                return;
            }
            
            const ctx = canvas.getContext('2d');
            
            // Always destroy existing chart first
            if (this.playerCardChart && typeof this.playerCardChart.destroy === 'function') {
                try {
                    this.playerCardChart.destroy();
                    this.playerCardChart = null;
                } catch (e) {
                    this.playerCardChart = null;
                }
            }
            
            const labels = this.evaluationCategories.map(c => c.name);
            const data = this.evaluationCategories.map(c => {
                const avg = this.getPlayerCardCategoryAverage(c.key);
                return parseFloat(avg);
            });
            
            // Determine chart label based on selected date
            let chartLabel = 'Season Average Performance';
            if (this.selectedEvaluationDate !== 'all') {
                const dateObj = new Date(this.selectedEvaluationDate);
                chartLabel = 'Performance on ' + dateObj.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
            }
            
            try {
                this.playerCardChart = new Chart(ctx, {
                    type: 'radar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: chartLabel,
                            data: data,
                            fill: true,
                            backgroundColor: 'rgba(249, 115, 22, 0.2)',
                            borderColor: 'rgb(249, 115, 22)',
                            pointBackgroundColor: 'rgb(249, 115, 22)',
                            pointBorderColor: '#fff',
                            pointHoverBackgroundColor: '#fff',
                            pointHoverBorderColor: 'rgb(249, 115, 22)',
                            pointRadius: 4,
                            pointHoverRadius: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: {
                            duration: 300
                        },
                        elements: {
                            line: {
                                borderWidth: 3
                            }
                        },
                        scales: {
                            r: {
                                angleLines: {
                                    display: true
                                },
                                suggestedMin: 0,
                                suggestedMax: 10,
                                ticks: {
                                    stepSize: 2,
                                    font: {
                                        size: 10
                                    }
                                },
                                pointLabels: {
                                    font: {
                                        size: 11
                                    }
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top',
                                labels: {
                                    font: {
                                        size: 12
                                    }
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.parsed.r.toFixed(1) + '/10';
                                    }
                                }
                            }
                        }
                    }
                });
                
            } catch (error) {
                console.error('Error creating chart:', error);
            }
        },
        
        forceUpdateSpiderChart() {
            if (this.playerCardChart) {
                this.playerCardChart.destroy();
                this.playerCardChart = null;
            }
            
            setTimeout(() => {
                this.createSpiderChart(this.isViewingClubTeam);
            }, 100);
        },
        
        // AI Advice Methods
        async loadPlayerAdvice(player, isClubView = false) {
            this.adviceLoading = true;
            
            try {
                const team = isClubView ? this.selectedClubTeam : this.selectedTeam;
                const action = isClubView ? 'cm_get_club_player_advice' : 'cm_get_player_advice';
                
                const data = await this.apiPost(action, {
                    player_id: player.id,
                    team_id: team.id,
                    season: this.currentSeason
                });
                
                this.playerAdvice = data.advice;
                this.adviceStatus = data.status || 'no_evaluations';
                this.lastAdviceTimestamp = data.generated_at || null;
                
                // Check if we have evaluations but no advice yet
                if (!this.playerAdvice && this.evaluations[player.id] && this.evaluations[player.id].length > 0) {
                    this.adviceStatus = 'no_advice_yet';
                }
                
            } catch (error) {
                console.error('Error loading advice:', error);
            }
            
            this.adviceLoading = false;
        },
        
        async generatePlayerAdvice(player) {
            try {
                await this.apiPost('cm_generate_player_advice', {
                    player_id: player.id,
                    team_id: this.selectedTeam.id,
                    season: this.currentSeason
                });
            } catch (error) {
                console.error('Error generating advice:', error);
            }
        },
        
        async pollForAdvice(player, attempts = 0) {
            if (!player || attempts > 15) {
                this.adviceLoading = false;
                this.adviceStatus = 'generation_failed';
                return;
            }
            
            // Check if we're still viewing the same player
            if (!this.viewingPlayer || this.viewingPlayer.id !== player.id) {
                return;
            }
            
            try {
                const data = await this.apiPost('cm_get_player_advice', {
                    player_id: player.id,
                    team_id: this.selectedTeam.id,
                    season: this.currentSeason
                });
                
                if (data.advice) {
                    // Check if this is new advice
                    const isNewAdvice = !this.lastAdviceTimestamp || 
                                       data.generated_at !== this.lastAdviceTimestamp;
                    
                    if (isNewAdvice) {
                        // New advice found!
                        this.playerAdvice = data.advice;
                        this.adviceStatus = 'current';
                        this.adviceLoading = false;
                        this.lastAdviceTimestamp = data.generated_at;
                    } else {
                        // This is still old advice, keep polling
                        setTimeout(() => {
                            this.pollForAdvice(player, attempts + 1);
                        }, 5000);
                    }
                } else {
                    // No advice found, keep polling
                    setTimeout(() => {
                        this.pollForAdvice(player, attempts + 1);
                    }, 5000);
                }
            } catch (error) {
                this.adviceLoading = false;
                this.adviceStatus = 'error';
            }
        },
        
        // PDF Download
        async downloadPlayerCardPDF(event, isClubView = false) {
            const viewingPlayer = isClubView ? this.viewingClubPlayer : this.viewingPlayer;
            if (!viewingPlayer) return;
            
            let button = null;
            let originalContent = '';
            
            try {
                // Check if jsPDF is loaded
                if (typeof window.jspdf === 'undefined') {
                    alert('PDF library not loaded. Please refresh the page and try again.');
                    return;
                }
                
                // Get button reference
                if (event && event.target) {
                    button = event.target.closest('button');
                }
                
                // Show loading state if button exists
                if (button) {
                    originalContent = button.innerHTML;
                    button.innerHTML = '<svg class="animate-spin h-5 w-5 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';
                    button.disabled = true;
                }
                
                // Create PDF
                const jsPDF = window.jspdf.jsPDF;
                const pdf = new jsPDF('p', 'mm', 'a4');
                
                // Colors
                const orangeColor = [255, 152, 0];
                const darkGray = [31, 41, 55];
                const mediumGray = [107, 114, 128];
                const lightGray = [229, 231, 235];
                
                // Dimensions
                const pageWidth = pdf.internal.pageSize.getWidth();
                const pageHeight = pdf.internal.pageSize.getHeight();
                const margin = 20;
                const contentWidth = pageWidth - (margin * 2);
                let yPosition = 20;
                
                // Title
                const playerName = viewingPlayer.first_name + ' ' + viewingPlayer.last_name;
                pdf.setFontSize(24);
                pdf.setTextColor.apply(pdf, orangeColor);
                pdf.text(playerName, pageWidth / 2, yPosition, { align: 'center' });
                yPosition += 10;
                
                // Subtitle
                const team = isClubView ? this.selectedClubTeam : this.selectedTeam;
                pdf.setFontSize(14);
                pdf.setTextColor.apply(pdf, mediumGray);
                pdf.text(team.name + ' - ' + this.currentSeason, pageWidth / 2, yPosition, { align: 'center' });
                yPosition += 15;
                
                // Player Info Box
                pdf.setDrawColor.apply(pdf, lightGray);
                pdf.setFillColor(255, 243, 224); // Orange-50
                pdf.roundedRect(margin, yPosition, contentWidth, 25, 3, 3, 'FD');
                
                pdf.setFontSize(12);
                pdf.setTextColor.apply(pdf, darkGray);
                pdf.text('Position: ' + (viewingPlayer.position || 'Not assigned'), margin + 5, yPosition + 8);
                pdf.text('Jersey #: ' + (viewingPlayer.jersey_number || '-'), margin + 60, yPosition + 8);
                pdf.text('Email: ' + viewingPlayer.email, margin + 5, yPosition + 18);
                pdf.text('Birth Date: ' + viewingPlayer.birth_date, margin + 100, yPosition + 18);
                yPosition += 35;
                
                // Notes if available
                if (viewingPlayer.notes) {
                    pdf.setFontSize(12);
                    pdf.setTextColor.apply(pdf, darkGray);
                    pdf.setFont(undefined, 'bold');
                    pdf.text('Notes:', margin, yPosition);
                    pdf.setFont(undefined, 'normal');
                    yPosition += 7;
                    
                    const splitNotes = pdf.splitTextToSize(viewingPlayer.notes, contentWidth);
                    pdf.text(splitNotes, margin, yPosition);
                    yPosition += splitNotes.length * 5 + 10;
                }
                
                // Performance Scores
                pdf.setFontSize(16);
                pdf.setTextColor.apply(pdf, orangeColor);
                pdf.setFont(undefined, 'bold');
                pdf.text('Performance Evaluation', margin, yPosition);
                pdf.setFont(undefined, 'normal');
                yPosition += 10;
                
                // Draw evaluation scores
                const categories = this.evaluationCategories;
                pdf.setFontSize(11);
                
                categories.forEach((category, index) => {
                    if (yPosition > pageHeight - 40) {
                        pdf.addPage();
                        yPosition = 20;
                    }
                    
                    const score = this.getPlayerCardCategoryAverage(category.key);
                    const scoreFloat = parseFloat(score);
                    
                    // Category name
                    pdf.setTextColor.apply(pdf, darkGray);
                    pdf.text(category.name, margin, yPosition);
                    
                    // Score
                    pdf.setTextColor.apply(pdf, orangeColor);
                    pdf.text(score + '/10', margin + 80, yPosition);
                    
                    // Progress bar
                    pdf.setDrawColor.apply(pdf, lightGray);
                    pdf.setFillColor.apply(pdf, lightGray);
                    pdf.rect(margin + 110, yPosition - 4, 50, 5, 'F');
                    
                    // Fill based on score
                    if (scoreFloat >= 7) {
                        pdf.setFillColor(34, 197, 94); // Green
                    } else if (scoreFloat >= 5) {
                        pdf.setFillColor.apply(pdf, orangeColor);
                    } else {
                        pdf.setFillColor(239, 68, 68); // Red
                    }
                    pdf.rect(margin + 110, yPosition - 4, (scoreFloat / 10) * 50, 5, 'F');
                    
                    yPosition += 8;
                });
                
                yPosition += 10;
                
                // AI Advice
                if (this.playerAdvice && this.adviceStatus !== 'no_evaluations') {
                    if (yPosition > pageHeight - 60) {
                        pdf.addPage();
                        yPosition = 20;
                    }
                    
                    pdf.setFontSize(16);
                    pdf.setTextColor.apply(pdf, orangeColor);
                    pdf.setFont(undefined, 'bold');
                    pdf.text('AI Coaching Advice', margin, yPosition);
                    pdf.setFont(undefined, 'normal');
                    yPosition += 10;
                    
                    pdf.setFontSize(10);
                    pdf.setTextColor.apply(pdf, darkGray);
                    const splitAdvice = pdf.splitTextToSize(this.playerAdvice, contentWidth);
                    pdf.text(splitAdvice, margin, yPosition);
                    yPosition += splitAdvice.length * 4 + 10;
                }
                
                // Footer
                pdf.setFontSize(8);
                pdf.setTextColor.apply(pdf, mediumGray);
                pdf.text('Generated: ' + new Date().toLocaleDateString() + ' at ' + new Date().toLocaleTimeString(), pageWidth / 2, pageHeight - 10, { align: 'center' });
                
                // Save the PDF
                const fileName = playerName.replace(/\s+/g, '_') + '_' + this.currentSeason + '_PlayerCard.pdf';
                pdf.save(fileName);
                
                // Restore button if it exists
                if (button) {
                    button.innerHTML = originalContent;
                    button.disabled = false;
                }
                
            } catch (error) {
                alert('Error generating PDF: ' + error.message);
                
                // Restore button if it exists
                if (button && originalContent) {
                    button.innerHTML = originalContent;
                    button.disabled = false;
                }
            }
        },
        
        // Season Management
        async changeSeason() {
            await this.apiPost('cm_save_season_preference', {
                season: this.currentSeason
            });
            
            await this.loadTeams();
            if (this.canViewClubTeams && this.activeTab === 'club-teams') {
                await this.loadClubTeams();
            } else if (this.canViewClubTeams && this.activeTab === 'trainer-management') {
                await this.loadTrainerManagementData();
            }
            
            this.selectedTeam = null;
            this.teamPlayers = [];
            this.viewingPlayer = null;
            this.selectedPlayerCard = null;
            
            this.selectedClubTeam = null;
            this.clubTeamPlayers = [];
            this.viewingClubPlayer = null;
            this.selectedClubPlayerCard = null;
        }
    };
};