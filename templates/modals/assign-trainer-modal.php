<!-- Assign Trainer Modal -->
<div x-show="showAssignTrainerModal" 
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="modal-wrapper" 
     style="display: none;">
    <div class="modal-container">
        <div class="modal-backdrop" @click="showAssignTrainerModal = false"></div>
        
        <div class="modal-content bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden"
             @click.stop>
            <div class="bg-gradient-to-r from-orange-500 to-orange-600 p-6 text-white">
                <h3 class="font-bold text-2xl">Assign Trainer</h3>
                <p class="text-orange-100 mt-1">Add a trainer to <span x-text="selectedManagedTeam?.name"></span></p>
            </div>
            <form @submit.prevent="trainerAssignment.teamId = selectedManagedTeam?.id; trainerAssignment.trainerId && assignTrainerToTeam()" class="p-6">
                <div class="form-control w-full">
                    <label class="label">
                        <span class="label-text font-semibold text-gray-700">Select Trainer</span>
                    </label>
                    <select x-model="trainerAssignment.trainerId" 
                            class="select select-bordered w-full bg-gray-50 border-gray-300 focus:border-orange-500 focus:ring-2 focus:ring-orange-200 rounded-lg"
                            required>
                        <option value="">Choose a trainer...</option>
                        <template x-for="trainer in availableTrainers" :key="trainer.id">
                            <option :value="trainer.id" x-text="trainer.display_name"></option>
                        </template>
                    </select>
                </div>
                
                <div class="modal-action mt-8">
                    <button type="button" 
                            class="btn bg-gray-200 hover:bg-gray-300 text-gray-800 border-0 rounded-lg px-6" 
                            @click="showAssignTrainerModal = false">Cancel</button>
                    <button type="submit" 
                            class="btn bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white border-0 rounded-lg px-8 shadow-lg">
                        Assign Trainer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>