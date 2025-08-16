<!-- Player Card Display -->
<div x-show="selectedPlayerCard && selectedPlayerCard.id === selectedTeam.id && viewingPlayer" 
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 transform translate-y-4"
     x-transition:enter-end="opacity-100 transform translate-y-0"
     class="mt-8">
    
    <!-- Player Card Content Container -->
    <div class="bg-white rounded-2xl shadow-xl border border-orange-100 overflow-hidden">
        <!-- Player Card Header -->
        <div class="bg-gradient-to-r from-orange-50 to-amber-50 border-b border-orange-200 p-6 md:p-8">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl p-3 shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"></path>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-2xl font-semibold text-gray-900 mb-1">Player Card</h2>
                        <p class="text-gray-600" x-text="viewingPlayer?.first_name + ' ' + viewingPlayer?.last_name + ' - ' + selectedTeam?.name"></p>
                    </div>
                </div>
                <!-- Download PDF Button -->
                <button @click="downloadPlayerCardPDF()" 
                        class="bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white font-bold py-3 px-6 rounded-xl shadow-lg transform transition-all duration-200 hover:scale-105 flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <span class="hidden sm:inline">Download PDF</span>
                    <span class="sm:hidden">PDF</span>
                </button>
            </div>
        </div>
        
        <!-- Player Card Content -->
        <div id="playerCardContent" class="p-6 md:p-8">
            <div class="flex flex-col lg:flex-row gap-8">
                <!-- Player Info Section -->
                <div class="flex-1">
                    <div class="flex items-center mb-6">
                        <div class="flex-shrink-0 h-20 w-20 bg-gradient-to-br from-orange-400 to-orange-600 rounded-full flex items-center justify-center text-white font-bold text-lg shadow-lg">
                            <span x-text="(viewingPlayer?.first_name ? viewingPlayer.first_name.charAt(0) : '') + (viewingPlayer?.last_name ? viewingPlayer.last_name.charAt(0) : '')"></span>
                        </div>
                        <div class="ml-6">
                            <h3 class="text-lg font-semibold text-gray-900" x-text="viewingPlayer?.first_name + ' ' + viewingPlayer?.last_name"></h3>
                            <p class="text-gray-600" x-text="selectedTeam?.name"></p>
                            <div class="flex items-center mt-2 space-x-4 text-sm">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                    <span x-text="viewingPlayer?.position || 'Not assigned'"></span>
                                </span>
                                <span class="inline-flex items-center">
                                    <span class="font-medium text-gray-500">Jersey #</span>
                                    <span class="ml-1 font-bold text-gray-900" x-text="viewingPlayer?.jersey_number || '-'"></span>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Player Details -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                            <p class="text-sm text-gray-500 font-medium">Email</p>
                            <p class="font-medium text-gray-900 break-all" x-text="viewingPlayer?.email"></p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                            <p class="text-sm text-gray-500 font-medium">Birth Date</p>
                            <p class="font-medium text-gray-900" x-text="viewingPlayer?.birth_date"></p>
                        </div>
                    </div>
                    
                    <!-- Notes -->
                    <div x-show="viewingPlayer?.notes" class="bg-gray-50 rounded-lg p-4 border border-gray-200 mb-6">
                        <p class="text-sm text-gray-500 font-medium mb-2">Notes</p>
                        <p class="text-gray-900" x-text="viewingPlayer?.notes"></p>
                    </div>
                    
                    <!-- Evaluation History -->
                    <div>
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4">
                            <h4 class="text-lg font-semibold text-gray-900 flex items-center">
                                <span class="bg-purple-100 rounded-lg p-2 mr-3">
                                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                </span>
                                Evaluation History
                            </h4>
                            <select x-model="selectedEvaluationDate" 
                                    @change="onEvaluationDateChange"
                                    x-show="availableEvaluationDates.length > 0"
                                    class="border border-gray-300 rounded-lg px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 bg-white border-gray-300 focus:border-orange-500 focus:ring-2 focus:ring-orange-200 rounded-lg w-full sm:w-auto">
                                <option value="all">All Evaluations</option>
                                <template x-for="date in availableEvaluationDates" :key="date">
                                    <option :value="date" x-text="new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })"></option>
                                </template>
                            </select>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                            <div x-show="getFilteredEvaluationHistory().length > 0" class="max-h-96 overflow-y-auto space-y-4">
                                <template x-for="(eval, index) in getFilteredEvaluationHistory()" :key="index">
                                    <div class="border-l-4 border-orange-300 pl-4 pb-4 bg-white rounded-r-lg p-3">
                                        <div class="flex justify-between items-center mb-2">
                                            <span class="text-sm font-medium text-gray-700" x-text="eval.category.replace(/_/g, ' ').split(' ').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ')"></span>
                                            <span class="text-sm text-gray-500" x-text="new Date(eval.evaluated_at).toLocaleDateString()"></span>
                                        </div>
                                        <div class="flex items-center mb-3">
                                            <span class="text-lg font-bold text-orange-600" x-text="parseFloat(eval.score).toFixed(1)"></span>
                                            <span class="text-sm text-gray-500 ml-2">/10</span>
                                        </div>
                                        
                                        <!-- Show subcategory scores for this evaluation -->
                                        <div class="space-y-2" x-data="{ subcategories: getSubcategoryEvaluations(eval.category, eval.evaluated_at) }">
                                            <template x-for="subEval in subcategories" :key="subEval.subcategory">
                                                <div class="flex justify-between items-center text-xs bg-gray-50 rounded px-2 py-1">
                                                    <span class="text-gray-600" x-text="formatSubcategoryName(subEval.subcategory)"></span>
                                                    <span class="font-medium text-gray-700" x-text="parseFloat(subEval.score).toFixed(1) + '/10'"></span>
                                                </div>
                                            </template>
                                        </div>
                                        
                                        <!-- Notes if available -->
                                        <div x-show="eval.notes && eval.notes.trim()" class="mt-2 text-xs text-gray-600 italic bg-gray-50 rounded p-2" x-text="eval.notes"></div>
                                    </div>
                                </template>
                            </div>
                            <div x-show="getFilteredEvaluationHistory().length === 0" class="text-gray-500 text-center py-8">
                                <div class="bg-gray-100 rounded-full w-16 h-16 mx-auto mb-4 flex items-center justify-center">
                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                </div>
                                <span x-show="selectedEvaluationDate === 'all'">No evaluations yet</span>
                                <span x-show="selectedEvaluationDate !== 'all'">No evaluations for this date</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Spider Chart Section -->
                <div class="flex-1 bg-gray-50 rounded-xl p-6 border border-gray-200">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-lg font-semibold text-gray-900 flex items-center">
                            <span class="bg-blue-100 rounded-lg p-2 mr-3">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </span>
                            Performance Overview
                        </h4>
                        <span x-show="selectedEvaluationDate !== 'all'" 
                              class="text-sm text-orange-600 font-medium px-3 py-1 bg-orange-100 rounded-full">
                            <span x-text="new Date(selectedEvaluationDate).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })"></span>
                        </span>
                    </div>
                    <div class="relative bg-white rounded-lg p-4" style="height: 400px; min-height: 400px;">
                        <canvas id="playerCardSpiderChart" style="display: block; width: 100%; height: 100%;"></canvas>
                    </div>
                    
                    <!-- Category Scores -->
                    <div class="mt-6 grid grid-cols-2 gap-2 text-sm max-h-48 overflow-y-auto">
                        <template x-for="category in evaluationCategories" :key="category.key">
                            <div class="flex justify-between items-center py-1 px-2 rounded hover:bg-white bg-white border border-gray-100">
                                <span class="text-gray-600 text-xs truncate pr-2" x-text="category.name"></span>
                                <span class="font-bold text-orange-600 flex-shrink-0" x-text="getPlayerCardCategoryAverage(category.key)"></span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
            
            <!-- AI Coaching Advice - Full Width -->
            <div class="mt-8">
                <h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                    <span class="bg-green-100 rounded-lg p-2 mr-3">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                        </svg>
                    </span>
                    AI Coaching Advice
                </h4>
                <div class="bg-gray-50 rounded-xl p-6 border border-gray-200">
                    <!-- No evaluations state -->
                    <div x-show="adviceStatus === 'no_evaluations' && !playerAdvice" class="text-center py-8">
                        <div class="bg-gray-100 rounded-full w-16 h-16 mx-auto mb-4 flex items-center justify-center">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                            </svg>
                        </div>
                        <p class="text-gray-600">Complete an evaluation first to receive personalized coaching advice.</p>
                    </div>
                    
                    <!-- No advice yet but has evaluations -->
                    <div x-show="adviceStatus === 'no_advice_yet' && !playerAdvice" class="text-center py-8">
                        <div class="bg-blue-50 rounded-full w-16 h-16 mx-auto mb-4 flex items-center justify-center">
                            <svg class="w-8 h-8 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <p class="text-gray-600">AI advice will be generated after your next evaluation.</p>
                    </div>
                    
                    <!-- Loading/Generating state -->
                    <div x-show="adviceLoading || adviceStatus === 'generating'" class="text-center py-8">
                        <div class="bg-orange-50 rounded-full w-16 h-16 mx-auto mb-4 flex items-center justify-center animate-pulse">
                            <svg class="w-8 h-8 text-orange-500 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                        <p class="text-gray-600">Generating personalized advice based on recent evaluations...</p>
                        <p class="text-sm text-gray-500 mt-2">This may take up to a minute.</p>
                    </div>
                    
                    <!-- Generation failed -->
                    <div x-show="adviceStatus === 'generation_failed'" class="text-center py-8">
                        <div class="bg-red-50 rounded-full w-16 h-16 mx-auto mb-4 flex items-center justify-center">
                            <svg class="w-8 h-8 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <p class="text-gray-600">AI advice generation timed out. Please try evaluating again.</p>
                    </div>
                    
                    <!-- Error state -->
                    <div x-show="adviceStatus === 'error'" class="text-center py-8">
                        <div class="bg-red-50 rounded-full w-16 h-16 mx-auto mb-4 flex items-center justify-center">
                            <svg class="w-8 h-8 text-red-400" style="width: 32px; height: 32px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </div>
                        <p class="text-gray-600">An error occurred while loading advice. Please try again later.</p>
                    </div>
                    
                    <!-- Advice content -->
                    <div x-show="playerAdvice && !adviceLoading && adviceStatus === 'current'" class="prose prose-sm max-w-none">
                        <div class="whitespace-pre-wrap text-gray-800 bg-white rounded-lg p-4 border border-gray-200" x-text="playerAdvice"></div>
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