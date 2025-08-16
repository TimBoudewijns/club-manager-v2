<!-- Player Evaluation Modal -->
<div x-show="showEvaluationModal" 
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-50 overflow-y-auto" 
     style="display: none;">
    <div class="modal-container">
        <div class="modal-backdrop" @click="showEvaluationModal = false"></div>
        
        <div class="modal-content bg-white rounded-2xl shadow-2xl max-w-4xl w-full"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform scale-90"
             x-transition:enter-end="opacity-100 transform scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 transform scale-100"
             x-transition:leave-end="opacity-0 transform scale-90"
             @click.stop>
            <!-- Fixed Header -->
            <div class="bg-gradient-to-r from-orange-500 to-orange-600 p-4 md:p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-semibold text-base md:text-lg">Player Evaluation</h3>
                        <p class="text-orange-100 mt-1 text-sm md:text-base">
                            <span x-text="evaluatingPlayer?.first_name + ' ' + evaluatingPlayer?.last_name"></span> - 
                            <span x-text="selectedTeam?.name"></span>
                        </p>
                    </div>
                    <button @click="closeEvaluationModal()" 
                            class="text-white hover:text-orange-200 p-1.5 rounded-full hover:bg-white/10 transition-colors">
                        <svg class="w-4 h-4" style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            
            <!-- Scrollable Content -->
            <div class="max-h-[calc(90vh-120px)] overflow-y-auto -webkit-overflow-scrolling-touch">
                <div class="p-4 md:p-6">
                    <!-- Evaluation Categories -->
                    <div class="space-y-6">
                        <template x-for="category in evaluationCategories" :key="category.name">
                            <div class="bg-white border border-gray-200 rounded-xl p-6">
                                <h4 class="text-lg font-semibold text-gray-900 mb-4" x-text="category.name"></h4>
                                
                                <!-- Main Category Score (Read-only, shows average) -->
                                <div class="mb-4">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-sm font-medium text-gray-700">Overall Average</span>
                                        <span class="text-lg font-bold text-orange-600" x-text="getCategoryAverage(category.key)"></span>
                                    </div>
                                    <div class="relative pt-1">
                                        <div class="overflow-hidden h-2 text-xs flex rounded bg-gray-200">
                                            <div :style="'width: ' + (getCategoryAverage(category.key) * 10) + '%'" 
                                                 class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-orange-500 transition-all duration-300"></div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Subcategories -->
                                <div class="space-y-3">
                                    <template x-for="sub in category.subcategories" :key="sub.key">
                                        <div class="pl-4 border-l-2 border-orange-200" x-data="{ sliderLocked: true }">
                                            <div class="flex items-center justify-between mb-1">
                                                <div>
                                                    <span class="text-sm font-medium text-gray-700" x-text="sub.name"></span>
                                                    <p class="text-xs text-gray-500" x-text="sub.description"></p>
                                                </div>
                                                <span class="text-sm font-bold text-gray-600" x-text="getSubcategoryScore(category.key, sub.key)"></span>
                                            </div>
                                            <div class="relative">
                                                <input type="range" 
                                                       :value="getSubcategoryScore(category.key, sub.key)"
                                                       @input="updateSubcategoryScore(category.key, sub.key, $event.target.value)"
                                                       @touchstart="sliderLocked = false"
                                                       @touchend="sliderLocked = true"
                                                       @mousedown="sliderLocked = false"
                                                       @mouseup="sliderLocked = true"
                                                       @mouseleave="sliderLocked = true"
                                                       :class="{'pointer-events-none opacity-75': sliderLocked && window.innerWidth < 768}"
                                                       min="1" max="10" step="0.5"
                                                       class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-orange-500">
                                                <div x-show="sliderLocked && window.innerWidth < 768" 
                                                     @click="sliderLocked = false"
                                                     class="absolute inset-0 flex items-center justify-center bg-gray-900 bg-opacity-10 rounded-lg">
                                                    <span class="text-xs text-gray-600 bg-white px-2 py-1 rounded shadow-sm">Tap to adjust</span>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                    
                    <!-- Notes -->
                    <div class="mt-6">
                        <label class="label">
                            <span class="label-text font-semibold text-gray-700">Additional Notes</span>
                        </label>
                        <textarea x-model="evaluationNotes" 
                                  class="textarea textarea-bordered w-full bg-gray-50 border-gray-300 focus:border-orange-500 focus:ring-2 focus:ring-orange-200 rounded-lg" 
                                  rows="3"
                                  placeholder="Add any additional notes about this player's performance..."></textarea>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="mt-6 md:mt-8 flex flex-col sm:flex-row gap-2">
                        <button type="button" 
                                class="bg-gray-200 hover:bg-gray-300 text-gray-800 border-0 rounded-lg font-medium py-2 px-6 order-2 sm:order-1" 
                                @click="closeEvaluationModal">Cancel</button>
                        <button type="button" 
                                @click="saveEvaluation($event)"
                                class="bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white border-0 rounded-lg px-8 shadow-lg order-1 sm:order-2">
                            Save Evaluation
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>