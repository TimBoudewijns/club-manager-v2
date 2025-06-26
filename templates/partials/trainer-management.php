<?php
/**
 * Trainer Management partial template
 */

// Helper function to get available seats from WooCommerce Teams
function cm_get_available_trainer_seats() {
    $user_id = get_current_user_id();
    
    if (!function_exists('wc_memberships_for_teams')) {
        return false;
    }
    
    // If user can view club teams, they have access - simplify the check
    if (!Club_Manager_Teams_Helper::can_view_club_teams($user_id)) {
        return array('available' => 0, 'total' => 0, 'used' => 0);
    }
    
    $total_seats = 0;
    $used_seats = 0;
    $debug_info = array();
    
    // Simple approach: Just get all teams where user has access
    global $wpdb;
    
    // First try: Get teams where user is post author (owner)
    $owned_teams = $wpdb->get_col($wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts} 
         WHERE post_type = 'wc_memberships_team' 
         AND post_author = %d 
         AND post_status = 'publish'",
        $user_id
    ));
    
    // Second try: Get teams where user is a member with owner/manager role
    $member_teams = $wpdb->get_col($wpdb->prepare(
        "SELECT DISTINCT p.ID 
         FROM {$wpdb->posts} p
         INNER JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id 
         WHERE p.post_type = 'wc_memberships_team'
         AND p.post_status = 'publish'
         AND pm1.meta_key = '_member_id' 
         AND pm1.meta_value = %d",
        $user_id
    ));
    
    // Combine all team IDs
    $all_team_ids = array_unique(array_merge($owned_teams, $member_teams));
    $debug_info['found_teams'] = count($all_team_ids);
    
    // Get seat count from the first team found
    if (!empty($all_team_ids) && function_exists('wc_memberships_for_teams_get_team')) {
        foreach ($all_team_ids as $team_id) {
            $wc_team = wc_memberships_for_teams_get_team($team_id);
            
            if ($wc_team && is_object($wc_team)) {
                // Get seat counts
                if (method_exists($wc_team, 'get_seat_count')) {
                    $seats = $wc_team->get_seat_count();
                    if ($seats > $total_seats) {
                        $total_seats = $seats;
                    }
                }
                
                if (method_exists($wc_team, 'get_used_seat_count')) {
                    $used = $wc_team->get_used_seat_count();
                    if ($used > $used_seats) {
                        $used_seats = $used;
                    }
                }
                
                // If we found seats, we can stop
                if ($total_seats > 0) {
                    break;
                }
            }
        }
    }
    
    // If still no seats found, assume unlimited
    if ($total_seats == 0) {
        // Return a high number to indicate no limit
        return array(
            'available' => 999,
            'total' => 999,
            'used' => 0,
            'unlimited' => true,
            'debug' => $debug_info
        );
    }
    
    return array(
        'available' => max(0, $total_seats - $used_seats),
        'total' => $total_seats,
        'used' => $used_seats,
        'debug' => $debug_info
    );
}

$seat_info = cm_get_available_trainer_seats();
?>

<!-- Trainer Management Content Container -->
<div class="bg-white rounded-2xl shadow-xl border border-orange-100 overflow-hidden">
    <!-- Trainer Management Header with Limits -->
    <div class="bg-gradient-to-r from-orange-50 to-amber-50 border-b border-orange-200 p-6 md:p-8">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl p-3 shadow-lg">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-1">Trainer Management</h2>
                    <p class="text-gray-600">Invite and manage trainers for your club teams</p>
                    <?php if ($seat_info !== false && $seat_info['total'] > 0): ?>
                        <div class="mt-3">
                            <?php if (isset($seat_info['unlimited']) && $seat_info['unlimited']): ?>
                                <p class="text-sm text-green-600 font-medium">
                                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Unlimited trainer seats available
                                </p>
                            <?php else: ?>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm text-gray-600">
                                        Team Seats Used: 
                                        <span class="font-semibold text-orange-600"><?php echo $seat_info['used']; ?></span>
                                        / 
                                        <span class="font-semibold text-gray-900"><?php echo $seat_info['total']; ?></span>
                                    </span>
                                    <span class="text-sm text-green-600 font-medium">
                                        <?php echo $seat_info['available']; ?> seats available
                                    </span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-orange-500 h-2 rounded-full transition-all duration-300" 
                                         style="width: <?php echo min(100, ($seat_info['used'] / $seat_info['total']) * 100); ?>%"></div>
                                </div>
                            <?php endif; ?>
                            <p class="text-xs text-gray-500 mt-1">
                                Trainers count towards your WooCommerce team membership seats
                            </p>
                        </div>
                    <?php elseif ($seat_info !== false): ?>
                        <p class="text-sm text-yellow-600 mt-3">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            No team membership seats configured. Contact your administrator.
                        </p>
                    <?php else: ?>
                        <p class="text-sm text-yellow-600 mt-3">
                            WooCommerce Teams for Memberships is not active. Install it to manage trainer limits.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
            <button @click="checkTrainerLimit() && (showInviteTrainerModal = true)" 
                    class="bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white font-bold py-3 px-6 rounded-xl shadow-lg transform transition-all duration-200 hover:scale-105 flex items-center space-x-2"
                    :class="{ 'opacity-50 cursor-not-allowed': !canInviteMoreTrainers() }"
                    <?php echo ($seat_info !== false && $seat_info['available'] <= 0) ? 'disabled' : ''; ?>>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                </svg>
                <span>Invite Trainer</span>
            </button>
        </div>
    </div>
    
    <!-- Content Area -->
    <div class="p-6 md:p-8">
        <!-- Pending Invitations -->
        <div class="mb-8" x-show="pendingInvitations && pendingInvitations.length > 0">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <span class="bg-yellow-100 rounded-lg p-2 mr-3">
                    <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </span>
                Pending Invitations
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <template x-for="invitation in pendingInvitations" :key="invitation.id">
                    <div class="bg-white rounded-xl shadow-lg p-6 border border-yellow-200 hover:shadow-xl transition-shadow">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center">
                                <div class="bg-yellow-100 rounded-full p-2 mr-3">
                                    <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900" x-text="invitation.email"></p>
                                    <p class="text-sm text-gray-500">Invited <span x-text="invitation.created_at ? new Date(invitation.created_at).toLocaleDateString() : ''"></span></p>
                                </div>
                            </div>
                            <button @click="cancelInvitation(invitation.id)" 
                                    class="text-red-600 hover:text-red-800 p-2 rounded-lg hover:bg-red-50 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        <div class="space-y-2 text-sm text-gray-600">
                            <div class="flex items-center">
                                <span class="font-medium text-gray-700 mr-2">Team:</span>
                                <span class="text-gray-900" x-text="invitation.team_name || 'Unknown'"></span>
                            </div>
                            <div class="flex items-center">
                                <span class="font-medium text-gray-700 mr-2">Role:</span>
                                <span class="px-2 py-1 bg-gray-100 text-gray-800 rounded-full text-xs font-medium" x-text="invitation.role || 'trainer'"></span>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
        
        <!-- Active Trainers -->
        <div>
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <span class="bg-green-100 rounded-lg p-2 mr-3">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </span>
                Active Trainers
            </h3>
            <div class="overflow-hidden">
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
                <div x-show="!activeTrainers || activeTrainers.length === 0" class="text-center py-16">
                    <div class="bg-orange-50 rounded-full w-24 h-24 mx-auto mb-6 flex items-center justify-center">
                        <svg class="w-12 h-12 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                    </div>
                    <h4 class="text-2xl font-bold text-gray-900 mb-2">No trainers yet</h4>
                    <p class="text-gray-600 mb-6">Invite trainers to help manage your club teams.</p>
                    <button @click="showInviteTrainerModal = true" 
                            class="bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white font-bold py-3 px-8 rounded-xl shadow-lg transform transition-all duration-200 hover:scale-105">
                        Invite Your First Trainer
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>