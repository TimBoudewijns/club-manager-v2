<!-- Import/Export Modal -->
<div x-show="showImportExportModal" 
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-50 overflow-y-auto" 
     style="display: none;"
     x-cloak>
    <div class="modal-container">
        <div class="modal-backdrop" @click="showImportExportModal = false"></div>
        
        <div class="modal-content bg-white rounded-2xl shadow-2xl max-w-4xl w-full overflow-hidden"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform scale-90"
             x-transition:enter-end="opacity-100 transform scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 transform scale-100"
             x-transition:leave-end="opacity-0 transform scale-90"
             @click.stop>
            
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-orange-500 to-orange-600 p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-semibold text-base">Import/Export Data</h3>
                        <p class="text-orange-100 mt-1">Bulk manage your club data</p>
                    </div>
                    <button @click="showImportExportModal = false; resetImportWizard()" 
                            class="text-white hover:text-orange-200 p-1.5 rounded-full hover:bg-white/10 transition-colors">
                        <svg class="w-4 h-4" style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <!-- Mode Tabs -->
                <div class="mt-6 flex space-x-2">
                    <button @click="switchImportExportMode('import')"
                            :class="importExportMode === 'import' ? 'bg-white text-orange-600' : 'bg-orange-400/20 text-white hover:bg-orange-400/30'"
                            class="px-6 py-2 rounded-lg font-medium transition-colors">
                        Import
                    </button>
                    <button @click="switchImportExportMode('export')"
                            :class="importExportMode === 'export' ? 'bg-white text-orange-600' : 'bg-orange-400/20 text-white hover:bg-orange-400/30'"
                            class="px-6 py-2 rounded-lg font-medium transition-colors">
                        Export
                    </button>
                </div>
            </div>
            
            <!-- Modal Content -->
            <div class="max-h-[calc(90vh-180px)] overflow-y-auto -webkit-overflow-scrolling-touch">
                
                <!-- Import Mode -->
                <div x-show="importExportMode === 'import'" class="p-6">
                    
                    <!-- Import Wizard Steps -->
                    <div class="mb-8">
                        <div class="flex items-center justify-between">
                            <template x-for="step in [1,2,3,4,5,6]" :key="step">
                                <div class="flex-1 flex items-center">
                                    <div class="relative">
                                        <div :class="importWizardStep >= step ? 'bg-orange-600 text-white' : 'bg-gray-200 text-gray-600'"
                                             class="w-10 h-10 rounded-full flex items-center justify-center font-bold transition-colors">
                                            <span x-text="step"></span>
                                        </div>
                                        <div x-show="importWizardStep > step" 
                                             class="absolute inset-0 flex items-center justify-center">
                                            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div x-show="step < 6" 
                                         :class="importWizardStep > step ? 'bg-orange-600' : 'bg-gray-200'"
                                         class="flex-1 h-1 mx-2 transition-colors"></div>
                                </div>
                            </template>
                        </div>
                        <div class="mt-2 grid grid-cols-6 text-xs text-center">
                            <div>Select Type</div>
                            <div>Upload File</div>
                            <div>Map Fields</div>
                            <div>Preview</div>
                            <div>Import</div>
                            <div>Results</div>
                        </div>
                    </div>
                    
                    <!-- Step 1: Select Import Type -->
                    <div x-show="importWizardStep === 1" class="space-y-6">
                        <h4 class="text-lg font-semibold text-gray-900">What would you like to import?</h4>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <button @click="selectImportType('teams')"
                                    class="p-6 border-2 border-gray-200 rounded-xl hover:border-orange-500 hover:bg-orange-50 transition-all group">
                                <div class="flex items-center space-x-4">
                                    <div class="p-3 bg-orange-100 rounded-lg group-hover:bg-orange-200 transition-colors">
                                        <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                        </svg>
                                    </div>
                                    <div class="text-left">
                                        <h5 class="font-semibold text-gray-900">Teams Only</h5>
                                        <p class="text-sm text-gray-600">Import team information</p>
                                    </div>
                                </div>
                            </button>
                            
                            <button @click="selectImportType('players')"
                                    class="p-6 border-2 border-gray-200 rounded-xl hover:border-orange-500 hover:bg-orange-50 transition-all group">
                                <div class="flex items-center space-x-4">
                                    <div class="p-3 bg-orange-100 rounded-lg group-hover:bg-orange-200 transition-colors">
                                        <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                        </svg>
                                    </div>
                                    <div class="text-left">
                                        <h5 class="font-semibold text-gray-900">Players Only</h5>
                                        <p class="text-sm text-gray-600">Import player profiles</p>
                                    </div>
                                </div>
                            </button>
                            
                            
                            <button @click="selectImportType('trainers')" x-show="hasPermission('can_manage_trainers')"
                                    class="p-6 border-2 border-gray-200 rounded-xl hover:border-orange-500 hover:bg-orange-50 transition-all group">
                                <div class="flex items-center space-x-4">
                                    <div class="p-3 bg-orange-100 rounded-lg group-hover:bg-orange-200 transition-colors">
                                        <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                        </svg>
                                    </div>
                                    <div class="text-left">
                                        <h5 class="font-semibold text-gray-900">Trainers</h5>
                                        <p class="text-sm text-gray-600">Import and invite trainers</p>
                                    </div>
                                </div>
                            </button>
                        </div>
                        
                        <!-- Download Templates -->
                        <div class="mt-8 p-4 bg-gray-50 rounded-lg">
                            <h5 class="font-medium text-gray-900 mb-2">Need a template?</h5>
                            <p class="text-sm text-gray-600 mb-3">Download our CSV templates to get started:</p>
                            <div class="flex flex-wrap gap-2">
                                <button @click="downloadTemplate('teams')" class="text-sm text-orange-600 hover:text-orange-700 underline">Teams Template</button>
                                <span class="text-gray-400">•</span>
                                <button @click="downloadTemplate('players')" class="text-sm text-orange-600 hover:text-orange-700 underline">Players Template</button>
                                <span class="text-gray-400">•</span>
                                <button @click="downloadTemplate('trainers')" x-show="hasPermission('can_manage_trainers')" class="text-sm text-orange-600 hover:text-orange-700 underline">Trainers Template</button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Step 2: Upload File -->
                    <div x-show="importWizardStep === 2" class="space-y-6">
                        <h4 class="text-lg font-semibold text-gray-900">Upload your CSV file</h4>
                        
                        <div class="border-2 border-dashed border-gray-300 rounded-xl p-8 text-center hover:border-orange-400 transition-colors">
                            <input type="file" 
                                   id="import-file-input"
                                   accept=".csv" 
                                   @change="handleFileUpload($event)"
                                   class="hidden">
                            
                            <label for="import-file-input" class="cursor-pointer">
                                <div class="mx-auto w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mb-4">
                                    <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                    </svg>
                                </div>
                                
                                <div x-show="!importFile">
                                    <p class="text-gray-900 font-medium mb-1">Click to upload or drag and drop</p>
                                    <p class="text-sm text-gray-600">CSV files only (max 10MB)</p>
                                    <p class="text-xs text-red-600 mt-2">Only CSV files are supported</p>
                                </div>
                                
                                <div x-show="importFile" class="text-left inline-block">
                                    <div class="flex items-center space-x-3 bg-orange-50 px-4 py-3 rounded-lg">
                                        <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        <div>
                                            <p class="font-medium text-gray-900" x-text="importFile?.name"></p>
                                            <p class="text-sm text-gray-600" x-text="importFile ? (importFile.size / 1024).toFixed(1) + ' KB' : ''"></p>
                                        </div>
                                    </div>
                                </div>
                            </label>
                        </div>
                        
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <div class="flex">
                                <svg class="w-5 h-5 text-yellow-600 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                                <div>
                                    <p class="text-sm text-yellow-800 font-medium">Need CSV format?</p>
                                    <p class="text-sm text-yellow-700">CSV files can be created with any spreadsheet application or text editor</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex justify-between">
                            <button @click="previousImportStep" 
                                    class="btn bg-gray-200 hover:bg-gray-300 text-gray-800 border-0 rounded-lg px-6">
                                Back
                            </button>
                        </div>
                    </div>
                    
                    <!-- Step 3: Map Fields -->
                    <div x-show="importWizardStep === 3" class="space-y-6">
                        <h4 class="text-lg font-semibold text-gray-900">Map your columns</h4>
                        <p class="text-sm text-gray-600">Match the columns from your file to the correct fields</p>
                        
                        <div class="bg-gray-50 rounded-lg p-4 mb-4">
                            <p class="text-sm text-gray-700">
                                <span class="font-medium">File contains:</span> 
                                <span x-text="importFileData?.total_rows || 0"></span> rows
                            </p>
                        </div>
                        
                        <div class="space-y-4">
                            <template x-for="field in getImportTypeFields()" :key="field.key">
                                <div class="flex items-center space-x-4">
                                    <div class="w-1/3">
                                        <label class="text-sm font-medium text-gray-700">
                                            <span x-text="field.label"></span>
                                            <span x-show="field.required" class="text-red-500">*</span>
                                        </label>
                                    </div>
                                    <div class="w-2/3">
                                        <select x-model="importMapping[field.key]" 
                                                :required="field.required"
                                                class="select select-bordered w-full bg-white">
                                            <option value="">-- Select column --</option>
                                            <template x-for="(header, index) in importFileData?.headers || []" :key="index">
                                                <option :value="index" x-text="header"></option>
                                            </template>
                                        </select>
                                    </div>
                                </div>
                            </template>
                        </div>
                        
                        <div class="flex justify-between">
                            <button @click="previousImportStep" 
                                    class="btn bg-gray-200 hover:bg-gray-300 text-gray-800 border-0 rounded-lg px-6">
                                Back
                            </button>
                            <button @click="nextImportStep" 
                                    class="btn bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white border-0 rounded-lg px-8 shadow-lg">
                                Next
                            </button>
                        </div>
                    </div>
                    
                    <!-- Step 4: Preview & Options -->
                    <div x-show="importWizardStep === 4" class="space-y-6">
                        <h4 class="text-lg font-semibold text-gray-900">Preview & Import Options</h4>
                        
                        <!-- Import Options -->
                        <div class="bg-gray-50 rounded-lg p-6 space-y-4">
                            <h5 class="font-medium text-gray-900">Import Options</h5>
                            
                            <div>
                                <label class="text-sm font-medium text-gray-700">How to handle duplicates?</label>
                                <select x-model="importOptions.duplicateHandling" class="mt-1 select select-bordered w-full bg-white">
                                    <option value="skip">Skip duplicate records</option>
                                    <option value="update">Update existing records</option>
                                    <option value="create">Create new records anyway</option>
                                </select>
                            </div>
                            
                            <div x-show="isTrainerImport()">
                                <label class="flex items-center space-x-3">
                                    <input type="checkbox" x-model="importOptions.sendInvitations" class="checkbox checkbox-purple">
                                    <span class="text-sm text-gray-700">Send invitation emails to new trainers</span>
                                </label>
                            </div>
                            
                            <div>
                                <label class="flex items-center space-x-3">
                                    <input type="checkbox" x-model="importOptions.validateEmails" class="checkbox checkbox-purple">
                                    <span class="text-sm text-gray-700">Validate email addresses</span>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Preview Table -->
                        <div>
                            <h5 class="font-medium text-gray-900 mb-3">Preview (first 5 rows)</h5>
                            <div class="overflow-x-auto border border-gray-200 rounded-lg">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Row</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                            <template x-for="field in getImportTypeFields()" :key="field.key">
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase" x-text="field.label"></th>
                                            </template>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <template x-for="preview in importPreviewData.slice(0, 5)" :key="preview.row">
                                            <tr :class="preview.status === 'error' ? 'bg-red-50' : ''">
                                                <td class="px-4 py-3 text-sm text-gray-900" x-text="preview.row"></td>
                                                <td class="px-4 py-3 text-sm">
                                                    <span x-show="preview.status === 'valid'" class="text-green-600">✓ Valid</span>
                                                    <span x-show="preview.status === 'error'" class="text-red-600">✗ Error</span>
                                                </td>
                                                <template x-for="field in getImportTypeFields()" :key="field.key">
                                                    <td class="px-4 py-3 text-sm text-gray-900" x-text="preview.data[field.key] || '-'"></td>
                                                </template>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Error Summary -->
                        <div x-show="importPreviewData.some(p => p.status === 'error')" class="bg-red-50 rounded-lg p-4">
                            <h5 class="font-medium text-red-900 mb-2">Validation Errors</h5>
                            <div class="space-y-1 max-h-32 overflow-y-auto">
                                <template x-for="preview in importPreviewData.filter(p => p.status === 'error').slice(0, 10)" :key="preview.row">
                                    <div>
                                        <p class="text-sm text-red-700">
                                            Row <span x-text="preview.row"></span>:
                                        </p>
                                        <ul class="ml-4 text-sm text-red-600">
                                            <template x-for="error in preview.errors" :key="error.field">
                                                <li x-text="error.message"></li>
                                            </template>
                                        </ul>
                                    </div>
                                </template>
                            </div>
                        </div>
                        
                        <div class="flex justify-between">
                            <button @click="previousImportStep" 
                                    class="btn bg-gray-200 hover:bg-gray-300 text-gray-800 border-0 rounded-lg px-6">
                                Back
                            </button>
                            <button @click="startImport" 
                                    class="btn bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white border-0 rounded-lg px-8 shadow-lg">
                                Start Import
                            </button>
                        </div>
                    </div>
                    
                    <!-- Step 5: Import Progress -->
                    <div x-show="importWizardStep === 5" class="space-y-6">
                        <h4 class="text-lg font-semibold text-gray-900">Importing Data...</h4>
                        
                        <!-- Progress Bar -->
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm text-gray-600">
                                <span>Progress</span>
                                <span x-text="formatProgress()"></span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-4 overflow-hidden">
                                <div class="bg-gradient-to-r from-orange-500 to-orange-600 h-4 rounded-full transition-all duration-300"
                                     :style="`width: ${importProgress.total > 0 ? (importProgress.processed / importProgress.total * 100) : 0}%`"></div>
                            </div>
                        </div>
                        
                        <!-- Stats -->
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="bg-gray-50 rounded-lg p-4 text-center">
                                <p class="text-lg font-semibold text-gray-900" x-text="importProgress.processed"></p>
                                <p class="text-sm text-gray-600">Processed</p>
                            </div>
                            <div class="bg-green-50 rounded-lg p-4 text-center">
                                <p class="text-lg font-semibold text-green-600" x-text="importProgress.successful"></p>
                                <p class="text-sm text-gray-600">Successful</p>
                            </div>
                            <div class="bg-red-50 rounded-lg p-4 text-center">
                                <p class="text-lg font-semibold text-red-600" x-text="importProgress.failed"></p>
                                <p class="text-sm text-gray-600">Failed</p>
                            </div>
                            <div class="bg-orange-50 rounded-lg p-4 text-center">
                                <p class="text-lg font-semibold text-orange-600" x-text="importProgress.total"></p>
                                <p class="text-sm text-gray-600">Total</p>
                            </div>
                        </div>
                        
                        <!-- Errors -->
                        <div x-show="importProgress.errors.length > 0" class="bg-red-50 rounded-lg p-4">
                            <h5 class="font-medium text-red-900 mb-2">Recent Errors</h5>
                            <div class="space-y-1 max-h-32 overflow-y-auto">
                                <template x-for="error in importProgress.errors.slice(-5)" :key="error.row">
                                    <p class="text-sm text-red-700">
                                        Row <span x-text="error.row"></span>: <span x-text="error.message"></span>
                                    </p>
                                </template>
                            </div>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="flex justify-center space-x-4">
                            <button @click="pauseImport" 
                                    x-show="importProgress.isProcessing && !importProgress.isPaused"
                                    class="btn bg-yellow-500 hover:bg-yellow-600 text-white border-0 rounded-lg px-6">
                                Pause
                            </button>
                            <button @click="resumeImport" 
                                    x-show="importProgress.isProcessing && importProgress.isPaused"
                                    class="btn bg-green-500 hover:bg-green-600 text-white border-0 rounded-lg px-6">
                                Resume
                            </button>
                            <button @click="cancelImport" 
                                    x-show="importProgress.isProcessing"
                                    class="btn bg-red-500 hover:bg-red-600 text-white border-0 rounded-lg px-6">
                                Cancel
                            </button>
                        </div>
                    </div>
                    
                    <!-- Step 6: Results -->
                    <div x-show="importWizardStep === 6" class="space-y-6">
                        <div class="text-center">
                            <div class="mx-auto w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mb-4">
                                <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <h4 class="text-lg font-semibold text-gray-900 mb-2">Import Complete!</h4>
                            <p class="text-gray-600">Your data has been successfully imported.</p>
                        </div>
                        
                        <!-- Results Summary -->
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="bg-green-50 rounded-lg p-4 text-center">
                                <p class="text-lg font-semibold text-green-600" x-text="importResults.created"></p>
                                <p class="text-sm text-gray-600">Created</p>
                            </div>
                            <div class="bg-orange-50 rounded-lg p-4 text-center">
                                <p class="text-lg font-semibold text-orange-600" x-text="importResults.updated"></p>
                                <p class="text-sm text-gray-600">Updated</p>
                            </div>
                            <div class="bg-yellow-50 rounded-lg p-4 text-center">
                                <p class="text-lg font-semibold text-yellow-600" x-text="importResults.skipped"></p>
                                <p class="text-sm text-gray-600">Skipped</p>
                            </div>
                            <div class="bg-red-50 rounded-lg p-4 text-center">
                                <p class="text-lg font-semibold text-red-600" x-text="importResults.failed"></p>
                                <p class="text-sm text-gray-600">Failed</p>
                            </div>
                        </div>
                        
                        <!-- Error Details -->
                        <div x-show="importResults.errors.length > 0" class="bg-red-50 rounded-lg p-4">
                            <div class="flex justify-between items-center mb-2">
                                <h5 class="font-medium text-red-900">Import Errors</h5>
                                <button @click="downloadErrorReport" class="text-sm text-red-700 hover:text-red-800 underline">
                                    Download Error Report
                                </button>
                            </div>
                            <div class="space-y-1 max-h-48 overflow-y-auto">
                                <template x-for="error in importResults.errors" :key="error.row">
                                    <p class="text-sm text-red-700">
                                        Row <span x-text="error.row"></span>: <span x-text="error.message"></span>
                                    </p>
                                </template>
                            </div>
                        </div>
                        
                        <!-- Trainer Invitation Notice -->
                        <div x-show="isTrainerImport() && importOptions.sendInvitations && importResults.created > 0" 
                             class="bg-orange-50 rounded-lg p-4">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-orange-600 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                                <div>
                                    <p class="text-orange-800 text-sm">
                                        Invitation emails are being sent to new trainers. This may take a few minutes to complete.
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex justify-center">
                            <button @click="showImportExportModal = false; resetImportWizard()" 
                                    class="btn bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white border-0 rounded-lg px-8 shadow-lg">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Export Mode -->
                <div x-show="importExportMode === 'export'" class="p-6 space-y-6">
                    <h4 class="text-lg font-semibold text-gray-900">Export Data</h4>
                    
                    <!-- Export Type -->
                    <div>
                        <label class="text-sm font-medium text-gray-700">What would you like to export?</label>
                        <select x-model="exportType" class="mt-1 select select-bordered w-full bg-white">
                            <option value="teams">Teams</option>
                            <option value="players">Players</option>
                            <option value="trainers" x-show="hasPermission('can_manage_trainers')">Trainers</option>
                        </select>
                    </div>
                    
                    <!-- Export Filters -->
                    <div class="space-y-4">
                        <h5 class="font-medium text-gray-900">Filters</h5>
                        
                        <!-- Season Filter -->
                        <div>
                            <label class="text-sm font-medium text-gray-700">Season</label>
                            <select x-model="exportFilters.season" class="mt-1 select select-bordered w-full bg-white">
                                <option value="">All seasons</option>
                                <option value="2024-2025">2024-2025</option>
                                <option value="2025-2026">2025-2026</option>
                            </select>
                        </div>
                        
                        <!-- Team Filter -->
                        <div x-show="exportType === 'players' || exportType === 'trainers'">
                            <label class="text-sm font-medium text-gray-700 mb-2 block">Select Teams</label>
                            <div class="space-y-2 max-h-48 overflow-y-auto border border-gray-200 rounded-lg p-3">
                                <label class="flex items-center space-x-3 p-2 hover:bg-gray-50 rounded cursor-pointer">
                                    <input type="checkbox" 
                                           @change="exportFilters.teamIds = []"
                                           :checked="exportFilters.teamIds.length === 0"
                                           class="checkbox checkbox-purple">
                                    <span class="text-gray-900">All teams</span>
                                </label>
                                <template x-for="team in myTeams" :key="team.id">
                                    <label class="flex items-center space-x-3 p-2 hover:bg-gray-50 rounded cursor-pointer">
                                        <input type="checkbox" 
                                               :value="team.id"
                                               @change="toggleExportTeam(team.id)"
                                               :checked="exportFilters.teamIds.includes(team.id)"
                                               class="checkbox checkbox-purple">
                                        <span class="text-gray-900" x-text="team.name"></span>
                                    </label>
                                </template>
                            </div>
                        </div>
                        
                        <!-- Include Evaluations -->
                        <div x-show="exportType === 'players'">
                            <label class="flex items-center space-x-3">
                                <input type="checkbox" x-model="exportFilters.includeEvaluations" class="checkbox checkbox-purple">
                                <span class="text-sm text-gray-700">Include player evaluations</span>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Export Format -->
                    <div>
                        <label class="text-sm font-medium text-gray-700">Export Format</label>
                        <div class="mt-2 space-y-2">
                            <label class="flex items-center space-x-3 p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50"
                                   :class="exportFormat === 'csv' ? 'border-orange-500 bg-orange-50' : ''">
                                <input type="radio" x-model="exportFormat" value="csv" class="radio radio-purple">
                                <div>
                                    <span class="font-medium">CSV</span>
                                    <p class="text-sm text-gray-600">Comma-separated values format</p>
                                </div>
                            </label>
                            <label class="flex items-center space-x-3 p-3 border border-gray-200 rounded-lg cursor-not-allowed opacity-50">
                                <input type="radio" x-model="exportFormat" value="xlsx" class="radio radio-purple" disabled>
                                <div>
                                    <span class="font-medium">Other formats</span>
                                    <p class="text-sm text-gray-600">Not supported - please use CSV format</p>
                                </div>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Export Info -->
                    <div class="bg-orange-50 rounded-lg p-4">
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-orange-600 mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div class="text-sm text-orange-800">
                                <p class="font-medium mb-1">Export Permissions:</p>
                                <ul class="space-y-1">
                                    <li x-show="userPermissions.user_role === 'trainer'">• As a trainer, you can only export data from teams you're assigned to</li>
                                    <li x-show="userPermissions.user_role === 'individual'">• As an individual user, you can only export your own data</li>
                                    <li x-show="userPermissions.user_role === 'owner' || userPermissions.user_role === 'manager'">• As a club owner/manager, you can export all club data</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Export Button -->
                    <div class="flex justify-end space-x-4">
                        <button @click="showImportExportModal = false" 
                                class="btn bg-gray-200 hover:bg-gray-300 text-gray-800 border-0 rounded-lg px-6">
                            Cancel
                        </button>
                        <button @click="exportData" 
                                class="btn bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white border-0 rounded-lg px-8 shadow-lg">
                            Export Data
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Import/Export Modal Specific Styles */
.checkbox-purple {
    --chkbg: #a855f7;
    --chkfg: white;
}

.radio-purple {
    --chkbg: #a855f7;
    --chkfg: white;
}

.checkbox-purple:checked,
.radio-purple:checked {
    background-color: #a855f7;
    border-color: #a855f7;
}
</style>