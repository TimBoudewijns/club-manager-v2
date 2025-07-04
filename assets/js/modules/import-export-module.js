// Import/Export Module
class ImportExportModule {
    constructor(app) {
        this.app = app;
        this.initializeData();
    }
    
    initializeData() {
        Object.assign(this.app, {
            // Import/Export state
            showImportExportModal: false,
            importExportMode: 'import', // 'import' or 'export'
            
            // Import wizard state
            importWizardStep: 1,
            importType: '', // teams, players, teams-with-players, trainers, trainers-with-assignments
            importFile: null,
            importFileData: null,
            importMapping: {},
            importPreviewData: [],
            importOptions: {
                duplicateHandling: 'skip', // skip, update, create
                sendInvitations: true,
                validateEmails: true,
                dateFormat: 'DD-MM-YYYY'
            },
            importProgress: {
                total: 0,
                processed: 0,
                successful: 0,
                failed: 0,
                errors: [],
                isProcessing: false,
                isPaused: false,
                sessionId: null
            },
            importResults: {
                created: 0,
                updated: 0,
                skipped: 0,
                failed: 0,
                errors: []
            },
            
            // Export state
            exportType: 'teams', // teams, players, trainers
            exportFilters: {
                season: '',
                teamIds: [],
                includeEvaluations: false
            },
            exportFormat: 'csv', // csv, xlsx
            
            // Field mappings with common header variations
            fieldMappings: {
                // Teams
                'name': ['name', 'team_name', 'team name', 'teamname'],
                'coach': ['coach', 'coach_name', 'coach name', 'trainer', 'head_coach'],
                'season': ['season', 'year', 'seizoen'],
                
                // Players
                'first_name': ['first_name', 'firstname', 'first name', 'voornaam', 'fname'],
                'last_name': ['last_name', 'lastname', 'last name', 'achternaam', 'lname'],
                'email': ['email', 'email_address', 'email address', 'e-mail', 'emailadres'],
                'birth_date': ['birth_date', 'birthdate', 'birth date', 'date_of_birth', 'dob', 'geboortedatum'],
                'position': ['position', 'pos', 'positie'],
                'jersey_number': ['jersey_number', 'jersey', 'number', 'shirt_number', 'rugnummer'],
                'team_name': ['team_name', 'team', 'team name', 'teamname'],
                
                // Trainers
                'team_names': ['team_names', 'teams', 'team names', 'assigned_teams']
            },
            availableFields: {
                teams: [
                    { key: 'name', label: 'Team Name', required: true },
                    { key: 'coach', label: 'Coach', required: true },
                    { key: 'season', label: 'Season', required: true }
                ],
                players: [
                    { key: 'first_name', label: 'First Name', required: true },
                    { key: 'last_name', label: 'Last Name', required: true },
                    { key: 'email', label: 'Email', required: true },
                    { key: 'birth_date', label: 'Birth Date', required: true },
                    { key: 'position', label: 'Position', required: false },
                    { key: 'jersey_number', label: 'Jersey Number', required: false },
                    { key: 'team_name', label: 'Team Name', required: false }
                ],
                trainers: [
                    { key: 'email', label: 'Email', required: true },
                    { key: 'team_names', label: 'Team Names (comma separated)', required: false }
                ],
                'teams-with-players': [
                    { key: 'name', label: 'Team Name', required: true },
                    { key: 'coach', label: 'Coach', required: true },
                    { key: 'season', label: 'Season', required: true }
                ],
                'trainers-with-assignments': [
                    { key: 'email', label: 'Email', required: true },
                    { key: 'team_names', label: 'Team Names (comma separated)', required: false }
                ]
            },
            
            // Templates - Headers must match field keys EXACTLY
            importTemplates: {
                teams: 'name,coach,season\n"Example Team","John Doe","2024-2025"',
                players: 'first_name,last_name,email,birth_date,position,jersey_number,team_name\n"John","Doe","john@example.com","01-01-2005","Forward","10","Example Team"',
                trainers: 'email,team_names\n"trainer@example.com","Team A, Team B"'
            }
        });
        
        // Bind methods
        this.bindMethods();
    }
    
    bindMethods() {
        // Import methods
        this.app.openImportExport = this.openImportExport.bind(this);
        this.app.switchImportExportMode = this.switchImportExportMode.bind(this);
        this.app.selectImportType = this.selectImportType.bind(this);
        this.app.handleFileUpload = this.handleFileUpload.bind(this);
        this.app.parseImportFile = this.parseImportFile.bind(this);
        this.app.autoMapColumns = this.autoMapColumns.bind(this);
        this.app.validateImportData = this.validateImportData.bind(this);
        this.app.startImport = this.startImport.bind(this);
        this.app.pauseImport = this.pauseImport.bind(this);
        this.app.resumeImport = this.resumeImport.bind(this);
        this.app.cancelImport = this.cancelImport.bind(this);
        this.app.resetImportWizard = this.resetImportWizard.bind(this);
        this.app.nextImportStep = this.nextImportStep.bind(this);
        this.app.previousImportStep = this.previousImportStep.bind(this);
        this.app.downloadTemplate = this.downloadTemplate.bind(this);
        
        // Export methods
        this.app.exportData = this.exportData.bind(this);
        this.app.toggleExportTeam = this.toggleExportTeam.bind(this);
        
        // Helper methods
        this.app.getFieldLabel = this.getFieldLabel.bind(this);
        this.app.isFieldRequired = this.isFieldRequired.bind(this);
        this.app.formatProgress = this.formatProgress.bind(this);
        this.app.isTrainerImport = this.isTrainerImport.bind(this);
        this.app.getImportTypeFields = this.getImportTypeFields.bind(this);
    }
    
    // Open import/export modal
    openImportExport(mode = 'import') {
        this.app.importExportMode = mode;
        this.app.showImportExportModal = true;
        this.resetImportWizard();
        
        if (mode === 'export') {
            this.loadExportData();
        }
    }
    
    // Switch between import and export modes
    switchImportExportMode(mode) {
        this.app.importExportMode = mode;
        if (mode === 'export') {
            this.loadExportData();
        }
    }
    
    // Select import type
    selectImportType(type) {
        this.app.importType = type;
        this.app.importWizardStep = 2;
    }
    
    // Handle file upload
    async handleFileUpload(event) {
        const file = event.target.files[0];
        if (!file) return;
        
        this.app.importFile = file;
        await this.parseImportFile();
    }
    
    // Parse import file
    async parseImportFile() {
        if (!this.app.importFile) return;
        
        const formData = new FormData();
        formData.append('file', this.app.importFile);
        
        try {
            const response = await this.app.apiPost('cm_parse_import_file', formData);
            this.app.importFileData = response;
            this.autoMapColumns();
            this.app.importWizardStep = 3;
        } catch (error) {
            alert('Error parsing file: ' + (error.message || 'Unknown error'));
            this.resetImportWizard();
        }
    }
    
    // Auto-map columns
    autoMapColumns() {
        if (!this.app.importFileData || !this.app.importFileData.headers) return;
        
        const headers = this.app.importFileData.headers;
        const fields = this.getImportTypeFields();
        this.app.importMapping = {};
        
        fields.forEach(field => {
            const possibleHeaders = this.app.fieldMappings[field.key] || [field.key.replace(/_/g, ' ')];
            const headerIndex = headers.findIndex(h => possibleHeaders.includes(h.toLowerCase().trim()));
            if (headerIndex !== -1) {
                this.app.importMapping[field.key] = headerIndex;
            }
        });
    }
    
    // Validate import data
    async validateImportData() {
        const data = {
            type: this.app.importType,
            mapping: JSON.stringify(this.app.importMapping),
            options: JSON.stringify(this.app.importOptions),
            sample_data: JSON.stringify(this.app.importFileData.rows.slice(0, 10))
        };

        try {
            const response = await this.app.apiPost('cm_validate_import_data', data);
            this.app.importPreviewData = response.preview || [];
            this.app.importProgress.total = this.app.importFileData.rows.length;
            this.app.importWizardStep = 4;
        } catch (error) {
            alert('Validation error: ' + (error.message || 'Unknown error'));
        }
    }
    
    // Start import process
    async startImport() {
        this.app.importProgress.isProcessing = true;
        this.app.importWizardStep = 5;
        
        try {
            const sessionData = {
                type: this.app.importType,
                mapping: this.app.importMapping,
                options: this.app.importOptions,
                file_data: this.app.importFileData
            };
            
            const initResponse = await this.app.apiPost('cm_init_import_session', sessionData);
            this.app.importProgress.sessionId = initResponse.session_id;
            await this.processImportBatch();
            
        } catch (error) {
            this.app.importProgress.errors.push({ row: 0, message: 'Import initialization failed: ' + (error.message || 'Unknown error') });
            this.app.importProgress.isProcessing = false;
        }
    }
    
    // Process import batch
    async processImportBatch() {
        if (!this.app.importProgress.isProcessing || this.app.importProgress.isPaused) return;

        try {
            const response = await this.app.apiPost('cm_process_import_batch', { session_id: this.app.importProgress.sessionId });
            
            this.app.importProgress.processed = response.processed || 0;
            this.app.importProgress.successful = response.successful || 0;
            this.app.importProgress.failed = response.failed || 0;
            if (response.errors) this.app.importProgress.errors.push(...response.errors);

            if (response.complete) {
                this.app.importProgress.isProcessing = false;
                this.app.importResults = response.results;
                this.app.importWizardStep = 6;
                await this.refreshDataAfterImport();
            } else {
                setTimeout(() => this.processImportBatch(), 100);
            }
        } catch (error) {
            this.app.importProgress.errors.push({ row: 0, message: 'Batch processing failed: ' + (error.message || 'Unknown error') });
            const remaining = this.app.importProgress.total - this.app.importProgress.processed;
            this.app.importProgress.failed += remaining;
            this.app.importProgress.processed = this.app.importProgress.total;
            
            this.app.importResults.failed = this.app.importProgress.failed;
            this.app.importResults.errors = this.app.importProgress.errors;
            
            this.app.importProgress.isProcessing = false;
            this.app.importWizardStep = 6;
        }
    }
    
    // Pause/Resume/Cancel
    pauseImport() { this.app.importProgress.isPaused = true; }
    resumeImport() { this.app.importProgress.isPaused = false; this.processImportBatch(); }
    async cancelImport() {
        if (this.app.importProgress.sessionId) {
            await this.app.apiPost('cm_cancel_import_session', { session_id: this.app.importProgress.sessionId });
        }
        this.resetImportWizard();
        this.app.showImportExportModal = false;
    }
    
    // Reset wizard
    resetImportWizard() {
        Object.assign(this.app, {
            importWizardStep: 1, importType: '', importFile: null, importFileData: null, importMapping: {}, importPreviewData: [],
            importProgress: { total: 0, processed: 0, successful: 0, failed: 0, errors: [], isProcessing: false, isPaused: false, sessionId: null },
            importResults: { created: 0, updated: 0, skipped: 0, failed: 0, errors: [] }
        });
        const fileInput = document.getElementById('import-file-input');
        if (fileInput) fileInput.value = '';
    }
    
    // Navigation
    nextImportStep() {
        if (this.app.importWizardStep === 3) {
            const requiredFields = this.getImportTypeFields().filter(f => f.required);
            const missing = requiredFields.some(f => this.app.importMapping[f.key] === undefined);
            if (missing) {
                alert('Please map all required fields.');
                return;
            }
            this.validateImportData();
        } else if (this.app.importWizardStep < 6) {
            this.app.importWizardStep++;
        }
    }
    previousImportStep() { if (this.app.importWizardStep > 1) this.app.importWizardStep--; }
    
    // Download template
    downloadTemplate(type) {
        const content = '\uFEFF' + this.app.importTemplates[type];
        const blob = new Blob([content], { type: 'text/csv;charset=utf-8' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `club_manager_${type}_template.csv`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    }
    
    // Export functionality
    async loadExportData() {
        if (!this.app.myTeams || this.app.myTeams.length === 0) {
            await this.app.teamModule.loadMyTeams();
        }
        this.app.exportFilters.season = this.app.currentSeason;
        this.app.exportFilters.teamIds = [];
    }
    async exportData() {
        try {
            const data = {
                type: this.app.exportType,
                format: this.app.exportFormat,
                filters: this.app.exportFilters
            };
            const response = await this.app.apiPost('cm_export_data', data);
            const blob = new Blob([response.data], { type: this.app.exportFormat === 'csv' ? 'text/csv' : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = response.filename || `export.${this.app.exportFormat}`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
            this.app.showImportExportModal = false;
        } catch (error) {
            alert('Export failed: ' + (error.message || 'Unknown error'));
        }
    }
    toggleExportTeam(teamId) {
        const index = this.app.exportFilters.teamIds.indexOf(teamId);
        if (index > -1) this.app.exportFilters.teamIds.splice(index, 1);
        else this.app.exportFilters.teamIds.push(teamId);
    }
    
    // Helpers
    async refreshDataAfterImport() {
        if (this.app.activeTab === 'player-management' && this.app.teamModule) {
            await this.app.teamModule.loadMyTeams();
        }
        if (this.app.activeTab === 'trainer-management' && this.app.trainerModule) {
            await this.app.trainerModule.loadTrainerManagementData();
        }
    }
    getFieldLabel(key) { return (this.getImportTypeFields().find(f => f.key === key) || {}).label || key; }
    isFieldRequired(key) { return (this.getImportTypeFields().find(f => f.key === key) || {}).required || false; }
    formatProgress() { return this.app.importProgress.total === 0 ? '0%' : ((this.app.importProgress.processed / this.app.importProgress.total) * 100).toFixed(1) + '%'; }
    isTrainerImport() { return this.app.importType.includes('trainer'); }
    getImportTypeFields() {
        const baseType = this.app.importType.replace('-with-players', '').replace('-with-assignments', '');
        return this.app.availableFields[baseType] || [];
    }
}