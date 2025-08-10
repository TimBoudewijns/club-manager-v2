<!-- Team Details Modal -->
<div x-show="showTeamDetailsModal" 
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 overflow-y-auto" 
     style="display: none; z-index: 40;">
    <div class="modal-container">
        <!-- Backdrop -->
        <div class="modal-backdrop" @click="closeTeamDetailsModal()"></div>
        
        <!-- Modal Content -->
        <div class="modal-content bg-white rounded-2xl shadow-2xl max-w-7xl w-full overflow-hidden"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform scale-90"
             x-transition:enter-end="opacity-100 transform scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 transform scale-100"
             x-transition:leave-end="opacity-0 transform scale-90"
             @click.stop>
            <!-- Fixed Team Header - Dynamic color based on team type -->
            <div class="p-4 md:p-6 text-white"
                 :class="isViewingClubTeam ? 'bg-gradient-to-r from-blue-500 to-blue-600' : 'bg-gradient-to-r from-orange-500 to-orange-600'">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-xl md:text-2xl" x-text="isViewingClubTeam ? selectedClubTeam?.name : selectedTeam?.name"></h3>
                        <p class="mt-1" :class="isViewingClubTeam ? 'text-blue-100' : 'text-orange-100'">
                            <span x-text="isViewingClubTeam ? 'Club Team Roster (Read-only)' : 'Team Roster Management'"></span>
                        </p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div x-show="isViewingClubTeam" 
                             class="bg-blue-400/30 text-white px-4 py-2 rounded-lg font-medium">
                            View Only
                        </div>
                        <button @click="closeTeamDetailsModal()" 
                                class="text-white p-1.5 rounded-full transition-colors"
                                :class="isViewingClubTeam ? 'hover:text-blue-200 hover:bg-white/10' : 'hover:text-orange-200 hover:bg-white/10'">
                            <svg class="w-4 h-4" style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Scrollable Modal Body -->
            <div class="max-h-[calc(90vh-120px)] overflow-y-auto -webkit-overflow-scrolling-touch">
                <!-- Team Info Bar (alleen voor club teams) -->
                <div x-show="isViewingClubTeam" class="p-4 md:p-6 border-b bg-blue-50">
                    <div class="flex flex-wrap gap-4 text-sm">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            <span class="font-medium text-gray-700">Coach:</span>
                            <span class="ml-2 text-gray-900" x-text="selectedClubTeam?.coach"></span>
                        </div>
                        <div class="flex items-center" x-show="selectedClubTeam?.trainer_names">
                            <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                            <span class="font-medium text-gray-700">Trainer(s):</span>
                            <span class="ml-2 text-gray-900" x-text="selectedClubTeam?.trainer_names || 'No trainer assigned'"></span>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons (alleen voor My Teams en team owners) -->
                <div x-show="!isViewingClubTeam && hasPermission('can_create_teams')" class="p-4 md:p-6 border-b bg-gray-50">
                    <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-3">
                        <button type="button"
                                @click="showAddPlayerModal = true" 
                                class="bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white font-bold py-2 px-4 md:px-6 rounded-lg shadow-md transform transition-all duration-200 hover:scale-105 flex items-center justify-center space-x-2">
                            <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                            </svg>
                            <span>Add New Player</span>
                        </button>
                        <button @click="showAddExistingPlayerModal = true" 
                                class="bg-orange-100 hover:bg-orange-200 text-orange-700 font-bold py-2 px-4 md:px-6 rounded-lg shadow-md transform transition-all duration-200 hover:scale-105 flex items-center justify-center space-x-2">
                            <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            <span>Add Existing Player</span>
                        </button>
                    </div>
                </div>
                
                <!-- Players Table -->
                <div class="p-4 md:p-6">
                    <div x-show="teamPlayers.length > 0" class="overflow-x-auto shadow ring-1 ring-black ring-opacity-5 rounded-lg">
                        <table class="min-w-full divide-y divide-gray-300">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 md:px-6 py-3 md:py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Player</th>
                                    <th class="hidden md:table-cell px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                                    <th class="hidden sm:table-cell px-4 md:px-6 py-3 md:py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Birth Date</th>
                                    <th class="px-4 md:px-6 py-3 md:py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Position</th>
                                    <th class="px-4 md:px-6 py-3 md:py-4 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Jersey #</th>
                                    <th class="hidden lg:table-cell px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Notes</th>
                                    <th class="px-4 md:px-6 py-3 md:py-4 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <template x-for="player in teamPlayers" :key="player.id">
                                    <tr :class="isViewingClubTeam ? 'hover:bg-blue-50' : 'hover:bg-orange-50'" class="transition-colors">
                                        <td class="px-4 md:px-6 py-3 md:py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-8 w-8 md:h-10 md:w-10 rounded-full flex items-center justify-center"
                                                     :class="isViewingClubTeam ? 'bg-blue-100' : 'bg-orange-100'">
                                                    <span class="font-bold text-xs md:text-sm" 
                                                          :class="isViewingClubTeam ? 'text-blue-600' : 'text-orange-600'" 
                                                          x-text="(player.first_name ? player.first_name.charAt(0) : '') + (player.last_name ? player.last_name.charAt(0) : '')"></span>
                                                </div>
                                                <div class="ml-3 md:ml-4">
                                                    <div class="text-sm font-medium text-gray-900" x-text="player.first_name + ' ' + player.last_name"></div>
                                                    <div class="text-xs text-gray-500 md:hidden" x-text="player.email"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="hidden md:table-cell px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900" x-text="player.email"></div>
                                        </td>
                                        <td class="hidden sm:table-cell px-4 md:px-6 py-3 md:py-4 whitespace-nowrap text-sm text-gray-900" x-text="player.birth_date"></td>
                                        <td class="px-4 md:px-6 py-3 md:py-4 whitespace-nowrap">
                                            <span class="px-2 md:px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full" 
                                                  :class="isViewingClubTeam ? 'bg-blue-100 text-blue-800' : 'bg-orange-100 text-orange-800'" 
                                                  x-text="player.position || 'Not assigned'"></span>
                                        </td>
                                        <td class="px-4 md:px-6 py-3 md:py-4 whitespace-nowrap text-center">
                                            <span class="inline-flex items-center justify-center h-6 w-6 md:h-8 md:w-8 rounded-full bg-gray-100 text-gray-800 font-bold text-xs md:text-sm" x-text="player.jersey_number || '-'"></span>
                                        </td>
                                        <td class="hidden lg:table-cell px-6 py-4 text-sm text-gray-900" x-text="player.notes || '-'"></td>
                                        <td class="px-4 md:px-6 py-3 md:py-4 whitespace-nowrap text-center">
                                            <div class="flex items-center justify-center space-x-1 md:space-x-2">
                                                <!-- Club Teams: Alleen View knoppen -->
                                                <template x-if="isViewingClubTeam">
                                                    <div class="flex items-center justify-center space-x-1 md:space-x-2">
                                                        <!-- View Player Card Button -->
                                                        <button @click="handlePlayerCardModalClick(player.id, isViewingClubTeam)" 
                                                                class="text-blue-600 hover:text-blue-900 transition-colors p-2 rounded-lg hover:bg-blue-50 active:bg-blue-100 min-w-[44px] min-h-[44px] flex items-center justify-center"
                                                                title="View player card"
                                                                type="button">
                                                            <svg class="w-4 h-4 md:w-5 md:h-5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"></path>
                                                            </svg>
                                                        </button>
                                                        <!-- History Button -->
                                                        <button @click="handleHistoryClick(player.id, isViewingClubTeam)" 
                                                                class="text-purple-600 hover:text-purple-900 transition-colors p-2 rounded-lg hover:bg-purple-50 active:bg-purple-100 min-w-[44px] min-h-[44px] flex items-center justify-center"
                                                                title="View player history"
                                                                type="button">
                                                            <svg class="w-4 h-4 md:w-5 md:h-5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </template>
                                                
                                                <!-- My Teams: Actie knoppen gebaseerd op permissions -->
                                                <template x-if="!isViewingClubTeam">
                                                    <div class="flex items-center justify-center space-x-1 md:space-x-2">
                                                        <!-- Evaluate Button - Voor alle trainers (owners en trainers) -->
                                                        <button @click="handleEvaluateClick(player.id)" 
                                                                class="text-orange-600 hover:text-orange-900 transition-colors p-2 rounded-lg hover:bg-orange-50 active:bg-orange-100 min-w-[44px] min-h-[44px] flex items-center justify-center"
                                                                title="Evaluate player"
                                                                type="button">
                                                            <svg class="w-4 h-4 md:w-5 md:h-5 pointer-events-none" fill="currentColor" viewBox="0 0 24 24">
                                                                <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                                                            </svg>
                                                        </button>
                                                        <!-- View Player Card Button - Voor alle trainers -->
                                                        <button @click="handlePlayerCardModalClick(player.id, isViewingClubTeam)" 
                                                                class="text-blue-600 hover:text-blue-900 transition-colors p-2 rounded-lg hover:bg-blue-50 active:bg-blue-100 min-w-[44px] min-h-[44px] flex items-center justify-center"
                                                                title="View player card"
                                                                type="button">
                                                            <svg class="w-4 h-4 md:w-5 md:h-5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"></path>
                                                            </svg>
                                                        </button>
                                                        <!-- History Button - Voor alle trainers -->
                                                        <button @click="handleHistoryClick(player.id, isViewingClubTeam)" 
                                                                class="text-purple-600 hover:text-purple-900 transition-colors p-2 rounded-lg hover:bg-purple-50 active:bg-purple-100 min-w-[44px] min-h-[44px] flex items-center justify-center"
                                                                title="View player history"
                                                                type="button">
                                                            <svg class="w-4 h-4 md:w-5 md:h-5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                            </svg>
                                                        </button>
                                                        <!-- Remove Button - Alleen voor club managers en independent trainers -->
                                                        <template x-if="hasPermission('can_delete_players') && userPermissions.user_type !== 'club_trainer'">
                                                            <button @click="handleRemoveClick(player.id)" 
                                                                    class="text-red-600 hover:text-red-900 transition-colors p-2 rounded-lg hover:bg-red-50 active:bg-red-100 min-w-[44px] min-h-[44px] flex items-center justify-center"
                                                                    title="Remove from team"
                                                                    type="button">
                                                                <svg class="w-4 h-4 md:w-5 md:h-5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                                </svg>
                                                            </button>
                                                        </template>
                                                    </div>
                                                </template>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Empty Players State -->
                    <div x-show="teamPlayers.length === 0" class="text-center py-12">
                        <div class="bg-gray-50 rounded-full w-20 h-20 mx-auto mb-4 flex items-center justify-center">
                            <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                        </div>
                        <h4 class="text-lg font-semibold text-gray-900 mb-2">No players yet</h4>
                        <p class="text-gray-600" x-text="isViewingClubTeam ? 'This team doesn\'t have any players yet.' : 'Add players to build your team roster.'"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>