<!-- Add Existing Player Modal -->
<div x-show="showAddExistingPlayerModal" 
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-50 overflow-y-auto" 
     style="display: none;">
    <div class="modal-container">
        <div class="modal-backdrop" @click="closeAddExistingPlayerModal()"></div>
        
        <div class="modal-content bg-white rounded-2xl shadow-2xl max-w-2xl w-full"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform scale-90"
             x-transition:enter-end="opacity-100 transform scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 transform scale-100"
             x-transition:leave-end="opacity-0 transform scale-90"
             @click.stop>
            <div class="bg-gradient-to-r from-orange-500 to-orange-600 p-4 md:p-6 text-white">
                <h3 class="font-bold text-xl md:text-2xl">Add Existing Player</h3>
                <p class="text-orange-100 mt-1 text-sm md:text-base">Search and add an existing player to <span x-text="selectedTeam?.name"></span></p>
            </div>
            <div class="p-4 md:p-6 max-h-[calc(90vh-120px)] overflow-y-auto -webkit-overflow-scrolling-touch">
                <!-- Search Input -->
                <div class="form-control w-full mb-6">
                    <label class="label">
                        <span class="label-text font-semibold text-gray-700">Search Player</span>
                    </label>
                    <div class="relative">
                        <input 
                            type="text" 
                            x-model="playerSearch" 
                            @input.debounce.300ms="searchPlayers"
                            placeholder="Search by name or email..." 
                            class="input input-bordered w-full bg-gray-50 border-gray-300 focus:border-orange-500 focus:ring-2 focus:ring-orange-200 rounded-lg pl-10"
                            autocomplete="off"
                            autocorrect="off"
                            autocapitalize="off"
                            spellcheck="false"
                        />
                        <svg class="absolute left-3 top-3.5 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                </div>
                
                <!-- Search Results -->
                <div x-show="searchResults.length > 0" class="mb-6">
                    <div class="bg-gray-50 rounded-lg p-2 max-h-48 overflow-y-auto">
                        <template x-for="player in searchResults" :key="player.id">
                            <div 
                                class="p-3 hover:bg-orange-100 cursor-pointer rounded-lg transition-colors flex items-center space-x-3"
                                @click="selectExistingPlayer(player)"
                            >
                                <div class="flex-shrink-0 h-10 w-10 bg-orange-200 rounded-full flex items-center justify-center">
                                    <span class="text-orange-700 font-bold text-sm" x-text="(player.first_name ? player.first_name.charAt(0) : '') + (player.last_name ? player.last_name.charAt(0) : '')"></span>
                                </div>
                                <div class="flex-1">
                                    <div class="font-semibold text-gray-900" x-text="player.first_name + ' ' + player.last_name"></div>
                                    <div class="text-sm text-gray-600" x-text="player.email"></div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
                
                <!-- Selected Player Form -->
                <form x-show="selectedExistingPlayer" @submit.prevent="addExistingPlayerToTeam">
                    <div class="bg-orange-50 border border-orange-200 rounded-lg p-4 mb-6">
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
                    
                    <div class="grid grid-cols-2 gap-6">
                        <div class="form-control w-full">
                            <label class="label">
                                <span class="label-text font-semibold text-gray-700">Position</span>
                            </label>
                            <select x-model="existingPlayerTeamData.position" 
                                    class="select select-bordered w-full bg-gray-50 border-gray-300 focus:border-orange-500 focus:ring-2 focus:ring-orange-200 rounded-lg">
                                <option value="">Select position</option>
                                <option value="Forward">Forward</option>
                                <option value="Midfielder">Midfielder</option>
                                <option value="Defender">Defender</option>
                                <option value="Goalkeeper">Goalkeeper</option>
                            </select>
                        </div>
                        
                        <div class="form-control w-full">
                            <label class="label">
                                <span class="label-text font-semibold text-gray-700">Jersey Number</span>
                            </label>
                            <input type="number" x-model="existingPlayerTeamData.jersey_number" 
                                   min="1" max="999" 
                                   class="input input-bordered w-full bg-gray-50 border-gray-300 focus:border-orange-500 focus:ring-2 focus:ring-orange-200 rounded-lg"
                                   inputmode="numeric"
                                   pattern="[0-9]*" />
                        </div>
                    </div>
                    
                    <div class="form-control w-full mt-6">
                        <label class="label">
                            <span class="label-text font-semibold text-gray-700">Notes</span>
                        </label>
                        <textarea x-model="existingPlayerTeamData.notes" 
                                  class="textarea textarea-bordered bg-gray-50 border-gray-300 focus:border-orange-500 focus:ring-2 focus:ring-orange-200 rounded-lg" 
                                  rows="3"
                                  placeholder="Add any team-specific notes..."></textarea>
                    </div>
                    
                    <div class="modal-action mt-6 md:mt-8 flex flex-col sm:flex-row gap-2">
                        <button type="button" 
                                class="btn bg-gray-200 hover:bg-gray-300 text-gray-800 border-0 rounded-lg px-6 order-2 sm:order-1" 
                                @click="closeAddExistingPlayerModal">Cancel</button>
                        <button type="submit" 
                                class="btn bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white border-0 rounded-lg px-8 shadow-lg order-1 sm:order-2">
                            Add to Team
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>