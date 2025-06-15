<?php

/**
 * Handle trainer-related AJAX requests with WooCommerce Teams synchronization.
 */
class Club_Manager_Trainer_Ajax extends Club_Manager_Ajax_Handler {
    
    /**
     * Initialize AJAX actions.
     */
    public function init() {
        add_action('wp_ajax_cm_get_managed_teams', array($this, 'get_managed_teams'));
        add_action('wp_ajax_cm_get_pending_invitations', array($this, 'get_pending_invitations'));
        add_action('wp_ajax_cm_get_active_trainers', array($this, 'get_active_trainers'));
        add_action('wp_ajax_cm_invite_trainer', array($this, 'invite_trainer'));
        add_action('wp_ajax_cm_cancel_invitation', array($this, 'cancel_invitation'));
        add_action('wp_ajax_cm_remove_trainer', array($this, 'remove_trainer'));
        
        // Hook into WooCommerce Teams invitation deletion
        add_action('wc_memberships_for_teams_invitation_deleted', array($this, 'sync_wc_invitation_deletion'), 10, 2);
        // Hook into WooCommerce Teams invitation status changes
        add_action('wc_memberships_for_teams_invitation_status_changed', array($this, 'sync_wc_invitation_status'), 10, 3);
    }
    
    /**
     * Get teams managed by the current user.
     */
    public function get_managed_teams() {
        $user_id = $this->verify_request();
        
        // Verify user can manage trainers (must be owner or manager)
        if (!class_exists('Club_Manager_Teams_Helper') || !Club_Manager_Teams_Helper::can_view_club_teams($user_id)) {
            wp_send_json_error('Unauthorized access');
            return;
        }
        
        $season = $this->get_post_data('season');
        
        // Get teams where user is owner or manager
        global $wpdb;
        $teams_table = Club_Manager_Database::get_table_name('teams');
        
        $teams = $wpdb->get_results($wpdb->prepare(
            "SELECT id, name, season FROM $teams_table 
            WHERE created_by = %d AND season = %s
            ORDER BY name",
            $user_id, $season
        ));
        
        wp_send_json_success($teams);
    }
    
    /**
     * Get pending trainer invitations.
     */
    public function get_pending_invitations() {
        $user_id = $this->verify_request();
        
        if (!class_exists('Club_Manager_Teams_Helper') || !Club_Manager_Teams_Helper::can_view_club_teams($user_id)) {
            wp_send_json_error('Unauthorized access');
            return;
        }
        
        global $wpdb;
        $invitations_table = Club_Manager_Database::get_table_name('trainer_invitations');
        $teams_table = Club_Manager_Database::get_table_name('teams');
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$invitations_table'") === $invitations_table;
        
        if (!$table_exists) {
            // Try to create the tables
            if (class_exists('Club_Manager_Trainers_Table')) {
                $charset_collate = $wpdb->get_charset_collate();
                Club_Manager_Trainers_Table::create_table($charset_collate);
            }
            
            // Check again
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$invitations_table'") === $invitations_table;
            
            if (!$table_exists) {
                wp_send_json_error('Trainer tables not found. Please deactivate and reactivate the plugin.');
                return;
            }
        }
        
        // Get pending invitations for teams owned by user
        $invitations = $wpdb->get_results($wpdb->prepare(
            "SELECT i.*, t.name as team_name 
            FROM $invitations_table i
            INNER JOIN $teams_table t ON i.team_id = t.id
            WHERE t.created_by = %d AND i.status = 'pending'
            ORDER BY i.created_at DESC",
            $user_id
        ));
        
        wp_send_json_success($invitations);
    }
    
    /**
     * Get active trainers.
     */
    public function get_active_trainers() {
        $user_id = $this->verify_request();
        
        if (!class_exists('Club_Manager_Teams_Helper') || !Club_Manager_Teams_Helper::can_view_club_teams($user_id)) {
            wp_send_json_error('Unauthorized access');
            return;
        }
        
        global $wpdb;
        $trainers_table = Club_Manager_Database::get_table_name('team_trainers');
        $teams_table = Club_Manager_Database::get_table_name('teams');
        
        // Get trainers for teams owned by user
        $trainers_data = $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT tt.trainer_id, tt.role, tt.is_active, u.display_name, u.user_email as email,
                    um1.meta_value as first_name, um2.meta_value as last_name
            FROM $trainers_table tt
            INNER JOIN $teams_table t ON tt.team_id = t.id
            INNER JOIN {$wpdb->users} u ON tt.trainer_id = u.ID
            LEFT JOIN {$wpdb->usermeta} um1 ON u.ID = um1.user_id AND um1.meta_key = 'first_name'
            LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = 'last_name'
            WHERE t.created_by = %d
            ORDER BY u.display_name",
            $user_id
        ));
        
        // Group teams by trainer
        $trainers = [];
        foreach ($trainers_data as $trainer) {
            $trainer_id = $trainer->trainer_id;
            
            if (!isset($trainers[$trainer_id])) {
                $trainers[$trainer_id] = [
                    'id' => $trainer_id,
                    'display_name' => $trainer->display_name,
                    'email' => $trainer->email,
                    'first_name' => $trainer->first_name,
                    'last_name' => $trainer->last_name,
                    'role' => $trainer->role,
                    'is_active' => $trainer->is_active,
                    'teams' => []
                ];
            }
            
            // Get teams for this trainer
            $trainer_teams = $wpdb->get_results($wpdb->prepare(
                "SELECT t.id, t.name 
                FROM $trainers_table tt
                INNER JOIN $teams_table t ON tt.team_id = t.id
                WHERE tt.trainer_id = %d AND t.created_by = %d",
                $trainer_id, $user_id
            ));
            
            $trainers[$trainer_id]['teams'] = $trainer_teams;
        }
        
        wp_send_json_success(array_values($trainers));
    }
    
    /**
     * Invite a trainer.
     */
    public function invite_trainer() {
        $user_id = $this->verify_request();
        
        if (!class_exists('Club_Manager_Teams_Helper') || !Club_Manager_Teams_Helper::can_view_club_teams($user_id)) {
            wp_send_json_error('Unauthorized access');
            return;
        }
        
        $email = $this->get_post_data('email', 'email');
        $team_ids = isset($_POST['teams']) ? array_map('intval', $_POST['teams']) : [];
        $role = $this->get_post_data('role');
        $message = $this->get_post_data('message', 'textarea');
        
        if (empty($email) || empty($team_ids)) {
            wp_send_json_error('Email and teams are required');
            return;
        }
        
        // Verify all teams belong to user
        global $wpdb;
        $teams_table = Club_Manager_Database::get_table_name('teams');
        
        foreach ($team_ids as $team_id) {
            $owner = $wpdb->get_var($wpdb->prepare(
                "SELECT created_by FROM $teams_table WHERE id = %d",
                $team_id
            ));
            
            if ($owner != $user_id) {
                wp_send_json_error('Unauthorized access to team');
                return;
            }
        }
        
        // Create invitation records
        $invitations_table = Club_Manager_Database::get_table_name('trainer_invitations');
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$invitations_table'") === $invitations_table;
        
        if (!$table_exists) {
            wp_send_json_error('Trainer tables not found. Please deactivate and reactivate the plugin.');
            return;
        }
        
        $invitation_token = wp_generate_password(32, false);
        $success_count = 0;
        $wc_invitations_created = 0;
        $wc_invitation_ids = [];
        
        foreach ($team_ids as $team_id) {
            // Create Club Manager invitation
            $result = $wpdb->insert(
                $invitations_table,
                [
                    'team_id' => $team_id,
                    'email' => $email,
                    'role' => $role,
                    'token' => $invitation_token,
                    'message' => $message,
                    'status' => 'pending',
                    'invited_by' => $user_id,
                    'created_at' => current_time('mysql')
                ],
                ['%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s']
            );
            
            if ($result) {
                $cm_invitation_id = $wpdb->insert_id;
                $success_count++;
                
                // Also create WooCommerce Team invitation if available
                $wc_invitation_id = $this->create_wc_team_invitation($team_id, $email, $user_id);
                if ($wc_invitation_id) {
                    $wc_invitations_created++;
                    $wc_invitation_ids[$cm_invitation_id] = $wc_invitation_id;
                    
                    // Store the WC invitation ID for synchronization
                    $wpdb->update(
                        $invitations_table,
                        ['wc_invitation_id' => $wc_invitation_id],
                        ['id' => $cm_invitation_id],
                        ['%d'],
                        ['%d']
                    );
                }
            }
        }
        
        if ($success_count === 0) {
            wp_send_json_error('Failed to create invitations. Database error: ' . $wpdb->last_error);
            return;
        }
        
        // Send invitation email
        $this->send_invitation_email($email, $invitation_token, $team_ids, $message);
        
        wp_send_json_success([
            'message' => 'Invitation sent successfully',
            'invitations_created' => $success_count,
            'wc_invitations_created' => $wc_invitations_created
        ]);
    }
    
    /**
     * Cancel a pending invitation.
     */
    public function cancel_invitation() {
        $user_id = $this->verify_request();
        
        if (!class_exists('Club_Manager_Teams_Helper') || !Club_Manager_Teams_Helper::can_view_club_teams($user_id)) {
            wp_send_json_error('Unauthorized access');
            return;
        }
        
        $invitation_id = $this->get_post_data('invitation_id', 'int');
        
        // Verify invitation belongs to user's team
        global $wpdb;
        $invitations_table = Club_Manager_Database::get_table_name('trainer_invitations');
        $teams_table = Club_Manager_Database::get_table_name('teams');
        
        $invitation = $wpdb->get_row($wpdb->prepare(
            "SELECT i.*, t.created_by 
            FROM $invitations_table i
            INNER JOIN $teams_table t ON i.team_id = t.id
            WHERE i.id = %d",
            $invitation_id
        ));
        
        if (!$invitation || $invitation->created_by != $user_id) {
            wp_send_json_error('Unauthorized access');
            return;
        }
        
        // Cancel WooCommerce invitation if exists
        if (!empty($invitation->wc_invitation_id)) {
            $this->cancel_wc_team_invitation($invitation->wc_invitation_id);
        }
        
        // Cancel Club Manager invitation
        $wpdb->update(
            $invitations_table,
            ['status' => 'cancelled'],
            ['id' => $invitation_id],
            ['%s'],
            ['%d']
        );
        
        wp_send_json_success(['message' => 'Invitation cancelled']);
    }
    
    /**
     * Remove a trainer.
     */
    public function remove_trainer() {
        $user_id = $this->verify_request();
        
        if (!class_exists('Club_Manager_Teams_Helper') || !Club_Manager_Teams_Helper::can_view_club_teams($user_id)) {
            wp_send_json_error('Unauthorized access');
            return;
        }
        
        $trainer_id = $this->get_post_data('trainer_id', 'int');
        
        global $wpdb;
        $trainers_table = Club_Manager_Database::get_table_name('team_trainers');
        $teams_table = Club_Manager_Database::get_table_name('teams');
        
        // Get all teams this trainer is part of that belong to current user
        $trainer_teams = $wpdb->get_results($wpdb->prepare(
            "SELECT tt.*, t.created_by
            FROM $trainers_table tt
            INNER JOIN $teams_table t ON tt.team_id = t.id
            WHERE tt.trainer_id = %d AND t.created_by = %d",
            $trainer_id, $user_id
        ));
        
        // Remove from WooCommerce teams
        foreach ($trainer_teams as $trainer_team) {
            $this->remove_from_wc_team($trainer_team->team_id, $trainer_id);
        }
        
        // Remove trainer from all teams owned by user
        $wpdb->query($wpdb->prepare(
            "DELETE tt FROM $trainers_table tt
            INNER JOIN $teams_table t ON tt.team_id = t.id
            WHERE tt.trainer_id = %d AND t.created_by = %d",
            $trainer_id, $user_id
        ));
        
        wp_send_json_success(['message' => 'Trainer removed successfully']);
    }
    
    /**
     * Send invitation email.
     */
    private function send_invitation_email($email, $token, $team_ids, $message) {
        $user = wp_get_current_user();
        $site_name = get_bloginfo('name');
        
        $subject = sprintf('[%s] Invitation to join as a trainer', $site_name);
        
        $body = sprintf(
            "Hello,\n\n%s has invited you to join as a trainer at %s.\n\n",
            $user->display_name,
            $site_name
        );
        
        if (!empty($message)) {
            $body .= "Personal message:\n" . $message . "\n\n";
        }
        
        // Try to find the page with the accept invitation shortcode
        global $wpdb;
        $page_id = $wpdb->get_var(
            "SELECT ID FROM {$wpdb->posts} 
             WHERE post_content LIKE '%[club_manager_accept_invitation]%' 
             AND post_status = 'publish' 
             AND post_type = 'page' 
             LIMIT 1"
        );
        
        if ($page_id) {
            // Use the page with the shortcode
            $accept_url = add_query_arg([
                'cm_trainer_invite' => $token
            ], get_permalink($page_id));
        } else {
            // Fallback to home URL with parameter
            $accept_url = add_query_arg([
                'cm_trainer_invite' => $token
            ], home_url());
        }
        
        $body .= "To accept this invitation, please click the following link:\n";
        $body .= $accept_url . "\n\n";
        $body .= "This invitation will expire in 7 days.\n\n";
        $body .= "Best regards,\n" . $site_name;
        
        wp_mail($email, $subject, $body);
    }
    
    /**
     * Create WooCommerce Team invitation
     */
    private function create_wc_team_invitation($cm_team_id, $email, $inviter_id) {
        if (!function_exists('wc_memberships_for_teams')) {
            return false;
        }
        
        // Find the WooCommerce team associated with this Club Manager team
        global $wpdb;
        $teams_table = Club_Manager_Database::get_table_name('teams');
        
        // Get the team owner
        $team_owner_id = $wpdb->get_var($wpdb->prepare(
            "SELECT created_by FROM $teams_table WHERE id = %d",
            $cm_team_id
        ));
        
        if (!$team_owner_id) {
            return false;
        }
        
        // Find WC team for this owner
        $wc_team = null;
        
        // Try to find teams where owner is a member
        if (function_exists('wc_memberships_for_teams_get_user_teams')) {
            $owner_teams = wc_memberships_for_teams_get_user_teams($team_owner_id);
            
            if (!empty($owner_teams)) {
                foreach ($owner_teams as $team) {
                    if (is_object($team) && method_exists($team, 'get_member')) {
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
        
        if (!$wc_team) {
            return false;
        }
        
        // Create WooCommerce Team invitation
        if (method_exists($wc_team, 'invite_member')) {
            try {
                $invitation = $wc_team->invite_member($email, array(
                    'sender_id' => $inviter_id,
                    'role' => 'member' // WC Teams doesn't have trainer role, so use member
                ));
                
                return $invitation ? $invitation->get_id() : false;
            } catch (Exception $e) {
                error_log('Club Manager: Failed to create WC Team invitation: ' . $e->getMessage());
                return false;
            }
        }
        
        return false;
    }
    
    /**
     * Cancel WooCommerce Team invitation
     */
    private function cancel_wc_team_invitation($wc_invitation_id) {
        if (!function_exists('wc_memberships_for_teams_get_invitation')) {
            return false;
        }
        
        $invitation = wc_memberships_for_teams_get_invitation($wc_invitation_id);
        
        if ($invitation && is_object($invitation) && method_exists($invitation, 'delete')) {
            try {
                $invitation->delete();
                return true;
            } catch (Exception $e) {
                error_log('Club Manager: Failed to cancel WC Team invitation: ' . $e->getMessage());
            }
        }
        
        return false;
    }
    
    /**
     * Remove user from WooCommerce team
     */
    private function remove_from_wc_team($cm_team_id, $user_id) {
        if (!function_exists('wc_memberships_for_teams')) {
            return false;
        }
        
        // Find the WooCommerce team
        global $wpdb;
        $teams_table = Club_Manager_Database::get_table_name('teams');
        
        $team_owner_id = $wpdb->get_var($wpdb->prepare(
            "SELECT created_by FROM $teams_table WHERE id = %d",
            $cm_team_id
        ));
        
        if (!$team_owner_id) {
            return false;
        }
        
        // Find WC team
        if (function_exists('wc_memberships_for_teams_get_user_teams')) {
            $owner_teams = wc_memberships_for_teams_get_user_teams($team_owner_id);
            
            foreach ($owner_teams as $team) {
                if (is_object($team) && method_exists($team, 'get_member')) {
                    $member = $team->get_member($user_id);
                    if ($member && method_exists($member, 'delete')) {
                        try {
                            $member->delete();
                            return true;
                        } catch (Exception $e) {
                            error_log('Club Manager: Failed to remove member from WC Team: ' . $e->getMessage());
                        }
                    }
                }
            }
        }
        
        return false;
    }
    
    /**
     * Sync WooCommerce invitation deletion with Club Manager
     */
    public function sync_wc_invitation_deletion($invitation_id, $invitation) {
        global $wpdb;
        $invitations_table = Club_Manager_Database::get_table_name('trainer_invitations');
        
        // Find and cancel the corresponding Club Manager invitation
        $wpdb->update(
            $invitations_table,
            ['status' => 'cancelled'],
            ['wc_invitation_id' => $invitation_id],
            ['%s'],
            ['%d']
        );
    }
    
    /**
     * Sync WooCommerce invitation status changes with Club Manager
     */
    public function sync_wc_invitation_status($invitation, $new_status, $old_status) {
        global $wpdb;
        $invitations_table = Club_Manager_Database::get_table_name('trainer_invitations');
        
        $invitation_id = $invitation->get_id();
        
        // Map WC Teams status to Club Manager status
        $status_map = [
            'pending' => 'pending',
            'accepted' => 'accepted',
            'cancelled' => 'cancelled',
            'expired' => 'expired'
        ];
        
        $cm_status = isset($status_map[$new_status]) ? $status_map[$new_status] : 'cancelled';
        
        // Update Club Manager invitation status
        $update_data = ['status' => $cm_status];
        
        if ($new_status === 'accepted') {
            $update_data['accepted_at'] = current_time('mysql');
        }
        
        $wpdb->update(
            $invitations_table,
            $update_data,
            ['wc_invitation_id' => $invitation_id],
            array_merge(['%s'], ($new_status === 'accepted' ? ['%s'] : [])),
            ['%d']
        );
    }
}