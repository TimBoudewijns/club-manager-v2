<!-- Season Management Modal -->
<div x-show="showSeasonManagementModal" 
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 overflow-y-auto" 
     style="display: none; z-index: 50;">
    <div class="modal-container">
        <!-- Backdrop -->
        <div class="modal-backdrop" @click="showSeasonManagementModal = false"></div>
        
        <!-- Modal Content -->
        <div class="modal-content bg-white rounded-2xl shadow-2xl max-w-2xl w-full overflow-hidden"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform scale-90"
             x-transition:enter-end="opacity-100 transform scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 transform scale-100"
             x-transition:leave-end="opacity-0 transform scale-90"
             @click.stop>
            
            <!-- Header -->
            <div class="p-6 border-b bg-gradient-to-r from-orange-500 to-orange-600 text-white">
                <div class="flex items-center justify-between">
                    <h3 class="font-bold text-xl">Season Management</h3>
                    <button @click="showSeasonManagementModal = false" 
                            class="text-white hover:text-orange-200 p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            
            <!-- Body -->
            <div class="p-6">
                <!-- Add New Season -->
                <div class="mb-6">
                    <h4 class="text-lg font-semibold text-gray-900 mb-3">Add New Season</h4>
                    <div class="flex space-x-3">
                        <input type="text" 
                               x-model="newSeasonName"
                               placeholder="e.g., 2025-2026"
                               class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                               @keyup.enter="addSeason">
                        <button @click="addSeason"
                                class="px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white rounded-lg font-medium transition-colors">
                            Add Season
                        </button>
                    </div>
                    <p class="text-sm text-gray-500 mt-2">Format: YYYY-YYYY (e.g., 2025-2026)</p>
                </div>
                
                <!-- Existing Seasons -->
                <div>
                    <h4 class="text-lg font-semibold text-gray-900 mb-3">Existing Seasons</h4>
                    <div class="space-y-2">
                        <template x-for="(seasonData, seasonKey) in (window.clubManagerAjax?.available_seasons || {})" :key="seasonKey">
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div class="flex items-center space-x-3">
                                    <div class="w-3 h-3 rounded-full"
                                         :class="seasonData.is_active ? 'bg-green-500' : 'bg-gray-400'"></div>
                                    <span class="font-medium" x-text="seasonKey"></span>
                                    <span x-show="seasonKey === currentSeason" 
                                          class="text-xs bg-orange-100 text-orange-800 px-2 py-1 rounded-full">
                                        Current
                                    </span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="text-xs text-gray-500" x-text="'Created: ' + (seasonData.created_at ? new Date(seasonData.created_at).toLocaleDateString() : 'Unknown')"></span>
                                    <button @click="removeSeason(seasonKey)"
                                            x-show="seasonKey !== currentSeason"
                                            class="text-red-600 hover:text-red-800 p-1 rounded"
                                            title="Remove season">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                    
                    <!-- Empty state -->
                    <div x-show="Object.keys(window.clubManagerAjax?.available_seasons || {}).length === 0" 
                         class="text-center py-8 text-gray-500">
                        No seasons available. Add your first season above.
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="p-6 border-t bg-gray-50">
                <div class="flex justify-end">
                    <button @click="showSeasonManagementModal = false"
                            class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-100 font-medium transition-colors">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>