<!-- Create Team Modal -->
<div x-show="showCreateTeamModal" 
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-50 overflow-y-auto" 
     style="display: none;">
    <!-- Modal container with scrolling -->
    <div class="modal-container">
        <!-- Backdrop -->
        <div class="modal-backdrop" @click="showCreateTeamModal = false"></div>
        
        <!-- Modal content -->
        <div class="modal-content bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform scale-90"
             x-transition:enter-end="opacity-100 transform scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 transform scale-100"
             x-transition:leave-end="opacity-0 transform scale-90"
             @click.stop>
            <div class="bg-gradient-to-r from-orange-500 to-orange-600 p-6 text-white">
                <h3 class="font-bold text-2xl">Create New Team</h3>
                <p class="text-orange-100 mt-1">Add a new team to your management dashboard</p>
            </div>
            <form @submit.prevent="createTeam" class="p-6">
                <div class="space-y-6">
                    <div class="form-control w-full">
                        <label class="label">
                            <span class="label-text font-semibold text-gray-700">Team Name</span>
                        </label>
                        <input type="text" x-model="newTeam.name" 
                               placeholder="Enter team name" 
                               class="input input-bordered w-full bg-gray-50 border-gray-300 focus:border-orange-500 focus:ring-2 focus:ring-orange-200 rounded-lg" 
                               required />
                    </div>
                    
                    <div class="form-control w-full">
                        <label class="label">
                            <span class="label-text font-semibold text-gray-700">Coach</span>
                        </label>
                        <input type="text" x-model="newTeam.coach" 
                               placeholder="Enter coach name" 
                               class="input input-bordered w-full bg-gray-50 border-gray-300 focus:border-orange-500 focus:ring-2 focus:ring-orange-200 rounded-lg" 
                               required />
                    </div>
                </div>
                
                <div class="modal-action mt-8">
                    <button type="button" 
                            class="btn bg-gray-200 hover:bg-gray-300 text-gray-800 border-0 rounded-lg px-6" 
                            @click="showCreateTeamModal = false">Cancel</button>
                    <button type="submit" 
                            class="btn bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white border-0 rounded-lg px-8 shadow-lg">
                        Create Team
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>