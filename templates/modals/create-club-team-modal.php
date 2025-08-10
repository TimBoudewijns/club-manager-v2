<!-- Create Club Team Modal -->
<div x-show="showCreateClubTeamModal" 
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-50 overflow-y-auto" 
     style="display: none;">
    <div class="modal-container">
        <div class="modal-backdrop" @click="showCreateClubTeamModal = false"></div>
        
        <div class="modal-content bg-white rounded-2xl shadow-2xl max-w-2xl w-full overflow-hidden"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform scale-90"
             x-transition:enter-end="opacity-100 transform scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 transform scale-100"
             x-transition:leave-end="opacity-0 transform scale-90"
             @click.stop>
            <!-- Fixed Header -->
            <div class="bg-gradient-to-r from-orange-500 to-orange-600 p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-2xl">Create Club Team</h3>
                        <p class="text-orange-100 mt-1">Add a new team to your club</p>
                    </div>
                    <button @click="showCreateClubTeamModal = false" 
                            class="text-white hover:text-orange-200 p-1.5 rounded-full hover:bg-white/10 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            
            <!-- Scrollable Content -->
            <div class="max-h-[calc(90vh-120px)] overflow-y-auto -webkit-overflow-scrolling-touch">
                <form @submit.prevent="createClubTeam($event)" class="p-6">
                    <div class="space-y-6">
                        <div class="form-control w-full">
                            <label class="label">
                                <span class="label-text font-semibold text-gray-700">Team Name</span>
                            </label>
                            <input type="text" x-model="newClubTeam.name" 
                                   placeholder="Enter team name" 
                                   class="input input-bordered w-full bg-gray-50 border-gray-300 focus:border-orange-500 focus:ring-2 focus:ring-orange-200 rounded-lg" 
                                   required />
                        </div>
                        
                        <div class="form-control w-full">
                            <label class="label">
                                <span class="label-text font-semibold text-gray-700">Coach</span>
                            </label>
                            <input type="text" x-model="newClubTeam.coach" 
                                   placeholder="Enter coach name" 
                                   class="input input-bordered w-full bg-gray-50 border-gray-300 focus:border-orange-500 focus:ring-2 focus:ring-orange-200 rounded-lg" 
                                   required />
                        </div>
                        
                        <div class="form-control w-full">
                            <label class="label">
                                <span class="label-text font-semibold text-gray-700">Assign Trainers (Optional)</span>
                            </label>
                            <div class="space-y-2 max-h-48 overflow-y-auto border border-gray-200 rounded-lg p-3">
                                <template x-for="trainer in availableTrainers" :key="trainer.id">
                                    <label class="flex items-center space-x-3 p-2 hover:bg-gray-50 rounded cursor-pointer">
                                        <input type="checkbox" 
                                               :value="trainer.id"
                                               x-model="newClubTeam.trainers"
                                               class="checkbox checkbox-orange" />
                                        <span class="text-gray-900" x-text="trainer.display_name"></span>
                                    </label>
                                </template>
                                <div x-show="availableTrainers.length === 0" class="text-gray-500 text-center py-4">
                                    No trainers available. Invite trainers first.
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="mt-8 flex flex-col sm:flex-row gap-3">
                        <button type="button" 
                                class="btn bg-gray-200 hover:bg-gray-300 text-gray-800 border-0 rounded-lg px-6 order-2 sm:order-1" 
                                @click="showCreateClubTeamModal = false">Cancel</button>
                        <button type="submit" 
                                class="btn bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white border-0 rounded-lg px-8 shadow-lg order-1 sm:order-2">
                            Create Team
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>