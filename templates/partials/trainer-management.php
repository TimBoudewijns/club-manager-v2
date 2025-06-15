<?php
/**
 * Trainer Management partial template
 */
?>

<div class="w-full">
    <!-- Trainer Management Header -->
    <div class="mb-8 bg-gradient-to-r from-orange-50 to-amber-50 border border-orange-200 rounded-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 mb-2">Trainer Management</h2>
                <p class="text-gray-600">Invite and manage trainers for your club teams</p>
            </div>
            <button @click="showInviteTrainerModal = true" 
                    class="bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white font-bold py-3 px-6 rounded-xl shadow-lg transform transition-all duration-200 hover:scale-105 flex items-center space-x-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                </svg>
                <span>Invite Trainer</span>
            </button>
        </div>
    </div>
    
    <!-- Pending Invitations -->
    <div class="mb-8" x-show="pendingInvitations && pendingInvitations.length > 0">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Pending Invitations</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <template x-for="invitation in pendingInvitations" :key="invitation.id">
                <div class="bg-white rounded-lg shadow-md p-4 border border-orange-100">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center">
                            <div class="bg-orange-100 rounded-full p-2 mr-3">
                                <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900" x-text="invitation.email"></p>
                                <p class="text-sm text-gray-500">Invited <span x-text="invitation.created_at ? new Date(invitation.created_at).toLocaleDateString() : ''"></span></p>
                            </div>
                        </div>
                        <button @click="cancelInvitation(invitation.id)" 
                                class="text-red-600 hover:text-red-800 p-2 rounded-lg hover:bg-red-50">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="text-sm text-gray-600">
                        <p>Team: <span class="font-medium text-gray-900" x-text="invitation.team_name || 'Unknown'"></span></p>
                        <p>Role: <span class="font-medium text-gray-900" x-text="invitation.role || 'trainer'"></span></p>
                    </div>
                </div>
            </template>
        </div>
    </div>
    
    <!-- Active Trainers -->
    <div>
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Active Trainers</h3>
        <div class="overflow-x-auto shadow ring-1 ring-black ring-opacity-5 rounded-lg">
            <table class="min-w-full divide-y divide-gray-300">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trainer</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Teams</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-4 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="trainer in activeTrainers" :key="trainer.id">
                        <tr class="hover:bg-orange-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10 bg-orange-100 rounded-full flex items-center justify-center">
                                        <span class="text-orange-600 font-bold" x-text="(trainer.first_name ? trainer.first_name.charAt(0) : '') + (trainer.last_name ? trainer.last_name.charAt(0) : '')"></span>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900" x-text="trainer.display_name"></div>
                                        <div class="text-sm text-gray-500" x-text="trainer.email"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1">
                                    <template x-for="team in (trainer.teams || [])" :key="team.id">
                                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-orange-100 text-orange-800" x-text="team.name || 'Unknown'"></span>
                                    </template>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800" x-text="trainer.role || 'trainer'"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                      :class="trainer.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'">
                                    <span x-text="trainer.is_active ? 'Active' : 'Inactive'"></span>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="flex items-center justify-center space-x-2">
                                    <button @click="editTrainer(trainer)" 
                                            class="text-blue-600 hover:text-blue-900 transition-colors p-2 rounded-lg hover:bg-blue-50"
                                            title="Edit trainer">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </button>
                                    <button @click="removeTrainer(trainer)" 
                                            class="text-red-600 hover:text-red-900 transition-colors p-2 rounded-lg hover:bg-red-50"
                                            title="Remove trainer">
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
        <div x-show="!activeTrainers || activeTrainers.length === 0" class="text-center py-12 bg-white rounded-lg shadow">
            <div class="bg-gray-50 rounded-full w-20 h-20 mx-auto mb-4 flex items-center justify-center">
                <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
            </div>
            <h4 class="text-lg font-semibold text-gray-900 mb-2">No trainers yet</h4>
            <p class="text-gray-600 mb-6">Invite trainers to help manage your club teams.</p>
            <button @click="showInviteTrainerModal = true" 
                    class="bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white font-bold py-2 px-6 rounded-lg shadow-md">
                Invite Your First Trainer
            </button>
        </div>
    </div>
</div>