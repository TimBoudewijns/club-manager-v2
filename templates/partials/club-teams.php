<?php
/**
 * Club Teams partial template
 */

// Get user's managed teams from Teams for WooCommerce Memberships
$managed_teams = Club_Manager_Assets::get_user_managed_teams();
?>

<div class="bg-white rounded-2xl shadow-xl p-8">
    <div class="mb-8">
        <h2 class="text-3xl font-bold text-gray-900 mb-2">Club Teams Management</h2>
        <p class="text-gray-600">Manage teams where you are an owner or manager</p>
    </div>
    
    <?php if (!empty($managed_teams)): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($managed_teams as $team_info): ?>
                <div class="bg-gradient-to-br from-orange-50 to-amber-50 rounded-xl shadow-lg hover:shadow-2xl transform transition-all duration-300 hover:scale-105 overflow-hidden border-2 border-orange-200">
                    <div class="bg-gradient-to-r from-orange-400 to-orange-500 h-3"></div>
                    <div class="p-6">
                        <div class="flex items-start justify-between mb-4">
                            <h3 class="text-2xl font-bold text-gray-900"><?php echo esc_html($team_info['team_name']); ?></h3>
                            <span class="bg-orange-600 text-white px-3 py-1 rounded-full text-sm font-semibold">
                                <?php echo ucfirst(esc_html($team_info['role'])); ?>
                            </span>
                        </div>
                        
                        <div class="space-y-2 mb-4">
                            <div class="flex items-center text-gray-600">
                                <svg class="w-5 h-5 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                </svg>
                                <span class="font-medium">Team ID:</span>
                                <span class="ml-2">#<?php echo esc_html($team_info['team_id']); ?></span>
                            </div>
                        </div>
                        
                        <div class="mt-6 pt-4 border-t border-orange-200">
                            <p class="text-sm text-gray-600">
                                You have <span class="font-semibold text-orange-600"><?php echo esc_html($team_info['role']); ?></span> permissions for this team.
                            </p>
                        </div>
                        
                        <div class="mt-4">
                            <button class="w-full bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white font-bold py-2 px-4 rounded-lg shadow-md transform transition-all duration-200 hover:scale-105">
                                Manage Team
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-6">
            <div class="flex items-start">
                <svg class="w-6 h-6 text-blue-600 mr-3 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div>
                    <h4 class="text-lg font-semibold text-blue-900 mb-2">About Club Teams</h4>
                    <p class="text-blue-800">
                        This section shows teams from your WooCommerce Memberships where you have owner or manager permissions. 
                        Full team management functionality will be available in a future update.
                    </p>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="text-center py-16">
            <div class="bg-gray-50 rounded-full w-24 h-24 mx-auto mb-6 flex items-center justify-center">
                <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-2">No Club Teams Found</h3>
            <p class="text-gray-600 mb-6">You are not currently an owner or manager of any teams.</p>
            <p class="text-sm text-gray-500">Contact your club administrator to be assigned to a team.</p>
        </div>
    <?php endif; ?>
</div>