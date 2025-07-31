<?php
/**
 * Team Management Content
 */
?>

<!-- Team Management Section -->
<div class="section-header">
    <div class="section-title">
        <span class="section-icon bg-gradient-to-br from-blue-100 to-blue-200 text-blue-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
            </svg>
        </span>
        Manage Club Teams
    </div>
    <p class="section-subtitle">Organize teams, assign trainers, and manage club operations</p>
</div>

<!-- Team Management Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
    <template x-for="team in managedTeams" :key="team.id">
        <div @click="selectManagedTeam(team)" 
             class="bg-gradient-to-br from-white to-blue-50 rounded-xl shadow-lg hover:shadow-2xl transform transition-all duration-300 hover:scale-105 cursor-pointer overflow-hidden group border border-blue-100">
            
            <div class="p-6">
                <div class="flex items-start justify-between mb-4">
                    <h4 class="text-xl font-bold text-gray-900 group-hover:text-blue-600 transition-colors" 
                       x-text="team.name"></h4>
                    <div class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-xs font-semibold">
                        Managed
                    </div>
                </div>
                
                <div class="space-y-3">
                    <div class="flex items-center text-gray-600">
                        <div class="bg-blue-100 rounded-lg p-2 mr-3">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <span class="font-medium text-sm">Players:</span>
                            <span class="ml-2 text-sm" x-text="team.player_count || '0'"></span>
                        </div>
                    </div>
                    
                    <div class="flex items-center text-gray-600">
                        <div class="bg-blue-100 rounded-lg p-2 mr-3">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <span class="font-medium text-sm">Trainers:</span>
                            <span class="ml-2 text-sm" x-text="team.trainer_count || '0'"></span>
                        </div>
                    </div>
                </div>
                
                <div class="mt-6 pt-4 border-t border-blue-100">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Manage team</span>
                        <svg class="w-5 h-5 text-blue-500 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>

<!-- Empty State -->
<div x-show="managedTeams.length === 0" class="text-center py-16">
    <div class="bg-blue-50 rounded-full w-24 h-24 mx-auto mb-6 flex items-center justify-center">
        <svg class="w-12 h-12 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
        </svg>
    </div>
    <h4 class="text-2xl font-bold text-gray-900 mb-3">No teams to manage</h4>
    <p class="text-gray-600 mb-6 max-w-md mx-auto">
        Create your first team to start managing club operations.
    </p>
    <button x-show="hasPermission('can_create_teams')" 
            @click="showCreateTeamModal = true" 
            class="quick-action-btn bg-gradient-to-r from-blue-500 to-blue-600 text-white hover:from-blue-600 hover:to-blue-700">
        Create First Team
    </button>
</div>