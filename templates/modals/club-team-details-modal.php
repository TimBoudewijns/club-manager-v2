<!-- Club Team Details Modal -->
<div x-show="showClubTeamDetailsModal" 
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
        <!-- Backdrop -->
        <div class="modal-backdrop" @click="closeClubTeamDetailsModal()"></div>
        
        <!-- Modal Content -->
        <div class="modal-content bg-white rounded-2xl shadow-2xl max-w-7xl w-full overflow-hidden"
             @click.stop>
            <!-- Team Header -->
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 p-4 md:p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-xl md:text-2xl" x-text="selectedClubTeam?.name"></h3>
                        <p class="text-blue-100 mt-1">Club Team Roster (Read-only)</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="bg-blue-400/30 text-white px-4 py-2 rounded-lg font-medium">
                            View Only
                        </div>
                        <button @click="closeClubTeamDetailsModal()" 
                                class="text-white hover:text-blue-200 p-2 rounded-lg hover:bg-white/10 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Modal Body with Scroll -->
            <div class="overflow-y-auto max-h-[calc(90vh-100px)]">
                <!-- Team Info Bar -->
                <div class="p-4 md:p-6 border-b bg-blue-50">
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
                
                <!-- Players Table -->
                <div class="p-4 md:p-6">
                    <div x-show="clubTeamPlayers.length > 0" class="overflow-x-auto shadow ring-1 ring-black ring-opacity-5 rounded-lg">
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
                                <template x-for="player in clubTeamPlayers" :key="player.id">
                                    <tr class="hover:bg-blue-50 transition-colors">
                                        <td class="px-4 md:px-6 py-3 md:py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-8 w-8 md:h-10 md:w-10 bg-blue-100 rounded-full flex items-center justify-center">
                                                    <span class="text-blue-600 font-bold text-xs md:text-sm" x-text="(player.first_name ? player.first_name.charAt(0) : '') + (player.last_name ? player.last_name.charAt(0) : '')"></span>
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
                                            <span class="px-2 md:px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800" x-text="player.position || 'Not assigned'"></span>
                                        </td>
                                        <td class="px-4 md:px-6 py-3 md:py-4 whitespace-nowrap text-center">
                                            <span class="inline-flex items-center justify-center h-6 w-6 md:h-8 md:w-8 rounded-full bg-gray-100 text-gray-800 font-bold text-xs md:text-sm" x-text="player.jersey_number || '-'"></span>
                                        </td>
                                        <td class="hidden lg:table-cell px-6 py-4 text-sm text-gray-900" x-text="player.notes || '-'"></td>
                                        <td class="px-4 md:px-6 py-3 md:py-4 whitespace-nowrap text-center">
                                            <div class="flex items-center justify-center space-x-1 md:space-x-2">
                                                <!-- View Player Card Button -->
                                                <button @click="handleClubPlayerCardClick(player.id)" 
                                                        class="text-blue-600 hover:text-blue-900 transition-colors p-2 rounded-lg hover:bg-blue-50 active:bg-blue-100 min-w-[44px] min-h-[44px] flex items-center justify-center"
                                                        title="View player card"
                                                        type="button">
                                                    <svg class="w-4 h-4 md:w-5 md:h-5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"></path>
                                                    </svg>
                                                </button>
                                                <!-- History Button -->
                                                <button @click="handleClubHistoryClick(player.id)" 
                                                        class="text-purple-600 hover:text-purple-900 transition-colors p-2 rounded-lg hover:bg-purple-50 active:bg-purple-100 min-w-[44px] min-h-[44px] flex items-center justify-center"
                                                        title="View player history"
                                                        type="button">
                                                    <svg class="w-4 h-4 md:w-5 md:h-5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Empty Players State -->
                    <div x-show="clubTeamPlayers.length === 0" class="text-center py-12">
                        <div class="bg-gray-50 rounded-full w-20 h-20 mx-auto mb-4 flex items-center justify-center">
                            <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                        </div>
                        <h4 class="text-lg font-semibold text-gray-900 mb-2">No players in this team</h4>
                        <p class="text-gray-600">This team doesn't have any players yet.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>