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
        <div class="modal-content bg-white rounded-lg border border-gray-200 max-w-md w-full overflow-hidden"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform scale-90"
             x-transition:enter-end="opacity-100 transform scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 transform scale-100"
             x-transition:leave-end="opacity-0 transform scale-90"
             @click.stop>
            <!-- Fixed Header -->
            <div class="border-b border-gray-200 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-semibold text-lg text-gray-900">Create New Team</h3>
                        <p class="text-gray-500 text-sm mt-1">Add a new team to your management dashboard</p>
                    </div>
                    <button @click="showCreateTeamModal = false" 
                            class="text-gray-400 hover:text-gray-600 p-2 rounded-lg hover:bg-gray-100 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            
            <!-- Scrollable Content -->
            <div class="max-h-[calc(90vh-120px)] overflow-y-auto -webkit-overflow-scrolling-touch">
                <form @submit.prevent="createTeam($event)" class="p-6">
                    <div class="space-y-6">
                        <div class="form-control w-full">
                            <label class="label">
                                <span class="label-text font-semibold text-gray-700">Team Name</span>
                            </label>
                            <input type="text" x-model="newTeam.name" 
                                   placeholder="Enter team name" 
                                   class="w-full bg-white border border-gray-300 focus:border-gray-900 focus:ring-1 focus:ring-gray-900 rounded-lg px-3 py-2.5 text-sm transition-all" 
                                   required />
                        </div>
                        
                        <div class="form-control w-full">
                            <label class="label">
                                <span class="label-text font-semibold text-gray-700">Coach</span>
                            </label>
                            <input type="text" x-model="newTeam.coach" 
                                   placeholder="Enter coach name" 
                                   class="w-full bg-white border border-gray-300 focus:border-gray-900 focus:ring-1 focus:ring-gray-900 rounded-lg px-3 py-2.5 text-sm transition-all" 
                                   required />
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="mt-8 flex flex-col sm:flex-row gap-3">
                        <button type="button" 
                                class="bg-white hover:bg-gray-50 text-gray-700 border border-gray-300 rounded-lg px-6 py-2.5 font-medium text-sm transition-colors order-2 sm:order-1" 
                                @click="showCreateTeamModal = false">Cancel</button>
                        <button type="submit" 
                                class="bg-gray-900 hover:bg-gray-800 text-white rounded-lg px-8 py-2.5 font-medium text-sm transition-colors order-1 sm:order-2">
                            Create Team
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>