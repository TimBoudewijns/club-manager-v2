// Evaluation Module - Handles player evaluation functionality
class EvaluationModule {
    constructor(app) {
        this.app = app;
        this.initializeData();
    }
    
    initializeData() {
        Object.assign(this.app, {
            // Evaluation data
            showEvaluationModal: false,
            evaluatingPlayer: null,
            evaluationNotes: '',
            currentEvaluationScores: {},
            evaluations: {},
            
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
            ]
        });
        
        this.bindMethods();
    }
    
    bindMethods() {
        this.app.evaluatePlayer = this.evaluatePlayer.bind(this);
        this.app.handleEvaluateClick = this.handleEvaluateClick.bind(this);
        this.app.loadEvaluations = this.loadEvaluations.bind(this);
        this.app.initializeCurrentEvaluationScores = this.initializeCurrentEvaluationScores.bind(this);
        this.app.getLastSubcategoryScore = this.getLastSubcategoryScore.bind(this);
        this.app.getSubcategoryScore = this.getSubcategoryScore.bind(this);
        this.app.getCategoryAverage = this.getCategoryAverage.bind(this);
        this.app.updateSubcategoryScore = this.updateSubcategoryScore.bind(this);
        this.app.saveEvaluation = this.saveEvaluation.bind(this);
        this.app.closeEvaluationModal = this.closeEvaluationModal.bind(this);
    }
    
    handleEvaluateClick(playerId) {
        const player = this.app.teamPlayers.find(p => p.id == playerId);
        if (player) {
            this.evaluatePlayer(player);
        }
    }
    
    async evaluatePlayer(player) {
        this.app.evaluatingPlayer = player;
        this.app.currentEvaluationScores = {};
        this.app.showEvaluationModal = true;
        await this.loadEvaluations(player);
        this.initializeCurrentEvaluationScores();
    }
    
    async loadEvaluations(player, isClubView = false) {
        try {
            const team = isClubView ? this.app.selectedClubTeam : this.app.selectedTeam;
            const action = isClubView ? 'cm_get_club_player_evaluations' : 'cm_get_evaluations';
            
            const data = await this.app.apiPost(action, {
                player_id: player.id,
                team_id: team.id,
                season: this.app.currentSeason
            });
            
            this.app.evaluations[player.id] = data.evaluations || [];
            
        } catch (error) {
            console.error('Error loading evaluations:', error);
        }
    }
    
    initializeCurrentEvaluationScores() {
        this.app.evaluationCategories.forEach(category => {
            category.subcategories.forEach(sub => {
                const key = category.key + '_' + sub.key;
                this.app.currentEvaluationScores[key] = this.getLastSubcategoryScore(category.key, sub.key);
            });
        });
    }
    
    getLastSubcategoryScore(categoryKey, subcategoryKey) {
        if (!this.app.evaluatingPlayer || !this.app.evaluations[this.app.evaluatingPlayer.id]) return 5;
        
        const evaluations = this.app.evaluations[this.app.evaluatingPlayer.id]
            .filter(e => e.category === categoryKey && e.subcategory === subcategoryKey)
            .sort((a, b) => new Date(b.evaluated_at) - new Date(a.evaluated_at));
        
        return evaluations.length > 0 ? parseFloat(evaluations[0].score) : 5;
    }
    
    getSubcategoryScore(categoryKey, subcategoryKey) {
        const key = categoryKey + '_' + subcategoryKey;
        return this.app.currentEvaluationScores[key] || 5;
    }
    
    getCategoryAverage(categoryKey) {
        const category = this.app.evaluationCategories.find(c => c.key === categoryKey);
        if (!category) return '5.0';
        
        let scores = [];
        category.subcategories.forEach(sub => {
            const score = this.getSubcategoryScore(categoryKey, sub.key);
            scores.push(score);
        });
        
        if (scores.length === 0) return '5.0';
        
        const average = scores.reduce((a, b) => a + b, 0) / scores.length;
        return average.toFixed(1);
    }
    
    updateSubcategoryScore(categoryKey, subcategoryKey, score) {
        const key = categoryKey + '_' + subcategoryKey;
        this.app.currentEvaluationScores[key] = parseFloat(score);
    }
    
    async saveEvaluation(event) {
        const button = event?.target?.closest('button');
        this.app.setButtonLoading(button, true, 'Save Evaluation');
        
        await this.app.withLoading(async () => {
            const savePromises = [];
            
            for (const category of this.app.evaluationCategories) {
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
                this.app.currentEvaluationScores = {};
                
                // Reload evaluations to get fresh data from database
                await this.loadEvaluations(this.app.evaluatingPlayer);
                
                // If player card is open for the same player, refresh that too
                if (this.app.viewingPlayer && this.app.viewingPlayer.id === this.app.evaluatingPlayer.id) {
                    await this.app.playerCardModule.loadEvaluationHistory(this.app.viewingPlayer);
                    
                    // Force update spider chart with new data
                    this.app.playerCardModule.forceUpdateSpiderChart();
                    
                    // Clear old advice and set status
                    this.app.playerAdvice = null;
                    this.app.adviceStatus = 'generating';
                    this.app.adviceLoading = false;
                    
                    // Force UI update
                    await this.app.$nextTick();
                    
                    // Start polling for new advice after delay
                    setTimeout(() => {
                        this.app.playerCardModule.pollForAdvice(this.app.viewingPlayer);
                    }, 2000);
                }
                
                this.closeEvaluationModal();
                alert('Evaluation saved successfully! AI advice is being generated...');
                
            } catch (error) {
                alert('Error saving evaluation. Please try again.');
            } finally {
                this.app.setButtonLoading(button, false, 'Save Evaluation');
            }
        }, 'Saving evaluation...');
    }
    
    async saveEvaluationScore(category, subcategory, score) {
        const data = {
            player_id: this.app.evaluatingPlayer.id,
            team_id: this.app.selectedTeam.id,
            season: this.app.currentSeason,
            category: category,
            score: score,
            notes: this.app.evaluationNotes
        };
        
        if (subcategory) {
            data.subcategory = subcategory;
        }
        
        const result = await this.app.apiPost('cm_save_evaluation', data);
        
        if (!result) {
            throw new Error('Failed to save evaluation');
        }
        
        return result;
    }
    
    closeEvaluationModal() {
        this.app.showEvaluationModal = false;
        this.app.evaluatingPlayer = null;
        this.app.evaluationNotes = '';
        this.app.currentEvaluationScores = {};
    }
}