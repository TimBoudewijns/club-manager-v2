<?php
/**
 * Help tab template
 * Shows help and guidance based on user permissions
 */
?>

<!-- Help Content Container -->
<div class="bg-white rounded-b-2xl shadow-xl border-x border-b border-gray-200 overflow-hidden">
    <!-- Help Header -->
    <div class="bg-gradient-to-r from-green-50 to-emerald-50 border-b border-green-200 p-6 md:p-8">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-3 shadow-lg">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl sm:text-2xl font-bold text-gray-900 mb-1">Help & Documentation</h2>
                    <p class="text-gray-600 text-sm sm:text-base">Everything you need to know about using Club Manager</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Content Area -->
    <div class="p-6 md:p-8">
        <!-- General Help Section - Always visible -->
        <div class="mb-12">
            <h3 class="text-lg sm:text-xl font-bold text-gray-900 mb-6 flex items-center">
                <span class="bg-green-100 rounded-lg p-2 mr-3">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </span>
                Getting Started
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-gray-50 rounded-xl p-6 border border-gray-200">
                    <h4 class="font-semibold text-gray-900 mb-3 flex items-center">
                        <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                        </svg>
                        Dashboard Overview
                    </h4>
                    <p class="text-gray-600 text-sm mb-3">The Club Manager dashboard is organized into tabs:</p>
                    <ul class="text-sm text-gray-600 space-y-2">
                        <li class="flex items-start">
                            <span class="text-green-500 mr-2">•</span>
                            <span><strong>Player Management:</strong> View and manage players across teams</span>
                        </li>
                        <li class="flex items-start" x-show="hasPermission('can_manage_teams')">
                            <span class="text-green-500 mr-2">•</span>
                            <span><strong>Team Management:</strong> Create and organize teams</span>
                        </li>
                        <li class="flex items-start" x-show="hasPermission('can_manage_trainers')">
                            <span class="text-green-500 mr-2">•</span>
                            <span><strong>Trainer Management:</strong> Invite and manage trainers</span>
                        </li>
                        <li class="flex items-start" x-show="hasPermission('can_import_export')">
                            <span class="text-green-500 mr-2">•</span>
                            <span><strong>Import/Export:</strong> Bulk data management</span>
                        </li>
                    </ul>
                </div>
                
                <div class="bg-gray-50 rounded-xl p-6 border border-gray-200">
                    <h4 class="font-semibold text-gray-900 mb-3 flex items-center">
                        <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                        Your Permissions
                    </h4>
                    <p class="text-gray-600 text-sm mb-3">Based on your role, you can:</p>
                    <ul class="text-sm text-gray-600 space-y-2">
                        <li class="flex items-start">
                            <svg class="w-4 h-4 text-green-500 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span>View and manage players in your teams</span>
                        </li>
                        <li class="flex items-start" x-show="hasPermission('can_see_club_teams_in_player_mgmt')">
                            <svg class="w-4 h-4 text-green-500 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span>View all club teams (read-only)</span>
                        </li>
                        <li class="flex items-start" x-show="hasPermission('can_add_teams_player_mgmt')">
                            <svg class="w-4 h-4 text-green-500 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span>Create new teams</span>
                        </li>
                        <li class="flex items-start" x-show="hasPermission('can_manage_teams')">
                            <svg class="w-4 h-4 text-green-500 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span>Full team management access</span>
                        </li>
                        <li class="flex items-start" x-show="hasPermission('can_manage_trainers')">
                            <svg class="w-4 h-4 text-green-500 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span>Invite and manage trainers</span>
                        </li>
                        <li class="flex items-start" x-show="hasPermission('can_import_export')">
                            <svg class="w-4 h-4 text-green-500 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span>Import and export data</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Player Management Help -->
        <div class="mb-12">
            <h3 class="text-lg sm:text-xl font-bold text-gray-900 mb-6 flex items-center">
                <span class="bg-orange-100 rounded-lg p-2 mr-3">
                    <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </span>
                Player Management
            </h3>
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="bg-orange-50 rounded-xl p-6 border border-orange-200">
                    <h4 class="font-semibold text-gray-900 mb-3">Adding Players</h4>
                    <ol class="text-sm text-gray-600 space-y-2">
                        <li>1. Click on a team in "My Teams"</li>
                        <li>2. Click "Add Player" button</li>
                        <li>3. Fill in player information</li>
                        <li>4. Submit to add player to team</li>
                    </ol>
                </div>
                
                <div class="bg-orange-50 rounded-xl p-6 border border-orange-200">
                    <h4 class="font-semibold text-gray-900 mb-3">Player Evaluations</h4>
                    <ol class="text-sm text-gray-600 space-y-2">
                        <li>1. Click on a player card</li>
                        <li>2. Select "Add Evaluation"</li>
                        <li>3. Rate various skills</li>
                        <li>4. Add optional notes</li>
                        <li>5. Save evaluation</li>
                    </ol>
                </div>
                
                <div class="bg-orange-50 rounded-xl p-6 border border-orange-200">
                    <h4 class="font-semibold text-gray-900 mb-3">Viewing History</h4>
                    <p class="text-sm text-gray-600">
                        Click "View History" on any player card to see their evaluation history, 
                        progress over time, and notes from trainers.
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Team Management Help - Only for users with permission -->
        <div class="mb-12" x-show="hasPermission('can_manage_teams')" x-cloak>
            <h3 class="text-lg sm:text-xl font-bold text-gray-900 mb-6 flex items-center">
                <span class="bg-blue-100 rounded-lg p-2 mr-3">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                    </svg>
                </span>
                Team Management
            </h3>
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="bg-blue-50 rounded-xl p-6 border border-blue-200">
                    <h4 class="font-semibold text-gray-900 mb-3">Creating Teams</h4>
                    <ol class="text-sm text-gray-600 space-y-2">
                        <li>1. Go to Team Management tab</li>
                        <li>2. Click "Create New Team"</li>
                        <li>3. Enter team details</li>
                        <li>4. Assign a coach</li>
                        <li>5. Set season/year</li>
                    </ol>
                </div>
                
                <div class="bg-blue-50 rounded-xl p-6 border border-blue-200">
                    <h4 class="font-semibold text-gray-900 mb-3">Assigning Trainers</h4>
                    <ol class="text-sm text-gray-600 space-y-2">
                        <li>1. Click on a team</li>
                        <li>2. Select "Assign Trainer"</li>
                        <li>3. Choose from available trainers</li>
                        <li>4. Confirm assignment</li>
                    </ol>
                </div>
                
                <div class="bg-blue-50 rounded-xl p-6 border border-blue-200">
                    <h4 class="font-semibold text-gray-900 mb-3">Managing Teams</h4>
                    <p class="text-sm text-gray-600">
                        Edit team details, remove trainers, or delete teams by clicking on the 
                        team card and using the available options.
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Trainer Management Help - Only for users with permission -->
        <div class="mb-12" x-show="hasPermission('can_manage_trainers')" x-cloak>
            <h3 class="text-lg sm:text-xl font-bold text-gray-900 mb-6 flex items-center">
                <span class="bg-purple-100 rounded-lg p-2 mr-3">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                </span>
                Trainer Management
            </h3>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-purple-50 rounded-xl p-6 border border-purple-200">
                    <h4 class="font-semibold text-gray-900 mb-3">Inviting Trainers</h4>
                    <ol class="text-sm text-gray-600 space-y-2">
                        <li>1. Go to Trainer Management tab</li>
                        <li>2. Click "Invite New Trainer"</li>
                        <li>3. Enter trainer's email</li>
                        <li>4. Send invitation</li>
                        <li>5. Trainer receives email with instructions</li>
                    </ol>
                </div>
                
                <div class="bg-purple-50 rounded-xl p-6 border border-purple-200">
                    <h4 class="font-semibold text-gray-900 mb-3">Managing Access</h4>
                    <p class="text-sm text-gray-600 mb-3">
                        Trainers can only access teams assigned to them. Use Team Management 
                        to assign or remove trainers from specific teams.
                    </p>
                    <p class="text-sm text-gray-600">
                        Remove trainer access by clicking "Remove" on their card in the Trainer Management tab.
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Import/Export Help - Only for users with permission -->
        <div class="mb-12" x-show="hasPermission('can_import_export')" x-cloak>
            <h3 class="text-lg sm:text-xl font-bold text-gray-900 mb-6 flex items-center">
                <span class="bg-indigo-100 rounded-lg p-2 mr-3">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
                    </svg>
                </span>
                Import/Export
            </h3>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-indigo-50 rounded-xl p-6 border border-indigo-200">
                    <h4 class="font-semibold text-gray-900 mb-3">Importing Data</h4>
                    <ol class="text-sm text-gray-600 space-y-2">
                        <li>1. Download the CSV template</li>
                        <li>2. Fill in player data following the format</li>
                        <li>3. Upload the CSV file</li>
                        <li>4. Review and confirm import</li>
                    </ol>
                    <p class="text-sm text-gray-500 mt-3">
                        <strong>Tip:</strong> Test with a small batch first
                    </p>
                </div>
                
                <div class="bg-indigo-50 rounded-xl p-6 border border-indigo-200">
                    <h4 class="font-semibold text-gray-900 mb-3">Exporting Data</h4>
                    <ol class="text-sm text-gray-600 space-y-2">
                        <li>1. Select teams to export</li>
                        <li>2. Choose export format (CSV/Excel)</li>
                        <li>3. Click "Export"</li>
                        <li>4. Download the file</li>
                    </ol>
                    <p class="text-sm text-gray-500 mt-3">
                        <strong>Note:</strong> Exports include all player data and evaluations
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Tips & Best Practices -->
        <div class="bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl p-6 border border-gray-200">
            <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                <svg class="w-5 h-5 text-yellow-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M11 3a1 1 0 10-2 0v1a1 1 0 102 0V3zM15.657 5.757a1 1 0 00-1.414-1.414l-.707.707a1 1 0 001.414 1.414l.707-.707zM18 10a1 1 0 01-1 1h-1a1 1 0 110-2h1a1 1 0 011 1zM5.05 6.464A1 1 0 106.464 5.05l-.707-.707a1 1 0 00-1.414 1.414l.707.707zM5 10a1 1 0 01-1 1H3a1 1 0 110-2h1a1 1 0 011 1zM8 16v-1h4v1a2 2 0 11-4 0zM12 14c.015-.34.208-.646.477-.859a4 4 0 10-4.954 0c.27.213.462.519.476.859h4.002z"></path>
                </svg>
                Tips & Best Practices
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <h4 class="font-semibold text-gray-800 mb-2">For Effective Player Management:</h4>
                    <ul class="text-sm text-gray-600 space-y-1">
                        <li>• Regular evaluations help track progress</li>
                        <li>• Add detailed notes for better insights</li>
                        <li>• Use consistent rating criteria</li>
                        <li>• Keep player information up-to-date</li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold text-gray-800 mb-2">For Smooth Operations:</h4>
                    <ul class="text-sm text-gray-600 space-y-1">
                        <li>• Organize teams by age groups or skill levels</li>
                        <li>• Assign clear responsibilities to trainers</li>
                        <li>• Regular backups via export feature</li>
                        <li>• Communicate changes to all stakeholders</li>
                    </ul>
                </div>
            </div>
        </div>
        
    </div>
</div>