<?php
/**
 * Club Teams partial template
 */
?>

<div class="w-full">
    <!-- Club Teams Notice -->
    <div class="mb-8 bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex items-center">
            <svg class="w-5 h-5 text-blue-600 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div>
                <p class="text-blue-800 text-sm">
                    <span class="font-semibold">Read-only mode:</span> You can view all teams and players in your club, but cannot make changes.
                </p>
            </div>
        </div>
    </div>
    
    <!-- Club Teams Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <template x-for="team in clubTeams" :key="team.id">
            <div @click="selectClubTeam(team)" 
                 class="bg-white rounded-xl shadow-lg hover:shadow-2xl transform transition-all duration-300 hover:scale-105 cursor-pointer overflow-hidden group">
                <div class="bg-gradient-to-r from-blue-400 to-blue-500 h-2 group-hover:h-3 transition-all duration-300"></div>
                <div class="p-6">
                    <div class="flex items-start justify-between mb-4">
                        <h3 class="text-2xl font-bold text-gray-900 group-hover:text-blue-600 transition-colors" x-text="team.name"></h3>
                        <div class="bg-blue-100 text-blue-600 px-3 py-1 rounded-full text-sm font-semibold">
                            <span x-text="team.season"></span>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <div class="flex items-center text-gray-600">
                            <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            <span class="font-medium">Coach:</span>
                            <span class="ml-2" x-text="team.coach"></span>
                        </div>
                        <div class="flex items-center text-gray-600" x-show="team.owner_name">
                            <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span class="font-medium">Owner:</span>
                            <span class="ml-2" x-text="team.owner_name"></span>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <span class="text-sm text-gray-500">Click to view players →</span>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Empty State -->
    <div x-show="clubTeams.length === 0" class="text-center py-16">
        <div class="bg-blue-50 rounded-full w-24 h-24 mx-auto mb-6 flex items-center justify-center">
            <svg class="w-12 h-12 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
            </svg>
        </div>
        <h3 class="text-2xl font-bold text-gray-900 mb-2">No club teams found</h3>
        <p class="text-gray-600 mb-6">There are no teams in your club yet.</p>
    </div>
    
    <!-- Club Team Details Section -->
    <div x-show="selectedClubTeam" x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform translate-y-4"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         class="mt-8">
        
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden border-t-4 border-blue-500">
            <!-- Team Header -->
            <div class="bg-white p-4 md:p-8">
                <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                    <div>
                        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2" x-text="selectedClubTeam?.name"></h2>
                        <p class="text-gray-600 text-sm md:text-base">Club Team Roster (Read-only)</p>
                    </div>
                    <div class="bg-blue-100 text-blue-700 px-4 py-2 rounded-lg font-medium">
                        View Only
                    </div>
                </div>
            </div>
            
            <!-- Players Table -->
            <div class="p-4 md:p-8">
                <div x-show="clubTeamPlayers.length > 0" class="overflow-x-auto shadow ring-1 ring-black ring-opacity-5 rounded-lg">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 md:px-6 py-3 md:py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Player</th>
                                <th class="hidden md:table-cell px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                                <th class="hidden sm:table-cell px-4 md:px-6 py-3 md:py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Birth Date</th>
                                <th class="px-4 md:px-6 py-3 md:py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Position</th>
                                <th class="px-4 md:px-6 py-3 md:py-4 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Jersey #</th>
                                <th class="hidden lg:table-cell px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Notes</th>
                                <th class="px-4 md:px-6 py-3 md:py-4 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <template x-for="player in clubTeamPlayers" :key="player.id">
                                <tr class="hover:bg-blue-50 transition-colors">
                                    <td class="px-4 md:px-6 py-3 md:py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-8 w-8 md:h-10 md:w-10 bg-blue-100 rounded-full flex items-center justify-center">
                                                <span class="text-blue-600 font-bold text-xs md:text-sm" x-text="(player.first_name ? player.first_name.charAt(0) : '') + (player.last_name ? player.last_name.charAt(0) : '')"></span>
                                            </div>
                                            <div class="ml-3 md:ml-4">
                                                <div class="text-sm font-medium text-gray-900" x-text="player.first_name + ' ' + player.last_name"></div>
                                                <div class="text-xs text-gray-500 md:hidden" x-text="player.email"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="hidden md:table-cell px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900" x-text="player.email"></div>
                                    </td>
                                    <td class="hidden sm:table-cell px-4 md:px-6 py-3 md:py-4 whitespace-nowrap text-sm text-gray-900" x-text="player.birth_date"></td>
                                    <td class="px-4 md:px-6 py-3 md:py-4 whitespace-nowrap">
                                        <span class="px-2 md:px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800" x-text="player.position || 'Not assigned'"></span>
                                    </td>
                                    <td class="px-4 md:px-6 py-3 md:py-4 whitespace-nowrap text-center">
                                        <span class="inline-flex items-center justify-center h-6 w-6 md:h-8 md:w-8 rounded-full bg-gray-100 text-gray-800 font-bold text-xs md:text-sm" x-text="player.jersey_number || '-'"></span>
                                    </td>
                                    <td class="hidden lg:table-cell px-6 py-4 text-sm text-gray-900" x-text="player.notes || '-'"></td>
                                    <td class="px-4 md:px-6 py-3 md:py-4 whitespace-nowrap text-center">
                                        <div class="flex items-center justify-center space-x-1 md:space-x-2">
                                            <!-- View Player Card Button -->
                                            <button @click="handlePlayerCardClick(player.id, true)" 
                                                    class="text-blue-600 hover:text-blue-900 transition-colors p-2 rounded-lg hover:bg-blue-50 active:bg-blue-100 min-w-[44px] min-h-[44px] flex items-center justify-center"
                                                    title="View player card"
                                                    type="button">
                                                <svg class="w-4 h-4 md:w-5 md:h-5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"></path>
                                                </svg>
                                            </button>
                                            <!-- History Button -->
                                            <button @click="handleHistoryClick(player.id, true)" 
                                                    class="text-purple-600 hover:text-purple-900 transition-colors p-2 rounded-lg hover:bg-purple-50 active:bg-purple-100 min-w-[44px] min-h-[44px] flex items-center justify-center"
                                                    title="View player history"
                                                    type="button">
                                                <svg class="w-4 h-4 md:w-5 md:h-5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                
                <!-- Empty Players State -->
                <div x-show="clubTeamPlayers.length === 0" class="text-center py-12">
                    <div class="bg-gray-50 rounded-full w-20 h-20 mx-auto mb-4 flex items-center justify-center">
                        <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                    </div>
                    <h4 class="text-lg font-semibold text-gray-900 mb-2">No players in this team</h4>
                    <p class="text-gray-600">This team doesn't have any players yet.</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Club Player Card -->
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
</div>