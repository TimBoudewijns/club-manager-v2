<!-- Player History Modal -->
<div x-show="showPlayerHistoryModal" 
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="modal-wrapper" 
     style="display: none;">
    <div class="modal-container">
        <div class="modal-backdrop" @click="showPlayerHistoryModal = false"></div>
        
        <div class="modal-content bg-white rounded-2xl shadow-2xl max-w-3xl w-full overflow-hidden"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform scale-90"
             x-transition:enter-end="opacity-100 transform scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 transform scale-100"
             x-transition:leave-end="opacity-0 transform scale-90"
             @click.stop>
            <div class="bg-gradient-to-r from-orange-500 to-orange-600 p-4 md:p-6 text-white">
                <h3 class="font-bold text-xl md:text-2xl">Player History</h3>
                <p class="text-orange-100 mt-1 text-sm md:text-base" x-show="historyPlayer">
                    <span x-text="historyPlayer?.first_name + ' ' + historyPlayer?.last_name"></span>
                </p>
            </div>
            <div class="p-4 md:p-6 max-h-[calc(90vh-120px)] overflow-y-auto -webkit-overflow-scrolling-touch">
                <!-- Loading State -->
                <div x-show="historyLoading" class="text-center py-8">
                    <div class="inline-flex items-center">
                        <svg class="animate-spin h-8 w-8 text-orange-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="ml-2">Loading history...</span>
                    </div>
                </div>
                
                <!-- History List -->
                <div x-show="!historyLoading && playerHistory && playerHistory.length > 0" class="space-y-4">
                    <template x-for="(record, index) in playerHistory" :key="index">
                        <div class="bg-gray-50 rounded-lg p-4 hover:bg-gray-100 transition-colors">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h4 class="font-semibold text-lg text-gray-900" x-text="record.team_name"></h4>
                                    <div class="mt-2 space-y-1 text-sm text-gray-600">
                                        <div class="flex items-center">
                                            <svg class="w-4 h-4 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                            <span>Season: <span class="font-medium text-gray-900" x-text="record.season"></span></span>
                                        </div>
                                        <div class="flex items-center" x-show="record.position">
                                            <svg class="w-4 h-4 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            </svg>
                                            <span>Position: <span class="font-medium text-gray-900" x-text="record.position || 'Not specified'"></span></span>
                                        </div>
                                        <div class="flex items-center" x-show="record.jersey_number">
                                            <svg class="w-4 h-4 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"></path>
                                            </svg>
                                            <span>Jersey: <span class="font-medium text-gray-900" x-text="record.jersey_number"></span></span>
                                        </div>
                                    </div>
                                    <div x-show="record.notes" class="mt-3 text-sm text-gray-600 italic" x-text="record.notes"></div>
                                </div>
                                <div class="text-right">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                        <span x-text="record.season"></span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
                
                <!-- Empty State -->
                <div x-show="!historyLoading && (!playerHistory || playerHistory.length === 0)" class="text-center py-8">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <p class="mt-2 text-gray-500">No history found for this player</p>
                </div>
                
                <div class="modal-action mt-8">
                    <button type="button" 
                            class="btn bg-gray-200 hover:bg-gray-300 text-gray-800 border-0 rounded-lg px-6" 
                            @click="showPlayerHistoryModal = false">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>