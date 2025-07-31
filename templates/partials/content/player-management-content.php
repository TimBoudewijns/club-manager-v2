<?php
/**
 * Player Management Content
 * Clean content without container styling
 */
?>

<!-- My Teams Section -->
<div class="mb-12">
    <!-- Section Header -->
    <div class="section-header">
        <div class="flex items-center justify-between">
            <div>
                <div class="section-title">
                    <span class="section-icon bg-gradient-to-br from-orange-100 to-orange-200 text-orange-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                    </span>
                    My Teams
                </div>
                <p class="section-subtitle">Teams you manage directly</p>
            </div>
            
            <!-- Add Team Button -->
            <button x-show="hasPermission('can_add_teams_player_mgmt')" 
                    @click="showCreateTeamModal = true" 
                    class="quick-action-btn bg-gradient-to-r from-orange-500 to-orange-600 text-white hover:from-orange-600 hover:to-orange-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Create Team
            </button>
        </div>
    </div>
    
    <!-- Notice for trainers -->
    <div x-show="userPermissions.is_trainer" class="mb-8 bg-orange-50 border border-orange-200 rounded-xl p-4">
        <div class="flex items-center">
            <svg class="w-5 h-5 text-orange-600 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div>
                <p class="text-orange-800 text-sm font-medium">
                    These are teams assigned to you where you can manage and evaluate players.
                </p>
            </div>
        </div>
    </div>
    
    <!-- My Teams Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        <template x-for="team in myTeams" :key="team.id">
            <div @click="selectTeam(team)" 
                 class="bg-gradient-to-br from-white to-gray-50 rounded-xl shadow-lg hover:shadow-2xl transform transition-all duration-300 hover:scale-105 cursor-pointer overflow-hidden group border border-gray-100">
                
                <!-- Team Badge -->
                <div class="absolute top-4 right-4 bg-orange-100 text-orange-700 px-3 py-1 rounded-full text-xs font-semibold z-10">
                    My Team
                </div>
                
                <div class="p-6">
                    <div class="flex items-start justify-between mb-4">
                        <h4 class="text-xl font-bold text-gray-900 group-hover:text-orange-600 transition-colors" 
                           x-text="team.name"></h4>
                    </div>
                    
                    <div class="space-y-3">
                        <div class="flex items-center text-gray-600">
                            <div class="bg-orange-100 rounded-lg p-2 mr-3">
                                <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>
                            <div>
                                <span class="font-medium text-sm">Coach:</span>
                                <span class="ml-2 text-sm" x-text="team.coach"></span>
                            </div>
                        </div>
                        
                        <div class="flex items-center text-gray-600">
                            <div class="bg-orange-100 rounded-lg p-2 mr-3">
                                <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <div>
                                <span class="font-medium text-sm">Season:</span>
                                <span class="ml-2 text-sm" x-text="team.season"></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-6 pt-4 border-t border-gray-100">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-500">Click to manage players</span>
                            <svg class="w-5 h-5 text-orange-500 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
    
    <!-- Empty State for My Teams -->
    <div x-show="myTeams.length === 0" class="text-center py-16">
        <div class="bg-orange-50 rounded-full w-24 h-24 mx-auto mb-6 flex items-center justify-center">
            <svg class="w-12 h-12 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
        </div>
        <h4 class="text-2xl font-bold text-gray-900 mb-3">No teams yet</h4>
        <p class="text-gray-600 mb-6 max-w-md mx-auto" x-show="userPermissions.is_trainer">
            Your club manager will assign teams to you.
        </p>
        <p class="text-gray-600 mb-6 max-w-md mx-auto" x-show="!userPermissions.is_trainer">
            Create your first team to get started managing your players.
        </p>
        <button x-show="hasPermission('can_add_teams_player_mgmt')" 
                @click="showCreateTeamModal = true" 
                class="quick-action-btn bg-gradient-to-r from-orange-500 to-orange-600 text-white hover:from-orange-600 hover:to-orange-700">
            Create Your First Team
        </button>
    </div>
</div>

<!-- Club Teams Section -->
<div x-show="hasPermission('can_see_club_teams_in_player_mgmt')" x-cloak>
    <!-- Divider -->
    <div class="flex items-center mb-8">
        <div class="flex-grow border-t border-gray-200"></div>
        <div class="flex-shrink-0 px-6">
            <span class="bg-gray-100 text-gray-500 px-4 py-2 rounded-full text-sm font-medium">Club Overview</span>
        </div>
        <div class="flex-grow border-t border-gray-200"></div>
    </div>
    
    <!-- Section Header -->
    <div class="section-header">
        <div class="section-title">
            <span class="section-icon bg-gradient-to-br from-blue-100 to-blue-200 text-blue-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
            </span>
            Club Teams
        </div>
        <p class="section-subtitle">View-only access to other teams in your club</p>
    </div>
    
    <!-- Club Teams Notice -->
    <div class="mb-8 bg-blue-50 border border-blue-200 rounded-xl p-4">
        <div class="flex items-center">
            <svg class="w-5 h-5 text-blue-600 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div>
                <p class="text-blue-800 text-sm font-medium">
                    <span class="font-semibold">Read-only mode:</span> You can view all teams and players in your club, but cannot make changes.
                </p>
            </div>
        </div>
    </div>
    
    <!-- Club Teams Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        <template x-for="team in clubTeams" :key="team.id">
            <div @click="selectClubTeam(team)" 
                 class="bg-gradient-to-br from-white to-blue-50 rounded-xl shadow-lg hover:shadow-2xl transform transition-all duration-300 hover:scale-105 cursor-pointer overflow-hidden group border border-blue-100">
                
                <!-- Club Team Badge -->
                <div class="absolute top-4 right-4 bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-xs font-semibold z-10">
                    Club Team
                </div>
                
                <div class="p-6">
                    <div class="flex items-start justify-between mb-4">
                        <h4 class="text-xl font-bold text-gray-900 group-hover:text-blue-600 transition-colors" 
                           x-text="team.name"></h4>
                    </div>
                    
                    <div class="space-y-3">
                        <div class="flex items-center text-gray-600">
                            <div class="bg-blue-100 rounded-lg p-2 mr-3">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>
                            <div>
                                <span class="font-medium text-sm">Coach:</span>
                                <span class="ml-2 text-sm" x-text="team.coach"></span>
                            </div>
                        </div>
                        
                        <div class="flex items-center text-gray-600">
                            <div class="bg-blue-100 rounded-lg p-2 mr-3">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <div>
                                <span class="font-medium text-sm">Season:</span>
                                <span class="ml-2 text-sm" x-text="team.season"></span>
                            </div>
                        </div>
                        
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
                    </div>
                    
                    <div class="mt-6 pt-4 border-t border-blue-100">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-500">View team details</span>
                            <svg class="w-5 h-5 text-blue-500 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
    
    <!-- Empty State for Club Teams -->
    <div x-show="clubTeams.length === 0" class="text-center py-16">
        <div class="bg-blue-50 rounded-full w-24 h-24 mx-auto mb-6 flex items-center justify-center">
            <svg class="w-12 h-12 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
            </svg>
        </div>
        <h4 class="text-2xl font-bold text-gray-900 mb-3">No club teams available</h4>
        <p class="text-gray-600 max-w-md mx-auto">
            No other teams are available in your club for the selected season.
        </p>
    </div>
</div>