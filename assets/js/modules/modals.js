// Modals module
export class ModalsModule {
    constructor(app) {
        this.app = app;
    }
    
    init() {
        // Set up modal watchers for body scroll locking
        this.setupModalWatchers();
    }
    
    setupModalWatchers() {
        // Fix for modals on mobile - prevent body scroll when modal is open
        this.app.$watch('showCreateTeamModal', value => {
            this.toggleBodyScroll(value);
        });
        
        this.app.$watch('showAddPlayerModal', value => {
            this.toggleBodyScroll(value);
        });
        
        this.app.$watch('showAddExistingPlayerModal', value => {
            this.toggleBodyScroll(value);
        });
        
        this.app.$watch('showEvaluationModal', value => {
            this.toggleBodyScroll(value);
        });
        
        this.app.$watch('showPlayerHistoryModal', value => {
            this.toggleBodyScroll(value);
        });
    }
    
    toggleBodyScroll(modalOpen) {
        if (modalOpen) {
            document.body.style.overflow = 'hidden';
            document.body.classList.add('modal-open');
        } else {
            document.body.style.overflow = '';
            document.body.classList.remove('modal-open');
        }
    }
    
    // Helper method to close all modals
    closeAllModals() {
        this.app.showCreateTeamModal = false;
        this.app.showAddPlayerModal = false;
        this.app.showAddExistingPlayerModal = false;
        this.app.showEvaluationModal = false;
        this.app.showPlayerHistoryModal = false;
        
        // Clean up body scroll
        document.body.style.overflow = '';
        document.body.classList.remove('modal-open');
    }
} 
