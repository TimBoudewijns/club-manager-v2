<?php
/**
 * Import/Export Content
 */
?>

<!-- Import/Export Section -->
<div class="section-header">
    <div class="section-title">
        <span class="section-icon bg-gradient-to-br from-purple-100 to-purple-200 text-purple-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
            </svg>
        </span>
        Data Management
    </div>
    <p class="section-subtitle">Import and export team data, manage bulk operations</p>
</div>

<!-- Quick Actions Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-12">
    
    <!-- Import Section -->
    <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-8 border border-green-200">
        <div class="flex items-center mb-6">
            <div class="bg-green-500 rounded-xl p-4 mr-4">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                </svg>
            </div>
            <div>
                <h3 class="text-xl font-bold text-gray-900">Import Data</h3>
                <p class="text-green-700">Add players and trainers in bulk</p>
            </div>
        </div>
        
        <div class="space-y-4">
            <button @click="startImport('players')" 
                    class="w-full flex items-center justify-center gap-3 bg-white border border-green-300 rounded-lg p-4 hover:bg-green-50 transition-all group">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                <span class="font-medium text-gray-900 group-hover:text-green-700">Import Players</span>
                <svg class="w-4 h-4 text-green-600 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </button>
            
            <button @click="startImport('trainers')" 
                    class="w-full flex items-center justify-center gap-3 bg-white border border-green-300 rounded-lg p-4 hover:bg-green-50 transition-all group">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
                <span class="font-medium text-gray-900 group-hover:text-green-700">Import Trainers</span>
                <svg class="w-4 h-4 text-green-600 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </button>
        </div>
    </div>
    
    <!-- Export Section -->
    <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-8 border border-blue-200">
        <div class="flex items-center mb-6">
            <div class="bg-blue-500 rounded-xl p-4 mr-4">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
                </svg>
            </div>
            <div>
                <h3 class="text-xl font-bold text-gray-900">Export Data</h3>
                <p class="text-blue-700">Download your data as CSV files</p>
            </div>
        </div>
        
        <div class="space-y-4">
            <button @click="startExport('players')" 
                    class="w-full flex items-center justify-center gap-3 bg-white border border-blue-300 rounded-lg p-4 hover:bg-blue-50 transition-all group">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                <span class="font-medium text-gray-900 group-hover:text-blue-700">Export Players</span>
                <svg class="w-4 h-4 text-blue-600 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </button>
            
            <button @click="startExport('teams')" 
                    class="w-full flex items-center justify-center gap-3 bg-white border border-blue-300 rounded-lg p-4 hover:bg-blue-50 transition-all group">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
                <span class="font-medium text-gray-900 group-hover:text-blue-700">Export Teams</span>
                <svg class="w-4 h-4 text-blue-600 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </button>
        </div>
    </div>
</div>

<!-- Recent Operations -->
<div>
    <h3 class="text-lg font-bold text-gray-900 mb-6 flex items-center">
        <span class="bg-purple-100 rounded-lg p-2 mr-3">
            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </span>
        Recent Operations
    </h3>
    
    <div class="bg-gray-50 rounded-xl p-6">
        <div x-show="recentOperations.length === 0" class="text-center py-8">
            <div class="bg-gray-200 rounded-full w-16 h-16 mx-auto mb-4 flex items-center justify-center">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                </svg>
            </div>
            <h4 class="text-lg font-medium text-gray-900 mb-2">No recent operations</h4>
            <p class="text-gray-600">Your import and export history will appear here.</p>
        </div>
        
        <div x-show="recentOperations.length > 0" class="space-y-4">
            <template x-for="operation in recentOperations" :key="operation.id">
                <div class="bg-white rounded-lg p-4 border border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="bg-purple-100 rounded-lg p-2 mr-3">
                                <svg x-show="operation.type === 'import'" class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                </svg>
                                <svg x-show="operation.type === 'export'" class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900" x-text="operation.title"></p>
                                <p class="text-sm text-gray-600" x-text="operation.date"></p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-1 text-xs font-medium rounded-full"
                                  :class="{
                                    'bg-green-100 text-green-700': operation.status === 'completed',
                                    'bg-red-100 text-red-700': operation.status === 'failed',
                                    'bg-yellow-100 text-yellow-700': operation.status === 'processing'
                                  }"
                                  x-text="operation.status"></span>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>