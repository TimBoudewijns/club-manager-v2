<?php
/**
 * Import/Export tab partial
 */
?>
<!-- Import/Export Management Content Container -->
<div class="bg-white rounded-b-2xl shadow-xl border-x border-b border-gray-200 overflow-hidden">
    <!-- Header -->
    <div class="bg-gradient-to-r from-orange-50 to-amber-50 border-b border-orange-200 p-6 md:p-8">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl p-3 shadow-lg">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 mb-1">Import/Export</h2>
                    <p class="text-gray-600">Bulk manage your club data</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Content -->
    <div class="p-6 md:p-8">
        <!-- Action Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <!-- Import Card -->
            <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-2xl p-8 border border-orange-200 hover:shadow-lg transition-shadow">
                <div class="flex items-center mb-6">
                    <div class="bg-orange-500 rounded-xl p-4 mr-4">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19v-6m3 6v-6m0 0l3-3m-3 3l-3-3"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-gray-900">Import Data</h3>
                        <p class="text-gray-600">Upload CSV files</p>
                    </div>
                </div>
                
                <p class="text-gray-700 mb-6">Import teams, players, or trainers from CSV files. Our wizard will guide you through the process.</p>
                
                <button @click="openImportExport('import')" 
                        class="w-full bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white font-bold py-3 px-6 rounded-xl shadow-lg transform transition-all duration-200 hover:scale-105">
                    Start Import Wizard
                </button>
            </div>
            
            <!-- Export Card -->
            <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-2xl p-8 border border-orange-200 hover:shadow-lg transition-shadow">
                <div class="flex items-center mb-6">
                    <div class="bg-orange-500 rounded-xl p-4 mr-4">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-gray-900">Export Data</h3>
                        <p class="text-gray-600">Download your club data</p>
                    </div>
                </div>
                
                <p class="text-gray-700 mb-6">Export your teams, players, or trainers to CSV format for backup or external use.</p>
                
                <button @click="openImportExport('export')" 
                        class="w-full bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white font-bold py-3 px-6 rounded-xl shadow-lg transform transition-all duration-200 hover:scale-105">
                    Export Data
                </button>
            </div>
        </div>
        
        <!-- Templates Section -->
        <div class="bg-gray-50 rounded-2xl p-8 border border-gray-200">
            <h3 class="text-base font-semibold text-gray-900 mb-4">Import Templates</h3>
            <p class="text-gray-600 mb-6">Download these templates to ensure your data is formatted correctly for import.</p>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div class="bg-white rounded-xl p-6 border border-gray-200 hover:shadow-md transition-shadow">
                    <div class="flex items-center mb-4">
                        <div class="bg-orange-100 rounded-lg p-2 mr-3">
                            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                        </div>
                        <h4 class="font-semibold text-gray-900">Teams Template</h4>
                    </div>
                    <p class="text-sm text-gray-600 mb-4">Basic team information template</p>
                    <button @click="downloadTemplate('teams')" 
                            class="w-full text-center text-orange-600 hover:text-orange-700 font-medium text-sm hover:underline">
                        Download CSV Template
                    </button>
                </div>
                
                <div class="bg-white rounded-xl p-6 border border-gray-200 hover:shadow-md transition-shadow">
                    <div class="flex items-center mb-4">
                        <div class="bg-orange-100 rounded-lg p-2 mr-3">
                            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                        <h4 class="font-semibold text-gray-900">Players Template</h4>
                    </div>
                    <p class="text-sm text-gray-600 mb-4">Player profiles with team assignments</p>
                    <button @click="downloadTemplate('players')" 
                            class="w-full text-center text-orange-600 hover:text-orange-700 font-medium text-sm hover:underline">
                        Download CSV Template
                    </button>
                </div>
                
                
                <div class="bg-white rounded-xl p-6 border border-gray-200 hover:shadow-md transition-shadow" x-show="hasPermission('can_manage_trainers')">
                    <div class="flex items-center mb-4">
                        <div class="bg-orange-100 rounded-lg p-2 mr-3">
                            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                        </div>
                        <h4 class="font-semibold text-gray-900">Trainers Template</h4>
                    </div>
                    <p class="text-sm text-gray-600 mb-4">Trainer profiles and team assignments</p>
                    <button @click="downloadTemplate('trainers')" 
                            class="w-full text-center text-orange-600 hover:text-orange-700 font-medium text-sm hover:underline">
                        Download CSV Template
                    </button>
                </div>
            </div>
        </div>
        
        <!-- File Format Section -->
        <div class="mt-8 bg-yellow-50 rounded-xl p-6 border border-yellow-200">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="w-6 h-6 text-yellow-600 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <h4 class="text-lg font-semibold text-yellow-900 mb-2">Important File Format Information</h4>
                    <ul class="text-sm text-yellow-800 space-y-1 list-disc list-inside">
                        <li><strong>Only CSV files are supported</strong> - Use comma-separated values format</li>
                        <li>CSV files can be created and edited with any spreadsheet application or text editor</li>
                        <li>Maximum file size is 10MB</li>
                        <li>Dates should be in DD-MM-YYYY format (e.g., 15-03-2005)</li>
                        <li>Email addresses will be validated during import</li>
                        <li>For trainers with multiple teams, use semicolon (;) to separate team names</li>
                        <li>Season format should be YYYY-YYYY (e.g., 2024-2025)</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Additional Tips -->
        <div class="mt-6 bg-blue-50 rounded-xl p-6 border border-blue-200">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="w-6 h-6 text-blue-600 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <h4 class="text-lg font-semibold text-blue-900 mb-2">Import/Export Tips</h4>
                    <ul class="text-sm text-blue-800 space-y-1 list-disc list-inside">
                        <li>Always backup your data before importing</li>
                        <li>Use the templates to ensure correct formatting</li>
                        <li>The import wizard will show you a preview before processing</li>
                        <li>You can choose how to handle duplicate records during import</li>
                        <li>Trainer invitations can be sent automatically after import</li>
                        <li>Exports will include data based on your current season selection</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>