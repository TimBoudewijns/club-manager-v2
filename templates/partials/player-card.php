<!-- Player Card Display -->
<div x-show="selectedPlayerCard && selectedPlayerCard.id === selectedTeam.id && viewingPlayer" 
     x-transition:enter="transition ease-out duration-500"
     x-transition:enter-start="opacity-0 transform translate-y-8 scale-95"
     x-transition:enter-end="opacity-100 transform translate-y-0 scale-100"
     x-transition:leave="transition ease-in duration-300"
     x-transition:leave-start="opacity-100 transform translate-y-0 scale-100"
     x-transition:leave-end="opacity-0 transform translate-y-4 scale-95"
     class="mt-8 mb-8">
     
    <div id="playerCardContent" class="player-card-container glass-effect rounded-3xl shadow-2xl p-6 md:p-8 lg:p-10 border-2 border-orange-200/30 relative overflow-hidden">
        
        <!-- Decorative background elements -->
        <div class="absolute top-0 right-0 w-64 h-64 bg-gradient-to-br from-orange-100/20 to-transparent rounded-full blur-3xl"></div>
        <div class="absolute bottom-0 left-0 w-48 h-48 bg-gradient-to-tr from-amber-100/20 to-transparent rounded-full blur-2xl"></div>
        
        <!-- Content wrapper with relative positioning -->
        <div class="relative z-10">
            <!-- Download PDF Button -->
            <div class="flex justify-end mb-6">
                <button @click="downloadPlayerCardPDF()" 
                        class="bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white font-bold py-3 px-6 rounded-xl shadow-lg hover:shadow-xl transform transition-all duration-300 hover:scale-105 flex items-center space-x-3 group">
                    <svg class="w-5 h-5 group-hover:rotate-12 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <span>Download PDF</span>
                </button>
            </div>
            
            <div class="flex flex-col xl:flex-row gap-8 lg:gap-12">
                <!-- Player Info Section -->
                <div class="flex-1 space-y-8">
                    <!-- Player Header -->
                    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-6">
                        <div class="flex-shrink-0 h-24 w-24 md:h-28 md:w-28 bg-gradient-to-br from-orange-400 via-orange-500 to-orange-600 rounded-full flex items-center justify-center text-white font-bold text-3xl shadow-2xl ring-4 ring-orange-100 relative group">
                            <span x-text="(viewingPlayer?.first_name ? viewingPlayer.first_name.charAt(0) : '') + (viewingPlayer?.last_name ? viewingPlayer.last_name.charAt(0) : '')"></span>
                            <div class="absolute inset-0 rounded-full bg-white/20 group-hover:bg-white/30 transition-all duration-300"></div>
                        </div>
                        <div class="flex-1 text-center sm:text-left">
                            <h3 class="text-3xl md:text-4xl font-bold text-gray-900 mb-2 bg-gradient-to-r from-orange-600 to-orange-800 bg-clip-text text-transparent" x-text="viewingPlayer?.first_name + ' ' + viewingPlayer?.last_name"></h3>
                            <p class="text-gray-600 text-lg mb-4" x-text="selectedTeam?.name"></p>
                            <div class="flex flex-col sm:flex-row items-center gap-4 text-sm">
                                <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold bg-gradient-to-r from-orange-100 to-orange-200 text-orange-800 shadow-lg border border-orange-300/50">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    </svg>
                                    <span x-text="viewingPlayer?.position || 'Not assigned'"></span>
                                </span>
                                <div class="inline-flex items-center bg-gray-100 rounded-full px-4 py-2 shadow-lg">
                                    <span class="font-medium text-gray-600 mr-2">Jersey #</span>
                                    <span class="font-bold text-gray-900 text-lg" x-text="viewingPlayer?.jersey_number || '-'"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Player Details Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="glass-effect rounded-2xl p-6 shadow-lg border border-white/30 group hover:border-orange-200/50 transition-all duration-300">
                            <div class="flex items-center mb-3">
                                <div class="bg-orange-100 rounded-lg p-2 mr-3">
                                    <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path>
                                    </svg>
                                </div>
                                <p class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Email</p>
                            </div>
                            <p class="font-medium text-gray-900 text-lg break-all" x-text="viewingPlayer?.email"></p>
                        </div>
                        
                        <div class="glass-effect rounded-2xl p-6 shadow-lg border border-white/30 group hover:border-orange-200/50 transition-all duration-300">
                            <div class="flex items-center mb-3">
                                <div class="bg-orange-100 rounded-lg p-2 mr-3">
                                    <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <p class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Birth Date</p>
                            </div>
                            <p class="font-medium text-gray-900 text-lg" x-text="viewingPlayer?.birth_date"></p>
                        </div>
                    </div>
                    
                    <!-- Notes -->
                    <div x-show="viewingPlayer?.notes" 
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 transform scale-95"
                         x-transition:enter-end="opacity-100 transform scale-100"
                         class="glass-effect rounded-2xl p-6 shadow-lg border border-white/30">
                        <div class="flex items-center mb-4">
                            <div class="bg-orange-100 rounded-lg p-2 mr-3">
                                <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <p class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Notes</p>
                        </div>
                        <p class="text-gray-900 text-lg leading-relaxed" x-text="viewingPlayer?.notes"></p>
                    </div>
                    
                    <!-- Evaluation History -->
                    <div class="space-y-6">
                        <div class="flex items-center justify-between">
                            <h4 class="text-2xl font-bold text-gray-900 flex items-center">
                                <div class="bg-gradient-to-br from-purple-100 to-purple-200 rounded-xl p-3 mr-4">
                                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                </div>
                                Evaluation History
                            </h4>
                            <select x-model="selectedEvaluationDate" 
                                    @change="onEvaluationDateChange"
                                    x-show="availableEvaluationDates.length > 0"
                                    class="select select-bordered bg-white/90 backdrop-filter backdrop-blur-sm border-orange-200 focus:border-orange-500 focus:ring-2 focus:ring-orange-200 rounded-xl shadow-lg">
                                <option value="all">All Evaluations</option>
                                <template x-for="date in availableEvaluationDates" :key="date">
                                    <option :value="date" x-text="new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })"></option>
                                </template>
                            </select>
                        </div>
                        
                        <div class="glass-effect rounded-2xl p-6 shadow-lg border border-white/30">
                            <div x-show="getFilteredEvaluationHistory().length > 0" class="max-h-96 overflow-y-auto space-y-4 custom-scrollbar">
                                <template x-for="(eval, index) in getFilteredEvaluationHistory()" :key="index">
                                    <div class="evaluation-history-item group">
                                        <div class="flex justify-between items-center mb-3">
                                            <span class="text-lg font-semibold text-gray-800" x-text="eval.category.replace(/_/g, ' ').split(' ').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ')"></span>
                                            <span class="text-sm text-gray-500 bg-gray-100 px-3 py-1 rounded-full" x-text="new Date(eval.evaluated_at).toLocaleDateString()"></span>
                                        </div>
                                        <div class="flex items-center mb-4">
                                            <span class="text-3xl font-bold text-orange-600" x-text="parseFloat(eval.score).toFixed(1)"></span>
                                            <span class="text-lg text-gray-500 ml-2">/10</span>
                                            <div class="ml-4 flex-1">
                                                <div class="category-average-bar">
                                                    <div class="category-average-fill" :style="'width: ' + (parseFloat(eval.score) * 10) + '%'"></div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Show subcategory scores -->
                                        <div class="space-y-2" x-data="{ subcategories: getSubcategoryEvaluations(eval.category, eval.evaluated_at) }">
                                            <template x-for="subEval in subcategories" :key="subEval.subcategory">
                                                <div class="flex justify-between items-center text-sm bg-white/60 rounded-lg px-4 py-2 border border-white/50">
                                                    <span class="text-gray-700 font-medium" x-text="formatSubcategoryName(subEval.subcategory)"></span>
                                                    <span class="font-bold text-gray-800" x-text="parseFloat(subEval.score).toFixed(1) + '/10'"></span>
                                                </div>
                                            </template>
                                        </div>
                                        
                                        <!-- Notes if available -->
                                        <div x-show="eval.notes && eval.notes.trim()" class="mt-4 p-3 bg-amber-50 rounded-lg border border-amber-200">
                                            <p class="text-sm text-amber-800 italic" x-text="eval.notes"></p>
                                        </div>
                                    </div>
                                </template>
                            </div>
                            <div x-show="getFilteredEvaluationHistory().length === 0" class="text-center py-12">
                                <div class="bg-gray-100 rounded-full w-16 h-16 mx-auto mb-4 flex items-center justify-center">
                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                </div>
                                <h5 class="text-lg font-semibold text-gray-600 mb-2">
                                    <span x-show="selectedEvaluationDate === 'all'">No evaluations yet</span>
                                    <span x-show="selectedEvaluationDate !== 'all'">No evaluations for this date</span>
                                </h5>
                                <p class="text-gray-500">
                                    <span x-show="selectedEvaluationDate === 'all'">Complete your first evaluation to see performance data here.</span>
                                    <span x-show="selectedEvaluationDate !== 'all'">Try selecting a different date or view all evaluations.</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Spider Chart Section -->
                <div class="flex-1 xl:max-w-lg">
                    <div class="glass-effect rounded-2xl p-6 md:p-8 shadow-xl border border-white/30 sticky top-6">
                        <div class="flex items-center justify-between mb-6">
                            <h4 class="text-xl md:text-2xl font-bold text-gray-900 flex items-center">
                                <div class="bg-gradient-to-br from-blue-100 to-blue-200 rounded-xl p-2 mr-3">
                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                </div>
                                Performance
                            </h4>
                            <span x-show="selectedEvaluationDate !== 'all'" 
                                  class="text-sm text-orange-600 font-semibold bg-orange-100 px-3 py-1 rounded-full">
                                <span x-text="new Date(selectedEvaluationDate).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })"></span>
                            </span>
                        </div>
                        
                        <div class="relative mb-8" style="height: 400px; min-height: 400px;">
                            <canvas id="playerCardSpiderChart" style="display: block; width: 100%; height: 100%;"></canvas>
                        </div>
                        
                        <!-- Category Scores Grid -->
                        <div class="grid grid-cols-1 gap-3 max-h-80 overflow-y-auto custom-scrollbar">
                            <template x-for="category in evaluationCategories" :key="category.key">
                                <div class="flex justify-between items-center py-3 px-4 rounded-xl bg-white/60 border border-white/50 hover:bg-white/80 transition-all duration-200 group">
                                    <span class="text-gray-700 font-medium text-sm group-hover:text-gray-900 transition-colors" x-text="category.name"></span>
                                    <div class="flex items-center space-x-2">
                                        <div class="w-16 h-2 bg-gray-200 rounded-full overflow-hidden">
                                            <div class="h-full bg-gradient-to-r from-orange-400 to-orange-600 rounded-full transition-all duration-500" 
                                                 :style="'width: ' + (getPlayerCardCategoryAverage(category.key) * 10) + '%'"></div>
                                        </div>
                                        <span class="font-bold text-orange-600 text-sm min-w-[2rem]" x-text="getPlayerCardCategoryAverage(category.key)"></span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- AI Coaching Advice - Full Width -->
            <div class="mt-12">
                <h4 class="text-2xl md:text-3xl font-bold text-gray-900 mb-6 flex items-center">
                    <div class="bg-gradient-to-br from-emerald-100 to-emerald-200 rounded-xl p-3 mr-4">
                        <svg class="w-7 h-7 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                        </svg>
                    </div>
                    AI Coaching Advice
                </h4>
                
                <div class="glass-effect rounded-2xl p-8 shadow-xl border border-white/30">
                    <!-- No evaluations state -->
                    <div x-show="adviceStatus === 'no_evaluations' && !playerAdvice" 
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 transform scale-95"
                         x-transition:enter-end="opacity-100 transform scale-100"
                         class="text-center py-12">
                        <div class="bg-gray-100 rounded-full w-20 h-20 mx-auto mb-6 flex items-center justify-center">
                            <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                            </svg>
                        </div>
                        <h5 class="text-xl font-bold text-gray-900 mb-3">No AI Advice Available</h5>
                        <p class="text-gray-600 text-lg">Complete an evaluation first to receive personalized coaching advice powered by AI.</p>
                    </div>
                    
                    <!-- No advice yet but has evaluations -->
                    <div x-show="adviceStatus === 'no_advice_yet' && !playerAdvice" 
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 transform scale-95"
                         x-transition:enter-end="opacity-100 transform scale-100"
                         class="text-center py-12">
                        <div class="bg-blue-100 rounded-full w-20 h-20 mx-auto mb-6 flex items-center justify-center">
                            <svg class="w-10 h-10 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h5 class="text-xl font-bold text-gray-900 mb-3">AI Analysis Pending</h5>
                        <p class="text-gray-600 text-lg">AI advice will be generated after your next evaluation.</p>
                    </div>
                    
                    <!-- Loading/Generating state -->
                    <div x-show="adviceLoading || adviceStatus === 'generating'" 
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 transform scale-95"
                         x-transition:enter-end="opacity-100 transform scale-100"
                         class="text-center py-12">
                        <div class="bg-orange-100 rounded-full w-20 h-20 mx-auto mb-6 flex items-center justify-center">
                            <svg class="w-10 h-10 text-orange-500 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                        <h5 class="text-xl font-bold text-gray-900 mb-3">Generating AI Advice</h5>
                        <p class="text-gray-600 text-lg mb-2">Analyzing performance data and generating personalized coaching advice...</p>
                        <p class="text-sm text-gray-500">This may take up to a minute.</p>
                    </div>
                    
                    <!-- Generation failed -->
                    <div x-show="adviceStatus === 'generation_failed'" 
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 transform scale-95"
                         x-transition:enter-end="opacity-100 transform scale-100"
                         class="text-center py-12">
                        <div class="bg-red-100 rounded-full w-20 h-20 mx-auto mb-6 flex items-center justify-center">
                            <svg class="w-10 h-10 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h5 class="text-xl font-bold text-gray-900 mb-3">Generation Timeout</h5>
                        <p class="text-gray-600 text-lg">AI advice generation timed out. Please try evaluating again.</p>
                    </div>
                    
                    <!-- Error state -->
                    <div x-show="adviceStatus === 'error'" 
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 transform scale-95"
                         x-transition:enter-end="opacity-100 transform scale-100"
                         class="text-center py-12">
                        <div class="bg-red-100 rounded-full w-20 h-20 mx-auto mb-6 flex items-center justify-center">
                            <svg class="w-10 h-10 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </div>
                        <h5 class="text-xl font-bold text-gray-900 mb-3">Error Loading Advice</h5>
                        <p class="text-gray-600 text-lg">An error occurred while loading advice. Please try again later.</p>
                    </div>
                    
                    <!-- Advice content -->
                    <div x-show="playerAdvice && !adviceLoading && adviceStatus === 'current'" 
                         x-transition:enter="transition ease-out duration-500"
                         x-transition:enter-start="opacity-0 transform translate-y-4"
                         x-transition:enter-end="opacity-100 transform translate-y-0"
                         class="prose prose-lg max-w-none">
                        <div class="bg-gradient-to-br from-emerald-50 to-teal-50 rounded-2xl p-6 border border-emerald-200/50">
                            <div class="flex items-center mb-4">
                                <div class="bg-emerald-100 rounded-lg p-2 mr-3">
                                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                                    </svg>
                                </div>
                                <h6 class="font-semibold text-emerald-900">AI Generated Coaching Advice</h6>
                            </div>
                            <div class="whitespace-pre-wrap text-gray-800 leading-relaxed" x-text="playerAdvice"></div>
                        </div>
                        <div class="mt-6 pt-4 border-t border-gray-200">
                            <p class="text-xs text-gray-500 flex items-center">
                                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span x-show="lastAdviceTimestamp">Last updated: <span x-text="new Date(lastAdviceTimestamp).toLocaleString()"></span></span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Custom scrollbar styles */
.custom-scrollbar {
    scrollbar-width: thin;
    scrollbar-color: rgba(249, 115, 22, 0.3) transparent;
}

.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: rgba(249, 115, 22, 0.1);
    border-radius: 3px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(249, 115, 22, 0.3);
    border-radius: 3px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(249, 115, 22, 0.5);
}

/* Enhance glass effect for this component */
.glass-effect {
    background: rgba(255, 255, 255, 0.25);
    backdrop-filter: blur(20px) saturate(120%);
    border: 1px solid rgba(255, 255, 255, 0.3);
    box-shadow: 
        0 8px 32px rgba(31, 38, 135, 0.37),
        inset 0 1px 0 rgba(255, 255, 255, 0.5);
}
</style>