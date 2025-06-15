<?php

/**
 * Handle trainer-related AJAX requests.
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
        $invitation_token = wp_generate_password(32, false);
        
        foreach ($team_ids as $team_id) {
            $wpdb->insert(
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
        }
        
        // Send invitation email
        $this->send_invitation_email($email, $invitation_token, $team_ids, $message);
        
        wp_send_json_success(['message' => 'Invitation sent successfully']);
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
        
        $owner = $wpdb->get_var($wpdb->prepare(
            "SELECT t.created_by 
            FROM $invitations_table i
            INNER JOIN $teams_table t ON i.team_id = t.id
            WHERE i.id = %d",
            $invitation_id
        ));
        
        if ($owner != $user_id) {
            wp_send_json_error('Unauthorized access');
            return;
        }
        
        // Cancel invitation
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
        
        $accept_url = add_query_arg([
            'cm_trainer_invite' => $token
        ], home_url());
        
        $body .= "To accept this invitation, please click the following link:\n";
        $body .= $accept_url . "\n\n";
        $body .= "This invitation will expire in 7 days.\n\n";
        $body .= "Best regards,\n" . $site_name;
        
        wp_mail($email, $subject, $body);
    }
}