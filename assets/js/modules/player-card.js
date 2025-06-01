// Player Card module
import { API } from '../utils/api.js';
import { formatDate, formatSubcategoryName } from '../utils/helpers.js';

export class PlayerCardModule {
    constructor(app) {
        this.app = app;
    }
    
    init() {
        // Initialize player card specific data
        Object.assign(this.app, {
            playerCardChart: null,
            playerEvaluationHistory: [],
            availableEvaluationDates: [],
            selectedEvaluationDate: 'all',
            playerAdvice: null,
            adviceLoading: false,
            adviceStatus: 'no_evaluations',
            lastAdviceTimestamp: null
        });
        
        // Bind methods to app context
        this.app.viewPlayerCard = this.viewPlayerCard.bind(this);
        this.app.handlePlayerCardClick = this.handlePlayerCardClick.bind(this);
        this.app.createSpiderChart = this.createSpiderChart.bind(this);
        this.app.forceUpdateSpiderChart = this.forceUpdateSpiderChart.bind(this);
        this.app.loadEvaluationHistory = this.loadEvaluationHistory.bind(this);
        this.app.getFilteredEvaluationHistory = this.getFilteredEvaluationHistory.bind(this);
        this.app.getSubcategoryEvaluations = this.getSubcategoryEvaluations.bind(this);
        this.app.formatSubcategoryName = formatSubcategoryName;
        this.app.onEvaluationDateChange = this.onEvaluationDateChange.bind(this);
        this.app.getPlayerCardCategoryAverage = this.getPlayerCardCategoryAverage.bind(this);
        this.app.loadPlayerAdvice = this.loadPlayerAdvice.bind(this);
        this.app.generatePlayerAdvice = this.generatePlayerAdvice.bind(this);
        this.app.pollForAdvice = this.pollForAdvice.bind(this);
        this.app.downloadPlayerCardPDF = this.downloadPlayerCardPDF.bind(this);
    }
    
    handlePlayerCardClick(playerId) {
        const player = this.app.teamPlayers.find(p => p.id == playerId);
        if (player) {
            this.viewPlayerCard(player);
        }
    }
    
    async viewPlayerCard(player) {
        // If clicking same player, toggle card
        if (this.app.viewingPlayer && this.app.viewingPlayer.id === player.id) {
            this.app.viewingPlayer = null;
            this.app.selectedPlayerCard = null;
            if (this.app.playerCardChart) {
                this.app.playerCardChart.destroy();
                this.app.playerCardChart = null;
            }
            return;
        }
        
        // Destroy existing chart if any
        if (this.app.playerCardChart) {
            this.app.playerCardChart.destroy();
            this.app.playerCardChart = null;
        }
        
        this.app.viewingPlayer = player;
        this.app.selectedPlayerCard = this.app.selectedTeam;
        
        // Load evaluations first
        await this.app.loadEvaluations(player);
        await this.loadEvaluationHistory(player);
        
        // Load AI advice
        await this.loadPlayerAdvice(player);
        
        // Wait for Alpine to update the DOM
        await this.app.$nextTick();
        
        // Wait a bit more for the DOM to be ready and try to create chart
        setTimeout(() => {
            this.createSpiderChart();
        }, 500);
    }
    
    createSpiderChart() {
        const canvas = document.getElementById('playerCardSpiderChart');
        
        if (!canvas || !this.app.viewingPlayer) {
            setTimeout(() => this.createSpiderChart(), 200);
            return;
        }
        
        if (canvas.offsetParent === null) {
            setTimeout(() => this.createSpiderChart(), 200);
            return;
        }
        
        if (typeof Chart === 'undefined') {
            setTimeout(() => this.createSpiderChart(), 200);
            return;
        }
        
        const ctx = canvas.getContext('2d');
        
        // Always destroy existing chart first
        if (this.app.playerCardChart && typeof this.app.playerCardChart.destroy === 'function') {
            try {
                this.app.playerCardChart.destroy();
                this.app.playerCardChart = null;
            } catch (e) {
                this.app.playerCardChart = null;
            }
        }
        
        const labels = this.app.evaluationCategories.map(c => c.name);
        const data = this.app.evaluationCategories.map(c => {
            const avg = this.getPlayerCardCategoryAverage(c.key);
            return parseFloat(avg);
        });
        
        // Determine chart label based on selected date
        let chartLabel = 'Season Average Performance';
        if (this.app.selectedEvaluationDate !== 'all') {
            const dateObj = new Date(this.app.selectedEvaluationDate);
            chartLabel = `Performance on ${formatDate(this.app.selectedEvaluationDate)}`;
        }
        
        try {
            this.app.playerCardChart = new Chart(ctx, {
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
    }
    
    forceUpdateSpiderChart() {
        if (this.app.playerCardChart) {
            this.app.playerCardChart.destroy();
            this.app.playerCardChart = null;
        }
        
        // Small delay to ensure chart is destroyed
        setTimeout(() => {
            this.createSpiderChart();
        }, 100);
    }
    
    async loadEvaluationHistory(player) {
        try {
            const data = await API.post('cm_get_evaluations', {
                player_id: player.id,
                team_id: this.app.selectedTeam.id,
                season: this.app.currentSeason
            });
            
            // Store all evaluations
            const allEvaluations = data.evaluations || [];
            this.app.evaluations[player.id] = allEvaluations;
            
            // Filter for main category scores only (no subcategory)
            this.app.playerEvaluationHistory = allEvaluations.filter(e => !e.subcategory);
            
            // Get unique evaluation dates
            const dates = [...new Set(allEvaluations.map(e => e.evaluated_at.split(' ')[0]))];
            this.app.availableEvaluationDates = dates.sort((a, b) => new Date(b) - new Date(a));
            
            // Sort by date descending
            this.app.playerEvaluationHistory.sort((a, b) => new Date(b.evaluated_at) - new Date(a.evaluated_at));
            
            // Reset selected date to 'all'
            this.app.selectedEvaluationDate = 'all';
            
        } catch (error) {
            this.app.playerEvaluationHistory = [];
            this.app.availableEvaluationDates = [];
        }
    }
    
    getFilteredEvaluationHistory() {
        if (this.app.selectedEvaluationDate === 'all') {
            return this.app.playerEvaluationHistory;
        }
        
        return this.app.playerEvaluationHistory.filter(e => 
            e.evaluated_at.startsWith(this.app.selectedEvaluationDate)
        );
    }
    
    getSubcategoryEvaluations(category, evaluatedAt) {
        if (!this.app.viewingPlayer || !this.app.evaluations[this.app.viewingPlayer.id]) {
            return [];
        }
        
        const evaluationDate = evaluatedAt.split(' ')[0]; // Get just the date part
        
        return this.app.evaluations[this.app.viewingPlayer.id].filter(e => 
            e.category === category && 
            e.subcategory && 
            e.evaluated_at.startsWith(evaluationDate)
        );
    }
    
    onEvaluationDateChange() {
        // Force recreate the chart with new data
        this.forceUpdateSpiderChart();
    }
    
    getPlayerCardCategoryAverage(categoryKey) {
        const category = this.app.evaluationCategories.find(c => c.key === categoryKey);
        if (!category || !this.app.viewingPlayer || !this.app.evaluations[this.app.viewingPlayer.id]) {
            return '5.0';
        }
        
        // Filter evaluations based on selected date
        let evaluationsToUse = this.app.evaluations[this.app.viewingPlayer.id];
        if (this.app.selectedEvaluationDate !== 'all') {
            evaluationsToUse = evaluationsToUse.filter(e => 
                e.evaluated_at.startsWith(this.app.selectedEvaluationDate)
            );
        }
        
        // Try to get main category evaluations first
        const categoryEvaluations = evaluationsToUse.filter(e => 
            e.category === categoryKey && !e.subcategory
        );
        
        if (categoryEvaluations.length > 0) {
            // Calculate average of main category evaluations
            const sum = categoryEvaluations.reduce((acc, eval) => acc + parseFloat(eval.score), 0);
            const average = sum / categoryEvaluations.length;
            return average.toFixed(1);
        }
        
        // If no main category evaluations, calculate from subcategories
        let scores = [];
        category.subcategories.forEach(sub => {
            // Get evaluations for this subcategory
            const subEvaluations = evaluationsToUse.filter(e => 
                e.category === categoryKey && e.subcategory === sub.key
            );
            
            if (subEvaluations.length > 0) {
                if (this.app.selectedEvaluationDate !== 'all') {
                    // For specific date: use all evaluations from that date
                    const sum = subEvaluations.reduce((acc, eval) => acc + parseFloat(eval.score), 0);
                    scores.push(sum / subEvaluations.length);
                } else {
                    // For all dates: use the most recent
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
    }
    
    async loadPlayerAdvice(player) {
        this.app.adviceLoading = true;
        
        try {
            const data = await API.post('cm_get_player_advice', {
                player_id: player.id,
                team_id: this.app.selectedTeam.id,
                season: this.app.currentSeason
            });
            
            this.app.playerAdvice = data.advice;
            this.app.adviceStatus = data.status || 'no_evaluations';
            this.app.lastAdviceTimestamp = data.generated_at || null;
            
            // Check if we have evaluations but no advice yet
            if (!this.app.playerAdvice && this.app.evaluations[player.id] && this.app.evaluations[player.id].length > 0) {
                this.app.adviceStatus = 'no_advice_yet';
            }
            
        } catch (error) {
            console.error('Error loading advice:', error);
        }
        
        this.app.adviceLoading = false;
    }
    
    async generatePlayerAdvice(player) {
        try {
            await API.post('cm_generate_player_advice', {
                player_id: player.id,
                team_id: this.app.selectedTeam.id,
                season: this.app.currentSeason
            });
        } catch (error) {
            console.error('Error generating advice:', error);
        }
    }
    
    async pollForAdvice(player, attempts = 0) {
        if (!player || attempts > 15) { // 15 attempts (75 seconds)
            this.app.adviceLoading = false;
            this.app.adviceStatus = 'generation_failed';
            return;
        }
        
        // Check if we're still viewing the same player
        if (!this.app.viewingPlayer || this.app.viewingPlayer.id !== player.id) {
            return;
        }
        
        try {
            const data = await API.post('cm_get_player_advice', {
                player_id: player.id,
                team_id: this.app.selectedTeam.id,
                season: this.app.currentSeason
            });
            
            if (data.advice) {
                // Check if this is new advice
                const isNewAdvice = !this.app.lastAdviceTimestamp || 
                                   data.generated_at !== this.app.lastAdviceTimestamp;
                
                if (isNewAdvice) {
                    // New advice found!
                    this.app.playerAdvice = data.advice;
                    this.app.adviceStatus = 'current';
                    this.app.adviceLoading = false;
                    this.app.lastAdviceTimestamp = data.generated_at;
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
            this.app.adviceLoading = false;
            this.app.adviceStatus = 'error';
        }
    }
    
    async downloadPlayerCardPDF(event) {
        if (!this.app.viewingPlayer) return;
        
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
            const { jsPDF } = window.jspdf;
            const pdf = new jsPDF('p', 'mm', 'a4');
            
            // Generate PDF content (simplified version)
            const playerName = `${this.app.viewingPlayer.first_name} ${this.app.viewingPlayer.last_name}`;
            pdf.setFontSize(24);
            pdf.text(playerName, 105, 20, { align: 'center' });
            
            // Save the PDF
            const fileName = `${playerName.replace(/\s+/g, '_')}_${this.app.currentSeason}_PlayerCard.pdf`;
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
    }
} 
