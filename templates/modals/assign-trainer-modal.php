<!-- Assign Trainer Modal -->
<div x-show="showAssignTrainerModal" 
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-50 overflow-y-auto" 
     style="display: none;">
    <div class="modal-container">
        <div class="modal-backdrop" @click="showAssignTrainerModal = false"></div>
        
        <div class="modal-content bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden"
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
                        <h3 class="font-semibold text-xl">Assign Trainer</h3>
                        <p class="text-orange-100 mt-1">Add a trainer to <span x-text="selectedManagedTeam?.name" class="font-semibold"></span></p>
                    </div>
                    <button @click="showAssignTrainerModal = false" 
                            class="text-white hover:text-orange-200 p-1.5 rounded-full hover:bg-white/10 transition-colors">
                        <svg class="w-4 h-4" style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            
            <!-- Scrollable Content -->
            <div class="max-h-[calc(90vh-120px)] overflow-y-auto -webkit-overflow-scrolling-touch">
                <form @submit.prevent="assignTrainerToTeam($event)" class="p-6">
                    <div class="form-control w-full">
                        <label class="label">
                            <span class="label-text font-semibold text-gray-700">Select Trainer</span>
                        </label>
                        
                        <!-- Loading state -->
                        <div x-show="availableTrainersLoading" class="text-center py-8 text-gray-500">
                            <svg class="animate-spin h-8 w-8 mx-auto mb-3 text-orange-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <p class="text-gray-600">Loading available trainers...</p>
                        </div>
                        
                        <!-- Trainer select dropdown -->
                        <div x-show="!availableTrainersLoading">
                            <select x-show="availableTrainers && availableTrainers.length > 0"
                                    x-model="trainerAssignment.trainerId" 
                                    class="border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 w-full bg-gray-50 border-gray-300 focus:border-orange-500 focus:ring-2 focus:ring-orange-200 rounded-lg"
                                    required>
                                <option value="">Choose a trainer...</option>
                                
                                <!-- Active trainers section -->
                                <template x-if="availableTrainers.filter(t => t.type === 'active').length > 0">
                                    <optgroup label="Active Trainers">
                                        <template x-for="trainer in availableTrainers.filter(t => t.type === 'active')" :key="'active-' + trainer.id">
                                            <option :value="trainer.id"
                                                    :disabled="teamTrainers && teamTrainers.some(tt => tt.trainer_id == trainer.id)">
                                                <span x-text="trainer.display_name"></span>
                                                <span x-show="trainer.email" x-text="' (' + trainer.email + ')'"></span>
                                                <span x-show="teamTrainers && teamTrainers.some(tt => tt.trainer_id == trainer.id)"> - Already assigned</span>
                                            </option>
                                        </template>
                                    </optgroup>
                                </template>
                                
                                <!-- Pending invitations section -->
                                <template x-if="availableTrainers.filter(t => t.type === 'pending').length > 0">
                                    <optgroup label="Pending Invitations">
                                        <template x-for="trainer in availableTrainers.filter(t => t.type === 'pending')" :key="'pending-' + trainer.id">
                                            <option :value="'pending:' + trainer.email">
                                                <span x-text="trainer.email || trainer.display_name || 'Unknown'"></span>
                                                <span> (Invitation Pending)</span>
                                            </option>
                                        </template>
                                    </optgroup>
                                </template>
                            </select>
                            
                            <!-- No trainers available message -->
                            <div x-show="!availableTrainersLoading && (!availableTrainers || availableTrainers.length === 0)" 
                                 class="text-center py-8 text-gray-500 bg-gray-50 rounded-lg border border-gray-200">
                                <svg class="w-12 h-12 mx-auto mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                </svg>
                                <p class="font-medium mb-2 text-gray-700">No trainers available</p>
                                <p class="text-sm">Invite trainers first from the Trainer Management tab.</p>
                            </div>
                        </div>
                        
                        <label class="label" x-show="availableTrainers && availableTrainers.length > 0">
                            <span class="label-text-alt text-gray-500">Trainers with pending invitations will be assigned when they accept</span>
                        </label>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="mt-8 flex flex-col sm:flex-row gap-3">
                        <button type="button" 
                                class="bg-gray-200 hover:bg-gray-300 text-gray-800 border-0 rounded-lg font-medium py-2 px-6 order-2 sm:order-1" 
                                @click="showAssignTrainerModal = false">Cancel</button>
                        <button type="submit" 
                                :disabled="!trainerAssignment.trainerId || !availableTrainers || availableTrainers.length === 0 || availableTrainersLoading"
                                class="bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 border-0 rounded-lg px-8 shadow-lg order-1 sm:order-2 disabled:opacity-50 disabled:cursor-not-allowed"
                                style="color: white !important;">
                            Assign Trainer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>