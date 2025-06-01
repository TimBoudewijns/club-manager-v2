// Evaluations module
import { API } from '../utils/api.js';

export class EvaluationsModule {
    constructor(app) {
        this.app = app;
    }
    
    init() {
        // Initialize evaluation-specific data
        Object.assign(this.app, {
            showEvaluationModal: false,
            evaluatingPlayer: null,
            evaluationNotes: '',
            currentEvaluationScores: {},
            evaluations: {}
        });
        
        // Bind methods to app context
        this.app.evaluatePlayer = this.evaluatePlayer.bind(this);
        this.app.handleEvaluateClick = this.handleEvaluateClick.bind(this);
        this.app.loadEvaluations = this.loadEvaluations.bind(this);
        this.app.saveEvaluation = this.saveEvaluation.bind(this);
        this.app.closeEvaluationModal = this.closeEvaluationModal.bind(this);
        this.app.initializeCurrentEvaluationScores = this.initializeCurrentEvaluationScores.bind(this);
        this.app.getLastSubcategoryScore = this.getLastSubcategoryScore.bind(this);
        this.app.getSubcategoryScore = this.getSubcategoryScore.bind(this);
        this.app.getCategoryAverage = this.getCategoryAverage.bind(this);
        this.app.updateSubcategoryScore = this.updateSubcategoryScore.bind(this);
    }
    
    handleEvaluateClick(playerId) {
        const player = this.app.teamPlayers.find(p => p.id == playerId);
        if (player) {
            this.evaluatePlayer(player);
        }
    }
    
    evaluatePlayer(player) {
        this.app.evaluatingPlayer = player;
        this.app.currentEvaluationScores = {};
        this.app.showEvaluationModal = true;
        this.loadEvaluations(player);
        this.initializeCurrentEvaluationScores();
        
        // Force DOM update for mobile
        this.app.$nextTick(() => {
            // Evaluation modal opened
        });
    }
    
    async loadEvaluations(player) {
        try {
            const data = await API.post('cm_get_evaluations', {
                player_id: player.id,
                team_id: this.app.selectedTeam.id,
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
                const key = `${category.key}_${sub.key}`;
                // Start with previous score or default to 5
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
        const key = `${categoryKey}_${subcategoryKey}`;
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
        const key = `${categoryKey}_${subcategoryKey}`;
        this.app.currentEvaluationScores[key] = parseFloat(score);
    }
    
    async saveEvaluation() {
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
        }
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
        
        const result = await API.post('cm_save_evaluation', data);
        
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
