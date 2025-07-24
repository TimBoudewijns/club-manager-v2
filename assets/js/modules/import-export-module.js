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
            importExportMode: 'import',
            
            // Import wizard state
            importWizardStep: 1,
            importType: '',
            importFile: null,
            importFileData: null,
            importTempKey: null,
            importMapping: {},
            importPreviewData: [],
            importOptions: {
                duplicateHandling: 'skip',
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
            exportType: 'teams',
            exportFilters: {
                season: '',
                teamIds: [],
                includeEvaluations: false
            },
            exportFormat: 'csv',
            
            // Field mappings - these match EXACTLY what the CSV parser outputs (lowercase with underscores)
            fieldMappings: {
                // Teams
                'name': ['name', 'team_name', 'teamname', 'naam'],
                'coach': ['coach', 'coach_name', 'trainer', 'head_coach'],
                'season': ['season', 'year', 'seizoen'],
                
                // Players
                'first_name': ['first_name', 'firstname', 'voornaam', 'fname'],
                'last_name': ['last_name', 'lastname', 'achternaam', 'lname'],
                'email': ['email', 'email_address', 'e-mail', 'emailadres'],
                'birth_date': ['birth_date', 'birthdate', 'date_of_birth', 'dob', 'geboortedatum'],
                'position': ['position', 'pos', 'positie'],
                'jersey_number': ['jersey_number', 'jersey', 'number', 'shirt_number', 'rugnummer'],
                'team_name': ['team_name', 'team', 'teamname', 'ploeg'],
                
                // Trainers
                'team_names': ['team_names', 'teams', 'assigned_teams', 'ploegen']
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
                    { key: 'team_names', label: 'Team Names (semicolon separated)', required: false }
                ],
                'teams-with-players': [
                    // Team fields
                    { key: 'team_name', label: 'Team Name', required: true },
                    { key: 'coach', label: 'Coach', required: true },
                    { key: 'season', label: 'Season', required: true },
                    // Player fields
                    { key: 'first_name', label: 'Player First Name', required: true },
                    { key: 'last_name', label: 'Player Last Name', required: true },
                    { key: 'email', label: 'Player Email', required: true },
                    { key: 'birth_date', label: 'Player Birth Date', required: true },
                    { key: 'position', label: 'Player Position', required: false },
                    { key: 'jersey_number', label: 'Jersey Number', required: false }
                ]
            },
            
            // CORRECTED Templates - NO QUOTES, proper formatting
            importTemplates: {
                teams: 'name,coach,season\nHockey Team Alpha,John Doe,2024-2025\nHockey Team Beta,Jane Smith,2024-2025\nHockey Team Gamma,Bob Wilson,2024-2025',
                players: 'first_name,last_name,email,birth_date,position,jersey_number,team_name\nJohn,Doe,john.doe@email.com,15-03-2005,Forward,10,Hockey Team Alpha\nJane,Smith,jane.smith@email.com,22-07-2006,Defense,5,Hockey Team Alpha\nBob,Johnson,bob.j@email.com,01-01-2005,Goalkeeper,1,Hockey Team Beta\nAlice,Wilson,alice.w@email.com,30-09-2005,Midfield,8,Hockey Team Beta',
                trainers: 'email,team_names\ntrainer1@club.com,Hockey Team Alpha\ntrainer2@club.com,Hockey Team Alpha;Hockey Team Beta\nheadcoach@club.com,Hockey Team Gamma',
                'teams-with-players': 'team_name,coach,season,first_name,last_name,email,birth_date,position,jersey_number\nHockey Team Alpha,John Doe,2024-2025,Emma,Johnson,emma.j@email.com,12-04-2005,Forward,9\nHockey Team Alpha,John Doe,2024-2025,Michael,Brown,michael.b@email.com,23-08-2006,Defense,4\nHockey Team Beta,Jane Smith,2024-2025,Sarah,Davis,sarah.d@email.com,05-11-2005,Goalkeeper,1\nHockey Team Beta,Jane Smith,2024-2025,James,Wilson,james.w@email.com,17-02-2006,Midfield,7'
            }
        });
        
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
    
    openImportExport(mode = 'import') {
        console.log('Opening import/export modal, mode:', mode);
        this.app.importExportMode = mode;
        this.app.showImportExportModal = true;
        this.resetImportWizard();
        
        if (mode === 'export') {
            this.loadExportData();
        }
    }
    
    switchImportExportMode(mode) {
        this.app.importExportMode = mode;
        if (mode === 'export') {
            this.loadExportData();
        }
    }
    
    selectImportType(type) {
        this.app.importType = type;
        this.app.importWizardStep = 2;
    }
    
    async handleFileUpload(event) {
        const file = event.target.files[0];
        if (!file) return;
        
        try {
            // Validate file extension
            const fileExtension = file.name.split('.').pop().toLowerCase();
            const validExtensions = ['csv'];
            
            if (!validExtensions.includes(fileExtension)) {
                alert('Please upload a CSV file. Excel files (.xls, .xlsx) are not supported.\n\nYou can save your Excel file as CSV by:\n1. Opening it in Excel\n2. Going to File > Save As\n3. Choosing "CSV (Comma delimited)" as the file type');
                event.target.value = '';
                return;
            }
            
            // Validate file size (max 10MB)
            if (file.size > 10 * 1024 * 1024) {
                alert('File size must be less than 10MB');
                event.target.value = '';
                return;
            }
            
            this.app.importFile = file;
            
            // Parse file
            await this.parseImportFile();
            
        } catch (error) {
            console.error('File upload error:', error);
            alert('Error uploading file: ' + error.message);
            event.target.value = '';
        }
    }
    
    async parseImportFile() {
        if (!this.app.importFile) return;
        
        try {
            const formData = new FormData();
            formData.append('file', this.app.importFile);
            formData.append('type', this.app.importType);
            
            const response = await this.app.apiPost('cm_parse_import_file', formData);
            
            if (!response) {
                throw new Error('No response from server');
            }
            
            console.log('Parsed file data:', response);
            
            this.app.importFileData = response;
            this.app.importTempKey = response.temp_key;
            
            // Auto-map columns
            this.autoMapColumns();
            
            // Move to mapping step
            this.app.importWizardStep = 3;
            
        } catch (error) {
            console.error('Error parsing file:', error);
            alert('Error parsing file: ' + error.message);
            this.app.importFile = null;
            const fileInput = document.getElementById('import-file-input');
            if (fileInput) {
                fileInput.value = '';
            }
        }
    }
    
    autoMapColumns() {
        if (!this.app.importFileData || !this.app.importFileData.headers) return;
        
        const headers = this.app.importFileData.headers;
        const fields = this.getImportTypeFields();
        
        this.app.importMapping = {};
        
        console.log('Auto-mapping - Headers:', headers);
        console.log('Auto-mapping - Fields:', fields);
        console.log('Auto-mapping - Import Type:', this.app.importType);
        
        // Special handling for teams-with-players
        if (this.app.importType === 'teams-with-players') {
            // Map team_name field specifically
            headers.forEach((header, index) => {
                const cleanHeader = header.toLowerCase().trim().replace(/[_\s]+/g, '_');
                
                // Map team_name (not just 'name')
                if (cleanHeader === 'team_name' || cleanHeader === 'team' || cleanHeader === 'teamname') {
                    this.app.importMapping['team_name'] = index;
                }
                // Map other team fields
                else if (cleanHeader === 'coach' || cleanHeader === 'coach_name' || cleanHeader === 'trainer') {
                    this.app.importMapping['coach'] = index;
                }
                else if (cleanHeader === 'season' || cleanHeader === 'year') {
                    this.app.importMapping['season'] = index;
                }
                // Map player fields
                else if (cleanHeader === 'first_name' || cleanHeader === 'firstname') {
                    this.app.importMapping['first_name'] = index;
                }
                else if (cleanHeader === 'last_name' || cleanHeader === 'lastname') {
                    this.app.importMapping['last_name'] = index;
                }
                else if (cleanHeader === 'email' || cleanHeader === 'email_address') {
                    this.app.importMapping['email'] = index;
                }
                else if (cleanHeader === 'birth_date' || cleanHeader === 'birthdate') {
                    this.app.importMapping['birth_date'] = index;
                }
                else if (cleanHeader === 'position' || cleanHeader === 'pos') {
                    this.app.importMapping['position'] = index;
                }
                else if (cleanHeader === 'jersey_number' || cleanHeader === 'jersey') {
                    this.app.importMapping['jersey_number'] = index;
                }
            });
        } else {
            // Normal mapping for other types
            headers.forEach((header, index) => {
                const cleanHeader = header.toLowerCase().trim().replace(/[_\s]+/g, '_');
                
                fields.forEach(field => {
                    // Skip if already mapped
                    if (this.app.importMapping[field.key] !== undefined) return;
                    
                    // Direct match with field key
                    if (cleanHeader === field.key) {
                        this.app.importMapping[field.key] = index;
                        return;
                    }
                    
                    // Check known variations
                    if (this.app.fieldMappings[field.key]) {
                        const variations = this.app.fieldMappings[field.key];
                        if (variations.includes(cleanHeader)) {
                            this.app.importMapping[field.key] = index;
                            return;
                        }
                    }
                });
            });
        }
        
        console.log('Auto-mapping result:', this.app.importMapping);
    }
    
    async validateImportData() {
        try {
            console.log('Validating data...');
            console.log('Mapping:', this.app.importMapping);
            console.log('Type:', this.app.importType);
            
            const data = {
                type: this.app.importType,
                mapping: this.app.importMapping,
                options: this.app.importOptions,
                temp_key: this.app.importTempKey
            };
            
            const response = await this.app.apiPost('cm_validate_import_data', data);
            
            if (!response) {
                throw new Error('Validation failed');
            }
            
            console.log('Validation response:', response);
            
            this.app.importPreviewData = response.preview || [];
            this.app.importProgress.total = response.total_rows || this.app.importFileData.total_rows;
            
            // Move to preview step
            this.app.importWizardStep = 4;
            
        } catch (error) {
            console.error('Validation error:', error);
            alert('Validation error: ' + error.message);
        }
    }

    async startImport() {
        if (this.app.importProgress.isProcessing) return;
        
        this.app.importProgress.isProcessing = true;
        this.app.importProgress.isPaused = false;
        this.app.importWizardStep = 5;
        
        try {
            const sessionData = {
                type: this.app.importType,
                mapping: this.app.importMapping,
                options: this.app.importOptions,
                temp_key: this.app.importTempKey
            };
            
            console.log('Starting import with:', sessionData);
            
            const initResponse = await this.app.apiPost('cm_init_import_session', sessionData);
            
            if (!initResponse || !initResponse.session_id) {
                throw new Error('Failed to initialize import session');
            }
            
            this.app.importProgress.sessionId = initResponse.session_id;
            
            // Process import in batches
            await this.processImportBatch();
            
        } catch (error) {
            console.error('Import error:', error);
            this.app.importProgress.errors.push({
                row: 0,
                message: 'Import initialization failed: ' + error.message
            });
            this.app.importProgress.isProcessing = false;
        }
    }
    
    async processImportBatch() {
        if (!this.app.importProgress.isProcessing || this.app.importProgress.isPaused) return;
        
        try {
            const response = await this.app.apiPost('cm_process_import_batch', {
                session_id: this.app.importProgress.sessionId
            });
            
            if (!response) {
                throw new Error('No response from server');
            }
            
            // Update progress
            this.app.importProgress.processed = response.processed || 0;
            this.app.importProgress.successful = response.successful || 0;
            this.app.importProgress.failed = response.failed || 0;
            
            // Add any new errors
            if (response.errors && response.errors.length > 0) {
                this.app.importProgress.errors.push(...response.errors);
            }
            
            // Check if complete
            if (response.complete) {
                this.app.importProgress.isProcessing = false;
                this.app.importResults = response.results || this.app.importResults;
                this.app.importWizardStep = 6;
                
                // Refresh data
                await this.refreshDataAfterImport();
            } else {
                // Continue with next batch
                setTimeout(() => this.processImportBatch(), 100);
            }
            
        } catch (error) {
            console.error('Batch processing error:', error);
            this.app.importProgress.errors.push({
                row: this.app.importProgress.processed,
                message: 'Batch processing failed: ' + error.message
            });
            this.app.importProgress.isProcessing = false;
        }
    }
    
    pauseImport() {
        this.app.importProgress.isPaused = true;
    }
    
    resumeImport() {
        this.app.importProgress.isPaused = false;
        this.processImportBatch();
    }
    
    async cancelImport() {
        if (this.app.importProgress.sessionId) {
            try {
                await this.app.apiPost('cm_cancel_import_session', {
                    session_id: this.app.importProgress.sessionId
                });
            } catch (error) {
                console.error('Error canceling import:', error);
            }
        }
        
        this.resetImportWizard();
        this.app.showImportExportModal = false;
    }
    
    resetImportWizard() {
        this.app.importWizardStep = 1;
        this.app.importType = '';
        this.app.importFile = null;
        this.app.importFileData = null;
        this.app.importTempKey = null;
        this.app.importMapping = {};
        this.app.importPreviewData = [];
        this.app.importProgress = {
            total: 0,
            processed: 0,
            successful: 0,
            failed: 0,
            errors: [],
            isProcessing: false,
            isPaused: false,
            sessionId: null
        };
        this.app.importResults = {
            created: 0,
            updated: 0,
            skipped: 0,
            failed: 0,
            errors: []
        };
        
        // Reset file input
        const fileInput = document.getElementById('import-file-input');
        if (fileInput) {
            fileInput.value = '';
        }
    }
    
    nextImportStep() {
        if (this.app.importWizardStep === 3) {
            // Validate mapping before proceeding
            const fields = this.getImportTypeFields();
            const requiredFields = fields.filter(f => f.required);
            
            const missingFields = requiredFields.filter(f => 
                this.app.importMapping[f.key] === undefined || 
                this.app.importMapping[f.key] === null || 
                this.app.importMapping[f.key] === ''
            );
            
            if (missingFields.length > 0) {
                alert('Please map all required fields: ' + missingFields.map(f => f.label).join(', '));
                return;
            }
            
            // Validate data
            this.validateImportData();
        } else if (this.app.importWizardStep < 6) {
            this.app.importWizardStep++;
        }
    }
    
    previousImportStep() {
        if (this.app.importWizardStep > 1) {
            this.app.importWizardStep--;
        }
    }
    
    downloadTemplate(type) {
        const template = this.app.importTemplates[type];
        if (!template) return;
        
        // Add UTF-8 BOM for Excel compatibility
        const BOM = '\uFEFF';
        const content = BOM + template;
        
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
    
    async loadExportData() {
        try {
            // Load teams for export filter
            if (!this.app.myTeams || this.app.myTeams.length === 0) {
                if (this.app.teamModule && typeof this.app.teamModule.loadMyTeams === 'function') {
                    await this.app.teamModule.loadMyTeams();
                }
            }
            
            // Reset filters
            this.app.exportFilters.season = this.app.currentSeason;
            this.app.exportFilters.teamIds = [];
            
        } catch (error) {
            console.error('Error loading export data:', error);
        }
    }
    
    async exportData() {
        try {
            // Check export format
            if (this.app.exportFormat !== 'csv') {
                alert('Excel export is not supported. Please choose CSV format.');
                return;
            }
            
            const data = {
                type: this.app.exportType,
                format: this.app.exportFormat,
                filters: this.app.exportFilters
            };
            
            const response = await this.app.apiPost('cm_export_data', data);
            
            if (!response) {
                throw new Error('Export failed - no response');
            }
            
            // Download file
            if (response.data) {
                const blob = new Blob([response.data], { 
                    type: 'text/csv;charset=utf-8' 
                });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = response.filename || `club_manager_export_${this.app.exportType}_${Date.now()}.csv`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
            }
            
            // Close modal
            this.app.showImportExportModal = false;
            
        } catch (error) {
            console.error('Export error:', error);
            alert('Export failed: ' + error.message);
        }
    }
    
    toggleExportTeam(teamId) {
        const index = this.app.exportFilters.teamIds.indexOf(teamId);
        if (index > -1) {
            this.app.exportFilters.teamIds.splice(index, 1);
        } else {
            this.app.exportFilters.teamIds.push(teamId);
        }
    }
    
    async refreshDataAfterImport() {
        switch (this.app.importType) {
            case 'teams':
            case 'teams-with-players':
                if (this.app.teamModule) {
                    await this.app.teamModule.loadMyTeams();
                }
                if (this.app.hasPermission('can_view_club_teams') && this.app.clubTeamsModule) {
                    await this.app.clubTeamsModule.loadClubTeams();
                }
                break;
                
            case 'players':
                if (this.app.selectedTeam && this.app.teamModule) {
                    await this.app.teamModule.loadTeamPlayers();
                }
                break;
                
            case 'trainers':
                if (this.app.activeTab === 'trainer-management' && this.app.trainerModule) {
                    await this.app.trainerModule.loadTrainerManagementData();
                }
                break;
        }
    }
    
    // Helper methods
    getFieldLabel(key) {
        const fields = this.getImportTypeFields();
        const field = fields.find(f => f.key === key);
        return field ? field.label : key;
    }
    
    isFieldRequired(key) {
        const fields = this.getImportTypeFields();
        const field = fields.find(f => f.key === key);
        return field ? field.required : false;
    }
    
    formatProgress() {
        if (this.app.importProgress.total === 0) return '0%';
        const percentage = (this.app.importProgress.processed / this.app.importProgress.total) * 100;
        return percentage.toFixed(1) + '%';
    }
    
    isTrainerImport() {
        return this.app.importType === 'trainers';
    }
    
    getImportTypeFields() {
        return this.app.availableFields[this.app.importType] || [];
    }
}