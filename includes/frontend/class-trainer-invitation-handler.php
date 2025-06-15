<?php

/**
 * Handle trainer invitation acceptance
 */
class Club_Manager_Trainer_Invitation_Handler {
    
    /**
     * Initialize the invitation handler
     */
    public function init() {
        add_action('init', array($this, 'check_invitation_token'));
        add_shortcode('club_manager_accept_invitation', array($this, 'render_accept_invitation'));
        add_action('woocommerce_created_customer', array($this, 'handle_new_trainer_registration'), 10, 3);
    }
    
    /**
     * Check if invitation token is present in URL
     */
    public function check_invitation_token() {
        if (isset($_GET['cm_trainer_invite'])) {
            $token = sanitize_text_field($_GET['cm_trainer_invite']);
            
            // Store token in session for registration process
            if (!session_id()) {
                session_start();
            }
            $_SESSION['cm_invitation_token'] = $token;
            
            // Check if this is the page with the shortcode
            global $post;
            if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'club_manager_accept_invitation')) {
                // We're already on the correct page, let the shortcode handle it
                return;
            }
            
            if (!is_user_logged_in()) {
                // Redirect to registration page with invitation token
                $redirect_url = add_query_arg('cm_trainer_invite', $token, wc_get_page_permalink('myaccount'));
                wp_redirect($redirect_url);
                exit;
            } else {
                // User is logged in, try to find the page with the accept invitation shortcode
                global $wpdb;
                $page_id = $wpdb->get_var(
                    "SELECT ID FROM {$wpdb->posts} 
                     WHERE post_content LIKE '%[club_manager_accept_invitation]%' 
                     AND post_status = 'publish' 
                     AND post_type = 'page' 
                     LIMIT 1"
                );
                
                if ($page_id) {
                    // Redirect to the page with the shortcode
                    $redirect_url = add_query_arg('cm_trainer_invite', $token, get_permalink($page_id));
                    wp_redirect($redirect_url);
                    exit;
                } else {
                    // No page found with shortcode, show message
                    wp_die(
                        'Trainer invitation page not found. Please contact the administrator and ask them to create a page with the [club_manager_accept_invitation] shortcode.',
                        'Page Not Found',
                        array('response' => 404)
                    );
                }
            }
        }
    }
    
    /**
     * Handle new trainer registration
     */
    public function handle_new_trainer_registration($customer_id, $new_customer_data, $password_generated) {
        if (!session_id()) {
            session_start();
        }
        
        // Check if we have an invitation token
        if (isset($_SESSION['cm_invitation_token'])) {
            $token = $_SESSION['cm_invitation_token'];
            
            // Get invitation details
            global $wpdb;
            $invitations_table = Club_Manager_Database::get_table_name('trainer_invitations');
            
            $invitation = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $invitations_table WHERE token = %s AND status = 'pending' LIMIT 1",
                $token
            ));
            
            if ($invitation && $invitation->email === $new_customer_data['user_email']) {
                // Process the invitation automatically
                $this->auto_accept_invitation($customer_id, $token);
            }
            
            // Clear the session token
            unset($_SESSION['cm_invitation_token']);
        }
    }
    
    /**
     * Auto-accept invitation after registration
     */
    private function auto_accept_invitation($user_id, $token) {
        global $wpdb;
        
        $invitations_table = Club_Manager_Database::get_table_name('trainer_invitations');
        $teams_table = Club_Manager_Database::get_table_name('teams');
        
        $invitations = $wpdb->get_results($wpdb->prepare(
            "SELECT i.*, t.id as team_id
            FROM $invitations_table i
            INNER JOIN $teams_table t ON i.team_id = t.id
            WHERE i.token = %s AND i.status = 'pending'",
            $token
        ));
        
        foreach ($invitations as $invitation) {
            // Add trainer to Club Manager
            $this->add_trainer_to_team($invitation->team_id, $user_id, $invitation->role, $invitation->invited_by);
            
            // Add to WooCommerce team
            $this->add_to_wc_team($invitation->team_id, $user_id);
        }
        
        // Update invitation status
        $wpdb->update(
            $invitations_table,
            [
                'status' => 'accepted',
                'accepted_at' => current_time('mysql')
            ],
            ['token' => $token],
            ['%s', '%s'],
            ['%s']
        );
    }
    
    /**
     * Render the invitation acceptance form
     */
    public function render_accept_invitation() {
        if (!is_user_logged_in()) {
            return '<p>Please <a href="' . wc_get_page_permalink('myaccount') . '">log in or register</a> to accept the trainer invitation.</p>';
        }
        
        $token = isset($_GET['cm_trainer_invite']) ? sanitize_text_field($_GET['cm_trainer_invite']) : '';
        
        if (empty($token)) {
            return '<p>Invalid invitation link.</p>';
        }
        
        // Get invitation details
        global $wpdb;
        $invitations_table = Club_Manager_Database::get_table_name('trainer_invitations');
        $teams_table = Club_Manager_Database::get_table_name('teams');
        
        $invitations = $wpdb->get_results($wpdb->prepare(
            "SELECT i.*, t.name as team_name, t.id as team_id, u.display_name as inviter_name
            FROM $invitations_table i
            INNER JOIN $teams_table t ON i.team_id = t.id
            LEFT JOIN {$wpdb->users} u ON i.invited_by = u.ID
            WHERE i.token = %s AND i.status = 'pending'",
            $token
        ));
        
        if (empty($invitations)) {
            return '<p>This invitation has already been accepted or is no longer valid.</p>';
        }
        
        $current_user = wp_get_current_user();
        
        // Check if email matches
        $email_match = false;
        foreach ($invitations as $invitation) {
            if ($invitation->email === $current_user->user_email) {
                $email_match = true;
                break;
            }
        }
        
        if (!$email_match) {
            return '<p>This invitation was sent to a different email address (' . esc_html($invitations[0]->email) . '). Please log in with the correct account.</p>';
        }
        
        // Handle form submission
        if (isset($_POST['accept_invitation']) && wp_verify_nonce($_POST['invitation_nonce'], 'accept_trainer_invitation')) {
            return $this->process_invitation_acceptance($invitations, $current_user->ID);
        }
        
        if (isset($_POST['decline_invitation']) && wp_verify_nonce($_POST['invitation_nonce'], 'accept_trainer_invitation')) {
            return $this->process_invitation_decline($token);
        }
        
        // Render the form
        ob_start();
        ?>
        <div class="club-manager-invitation-wrapper max-w-2xl mx-auto p-6">
            <div class="bg-white rounded-lg shadow-lg p-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Trainer Invitation</h2>
                
                <p class="text-gray-600 mb-6">
                    You have been invited by <strong><?php echo esc_html($invitations[0]->inviter_name); ?></strong> 
                    to join as a trainer for the following teams:
                </p>
                
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">Teams:</h3>
                    <ul class="list-disc list-inside space-y-2">
                        <?php foreach ($invitations as $invitation): ?>
                            <li class="text-gray-700">
                                <?php echo esc_html($invitation->team_name); ?> 
                                <span class="text-sm text-gray-500">(Role: <?php echo esc_html(ucfirst($invitation->role)); ?>)</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <?php if (!empty($invitations[0]->message)): ?>
                    <div class="mb-6 bg-gray-50 rounded-lg p-4">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Personal Message:</h3>
                        <p class="text-gray-700"><?php echo nl2br(esc_html($invitations[0]->message)); ?></p>
                    </div>
                <?php endif; ?>
                
                <div class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h3 class="text-lg font-semibold text-blue-800 mb-2">What happens next?</h3>
                    <p class="text-blue-700">
                        By accepting this invitation, you will:
                    </p>
                    <ul class="list-disc list-inside mt-2 space-y-1 text-blue-700">
                        <li>Be added as a trainer to the teams listed above</li>
                        <li>Gain access to the Club Manager dashboard</li>
                        <li>Be able to view and evaluate players in these teams</li>
                        <li>Receive team membership benefits through the club</li>
                    </ul>
                </div>
                
                <form method="post" class="mt-6">
                    <?php wp_nonce_field('accept_trainer_invitation', 'invitation_nonce'); ?>
                    <input type="hidden" name="token" value="<?php echo esc_attr($token); ?>">
                    
                    <div class="flex space-x-4">
                        <button type="submit" name="accept_invitation" value="1" 
                                class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded-lg">
                            Accept Invitation
                        </button>
                        <button type="submit" name="decline_invitation" value="1" 
                                class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-6 rounded-lg">
                            Decline Invitation
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Process the invitation acceptance
     */
    private function process_invitation_acceptance($invitations, $user_id) {
        global $wpdb;
        
        $trainers_table = Club_Manager_Database::get_table_name('team_trainers');
        $invitations_table = Club_Manager_Database::get_table_name('trainer_invitations');
        
        $success = true;
        $teams_joined = [];
        $wc_teams_joined = [];
        
        // Add trainer to each team
        foreach ($invitations as $invitation) {
            // Add to Club Manager
            if ($this->add_trainer_to_team($invitation->team_id, $user_id, $invitation->role, $invitation->invited_by)) {
                $teams_joined[] = $invitation->team_name;
                
                // Add to WooCommerce team
                $wc_result = $this->add_to_wc_team($invitation->team_id, $user_id);
                if ($wc_result['success']) {
                    $wc_teams_joined[] = $wc_result['team_name'];
                }
            } else {
                $success = false;
            }
        }
        
        // Update invitation status
        if ($success) {
            $wpdb->update(
                $invitations_table,
                [
                    'status' => 'accepted',
                    'accepted_at' => current_time('mysql')
                ],
                ['token' => $invitations[0]->token],
                ['%s', '%s'],
                ['%s']
            );
            
            // Send notification to inviter
            $this->send_acceptance_notification($invitations[0]->invited_by, $user_id, $teams_joined);
            
            $message = '<div class="bg-green-50 border border-green-200 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-green-800 mb-2">Success!</h3>
                <p class="text-green-700">You have been added as a trainer to the following teams: ' . 
                implode(', ', $teams_joined) . '</p>';
                
            if (!empty($wc_teams_joined)) {
                $message .= '<p class="text-green-700 mt-2">You have also been added to the WooCommerce team memberships.</p>';
            }
                
            $message .= '<p class="mt-4"><a href="' . home_url('/club-manager/') . '" class="text-blue-600 hover:underline">
                    Go to Club Manager Dashboard
                </a></p>
            </div>';
            
            return $message;
        } else {
            return '<div class="bg-red-50 border border-red-200 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-red-800 mb-2">Error</h3>
                <p class="text-red-700">There was an error accepting the invitation. Please try again or contact support.</p>
            </div>';
        }
    }
    
    /**
     * Process invitation decline
     */
    private function process_invitation_decline($token) {
        global $wpdb;
        
        $invitations_table = Club_Manager_Database::get_table_name('trainer_invitations');
        
        $wpdb->update(
            $invitations_table,
            ['status' => 'declined'],
            ['token' => $token],
            ['%s'],
            ['%s']
        );
        
        return '<div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-yellow-800 mb-2">Invitation Declined</h3>
            <p class="text-yellow-700">You have declined the trainer invitation.</p>
        </div>';
    }
    
    /**
     * Add trainer to team
     */
    private function add_trainer_to_team($team_id, $user_id, $role, $invited_by) {
        global $wpdb;
        
        $trainers_table = Club_Manager_Database::get_table_name('team_trainers');
        
        // Check if already a trainer
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $trainers_table WHERE team_id = %d AND trainer_id = %d",
            $team_id, $user_id
        ));
        
        if (!$existing) {
            return $wpdb->insert(
                $trainers_table,
                [
                    'team_id' => $team_id,
                    'trainer_id' => $user_id,
                    'role' => $role,
                    'is_active' => 1,
                    'added_by' => $invited_by,
                    'added_at' => current_time('mysql')
                ],
                ['%d', '%d', '%s', '%d', '%d', '%s']
            );
        }
        
        return true;
    }
    
    /**
     * Add user to WooCommerce team
     */
    private function add_to_wc_team($cm_team_id, $user_id) {
        if (!function_exists('wc_memberships_for_teams')) {
            return ['success' => false, 'message' => 'Teams for Memberships not active'];
        }
        
        // Find the WooCommerce team associated with this Club Manager team
        // This assumes team owners in Club Manager are also team owners in WC Teams
        global $wpdb;
        $teams_table = Club_Manager_Database::get_table_name('teams');
        
        // Get the team owner
        $team_owner_id = $wpdb->get_var($wpdb->prepare(
            "SELECT created_by FROM $teams_table WHERE id = %d",
            $cm_team_id
        ));
        
        if (!$team_owner_id) {
            return ['success' => false, 'message' => 'Team owner not found'];
        }
        
        // Find the WooCommerce team associated with this Club Manager team
        // First try to find teams where the owner is a member
        if (function_exists('wc_memberships_for_teams_get_user_teams')) {
            $owner_teams = wc_memberships_for_teams_get_user_teams($team_owner_id);
            
            if (!empty($owner_teams)) {
                foreach ($owner_teams as $team) {
                    if (is_object($team)) {
                        // Check if owner has owner or manager role
                        if (method_exists($team, 'get_member')) {
                            $member = $team->get_member($team_owner_id);
                            if ($member && method_exists($member, 'get_role')) {
                                $role = $member->get_role();
                                if (in_array($role, array('owner', 'manager'))) {
                                    $wc_team = $team;
                                    break;
                                }
                            }
                        }
                    }
                }
            }
        }
        
        // Fallback: Find WC teams owned by this user (post author)
        if (!isset($wc_team)) {
            $args = array(
                'post_type' => 'wc_memberships_team',
                'post_status' => 'publish',
                'author' => $team_owner_id,
                'posts_per_page' => 1
            );
            
            $wc_teams = get_posts($args);
            
            if (!empty($wc_teams)) {
                $wc_team = wc_memberships_for_teams_get_team($wc_teams[0]->ID);
            }
        }
        
        if (!isset($wc_team) || !$wc_team) {
            return ['success' => false, 'message' => 'No WooCommerce team found for user'];
        }
        
        // Check if team has available seats
        if (method_exists($wc_team, 'has_available_seats') && !$wc_team->has_available_seats()) {
            return ['success' => false, 'message' => 'No available seats in team'];
        }
        
        // Add member to team
        try {
            if (method_exists($wc_team, 'add_member')) {
                $member = $wc_team->add_member($user_id);
                
                if ($member) {
                    return [
                        'success' => true,
                        'team_name' => $wc_team->get_name(),
                        'member_id' => $member->get_id()
                    ];
                }
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
        
        return ['success' => false, 'message' => 'Could not add member to team'];
    }
    
    /**
     * Send acceptance notification
     */
    private function send_acceptance_notification($inviter_id, $trainer_id, $teams) {
        $inviter = get_user_by('id', $inviter_id);
        $trainer = get_user_by('id', $trainer_id);
        
        if (!$inviter || !$trainer) {
            return;
        }
        
        $subject = sprintf('[%s] Trainer invitation accepted', get_bloginfo('name'));
        
        $message = sprintf(
            "Hello %s,\n\n%s has accepted your invitation to become a trainer for the following teams:\n\n%s\n\nThey now have access to the Club Manager dashboard and can view/evaluate players in these teams.\n\nBest regards,\n%s",
            $inviter->display_name,
            $trainer->display_name,
            implode("\n", array_map(function($team) { return "- " . $team; }, $teams)),
            get_bloginfo('name')
        );
        
        wp_mail($inviter->user_email, $subject, $message);
    }
}