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

<!-- Create Club Team Modal -->
<div x-show="showCreateClubTeamModal" 
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="modal-wrapper" 
     style="display: none;">
    <div class="modal-container">
        <div class="modal-backdrop" @click="showCreateClubTeamModal = false"></div>
        
        <div class="modal-content bg-white rounded-2xl shadow-2xl max-w-2xl w-full overflow-hidden"
             @click.stop>
            <div class="bg-gradient-to-r from-orange-500 to-orange-600 p-6 text-white">
                <h3 class="font-bold text-2xl">Create Club Team</h3>
                <p class="text-orange-100 mt-1">Add a new team to your club</p>
            </div>
            <form @submit.prevent="createClubTeam" class="p-6">
                <div class="space-y-6">
                    <div class="form-control w-full">
                        <label class="label">
                            <span class="label-text font-semibold text-gray-700">Team Name</span>
                        </label>
                        <input type="text" x-model="newClubTeam.name" 
                               placeholder="Enter team name" 
                               class="input input-bordered w-full bg-gray-50 border-gray-300 focus:border-orange-500 focus:ring-2 focus:ring-orange-200 rounded-lg" 
                               required />
                    </div>
                    
                    <div class="form-control w-full">
                        <label class="label">
                            <span class="label-text font-semibold text-gray-700">Coach</span>
                        </label>
                        <input type="text" x-model="newClubTeam.coach" 
                               placeholder="Enter coach name" 
                               class="input input-bordered w-full bg-gray-50 border-gray-300 focus:border-orange-500 focus:ring-2 focus:ring-orange-200 rounded-lg" 
                               required />
                    </div>
                    
                    <div class="form-control w-full">
                        <label class="label">
                            <span class="label-text font-semibold text-gray-700">Assign Trainers (Optional)</span>
                        </label>
                        <div class="space-y-2 max-h-48 overflow-y-auto border border-gray-200 rounded-lg p-3">
                            <template x-for="trainer in availableTrainers" :key="trainer.id">
                                <label class="flex items-center space-x-3 p-2 hover:bg-gray-50 rounded cursor-pointer">
                                    <input type="checkbox" 
                                           :value="trainer.id"
                                           x-model="newClubTeam.trainers"
                                           class="checkbox checkbox-orange" />
                                    <span class="text-gray-900" x-text="trainer.display_name"></span>
                                </label>
                            </template>
                            <div x-show="availableTrainers.length === 0" class="text-gray-500 text-center py-4">
                                No trainers available. Invite trainers first.
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-action mt-8">
                    <button type="button" 
                            class="btn bg-gray-200 hover:bg-gray-300 text-gray-800 border-0 rounded-lg px-6" 
                            @click="showCreateClubTeamModal = false">Cancel</button>
                    <button type="submit" 
                            class="btn bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white border-0 rounded-lg px-8 shadow-lg">
                        Create Team
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Assign Trainer Modal -->
<div x-show="showAssignTrainerModal" 
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="modal-wrapper" 
     style="display: none;">
    <div class="modal-container">
        <div class="modal-backdrop" @click="showAssignTrainerModal = false"></div>
        
        <div class="modal-content bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden"
             @click.stop>
            <div class="bg-gradient-to-r from-orange-500 to-orange-600 p-6 text-white">
                <h3 class="font-bold text-2xl">Assign Trainer</h3>
                <p class="text-orange-100 mt-1">Add a trainer to <span x-text="selectedManagedTeam?.name"></span></p>
            </div>
            <form @submit.prevent="trainerAssignment.teamId = selectedManagedTeam?.id; trainerAssignment.trainerId && assignTrainerToTeam()" class="p-6">
                <div class="form-control w-full">
                    <label class="label">
                        <span class="label-text font-semibold text-gray-700">Select Trainer</span>
                    </label>
                    <select x-model="trainerAssignment.trainerId" 
                            class="select select-bordered w-full bg-gray-50 border-gray-300 focus:border-orange-500 focus:ring-2 focus:ring-orange-200 rounded-lg"
                            required>
                        <option value="">Choose a trainer...</option>
                        <template x-for="trainer in availableTrainers" :key="trainer.id">
                            <option :value="trainer.id" x-text="trainer.display_name"></option>
                        </template>
                    </select>
                </div>
                
                <div class="modal-action mt-8">
                    <button type="button" 
                            class="btn bg-gray-200 hover:bg-gray-300 text-gray-800 border-0 rounded-lg px-6" 
                            @click="showAssignTrainerModal = false">Cancel</button>
                    <button type="submit" 
                            class="btn bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white border-0 rounded-lg px-8 shadow-lg">
                        Assign Trainer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>