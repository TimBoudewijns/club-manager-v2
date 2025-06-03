<!-- Add Existing Player Modal -->
<div x-show="showAddExistingPlayerModal" 
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-[9999] overflow-y-auto" 
     style="display: none;">
    <!-- Modal container with proper mobile handling -->
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <!-- Background overlay -->
        <div class="fixed inset-0 transition-opacity" 
             @click="closeAddExistingPlayerModal()"
             aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>

        <!-- This element is to trick the browser into centering the modal contents. -->
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <!-- Modal panel -->
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             @click.stop>
            
            <!-- Header -->
            <div class="bg-gradient-to-r from-orange-500 to-orange-600 px-4 py-5 sm:p-6 text-white">
                <h3 class="text-lg leading-6 font-bold sm:text-2xl">Add Existing Player</h3>
                <p class="mt-1 text-sm text-orange-100 sm:text-base">Search and add an existing player to <span x-text="selectedTeam?.name"></span></p>
            </div>
            
            <!-- Content -->
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                <!-- Search Input -->
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Search Player</label>
                    <div class="relative">
                        <input 
                            type="text" 
                            x-model="playerSearch" 
                            @input.debounce.300ms="searchPlayers"
                            placeholder="Search by name or email..." 
                            class="w-full px-10 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 text-base"
                            autocomplete="off"
                            autocorrect="off"
                            autocapitalize="off"
                            spellcheck="false"
                        />
                        <svg class="absolute left-3 top-3 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                </div>
                
                <!-- Search Results -->
                <div x-show="searchResults.length > 0" 
                     x-cloak
                     class="mb-6 bg-gray-50 rounded-lg p-2 max-h-48 overflow-y-auto -webkit-overflow-scrolling-touch">
                    <template x-for="player in searchResults" :key="player.id">
                        <button type="button"
                                class="w-full p-3 hover:bg-orange-100 cursor-pointer rounded-lg transition-colors flex items-center space-x-3 text-left"
                                @click="selectExistingPlayer(player)"
                                @touchstart.passive
                                :aria-label="'Select ' + player.first_name + ' ' + player.last_name">
                            <div class="flex-shrink-0 h-10 w-10 bg-orange-200 rounded-full flex items-center justify-center">
                                <span class="text-orange-700 font-bold text-sm" x-text="(player.first_name ? player.first_name.charAt(0) : '') + (player.last_name ? player.last_name.charAt(0) : '')"></span>
                            </div>
                            <div class="flex-1">
                                <div class="font-semibold text-gray-900" x-text="player.first_name + ' ' + player.last_name"></div>
                                <div class="text-sm text-gray-600" x-text="player.email"></div>
                            </div>
                        </button>
                    </template>
                </div>
                
                <!-- Selected Player Form -->
                <form x-show="selectedExistingPlayer" 
                      x-cloak
                      @submit.prevent="addExistingPlayerToTeam" 
                      class="space-y-4">
                    <!-- Selected Player Display -->
                    <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0 h-12 w-12 bg-orange-200 rounded-full flex items-center justify-center">
                                <span class="text-orange-700 font-bold" x-text="selectedExistingPlayer ? (selectedExistingPlayer.first_name ? selectedExistingPlayer.first_name.charAt(0) : '') + (selectedExistingPlayer.last_name ? selectedExistingPlayer.last_name.charAt(0) : '') : ''"></span>
                            </div>
                            <div>
                                <div class="font-semibold text-gray-900" x-text="selectedExistingPlayer?.first_name + ' ' + selectedExistingPlayer?.last_name"></div>
                                <div class="text-sm text-gray-600" x-text="selectedExistingPlayer?.email"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Form Fields -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Position</label>
                            <select x-model="existingPlayerTeamData.position" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                                <option value="">Select position</option>
                                <option value="Forward">Forward</option>
                                <option value="Midfielder">Midfielder</option>
                                <option value="Defender">Defender</option>
                                <option value="Goalkeeper">Goalkeeper</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Jersey Number</label>
                            <input type="number" 
                                   x-model="existingPlayerTeamData.jersey_number" 
                                   min="1" 
                                   max="999" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                                   inputmode="numeric"
                                   pattern="[0-9]*" />
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Notes</label>
                        <textarea x-model="existingPlayerTeamData.notes" 
                                  rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                                  placeholder="Add any team-specific notes..."></textarea>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="flex flex-col-reverse sm:flex-row sm:justify-end sm:space-x-3 space-y-3 space-y-reverse sm:space-y-0 pt-4">
                        <button type="button" 
                                class="w-full sm:w-auto px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium rounded-lg transition-colors duration-200"
                                @click="closeAddExistingPlayerModal()">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="w-full sm:w-auto px-6 py-3 bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white font-medium rounded-lg shadow-lg transition-all duration-200">
                            Add to Team
                        </button>
                    </div>
                </form>
                
                <!-- Empty state when no player selected -->
                <div x-show="!selectedExistingPlayer && searchResults.length === 0 && playerSearch.length >= 2" 
                     x-cloak
                     class="text-center py-8 text-gray-500">
                    <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                    </svg>
                    <p>No players found matching your search.</p>
                </div>
            </div>
        </div>
    </div>
</div>