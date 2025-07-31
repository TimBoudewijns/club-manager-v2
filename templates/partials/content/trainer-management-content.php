<?php
/**
 * Trainer Management Content
 */
?>

<!-- Trainer Management Section -->
<div class="section-header">
    <div class="section-title">
        <span class="section-icon bg-gradient-to-br from-green-100 to-green-200 text-green-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
            </svg>
        </span>
        Trainer Management
    </div>
    <p class="section-subtitle">Invite trainers, assign teams, and manage permissions</p>
</div>

<!-- Trainer Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-6 border border-green-200">
        <div class="flex items-center">
            <div class="bg-green-500 rounded-lg p-3 mr-4">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
            </div>
            <div>
                <p class="text-green-600 text-sm font-medium">Active Trainers</p>
                <p class="text-2xl font-bold text-green-900" x-text="activeTrainers.length || '0'"></p>
            </div>
        </div>
    </div>
    
    <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-xl p-6 border border-yellow-200">
        <div class="flex items-center">
            <div class="bg-yellow-500 rounded-lg p-3 mr-4">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div>
                <p class="text-yellow-600 text-sm font-medium">Pending Invitations</p>
                <p class="text-2xl font-bold text-yellow-900" x-text="pendingInvitations.length || '0'"></p>
            </div>
        </div>
    </div>
    
    <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-6 border border-blue-200">
        <div class="flex items-center">
            <div class="bg-blue-500 rounded-lg p-3 mr-4">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
            </div>
            <div>
                <p class="text-blue-600 text-sm font-medium">Teams Managed</p>
                <p class="text-2xl font-bold text-blue-900" x-text="totalManagedTeams || '0'"></p>
            </div>
        </div>
    </div>
</div>

<!-- Active Trainers -->
<div class="mb-12">
    <h3 class="text-lg font-bold text-gray-900 mb-6 flex items-center">
        <span class="bg-green-100 rounded-lg p-2 mr-3">
            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
            </svg>
        </span>
        Active Trainers
    </h3>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <template x-for="trainer in activeTrainers" :key="trainer.id">
            <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 border border-gray-100 p-6">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center">
                        <div class="bg-green-100 rounded-full w-12 h-12 flex items-center justify-center mr-4">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900" x-text="trainer.name"></h4>
                            <p class="text-sm text-gray-600" x-text="trainer.email"></p>
                        </div>
                    </div>
                    
                    <div class="flex gap-2">
                        <button @click="editTrainer(trainer)" 
                                class="p-2 text-gray-400 hover:text-green-600 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <div class="space-y-2">
                    <div class="flex items-center text-sm text-gray-600">
                        <span class="font-medium">Teams assigned:</span>
                        <span class="ml-2" x-text="trainer.team_count || '0'"></span>
                    </div>
                    <div class="flex items-center text-sm text-gray-600">
                        <span class="font-medium">Role:</span>
                        <span class="ml-2 capitalize" x-text="trainer.role || 'trainer'"></span>
                    </div>
                </div>
            </div>
        </template>
    </div>
    
    <!-- Empty State for Active Trainers -->
    <div x-show="activeTrainers.length === 0" class="text-center py-12">
        <div class="bg-green-50 rounded-full w-16 h-16 mx-auto mb-4 flex items-center justify-center">
            <svg class="w-8 h-8 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
        </div>
        <h4 class="text-lg font-bold text-gray-900 mb-2">No active trainers</h4>
        <p class="text-gray-600">Start by inviting trainers to join your club.</p>
    </div>
</div>

<!-- Pending Invitations -->
<div x-show="pendingInvitations.length > 0">
    <h3 class="text-lg font-bold text-gray-900 mb-6 flex items-center">
        <span class="bg-yellow-100 rounded-lg p-2 mr-3">
            <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </span>
        Pending Invitations
    </h3>
    
    <div class="space-y-4">
        <template x-for="invitation in pendingInvitations" :key="invitation.id">
            <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-medium text-gray-900" x-text="invitation.email"></p>
                        <p class="text-sm text-gray-600">
                            Invited <span x-text="invitation.created_at"></span>
                        </p>
                    </div>
                    <div class="flex gap-2">
                        <button @click="resendInvitation(invitation)" 
                                class="text-sm bg-yellow-100 text-yellow-700 px-3 py-1 rounded-lg hover:bg-yellow-200 transition-colors">
                            Resend
                        </button>
                        <button @click="cancelInvitation(invitation)" 
                                class="text-sm text-gray-500 hover:text-red-600 transition-colors">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>