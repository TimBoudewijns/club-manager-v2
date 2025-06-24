<!-- Player Card Modal -->
<div x-show="showPlayerCardModal" 
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-[60] overflow-y-auto" 
     style="display: none;">
    <div class="modal-container">
        <div class="modal-backdrop" @click="closePlayerCardModal()"></div>
        
        <div class="modal-content bg-white rounded-2xl shadow-2xl max-w-6xl w-full"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform scale-90"
             x-transition:enter-end="opacity-100 transform scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 transform scale-100"
             x-transition:leave-end="opacity-0 transform scale-90"
             @click.stop>
            <!-- Modal Header - Dynamic color based on team type -->
            <div class="p-4 md:p-6 text-white"
                 :class="modalIsClubView ? 'bg-gradient-to-r from-blue-500 to-blue-600' : 'bg-gradient-to-r from-orange-500 to-orange-600'">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-xl md:text-2xl">Player Card</h3>
                        <p class="mt-1" :class="modalIsClubView ? 'text-blue-100' : 'text-orange-100'" 
                           x-text="modalViewingPlayer?.first_name + ' ' + modalViewingPlayer?.last_name"></p>
                    </div>
                    <button @click="closePlayerCardModal()" 
                            class="text-white hover:bg-white/20 rounded-full p-2 transition-all duration-200 hover:scale-110 hover:rotate-90 group">
                        <svg class="w-5 h-5 stroke-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            
            <!-- Modal Body -->
            <div class="max-h-[calc(90vh-120px)] overflow-y-auto -webkit-overflow-scrolling-touch">
                <!-- Player Card Content - Dynamic colors based on team type -->
                <div id="modalPlayerCardContent" class="p-3 md:p-6 lg:p-8"
                     :class="modalIsClubView ? 'bg-gradient-to-br from-blue-50 to-sky-50' : 'bg-gradient-to-br from-orange-50 to-amber-50'">
                    <!-- Download PDF Button -->
                    <div class="flex justify-end mb-3 md:mb-4">
                        <button @click="downloadPlayerCardPDF($event, modalIsClubView, true)" 
                                class="text-white font-bold py-2 px-3 md:px-4 rounded-lg shadow-md transform transition-all duration-200 hover:scale-105 flex items-center space-x-1 md:space-x-2 text-sm md:text-base"
                                :class="modalIsClubView ? 'bg-blue-500 hover:bg-blue-600' : 'bg-orange-500 hover:bg-orange-600'">
                            <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <span class="hidden sm:inline">Download PDF</span>
                            <span class="sm:hidden">PDF</span>
                        </button>
                    </div>
                    
                    <div class="flex flex-col lg:flex-row gap-4 md:gap-6 lg:gap-8">
                        <!-- Player Info Section -->
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-col sm:flex-row sm:items-center gap-4 mb-4 md:mb-6">
                                <div class="flex-shrink-0 h-16 w-16 md:h-20 md:w-20 rounded-full flex items-center justify-center text-white font-bold text-xl md:text-2xl shadow-lg mx-auto sm:mx-0"
                                     :class="modalIsClubView ? 'bg-gradient-to-br from-blue-400 to-blue-600' : 'bg-gradient-to-br from-orange-400 to-orange-600'">
                                    <span x-text="(modalViewingPlayer?.first_name ? modalViewingPlayer.first_name.charAt(0) : '') + (modalViewingPlayer?.last_name ? modalViewingPlayer.last_name.charAt(0) : '')"></span>
                                </div>
                                <div class="text-center sm:text-left sm:ml-4 lg:ml-6">
                                    <h3 class="text-xl md:text-2xl font-bold text-gray-900" x-text="modalViewingPlayer?.first_name + ' ' + modalViewingPlayer?.last_name"></h3>
                                    <p class="text-gray-600" x-text="modalIsClubView ? selectedClubTeam?.name : selectedTeam?.name"></p>
                                    <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4 mt-2 text-sm">
                                        <span class="inline-flex items-center justify-center px-3 py-1 rounded-full text-xs font-medium"
                                              :class="modalIsClubView ? 'bg-blue-100 text-blue-800' : 'bg-orange-100 text-orange-800'">
                                            <span x-text="modalViewingPlayer?.position || 'Not assigned'"></span>
                                        </span>
                                        <span class="inline-flex items-center justify-center">
                                            <span class="font-medium text-gray-500">Jersey #</span>
                                            <span class="ml-1 font-bold text-gray-900" x-text="modalViewingPlayer?.jersey_number || '-'"></span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Player Details -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 md:gap-4 mb-4 md:mb-6">
                                <div class="bg-white rounded-lg p-3 md:p-4 shadow-sm">
                                    <p class="text-xs md:text-sm text-gray-500">Email</p>
                                    <p class="font-medium text-gray-900 text-sm md:text-base break-all" x-text="modalViewingPlayer?.email"></p>
                                </div>
                                <div class="bg-white rounded-lg p-3 md:p-4 shadow-sm">
                                    <p class="text-xs md:text-sm text-gray-500">Birth Date</p>
                                    <p class="font-medium text-gray-900 text-sm md:text-base" x-text="modalViewingPlayer?.birth_date"></p>
                                </div>
                            </div>
                            
                            <!-- Notes -->
                            <div x-show="modalViewingPlayer?.notes" class="bg-white rounded-lg p-3 md:p-4 shadow-sm mb-4 md:mb-6">
                                <p class="text-xs md:text-sm text-gray-500 mb-2">Notes</p>
                                <p class="text-gray-900 text-sm md:text-base" x-text="modalViewingPlayer?.notes"></p>
                            </div>
                            
                            <!-- Evaluation History -->
                            <div>
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-3">
                                    <h4 class="text-base md:text-lg font-semibold text-gray-900">Evaluation History</h4>
                                    <select x-model="selectedEvaluationDate" 
                                            @change="onEvaluationDateChange"
                                            x-show="availableEvaluationDates.length > 0"
                                            class="select select-sm select-bordered bg-white border-gray-300 focus:ring-2 rounded-lg text-sm w-full sm:w-auto"
                                            :class="modalIsClubView ? 'focus:border-blue-500 focus:ring-blue-200' : 'focus:border-orange-500 focus:ring-orange-200'">
                                        <option value="all">All Evaluations</option>
                                        <template x-for="date in availableEvaluationDates" :key="date">
                                            <option :value="date" x-text="new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })"></option>
                                        </template>
                                    </select>
                                </div>
                                <div class="bg-white rounded-lg p-3 md:p-4 shadow-sm">
                                    <div x-show="getFilteredEvaluationHistory().length > 0" class="max-h-64 md:max-h-96 overflow-y-auto space-y-3 md:space-y-4">
                                        <template x-for="(eval, index) in getFilteredEvaluationHistory()" :key="index">
                                            <div class="border-l-4 pl-3 md:pl-4 pb-3 md:pb-4"
                                                 :class="modalIsClubView ? 'border-blue-300' : 'border-orange-300'">
                                                <div class="flex justify-between items-center mb-2">
                                                    <span class="text-xs md:text-sm font-medium text-gray-700" x-text="eval.category.replace(/_/g, ' ').split(' ').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ')"></span>
                                                    <span class="text-xs text-gray-500" x-text="new Date(eval.evaluated_at).toLocaleDateString()"></span>
                                                </div>
                                                <div class="flex items-center mb-2 md:mb-3">
                                                    <span class="text-base md:text-lg font-bold" 
                                                          :class="modalIsClubView ? 'text-blue-600' : 'text-orange-600'" 
                                                          x-text="parseFloat(eval.score).toFixed(1)"></span>
                                                    <span class="text-xs md:text-sm text-gray-500 ml-2">/10</span>
                                                </div>
                                                
                                                <!-- Show subcategory scores for this evaluation -->
                                                <div class="space-y-1 md:space-y-2" x-data="{ subcategories: getSubcategoryEvaluations(eval.category, eval.evaluated_at) }">
                                                    <template x-for="subEval in subcategories" :key="subEval.subcategory">
                                                        <div class="flex justify-between items-center text-xs bg-gray-50 rounded px-2 py-1">
                                                            <span class="text-gray-600 flex-1 mr-2" x-text="formatSubcategoryName(subEval.subcategory)"></span>
                                                            <span class="font-medium text-gray-700 flex-shrink-0" x-text="parseFloat(subEval.score).toFixed(1) + '/10'"></span>
                                                        </div>
                                                    </template>
                                                </div>
                                                
                                                <!-- Notes if available -->
                                                <div x-show="eval.notes && eval.notes.trim()" class="mt-2 text-xs text-gray-600 italic" x-text="eval.notes"></div>
                                            </div>
                                        </template>
                                    </div>
                                    <div x-show="getFilteredEvaluationHistory().length === 0" class="text-gray-500 text-center py-4 text-sm">
                                        <span x-show="selectedEvaluationDate === 'all'">No evaluations yet</span>
                                        <span x-show="selectedEvaluationDate !== 'all'">No evaluations for this date</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Spider Chart Section -->
                        <div class="flex-1 bg-white rounded-xl p-4 md:p-6 shadow-lg min-w-0">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-3 md:mb-4">
                                <h4 class="text-base md:text-lg font-semibold text-gray-900">Performance Overview</h4>
                                <span x-show="selectedEvaluationDate !== 'all'" 
                                      class="text-xs md:text-sm font-medium"
                                      :class="modalIsClubView ? 'text-blue-600' : 'text-orange-600'">
                                    <span x-text="new Date(selectedEvaluationDate).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })"></span>
                                </span>
                            </div>
                            <div class="relative h-64 md:h-80 lg:h-96 mb-4 md:mb-6">
                                <canvas id="modalPlayerCardSpiderChart" style="display: block; width: 100%; height: 100%;"></canvas>
                            </div>
                            
                            <!-- Category Scores -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-1 md:gap-2 text-xs md:text-sm max-h-32 md:max-h-48 overflow-y-auto">
                                <template x-for="category in evaluationCategories" :key="category.key">
                                    <div class="flex justify-between items-center py-1 px-2 rounded hover:bg-gray-50">
                                        <span class="text-gray-600 text-xs truncate pr-2" x-text="category.name"></span>
                                        <span class="font-bold flex-shrink-0" 
                                              :class="modalIsClubView ? 'text-blue-600' : 'text-orange-600'" 
                                              x-text="getPlayerCardCategoryAverage(category.key)"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                    
                    <!-- AI Coaching Advice - Full Width -->
                    <div class="mt-6 md:mt-8">
                        <h4 class="text-base md:text-lg font-semibold text-gray-900 mb-3">AI Coaching Advice</h4>
                        <div class="bg-white rounded-lg p-4 md:p-6 shadow-sm">
                            <!-- No evaluations state -->
                            <div x-show="adviceStatus === 'no_evaluations' && !playerAdvice" class="text-center py-8">
                                <div class="bg-gray-50 rounded-full w-16 h-16 mx-auto mb-4 flex items-center justify-center">
                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                                    </svg>
                                </div>
                                <p class="text-gray-600" x-show="modalIsClubView">This player has no evaluations yet. Only the team's trainer can evaluate players.</p>
                                <p class="text-gray-600" x-show="!modalIsClubView">Complete an evaluation first to receive personalized coaching advice.</p>
                            </div>
                            
                            <!-- No advice yet but has evaluations -->
                            <div x-show="adviceStatus === 'no_advice_yet' && !playerAdvice" class="text-center py-8">
                                <div class="bg-blue-50 rounded-full w-16 h-16 mx-auto mb-4 flex items-center justify-center">
                                    <svg class="w-8 h-8 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <p class="text-gray-600">AI advice will be generated after the next evaluation.</p>
                            </div>
                            
                            <!-- Loading/Generating state -->
                            <div x-show="adviceLoading || adviceStatus === 'generating'" class="text-center py-8">
                                <div class="rounded-full w-16 h-16 mx-auto mb-4 flex items-center justify-center animate-pulse"
                                     :class="modalIsClubView ? 'bg-blue-50' : 'bg-orange-50'">
                                    <svg class="w-8 h-8 animate-spin" 
                                         :class="modalIsClubView ? 'text-blue-500' : 'text-orange-500'" 
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>
                                <p class="text-gray-600">Generating personalized advice based on recent evaluations...</p>
                                <p class="text-sm text-gray-500 mt-2">This may take up to a minute.</p>
                            </div>
                            
                            <!-- Advice content -->
                            <div x-show="playerAdvice && !adviceLoading && adviceStatus === 'current'" class="prose prose-sm max-w-none">
                                <div class="whitespace-pre-wrap text-gray-800" x-text="playerAdvice"></div>
                                <div class="mt-4 pt-4 border-t border-gray-200">
                                    <p class="text-xs text-gray-500">
                                        <span x-show="lastAdviceTimestamp">Last updated: <span x-text="new Date(lastAdviceTimestamp).toLocaleString()"></span></span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>