<!-- Edit Team Modal -->
<div x-show="showEditTeamModal" 
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="modal-wrapper" 
     style="display: none;">
    <div class="modal-container">
        <div class="modal-backdrop" @click="showEditTeamModal = false"></div>
        
        <div class="modal-content bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden"
             @click.stop>
            <div class="bg-gradient-to-r from-orange-500 to-orange-600 p-6 text-white">
                <h3 class="font-bold text-2xl">Edit Team</h3>
                <p class="text-orange-100 mt-1">Update team information</p>
            </div>
            <form @submit.prevent="updateManagedTeam" class="p-6">
                <div class="space-y-6">
                    <div class="form-control w-full">
                        <label class="label">
                            <span class="label-text font-semibold text-gray-700">Team Name</span>
                        </label>
                        <input type="text" x-model="editTeamData.name" 
                               placeholder="Enter team name" 
                               class="input input-bordered w-full bg-gray-50 border-gray-300 focus:border-orange-500 focus:ring-2 focus:ring-orange-200 rounded-lg" 
                               required />
                    </div>
                    
                    <div class="form-control w-full">
                        <label class="label">
                            <span class="label-text font-semibold text-gray-700">Coach</span>
                        </label>
                        <input type="text" x-model="editTeamData.coach" 
                               placeholder="Enter coach name" 
                               class="input input-bordered w-full bg-gray-50 border-gray-300 focus:border-orange-500 focus:ring-2 focus:ring-orange-200 rounded-lg" 
                               required />
                    </div>
                </div>
                
                <div class="modal-action mt-8">
                    <button type="button" 
                            class="btn bg-gray-200 hover:bg-gray-300 text-gray-800 border-0 rounded-lg px-6" 
                            @click="showEditTeamModal = false">Cancel</button>
                    <button type="submit" 
                            class="btn bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white border-0 rounded-lg px-8 shadow-lg">
                        Update Team
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>