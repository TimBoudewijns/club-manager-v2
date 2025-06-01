<!-- Add Player Modal -->
<div x-show="showAddPlayerModal" 
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="modal-wrapper" 
     style="display: none;">
    <div class="modal-container">
        <div class="modal-backdrop" @click="showAddPlayerModal = false"></div>
        
        <div class="modal-content bg-white rounded-2xl shadow-2xl max-w-3xl w-full overflow-hidden max-h-[90vh] overflow-y-auto"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform scale-90"
             x-transition:enter-end="opacity-100 transform scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 transform scale-100"
             x-transition:leave-end="opacity-0 transform scale-90"
             @click.stop>
            <div class="bg-gradient-to-r from-orange-500 to-orange-600 p-4 md:p-6 text-white sticky top-0 z-10">
                <h3 class="font-bold text-xl md:text-2xl">Add New Player</h3>
                <p class="text-orange-100 mt-1 text-sm md:text-base">Create a new player profile for <span x-text="selectedTeam?.name"></span></p>
            </div>
            <form @submit.prevent="createPlayer" class="p-4 md:p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
                    <div class="form-control w-full">
                        <label class="label">
                            <span class="label-text font-semibold text-gray-700">First Name</span>
                        </label>
                        <input type="text" x-model="newPlayer.first_name" 
                               class="input input-bordered w-full bg-gray-50 border-gray-300 focus:border-orange-500 focus:ring-2 focus:ring-orange-200 rounded-lg" 
                               required />
                    </div>
                    
                    <div class="form-control w-full">
                        <label class="label">
                            <span class="label-text font-semibold text-gray-700">Last Name</span>
                        </label>
                        <input type="text" x-model="newPlayer.last_name" 
                               class="input input-bordered w-full bg-gray-50 border-gray-300 focus:border-orange-500 focus:ring-2 focus:ring-orange-200 rounded-lg" 
                               required />
                    </div>
                    
                    <div class="form-control w-full">
                        <label class="label">
                            <span class="label-text font-semibold text-gray-700">Birth Date</span>
                        </label>
                        <input type="date" x-model="newPlayer.birth_date" 
                               class="input input-bordered w-full bg-gray-50 border-gray-300 focus:border-orange-500 focus:ring-2 focus:ring-orange-200 rounded-lg" 
                               required />
                    </div>
                    
                    <div class="form-control w-full">
                        <label class="label">
                            <span class="label-text font-semibold text-gray-700">Email</span>
                        </label>
                        <input type="email" x-model="newPlayer.email" 
                               class="input input-bordered w-full bg-gray-50 border-gray-300 focus:border-orange-500 focus:ring-2 focus:ring-orange-200 rounded-lg" 
                               required />
                    </div>
                    
                    <div class="form-control w-full">
                        <label class="label">
                            <span class="label-text font-semibold text-gray-700">Position</span>
                        </label>
                        <select x-model="newPlayer.position" 
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
                        <input type="number" x-model="newPlayer.jersey_number" 
                               min="1" max="999" 
                               class="input input-bordered w-full bg-gray-50 border-gray-300 focus:border-orange-500 focus:ring-2 focus:ring-orange-200 rounded-lg" />
                    </div>
                </div>
                
                <div class="form-control w-full mt-4 md:mt-6">
                    <label class="label">
                        <span class="label-text font-semibold text-gray-700">Notes</span>
                    </label>
                    <textarea x-model="newPlayer.notes" 
                              class="textarea textarea-bordered bg-gray-50 border-gray-300 focus:border-orange-500 focus:ring-2 focus:ring-orange-200 rounded-lg" 
                              rows="3"
                              placeholder="Add any additional notes about this player..."></textarea>
                </div>
                
                <div class="modal-action mt-6 md:mt-8 flex flex-col sm:flex-row gap-2">
                    <button type="button" 
                            class="btn bg-gray-200 hover:bg-gray-300 text-gray-800 border-0 rounded-lg px-6 order-2 sm:order-1" 
                            @click="showAddPlayerModal = false">Cancel</button>
                    <button type="submit" 
                            class="btn bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white border-0 rounded-lg px-8 shadow-lg order-1 sm:order-2">
                        Add Player
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>