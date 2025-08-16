<!-- Add Player Modal -->
<div x-show="showAddPlayerModal" 
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-50 overflow-y-auto" 
     style="display: none;">
    <div class="modal-container">
        <div class="modal-backdrop" @click="showAddPlayerModal = false"></div>
        
        <div class="modal-content bg-white rounded-2xl shadow-xl border border-slate-200 max-w-3xl w-full"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform scale-90"
             x-transition:enter-end="opacity-100 transform scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 transform scale-100"
             x-transition:leave-end="opacity-0 transform scale-90"
             @click.stop>
            <div class="bg-gradient-to-r from-[#F77F00] to-orange-600 p-4 md:p-6 text-white rounded-t-2xl">
                <h3 class="font-semibold text-base md:text-lg tracking-tight">Add New Player</h3>
                <p class="text-orange-100 mt-1 text-sm md:text-base">Create a new player profile for <span x-text="selectedTeam?.name"></span></p>
            </div>
            <form @submit.prevent="createPlayer($event)" class="p-4 md:p-6 max-h-[calc(90vh-120px)] overflow-y-auto -webkit-overflow-scrolling-touch">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
                    <div class="form-control w-full">
                        <label class="label">
                            <span class="label-text font-semibold text-gray-700">First Name</span>
                        </label>
                        <input type="text" x-model="newPlayer.first_name" 
                               class="input input-bordered w-full bg-slate-50 border-slate-200 focus:border-[#F77F00] focus:ring-2 focus:ring-orange-200 rounded-xl" 
                               required />
                    </div>
                    
                    <div class="form-control w-full">
                        <label class="label">
                            <span class="label-text font-semibold text-gray-700">Last Name</span>
                        </label>
                        <input type="text" x-model="newPlayer.last_name" 
                               class="input input-bordered w-full bg-slate-50 border-slate-200 focus:border-[#F77F00] focus:ring-2 focus:ring-orange-200 rounded-xl" 
                               required />
                    </div>
                    
                    <div class="form-control w-full">
                        <label class="label">
                            <span class="label-text font-semibold text-gray-700">Birth Date</span>
                        </label>
                        <input type="date" x-model="newPlayer.birth_date" 
                               class="input input-bordered w-full bg-slate-50 border-slate-200 focus:border-[#F77F00] focus:ring-2 focus:ring-orange-200 rounded-xl" 
                               required />
                    </div>
                    
                    <div class="form-control w-full">
                        <label class="label">
                            <span class="label-text font-semibold text-gray-700">Email</span>
                        </label>
                        <input type="email" x-model="newPlayer.email" 
                               class="input input-bordered w-full bg-slate-50 border-slate-200 focus:border-[#F77F00] focus:ring-2 focus:ring-orange-200 rounded-xl" 
                               required />
                    </div>
                    
                    <div class="form-control w-full">
                        <label class="label">
                            <span class="label-text font-semibold text-gray-700">Position</span>
                        </label>
                        <select x-model="newPlayer.position" 
                                class="select select-bordered w-full bg-slate-50 border-slate-200 focus:border-[#F77F00] focus:ring-2 focus:ring-orange-200 rounded-xl">
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
                               class="input input-bordered w-full bg-slate-50 border-slate-200 focus:border-[#F77F00] focus:ring-2 focus:ring-orange-200 rounded-xl"
                               inputmode="numeric"
                               pattern="[0-9]*" />
                    </div>
                </div>
                
                <div class="form-control w-full mt-4 md:mt-6">
                    <label class="label">
                        <span class="label-text font-semibold text-gray-700">Notes</span>
                    </label>
                    <textarea x-model="newPlayer.notes" 
                              class="textarea textarea-bordered bg-slate-50 border-slate-200 focus:border-[#F77F00] focus:ring-2 focus:ring-orange-200 rounded-xl" 
                              rows="3"
                              placeholder="Add any additional notes about this player..."></textarea>
                </div>
                
                <div class="modal-action mt-6 md:mt-8 flex flex-col sm:flex-row gap-2">
                    <button type="button" 
                            class="btn border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 rounded-xl px-6 font-semibold order-2 sm:order-1 transition-all duration-200" 
                            @click="showAddPlayerModal = false">Cancel</button>
                    <button type="submit" 
                            class="btn bg-[#F77F00] hover:bg-[#ea580c] text-white border-0 rounded-xl px-8 shadow-md font-semibold order-1 sm:order-2 transition-all duration-200 hover:-translate-y-0.5">
                        Add Player
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>