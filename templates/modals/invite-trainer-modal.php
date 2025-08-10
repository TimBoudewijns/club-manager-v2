<!-- Invite Trainer Modal -->
<div x-show="showInviteTrainerModal" 
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-50 overflow-y-auto" 
     style="display: none;">
    <div class="modal-container">
        <div class="modal-backdrop" @click="showInviteTrainerModal = false"></div>
        
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
                        <h3 class="font-bold text-2xl">Invite Trainer</h3>
                        <p class="text-orange-100 mt-1">Send an invitation to a trainer to join your club</p>
                    </div>
                    <button @click="showInviteTrainerModal = false" 
                            class="text-white hover:text-orange-200 p-1.5 rounded-full hover:bg-white/10 transition-colors">
                        <svg class="w-4 h-4" style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            
            <!-- Scrollable Content -->
            <div class="max-h-[calc(90vh-120px)] overflow-y-auto -webkit-overflow-scrolling-touch">
                <form @submit.prevent="inviteTrainer($event)" class="p-6">
                    <div class="space-y-6">
                        <div class="form-control w-full">
                            <label class="label">
                                <span class="label-text font-semibold text-gray-700">Email Address</span>
                            </label>
                            <input type="email" x-model="newTrainerInvite.email" 
                                   placeholder="trainer@example.com" 
                                   class="input input-bordered w-full bg-gray-50 border-gray-300 focus:border-orange-500 focus:ring-2 focus:ring-orange-200 rounded-lg" 
                                   required />
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
                                               @change="toggleTeamSelection(team.id)"
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
                            <select x-model="newTrainerInvite.role" 
                                    class="select select-bordered w-full bg-gray-50 border-gray-300 focus:border-orange-500 focus:ring-2 focus:ring-orange-200 rounded-lg">
                                <option value="trainer">Trainer</option>
                                <option value="assistant_trainer">Assistant Trainer</option>
                                <option value="analyst">Analyst</option>
                            </select>
                        </div>
                        
                        <div class="form-control w-full">
                            <label class="label">
                                <span class="label-text font-semibold text-gray-700">Personal Message (Optional)</span>
                            </label>
                            <textarea x-model="newTrainerInvite.message" 
                                      class="textarea textarea-bordered bg-gray-50 border-gray-300 focus:border-orange-500 focus:ring-2 focus:ring-orange-200 rounded-lg" 
                                      rows="3"
                                      placeholder="Add a personal message to the invitation..."></textarea>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="mt-8 flex flex-col sm:flex-row gap-3">
                        <button type="button" 
                                class="btn bg-gray-200 hover:bg-gray-300 text-gray-800 border-0 rounded-lg px-6 order-2 sm:order-1" 
                                @click="showInviteTrainerModal = false">Cancel</button>
                        <button type="submit" 
                                class="btn bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white border-0 rounded-lg px-8 shadow-lg order-1 sm:order-2">
                            Send Invitation
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>