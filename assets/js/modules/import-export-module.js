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
        console.log('Opening import/export modal, mode:', mode);
        console.log('User permissions:', this.app.userPermissions);
        console.log('Can import/export:', this.app.hasPermission('can_import_export'));
        
        this.app.importExportMode = mode;
        this.app.showImportExportModal = true;
        this.resetImportWizard();
        
        // Load initial data if export mode
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
        
        try {
            // Validate file type
            const validTypes = ['text/csv', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
            const fileExtension = file.name.split('.').pop().toLowerCase();
            
            // Check by extension if MIME type check fails
            const validExtensions = ['csv', 'xls', 'xlsx'];
            if (!validTypes.includes(file.type) && !validExtensions.includes(fileExtension)) {
                alert('Please upload a CSV or Excel file');
                event.target.value = ''; // Reset input
                return;
            }
            
            // Validate file size (max 10MB)
            if (file.size > 10 * 1024 * 1024) {
                alert('File size must be less than 10MB');
                event.target.value = ''; // Reset input
                return;
            }
            
            this.app.importFile = file;
            
            // Parse file
            await this.parseImportFile();
            
        } catch (error) {
            console.error('File upload error:', error);
            alert('Error uploading file: ' + error.message);
            event.target.value = ''; // Reset input
        }
    }
    
    // Parse import file
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
            
            // Debug logging
            console.log('Parsed file data:', response);
            console.log('Headers:', response.headers);
            console.log('First row:', response.rows[0]);
            
            this.app.importFileData = response;
            
            // Auto-map columns
            this.autoMapColumns();
            
            // Move to mapping step
            this.app.importWizardStep = 3;
            
        } catch (error) {
            console.error('Error parsing file:', error);
            alert('Error parsing file: ' + error.message);
            // Reset file input
            this.app.importFile = null;
            const fileInput = document.getElementById('import-file-input');
            if (fileInput) {
                fileInput.value = '';
            }
        }
    }
    
    // Auto-map columns based on headers - ENHANCED with mapping variations
    autoMapColumns() {
        if (!this.app.importFileData || !this.app.importFileData.headers) return;
        
        const headers = this.app.importFileData.headers;
        const fields = this.getImportTypeFields();
        
        this.app.importMapping = {};
        
        // Try to match headers to fields
        headers.forEach((header, index) => {
            const cleanHeader = header.toLowerCase().trim();
            
            // Check each field
            fields.forEach(field => {
                // Skip if already mapped
                if (this.app.importMapping[field.key] !== undefined) return;
                
                // Check if header matches any known variations for this field
                if (this.app.fieldMappings[field.key]) {
                    const variations = this.app.fieldMappings[field.key];
                    if (variations.includes(cleanHeader)) {
                        this.app.importMapping[field.key] = index;
                        return;
                    }
                }
                
                // Try exact match with field key
                if (cleanHeader === field.key || 
                    cleanHeader === field.key.replace(/_/g, ' ')) {
                    this.app.importMapping[field.key] = index;
                    return;
                }
                
                // Try normalized matching
                const normalizedHeader = cleanHeader.replace(/[^a-z0-9]/g, '');
                const normalizedField = field.label.toLowerCase().replace(/[^a-z0-9]/g, '');
                const normalizedKey = field.key.replace(/_/g, '');
                
                if (normalizedHeader === normalizedField || normalizedHeader === normalizedKey) {
                    this.app.importMapping[field.key] = index;
                }
            });
        });
        
        // Log mapping for debugging
        console.log('Auto-mapping results:', this.app.importMapping);
        console.log('Headers:', headers);
        console.log('Fields:', fields);
    }
    
    // Validate import data
    async validateImportData() {
        try {
            // Debug logging
            console.log('Validating with mapping:', this.app.importMapping);
            console.log('Import type:', this.app.importType);
            console.log('Sample data:', this.app.importFileData.rows.slice(0, 3));
            
            const data = {
                type: this.app.importType,
                mapping: this.app.importMapping,
                options: this.app.importOptions,
                sample_data: this.app.importFileData.rows.slice(0, 10) // Send first 10 rows for validation
            };
            
            const response = await this.app.apiPost('cm_validate_import_data', data);
            
            if (!response) {
                throw new Error('Validation failed');
            }
            
            console.log('Validation response:', response);
            
            this.app.importPreviewData = response.preview || [];
            this.app.importProgress.total = response.total_rows || this.app.importFileData.rows.length;
            
            // Move to preview step
            this.app.importWizardStep = 4;
            
        } catch (error) {
            console.error('Validation error:', error);
            alert('Validation error: ' + error.message);
        }
    }
    
    // Start import process
    async startImport() {
        if (this.app.importProgress.isProcessing) return;
        
        this.app.importProgress.isProcessing = true;
        this.app.importProgress.isPaused = false;
        this.app.importWizardStep = 5;
        
        try {
            // Initialize import session
            const sessionData = {
                type: this.app.importType,
                mapping: this.app.importMapping,
                options: this.app.importOptions,
                file_data: this.app.importFileData
            };
            
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
    
    // Process import batch
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
                
                // Refresh data based on import type
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
    
    // Pause import
    pauseImport() {
        this.app.importProgress.isPaused = true;
    }
    
    // Resume import
    resumeImport() {
        this.app.importProgress.isPaused = false;
        this.processImportBatch();
    }
    
    // Cancel import
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
    
    // Reset import wizard
    resetImportWizard() {
        this.app.importWizardStep = 1;
        this.app.importType = '';
        this.app.importFile = null;
        this.app.importFileData = null;
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
    
    // Navigate wizard steps
    nextImportStep() {
        if (this.app.importWizardStep === 3) {
            // Validate mapping before proceeding
            const fields = this.getImportTypeFields();
            const requiredFields = fields.filter(f => f.required);
            
            console.log('Required fields:', requiredFields);
            console.log('Current mapping:', this.app.importMapping);
            
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
    
    // Download template
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
    
    // Load export data
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
    
    // Export data
    async exportData() {
        try {
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
            if (response.download_url) {
                window.location.href = response.download_url;
            } else if (response.data) {
                // Create blob and download
                const blob = new Blob([response.data], { 
                    type: this.app.exportFormat === 'csv' ? 'text/csv' : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' 
                });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `club_manager_export_${this.app.exportType}_${Date.now()}.${this.app.exportFormat}`;
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
    
    // Toggle team selection for export
    toggleExportTeam(teamId) {
        const index = this.app.exportFilters.teamIds.indexOf(teamId);
        if (index > -1) {
            this.app.exportFilters.teamIds.splice(index, 1);
        } else {
            this.app.exportFilters.teamIds.push(teamId);
        }
    }
    
    // Refresh data after import
    async refreshDataAfterImport() {
        switch (this.app.importType) {
            case 'teams':
            case 'teams-with-players':
                if (this.app.teamModule && typeof this.app.teamModule.loadMyTeams === 'function') {
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
            case 'trainers-with-assignments':
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
        return this.app.importType === 'trainers' || 
               this.app.importType === 'trainers-with-assignments';
    }
    
    getImportTypeFields() {
        const baseType = this.app.importType
            .replace('-with-players', '')
            .replace('-with-assignments', '');
        return this.app.availableFields[baseType] || [];
    }
}