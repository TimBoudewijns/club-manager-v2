<!-- Edit Trainer Modal -->
<div x-show="showEditTrainerModal" 
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="modal-wrapper" 
     style="display: none;">
    <div class="modal-container">
        <div class="modal-backdrop" @click="showEditTrainerModal = false"></div>
        
        <div class="modal-content bg-white rounded-2xl shadow-2xl max-w-2xl w-full overflow-hidden"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform scale-90"
             x-transition:enter-end="opacity-100 transform scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 transform scale-100"
             x-transition:leave-end="opacity-0 transform scale-90"
             @click.stop>
            <div class="bg-gradient-to-r from-orange-500 to-orange-600 p-6 text-white">
                <h3 class="font-bold text-2xl">Edit Trainer</h3>
                <p class="text-orange-100 mt-1">Update trainer teams and role</p>
            </div>
            <form @submit.prevent="updateTrainer" class="p-6">
                <div class="space-y-6">
                    <!-- Trainer Info (Read-only) -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="flex items-center space-x-4">
                            <div class="flex-shrink-0 h-12 w-12 bg-orange-100 rounded-full flex items-center justify-center">
                                <span class="text-orange-600 font-bold" x-text="editingTrainer ? (editingTrainer.first_name ? editingTrainer.first_name.charAt(0) : '') + (editingTrainer.last_name ? editingTrainer.last_name.charAt(0) : '') : ''"></span>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900" x-text="editingTrainer?.display_name"></p>
                                <p class="text-sm text-gray-500" x-text="editingTrainer?.email"></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-control w-full">
                        <label class="label">
                            <span class="label-text font-semibold text-gray-700">Select Teams</span>
                            <span class="label-text-alt text-gray-500">Choose which teams this trainer can access</span>
                        </label>
                        <div class="space-y-2 max-h-48 overflow-y-auto border border-gray-200 rounded-lg p-3">
                            <template x-for="team in (managedTeams || [])" :key="team.id">
                                <label class="flex items-center space-x-3 p-2 hover:bg-gray-50 rounded cursor-pointer">
                                    <input type="checkbox" 
                                           :value="team.id"
                                           :checked="editTrainerData.selectedTeams && editTrainerData.selectedTeams.includes(team.id)"
                                           @change="toggleEditTeamSelection(team.id)"
                                           class="checkbox checkbox-orange" />
                                    <span class="text-gray-900" x-text="team.name || 'Unknown'"></span>
                                    <span class="text-sm text-gray-500">(<span x-text="team.season || ''"></span>)</span>
                                </label>
                            </template>
                        </div>
                    </div>
                    
                    <div class="form-control w-full">
                        <label class="label">
                            <span class="label-text font-semibold text-gray-700">Role</span>
                        </label>
                        <select x-model="editTrainerData.role" 
                                class="select select-bordered w-full bg-gray-50 border-gray-300 focus:border-orange-500 focus:ring-2 focus:ring-orange-200 rounded-lg">
                            <option value="trainer">Trainer</option>
                            <option value="assistant_trainer">Assistant Trainer</option>
                            <option value="analyst">Analyst</option>
                        </select>
                    </div>
                </div>
                
                <div class="modal-action mt-8">
                    <button type="button" 
                            class="btn bg-gray-200 hover:bg-gray-300 text-gray-800 border-0 rounded-lg px-6" 
                            @click="showEditTrainerModal = false">Cancel</button>
                    <button type="submit" 
                            class="btn bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white border-0 rounded-lg px-8 shadow-lg">
                        Update Trainer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>