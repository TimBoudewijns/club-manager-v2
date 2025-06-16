<?php

/**
 * Handle trainer-related AJAX requests using official WC Teams invitations.
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
     * Get pending trainer invitations from WC Teams.
     */
    public function get_pending_invitations() {
        $user_id = $this->verify_request();
        
        if (!class_exists('Club_Manager_Teams_Helper') || !Club_Manager_Teams_Helper::can_view_club_teams($user_id)) {
            wp_send_json_error('Unauthorized access');
            return;
        }
        
        if (!function_exists('wc_memberships_for_teams')) {
            wp_send_json_success([]);
            return;
        }
        
        $invitations = [];
        
        // Get all WC Teams where user is owner/manager
        $managed_teams = Club_Manager_Teams_Helper::get_user_managed_teams($user_id);
        $wc_team_ids = [];
        
        foreach ($managed_teams as $team_data) {
            $wc_team_ids[] = $team_data['team_id'];
        }
        
        if (!empty($wc_team_ids)) {
            global $wpdb;
            
            // Get pending invitations for these teams
            $placeholders = implode(',', array_fill(0, count($wc_team_ids), '%d'));
            $query_args = array_merge($wc_team_ids);
            
            $invitation_posts = $wpdb->get_results($wpdb->prepare(
                "SELECT p.*, pm_email.meta_value as email, pm_token.meta_value as token
                FROM {$wpdb->posts} p
                LEFT JOIN {$wpdb->postmeta} pm_email ON p.ID = pm_email.post_id AND pm_email.meta_key = '_email'
                LEFT JOIN {$wpdb->postmeta} pm_token ON p.ID = pm_token.post_id AND pm_token.meta_key = '_token'
                WHERE p.post_type = 'wc_team_invitation'
                AND p.post_status = 'wcmti-pending'
                AND p.post_parent IN ($placeholders)
                ORDER BY p.post_date DESC",
                ...$query_args
            ));
            
            foreach ($invitation_posts as $inv_post) {
                // Check if this is a Club Manager invitation
                $cm_team_ids = get_post_meta($inv_post->ID, '_cm_team_ids', true);
                if (!$cm_team_ids) {
                    continue; // Skip non-CM invitations
                }
                
                $team_names = [];
                if ($cm_team_ids && is_array($cm_team_ids)) {
                    $teams_table = Club_Manager_Database::get_table_name('teams');
                    foreach ($cm_team_ids as $team_id) {
                        $team_name = $wpdb->get_var($wpdb->prepare(
                            "SELECT name FROM $teams_table WHERE id = %d",
                            $team_id
                        ));
                        if ($team_name) {
                            $team_names[] = $team_name;
                        }
                    }
                }
                
                $invitations[] = array(
                    'id' => $inv_post->ID,
                    'email' => $inv_post->email,
                    'team_name' => !empty($team_names) ? implode(', ', $team_names) : 'Unknown',
                    'team_id' => $inv_post->post_parent,
                    'created_at' => $inv_post->post_date,
                    'role' => get_post_meta($inv_post->ID, '_cm_role', true) ?: 'trainer'
                );
            }
        }
        
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
     * Invite a trainer using official WC Teams system.
     */
    public function invite_trainer() {
        $user_id = $this->verify_request();
        
        if (!class_exists('Club_Manager_Teams_Helper') || !Club_Manager_Teams_Helper::can_view_club_teams($user_id)) {
            wp_send_json_error('Unauthorized access');
            return;
        }
        
        if (!function_exists('wc_memberships_for_teams')) {
            wp_send_json_error('WooCommerce Teams for Memberships is required');
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
        
        // Get the first WC Team where user is owner/manager
        $managed_teams = Club_Manager_Teams_Helper::get_user_managed_teams($user_id);
        if (empty($managed_teams)) {
            wp_send_json_error('Je hebt geen WooCommerce Team waar je trainers aan kunt toevoegen.');
            return;
        }
        
        $wc_team_id = $managed_teams[0]['team_id'];
        $wc_team = wc_memberships_for_teams_get_team($wc_team_id);
        
        if (!$wc_team) {
            wp_send_json_error('Geen geldige WooCommerce Team gevonden.');
            return;
        }
        
        try {
            // Get the invitations instance
            $invitations_instance = wc_memberships_for_teams()->get_invitations_instance();
            
            if (!$invitations_instance || !method_exists($invitations_instance, 'create_invitation')) {
                wp_send_json_error('Could not access invitations system');
                return;
            }
            
            // Create invitation using the official API
            $invitation = $invitations_instance->create_invitation(array(
                'team_id' => $wc_team->get_id(),
                'email' => $email,
                'sender_id' => $user_id,
                'role' => 'member' // WC Teams only supports 'member' role
            ));
            
            if (!$invitation || is_wp_error($invitation)) {
                $error_message = is_wp_error($invitation) ? $invitation->get_error_message() : 'Could not create invitation';
                wp_send_json_error($error_message);
                return;
            }
            
            // Store Club Manager specific data
            update_post_meta($invitation->get_id(), '_cm_team_ids', $team_ids);
            update_post_meta($invitation->get_id(), '_cm_role', $role);
            update_post_meta($invitation->get_id(), '_cm_message', $message);
            
            // Get team names for email
            $team_names = [];
            global $wpdb;
            $teams_table = Club_Manager_Database::get_table_name('teams');
            foreach ($team_ids as $team_id) {
                $team_name = $wpdb->get_var($wpdb->prepare(
                    "SELECT name FROM $teams_table WHERE id = %d",
                    $team_id
                ));
                if ($team_name) {
                    $team_names[] = $team_name;
                }
            }
            
            // Send custom email
            $this->send_trainer_invitation_email(
                $email,
                $invitation->get_token(),
                $user_id,
                $team_names,
                $message
            );
            
            wp_send_json_success([
                'message' => 'Uitnodiging succesvol verzonden',
                'invitation_id' => $invitation->get_id()
            ]);
            
        } catch (Exception $e) {
            wp_send_json_error('Error creating invitation: ' . $e->getMessage());
        }
    }
    
    /**
     * Send custom trainer invitation email
     */
    private function send_trainer_invitation_email($email, $token, $inviter_id, $team_names, $message) {
        $inviter = get_user_by('id', $inviter_id);
        
        // Get accept URL
        global $wpdb;
        $page_id = $wpdb->get_var(
            "SELECT ID FROM {$wpdb->posts} 
             WHERE post_content LIKE '%[club_manager_accept_invitation]%' 
             AND post_status = 'publish' 
             AND post_type = 'page' 
             LIMIT 1"
        );
        
        if ($page_id && $token) {
            $accept_url = add_query_arg([
                'wc_invite' => $token
            ], get_permalink($page_id));
        } else {
            // Fallback
            $accept_url = home_url('/invitation/?wc_invite=' . $token);
        }
        
        // Build email
        $subject = sprintf('[%s] %s heeft je uitgenodigd als trainer', 
            get_bloginfo('name'), 
            $inviter ? $inviter->display_name : 'Een beheerder'
        );
        
        $body = "Hallo,\n\n";
        
        if ($inviter) {
            $body .= sprintf("%s heeft je uitgenodigd om trainer te worden bij %s.\n\n", 
                $inviter->display_name, 
                get_bloginfo('name')
            );
        }
        
        if (!empty($team_names)) {
            $body .= "Je krijgt toegang tot de volgende teams:\n";
            foreach ($team_names as $tn) {
                $body .= "- " . $tn . "\n";
            }
            $body .= "\n";
        }
        
        if (!empty($message)) {
            $body .= "Persoonlijk bericht:\n" . $message . "\n\n";
        }
        
        $body .= "Klik op de onderstaande link om de uitnodiging te accepteren:\n";
        $body .= $accept_url . "\n\n";
        $body .= "Deze uitnodiging verloopt over 7 dagen.\n\n";
        $body .= "Met vriendelijke groet,\n" . get_bloginfo('name');
        
        // Add headers for better deliverability
        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );
        
        // Send email
        $sent = wp_mail($email, $subject, $body, $headers);
        
        if (!$sent) {
            error_log('Club Manager: Failed to send invitation email to ' . $email);
        }
        
        return $sent;
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
        
        if (!function_exists('wc_memberships_for_teams')) {
            wp_send_json_error('WooCommerce Teams for Memberships is required');
            return;
        }
        
        try {
            // Get the invitations instance
            $invitations_instance = wc_memberships_for_teams()->get_invitations_instance();
            
            if (!$invitations_instance || !method_exists($invitations_instance, 'get_invitation')) {
                wp_send_json_error('Could not access invitations system');
                return;
            }
            
            // Get the invitation
            $invitation = $invitations_instance->get_invitation($invitation_id);
            
            if (!$invitation) {
                wp_send_json_error('Invitation not found');
                return;
            }
            
            // Verify permission
            $managed_teams = Club_Manager_Teams_Helper::get_user_managed_teams($user_id);
            $allowed_team_ids = array_column($managed_teams, 'team_id');
            
            if (!in_array($invitation->get_team_id(), $allowed_team_ids)) {
                wp_send_json_error('Unauthorized access');
                return;
            }
            
            // Cancel the invitation
            $invitation->cancel();
            
            wp_send_json_success(['message' => 'Invitation cancelled']);
            
        } catch (Exception $e) {
            wp_send_json_error('Error cancelling invitation: ' . $e->getMessage());
        }
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
        
        // Remove from WooCommerce team
        if (!empty($trainer_teams)) {
            $this->remove_from_wc_team($trainer_id, $user_id);
        }
        
        // Remove trainer from Club Manager tables
        $wpdb->query($wpdb->prepare(
            "DELETE tt FROM $trainers_table tt
            INNER JOIN $teams_table t ON tt.team_id = t.id
            WHERE tt.trainer_id = %d AND t.created_by = %d",
            $trainer_id, $user_id
        ));
        
        wp_send_json_success(['message' => 'Trainer removed successfully']);
    }
    
    /**
     * Remove user from WooCommerce team.
     */
    private function remove_from_wc_team($trainer_id, $owner_id) {
        if (!function_exists('wc_memberships_for_teams')) {
            return false;
        }
        
        $managed_teams = Club_Manager_Teams_Helper::get_user_managed_teams($owner_id);
        if (empty($managed_teams)) {
            return false;
        }
        
        foreach ($managed_teams as $team_data) {
            $wc_team = wc_memberships_for_teams_get_team($team_data['team_id']);
            
            if (!$wc_team) {
                continue;
            }
            
            $member = $wc_team->get_member($trainer_id);
            if ($member && method_exists($member, 'delete')) {
                try {
                    $member->delete();
                    return true;
                } catch (Exception $e) {
                    error_log('Club Manager: Failed to remove member from WC Team: ' . $e->getMessage());
                }
            }
        }
        
        return false;
    }
}