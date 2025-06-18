<?php
/**
 * Club Player Card partial - Read-only player card for club teams
 */
?>
<!-- Club Player Card Display -->
<div x-show="selectedClubPlayerCard && selectedClubPlayerCard.id === selectedClubTeam.id && viewingClubPlayer" 
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 transform translate-y-4"
     x-transition:enter-end="opacity-100 transform translate-y-0"
     class="mt-8 mb-8">
    <div id="clubPlayerCardContent" class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-2xl shadow-xl p-6 md:p-8 border-2 border-blue-200">
        <!-- Download PDF Button -->
        <div class="flex justify-end mb-4">
            <button @click="downloadPlayerCardPDF($event, true)" 
                    class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg shadow-md transform transition-all duration-200 hover:scale-105 flex items-center space-x-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <span>Download PDF</span>
            </button>
        </div>
        
        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Player Info Section -->
            <div class="flex-1">
                <div class="flex items-center mb-6">
                    <div class="flex-shrink-0 h-20 w-20 bg-gradient-to-br from-blue-400 to-blue-600 rounded-full flex items-center justify-center text-white font-bold text-2xl shadow-lg">
                        <span x-text="(viewingClubPlayer?.first_name ? viewingClubPlayer.first_name.charAt(0) : '') + (viewingClubPlayer?.last_name ? viewingClubPlayer.last_name.charAt(0) : '')"></span>
                    </div>
                    <div class="ml-6">
                        <h3 class="text-2xl font-bold text-gray-900" x-text="viewingClubPlayer?.first_name + ' ' + viewingClubPlayer?.last_name"></h3>
                        <p class="text-gray-600" x-text="selectedClubTeam?.name"></p>
                        <div class="flex items-center mt-2 space-x-4 text-sm">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                <span x-text="viewingClubPlayer?.position || 'Not assigned'"></span>
                            </span>
                            <span class="inline-flex items-center">
                                <span class="font-medium text-gray-500">Jersey #</span>
                                <span class="ml-1 font-bold text-gray-900" x-text="viewingClubPlayer?.jersey_number || '-'"></span>
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Player Details -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div class="bg-white rounded-lg p-4 shadow-sm">
                        <p class="text-sm text-gray-500">Email</p>
                        <p class="font-medium text-gray-900" x-text="viewingClubPlayer?.email"></p>
                    </div>
                    <div class="bg-white rounded-lg p-4 shadow-sm">
                        <p class="text-sm text-gray-500">Birth Date</p>
                        <p class="font-medium text-gray-900" x-text="viewingClubPlayer?.birth_date"></p>
                    </div>
                </div>
                
                <!-- Notes -->
                <div x-show="viewingClubPlayer?.notes" class="bg-white rounded-lg p-4 shadow-sm">
                    <p class="text-sm text-gray-500 mb-2">Notes</p>
                    <p class="text-gray-900" x-text="viewingClubPlayer?.notes"></p>
                </div>
                
                <!-- Evaluation History -->
                <div class="mt-6">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="text-lg font-semibold text-gray-900">Evaluation History</h4>
                        <select x-model="selectedEvaluationDate" 
                                @change="onEvaluationDateChange"
                                x-show="availableEvaluationDates.length > 0"
                                class="select select-sm select-bordered bg-white border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 rounded-lg">
                            <option value="all">All Evaluations</option>
                            <template x-for="date in availableEvaluationDates" :key="date">
                                <option :value="date" x-text="new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })"></option>
                            </template>
                        </select>
                    </div>
                    <div class="bg-white rounded-lg p-4 shadow-sm">
                        <div x-show="getFilteredEvaluationHistory().length > 0" class="max-h-96 overflow-y-auto space-y-4">
                            <template x-for="(eval, index) in getFilteredEvaluationHistory()" :key="index">
                                <div class="border-l-4 border-blue-300 pl-4 pb-4">
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-sm font-medium text-gray-700" x-text="eval.category.replace(/_/g, ' ').split(' ').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ')"></span>
                                        <span class="text-sm text-gray-500" x-text="new Date(eval.evaluated_at).toLocaleDateString()"></span>
                                    </div>
                                    <div class="flex items-center mb-3">
                                        <span class="text-lg font-bold text-blue-600" x-text="parseFloat(eval.score).toFixed(1)"></span>
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
                                    <div x-show="eval.notes && eval.notes.trim()" class="mt-2 text-xs text-gray-600 italic" x-text="eval.notes"></div>
                                </div>
                            </template>
                        </div>
                        <div x-show="getFilteredEvaluationHistory().length === 0" class="text-gray-500 text-center py-4">
                            <span x-show="selectedEvaluationDate === 'all'">No evaluations yet</span>
                            <span x-show="selectedEvaluationDate !== 'all'">No evaluations for this date</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Spider Chart Section -->
            <div class="flex-1 bg-white rounded-xl p-6 shadow-lg">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="text-lg font-semibold text-gray-900">Performance Overview</h4>
                    <span x-show="selectedEvaluationDate !== 'all'" 
                          class="text-sm text-blue-600 font-medium">
                        <span x-text="new Date(selectedEvaluationDate).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })"></span>
                    </span>
                </div>
                <div class="relative" style="height: 400px; min-height: 400px;">
                    <canvas id="clubPlayerCardSpiderChart" style="display: block; width: 100%; height: 100%;"></canvas>
                </div>
                
                <!-- Category Scores -->
                <div class="mt-6 grid grid-cols-2 gap-2 text-sm max-h-48 overflow-y-auto">
                    <template x-for="category in evaluationCategories" :key="category.key">
                        <div class="flex justify-between items-center py-1 px-2 rounded hover:bg-gray-50">
                            <span class="text-gray-600 text-xs" x-text="category.name"></span>
                            <span class="font-bold text-blue-600" x-text="getPlayerCardCategoryAverage(category.key)"></span>
                        </div>
                    </template>
                </div>
            </div>
        </div>
        
        <!-- AI Coaching Advice - Full Width -->
        <div class="mt-8">
            <h4 class="text-lg font-semibold text-gray-900 mb-3">AI Coaching Advice</h4>
            <div class="bg-white rounded-lg p-6 shadow-sm">
                <!-- No evaluations state -->
                <div x-show="adviceStatus === 'no_evaluations' && !playerAdvice" class="text-center py-8">
                    <div class="bg-gray-50 rounded-full w-16 h-16 mx-auto mb-4 flex items-center justify-center">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                        </svg>
                    </div>
                    <p class="text-gray-600">No evaluations available for this player.</p>
                </div>
                
                <!-- No advice yet but has evaluations -->
                <div x-show="adviceStatus === 'no_advice_yet' && !playerAdvice" class="text-center py-8">
                    <div class="bg-blue-50 rounded-full w-16 h-16 mx-auto mb-4 flex items-center justify-center">
                        <svg class="w-8 h-8 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <p class="text-gray-600">AI advice has not been generated for this player yet.</p>
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