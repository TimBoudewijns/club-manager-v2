// Player Card Module - Handles player card display and functionality
class PlayerCardModule {
    constructor(app) {
        this.app = app;
        this.initializeData();
    }
    
    initializeData() {
        Object.assign(this.app, {
            // Player card data
            playerCardChart: null,
            modalPlayerCardChart: null,
            playerEvaluationHistory: [],
            availableEvaluationDates: [],
            selectedEvaluationDate: 'all',
            playerAdvice: null,
            adviceLoading: false,
            adviceStatus: 'no_evaluations',
            lastAdviceTimestamp: null,
            
            // Modal specific data
            showPlayerCardModal: false,
            modalViewingPlayer: null
        });
        
        this.bindMethods();
    }
    
    bindMethods() {
        this.app.viewPlayerCard = this.viewPlayerCard.bind(this);
        this.app.handlePlayerCardClick = this.handlePlayerCardClick.bind(this);
        this.app.viewPlayerCardInModal = this.viewPlayerCardInModal.bind(this);
        this.app.closePlayerCardModal = this.closePlayerCardModal.bind(this);
        this.app.createSpiderChart = this.createSpiderChart.bind(this);
        this.app.createModalSpiderChart = this.createModalSpiderChart.bind(this);
        this.app.forceUpdateSpiderChart = this.forceUpdateSpiderChart.bind(this);
        this.app.loadEvaluationHistory = this.loadEvaluationHistory.bind(this);
        this.app.getFilteredEvaluationHistory = this.getFilteredEvaluationHistory.bind(this);
        this.app.getSubcategoryEvaluations = this.getSubcategoryEvaluations.bind(this);
        this.app.formatSubcategoryName = this.formatSubcategoryName.bind(this);
        this.app.onEvaluationDateChange = this.onEvaluationDateChange.bind(this);
        this.app.getPlayerCardCategoryAverage = this.getPlayerCardCategoryAverage.bind(this);
        this.app.loadPlayerAdvice = this.loadPlayerAdvice.bind(this);
        this.app.generatePlayerAdvice = this.generatePlayerAdvice.bind(this);
        this.app.pollForAdvice = this.pollForAdvice.bind(this);
        this.app.downloadPlayerCardPDF = this.downloadPlayerCardPDF.bind(this);
    }
    
    handlePlayerCardClick(playerId, isClubView = false) {
        const players = isClubView ? this.app.clubTeamPlayers : this.app.teamPlayers;
        const player = players.find(p => p.id == playerId);
        if (player) {
            this.viewPlayerCard(player, isClubView);
        }
    }
    
    async viewPlayerCardInModal(playerId) {
        const player = this.app.teamPlayers.find(p => p.id == playerId);
        if (!player) return;
        
        // Set modal player
        this.app.modalViewingPlayer = player;
        
        // Destroy existing modal chart if any
        if (this.app.modalPlayerCardChart) {
            this.app.modalPlayerCardChart.destroy();
            this.app.modalPlayerCardChart = null;
        }
        
        // Load evaluations first
        await this.app.evaluationModule.loadEvaluations(player, false);
        await this.loadEvaluationHistory(player, false);
        
        // Load AI advice
        await this.loadPlayerAdvice(player, false);
        
        // Show modal
        this.app.showPlayerCardModal = true;
        
        // Wait for Alpine to update the DOM
        await this.app.$nextTick();
        
        // Wait a bit more for the DOM to be ready and try to create chart
        setTimeout(() => {
            this.createModalSpiderChart();
        }, 500);
    }
    
    closePlayerCardModal() {
        this.app.showPlayerCardModal = false;
        this.app.modalViewingPlayer = null;
        
        // Destroy modal chart
        if (this.app.modalPlayerCardChart) {
            this.app.modalPlayerCardChart.destroy();
            this.app.modalPlayerCardChart = null;
        }
        
        // Reset data
        this.app.playerAdvice = null;
        this.app.adviceStatus = 'no_evaluations';
        this.app.adviceLoading = false;
    }
    
    async viewPlayerCard(player, isClubView = false) {
        // If clicking same player, toggle card
        if ((isClubView && this.app.viewingClubPlayer && this.app.viewingClubPlayer.id === player.id) ||
            (!isClubView && this.app.viewingPlayer && this.app.viewingPlayer.id === player.id)) {
            if (isClubView) {
                this.app.viewingClubPlayer = null;
                this.app.selectedClubPlayerCard = null;
            } else {
                this.app.viewingPlayer = null;
                this.app.selectedPlayerCard = null;
            }
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
        
        if (isClubView) {
            this.app.viewingClubPlayer = player;
            this.app.selectedClubPlayerCard = this.app.selectedClubTeam;
        } else {
            this.app.viewingPlayer = player;
            this.app.selectedPlayerCard = this.app.selectedTeam;
        }
        
        // Load evaluations first
        await this.app.evaluationModule.loadEvaluations(player, isClubView);
        await this.loadEvaluationHistory(player, isClubView);
        
        // Load AI advice
        await this.loadPlayerAdvice(player, isClubView);
        
        // Wait for Alpine to update the DOM
        await this.app.$nextTick();
        
        // Wait a bit more for the DOM to be ready and try to create chart
        setTimeout(() => {
            this.createSpiderChart(isClubView);
        }, 500);
    }
    
    createModalSpiderChart() {
        const canvas = document.getElementById('modalPlayerCardSpiderChart');
        
        if (!canvas || !this.app.modalViewingPlayer) {
            setTimeout(() => this.createModalSpiderChart(), 200);
            return;
        }
        
        if (canvas.offsetParent === null) {
            setTimeout(() => this.createModalSpiderChart(), 200);
            return;
        }
        
        if (typeof Chart === 'undefined') {
            setTimeout(() => this.createModalSpiderChart(), 200);
            return;
        }
        
        const ctx = canvas.getContext('2d');
        
        // Always destroy existing chart first
        if (this.app.modalPlayerCardChart && typeof this.app.modalPlayerCardChart.destroy === 'function') {
            try {
                this.app.modalPlayerCardChart.destroy();
                this.app.modalPlayerCardChart = null;
            } catch (e) {
                this.app.modalPlayerCardChart = null;
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
            chartLabel = 'Performance on ' + dateObj.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
        }
        
        try {
            this.app.modalPlayerCardChart = new Chart(ctx, {
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
            console.error('Error creating modal chart:', error);
        }
    }
    
    createSpiderChart(isClubView = false) {
        const canvasId = isClubView ? 'clubPlayerCardSpiderChart' : 'playerCardSpiderChart';
        const canvas = document.getElementById(canvasId);
        const viewingPlayer = isClubView ? this.app.viewingClubPlayer : this.app.viewingPlayer;
        
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
            chartLabel = 'Performance on ' + dateObj.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
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
        
        if (this.app.modalPlayerCardChart) {
            this.app.modalPlayerCardChart.destroy();
            this.app.modalPlayerCardChart = null;
        }
        
        setTimeout(() => {
            if (this.app.showPlayerCardModal) {
                this.createModalSpiderChart();
            } else {
                this.createSpiderChart(this.app.isViewingClubTeam);
            }
        }, 100);
    }
    
    async loadEvaluationHistory(player, isClubView = false) {
        try {
            const team = isClubView ? this.app.selectedClubTeam : this.app.selectedTeam;
            const action = isClubView ? 'cm_get_club_player_evaluations' : 'cm_get_evaluations';
            
            const data = await this.app.apiPost(action, {
                player_id: player.id,
                team_id: team.id,
                season: this.app.currentSeason
            });
            
            // Store all evaluations
            const allEvaluations = data.evaluations || [];
            this.app.evaluations[player.id] = allEvaluations;
            
            // Filter for main category scores only (no subcategory)
            this.app.playerEvaluationHistory = allEvaluations.filter(e => !e.subcategory);
            
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
        
        const selectedDate = this.app.selectedEvaluationDate;
        return this.app.playerEvaluationHistory.filter(e => 
            e.evaluated_at.startsWith(selectedDate)
        );
    }
    
    getSubcategoryEvaluations(category, evaluatedAt) {
        const viewingPlayer = this.app.modalViewingPlayer || (this.app.isViewingClubTeam ? this.app.viewingClubPlayer : this.app.viewingPlayer);
        if (!viewingPlayer || !this.app.evaluations[viewingPlayer.id]) {
            return [];
        }
        
        const evaluationDate = evaluatedAt.split(' ')[0];
        
        return this.app.evaluations[viewingPlayer.id].filter(e => 
            e.category === category && 
            e.subcategory && 
            e.evaluated_at.startsWith(evaluationDate)
        );
    }
    
    formatSubcategoryName(subcategoryKey) {
        return subcategoryKey
            .replace(/_/g, ' ')
            .split(' ')
            .map(w => w.charAt(0).toUpperCase() + w.slice(1))
            .join(' ');
    }
    
    onEvaluationDateChange() {
        this.forceUpdateSpiderChart();
    }
    
    getPlayerCardCategoryAverage(categoryKey) {
        const category = this.app.evaluationCategories.find(c => c.key === categoryKey);
        const viewingPlayer = this.app.modalViewingPlayer || (this.app.isViewingClubTeam ? this.app.viewingClubPlayer : this.app.viewingPlayer);
        
        if (!category || !viewingPlayer || !this.app.evaluations[viewingPlayer.id]) {
            return '5.0';
        }
        
        // Filter evaluations based on selected date
        let evaluationsToUse = this.app.evaluations[viewingPlayer.id];
        if (this.app.selectedEvaluationDate !== 'all') {
            const selectedDate = this.app.selectedEvaluationDate;
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
                if (this.app.selectedEvaluationDate !== 'all') {
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
    }
    
    async loadPlayerAdvice(player, isClubView = false) {
        this.app.adviceLoading = true;
        
        try {
            const team = isClubView ? this.app.selectedClubTeam : this.app.selectedTeam;
            const action = isClubView ? 'cm_get_club_player_advice' : 'cm_get_player_advice';
            
            const data = await this.app.apiPost(action, {
                player_id: player.id,
                team_id: team.id,
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
            await this.app.apiPost('cm_generate_player_advice', {
                player_id: player.id,
                team_id: this.app.selectedTeam.id,
                season: this.app.currentSeason
            });
        } catch (error) {
            console.error('Error generating advice:', error);
        }
    }
    
    async pollForAdvice(player, attempts = 0) {
        if (!player || attempts > 15) {
            this.app.adviceLoading = false;
            this.app.adviceStatus = 'generation_failed';
            return;
        }
        
        // Check if we're still viewing the same player
        const currentViewingPlayer = this.app.modalViewingPlayer || this.app.viewingPlayer;
        if (!currentViewingPlayer || currentViewingPlayer.id !== player.id) {
            return;
        }
        
        try {
            const data = await this.app.apiPost('cm_get_player_advice', {
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
    
    async downloadPlayerCardPDF(event, isClubView = false, isModal = false) {
        const viewingPlayer = isModal ? this.app.modalViewingPlayer : (isClubView ? this.app.viewingClubPlayer : this.app.viewingPlayer);
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
            const team = isClubView ? this.app.selectedClubTeam : this.app.selectedTeam;
            pdf.setFontSize(14);
            pdf.setTextColor.apply(pdf, mediumGray);
            pdf.text(team.name + ' - ' + this.app.currentSeason, pageWidth / 2, yPosition, { align: 'center' });
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
           const categories = this.app.evaluationCategories;
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
           if (this.app.playerAdvice && this.app.adviceStatus !== 'no_evaluations') {
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
               const splitAdvice = pdf.splitTextToSize(this.app.playerAdvice, contentWidth);
               pdf.text(splitAdvice, margin, yPosition);
               yPosition += splitAdvice.length * 4 + 10;
           }
           
           // Footer
           pdf.setFontSize(8);
           pdf.setTextColor.apply(pdf, mediumGray);
           pdf.text('Generated: ' + new Date().toLocaleDateString() + ' at ' + new Date().toLocaleTimeString(), pageWidth / 2, pageHeight - 10, { align: 'center' });
           
           // Save the PDF
           const fileName = playerName.replace(/\s+/g, '_') + '_' + this.app.currentSeason + '_PlayerCard.pdf';
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