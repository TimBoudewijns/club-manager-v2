<?php
/**
 * Team Management partial template - For owners/managers to manage all club teams
 */
?>

<div class="w-full">
    <!-- Team Management Header -->
    <div class="mb-8 bg-gradient-to-r from-orange-50 to-amber-50 border border-orange-200 rounded-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 mb-2">Team Management</h2>
                <p class="text-gray-600">Create and manage teams for your club</p>
            </div>
            <button @click="showCreateClubTeamModal = true" 
                    class="bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white font-bold py-3 px-6 rounded-xl shadow-lg transform transition-all duration-200 hover:scale-105 flex items-center space-x-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                <span>Create Team</span>
            </button>
        </div>
    </div>
    
    <!-- Teams Table -->
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Team</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Coach</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Season</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created By</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trainers</th>
                        <th class="px-6 py-4 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="team in managedTeams" :key="team.id">
                        <tr class="hover:bg-orange-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900" x-text="team.name"></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900" x-text="team.coach"></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-orange-100 text-orange-800" 
                                      x-text="team.season"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500" x-text="team.owner_name || 'Unknown'"></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-900" x-text="(team.trainer_count || 0) + ' trainer(s)'"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="flex items-center justify-center space-x-2">
                                    <!-- Manage Trainers -->
                                    <button @click="selectManagedTeam(team); showAssignTrainerModal = true" 
                                            class="text-blue-600 hover:text-blue-900 p-2 rounded-lg hover:bg-blue-50"
                                            title="Manage trainers">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                        </svg>
                                    </button>
                                    <!-- Edit Team -->
                                    <button @click="editManagedTeam(team)" 
                                            class="text-orange-600 hover:text-orange-900 p-2 rounded-lg hover:bg-orange-50"
                                            title="Edit team">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </button>
                                    <!-- Delete Team -->
                                    <button @click="deleteManagedTeam(team.id)" 
                                            class="text-red-600 hover:text-red-900 p-2 rounded-lg hover:bg-red-50"
                                            title="Delete team">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        
        <!-- Empty State -->
        <div x-show="!managedTeams || managedTeams.length === 0" class="text-center py-12">
            <div class="bg-gray-50 rounded-full w-20 h-20 mx-auto mb-4 flex items-center justify-center">
                <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
            </div>
            <h4 class="text-lg font-semibold text-gray-900 mb-2">No teams yet</h4>
            <p class="text-gray-600 mb-6">Create your first club team to get started.</p>
            <button @click="showCreateClubTeamModal = true" 
                    class="bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white font-bold py-2 px-6 rounded-lg shadow-md">
                Create Your First Team
            </button>
        </div>
    </div>
    
    <!-- Team Trainers Section -->
    <div x-show="selectedManagedTeam && teamTrainers" class="mt-8">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                Trainers for <span x-text="selectedManagedTeam?.name"></span>
            </h3>
            
            <div class="space-y-3">
                <template x-for="trainer in teamTrainers" :key="trainer.trainer_id">
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10 bg-orange-100 rounded-full flex items-center justify-center mr-3">
                                <span class="text-orange-600 font-bold" x-text="(trainer.first_name ? trainer.first_name.charAt(0) : '') + (trainer.last_name ? trainer.last_name.charAt(0) : '')"></span>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900" x-text="trainer.display_name"></p>
                                <p class="text-sm text-gray-500" x-text="trainer.email"></p>
                            </div>
                        </div>
                        <button @click="removeTrainerFromTeam(selectedManagedTeam.id, trainer.trainer_id)" 
                                class="text-red-600 hover:text-red-800 p-2 rounded-lg hover:bg-red-50">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </template>
            </div>
            
            <div x-show="teamTrainers.length === 0" class="text-center py-6 text-gray-500">
                No trainers assigned to this team yet.
            </div>
        </div>
    </div>
</div>