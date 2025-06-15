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
        
        // Hook into WC Teams invitation emails to customize them
        add_filter('wc_memberships_for_teams_team_member_invitation_email_subject', array($this, 'customize_invitation_subject'), 10, 3);
        add_filter('wc_memberships_for_teams_team_member_invitation_email_body', array($this, 'customize_invitation_body'), 10, 3);
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
        
        // Get WC teams for this user
        if (function_exists('wc_memberships_for_teams_get_user_teams')) {
            $teams = wc_memberships_for_teams_get_user_teams($user_id);
            
            foreach ($teams as $team) {
                if (!is_object($team)) continue;
                
                // Check if user is owner/manager
                $member = $team->get_member($user_id);
                if (!$member || !in_array($member->get_role(), ['owner', 'manager'])) {
                    continue;
                }
                
                // Get pending invitations for this team
                if (method_exists($team, 'get_invitations')) {
                    $team_invitations = $team->get_invitations(array(
                        'status' => 'pending'
                    ));
                    
                    foreach ($team_invitations as $invitation) {
                        if (is_object($invitation)) {
                            $invitations[] = array(
                                'id' => $invitation->get_id(),
                                'email' => $invitation->get_email(),
                                'team_name' => $team->get_name(),
                                'team_id' => $team->get_id(),
                                'created_at' => $invitation->get_date_created() ? $invitation->get_date_created()->format('Y-m-d H:i:s') : '',
                                'role' => 'trainer' // We track this separately in Club Manager
                            );
                        }
                    }
                }
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
        
        // Store the custom message and role in user meta temporarily
        $invitation_data = array(
            'role' => $role,
            'message' => $message,
            'inviter_id' => $user_id
        );
        set_transient('cm_invitation_' . md5($email), $invitation_data, DAY_IN_SECONDS);
        
        // Process each team
        $success_count = 0;
        $errors = [];
        
        foreach ($team_ids as $cm_team_id) {
            // Find the corresponding WC team
            $wc_team = $this->get_wc_team_for_cm_team($cm_team_id, $user_id);
            
            if (!$wc_team) {
                // Get team name for better error message
                global $wpdb;
                $teams_table = Club_Manager_Database::get_table_name('teams');
                $team_name = $wpdb->get_var($wpdb->prepare(
                    "SELECT name FROM $teams_table WHERE id = %d",
                    $cm_team_id
                ));
                
                $errors[] = "Kan geen WooCommerce team vinden voor: " . ($team_name ?: "Team ID $cm_team_id") . 
                           ". Zorg ervoor dat je een Teams for Memberships team hebt aangemaakt.";
                continue;
            }
            
            // Create official WC Teams invitation
            if (method_exists($wc_team, 'invite_member')) {
                try {
                    $invitation = $wc_team->invite_member($email, array(
                        'sender_id' => $user_id,
                        'role' => 'member' // WC Teams doesn't have trainer role
                    ));
                    
                    if ($invitation) {
                        $success_count++;
                        
                        // Store Club Manager specific data
                        $this->store_cm_invitation_data($invitation->get_id(), $cm_team_id, $role, $message);
                    }
                } catch (Exception $e) {
                    $errors[] = $e->getMessage();
                }
            }
        }
        
        if ($success_count === 0) {
            wp_send_json_error('Failed to create invitations. ' . implode(' ', $errors));
            return;
        }
        
        wp_send_json_success([
            'message' => 'Invitation sent successfully',
            'invitations_created' => $success_count,
            'errors' => $errors
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
        
        if (!function_exists('wc_memberships_for_teams_get_invitation')) {
            wp_send_json_error('WooCommerce Teams for Memberships is required');
            return;
        }
        
        $invitation = wc_memberships_for_teams_get_invitation($invitation_id);
        
        if (!$invitation) {
            wp_send_json_error('Invitation not found');
            return;
        }
        
        // Verify user has permission
        $team = $invitation->get_team();
        if (!$team) {
            wp_send_json_error('Team not found');
            return;
        }
        
        $member = $team->get_member($user_id);
        if (!$member || !in_array($member->get_role(), ['owner', 'manager'])) {
            wp_send_json_error('Unauthorized access');
            return;
        }
        
        // Delete the invitation
        try {
            $invitation->delete();
            wp_send_json_success(['message' => 'Invitation cancelled']);
        } catch (Exception $e) {
            wp_send_json_error('Failed to cancel invitation: ' . $e->getMessage());
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
        
        // Remove from WooCommerce teams
        foreach ($trainer_teams as $trainer_team) {
            $this->remove_from_wc_team($trainer_team->team_id, $trainer_id);
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
     * Get WC team for a Club Manager team.
     */
    private function get_wc_team_for_cm_team($cm_team_id, $user_id) {
        global $wpdb;
        
        // First check if we have a mapping
        $mapping_table = Club_Manager_Database::get_table_name('team_wc_mapping');
        $wc_team_id = $wpdb->get_var($wpdb->prepare(
            "SELECT wc_team_id FROM $mapping_table WHERE cm_team_id = %d",
            $cm_team_id
        ));
        
        if ($wc_team_id && function_exists('wc_memberships_for_teams_get_team')) {
            $team = wc_memberships_for_teams_get_team($wc_team_id);
            if ($team && $team->post && $team->post->post_status === 'publish') {
                return $team;
            }
        }
        
        // If no mapping exists, try to find and create one
        $teams_table = Club_Manager_Database::get_table_name('teams');
        
        // Get the team owner
        $team_owner_id = $wpdb->get_var($wpdb->prepare(
            "SELECT created_by FROM $teams_table WHERE id = %d",
            $cm_team_id
        ));
        
        if (!$team_owner_id) {
            error_log('Club Manager: No owner found for team ID: ' . $cm_team_id);
            return false;
        }
        
        // Find WC team for this owner
        $wc_team = null;
        
        if (function_exists('wc_memberships_for_teams_get_user_teams')) {
            // Method 1: Get all teams where owner is a member with owner/manager role
            $teams = wc_memberships_for_teams_get_user_teams($team_owner_id);
            
            if (!empty($teams)) {
                foreach ($teams as $team) {
                    if (is_object($team)) {
                        $member = $team->get_member($team_owner_id);
                        if ($member && in_array($member->get_role(), ['owner', 'manager'])) {
                            $wc_team = $team;
                            break;
                        }
                    }
                }
            }
            
            // Method 2: Find teams by post author
            if (!$wc_team) {
                $wc_team_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT ID 
                    FROM {$wpdb->posts}
                    WHERE post_type = 'wc_memberships_team'
                    AND post_status = 'publish'
                    AND post_author = %d
                    ORDER BY ID DESC
                    LIMIT 1",
                    $team_owner_id
                ));
                
                if ($wc_team_id) {
                    $wc_team = wc_memberships_for_teams_get_team($wc_team_id);
                }
            }
        }
        
        // If we found a team, save the mapping
        if ($wc_team) {
            $wpdb->insert(
                $mapping_table,
                [
                    'cm_team_id' => $cm_team_id,
                    'wc_team_id' => $wc_team->get_id()
                ],
                ['%d', '%d']
            );
            
            return $wc_team;
        }
        
        error_log('Club Manager: No WC team found for CM team ID: ' . $cm_team_id . ' (owner: ' . $team_owner_id . ')');
        return false;
    }
    
    /**
     * Store Club Manager specific invitation data.
     */
    private function store_cm_invitation_data($wc_invitation_id, $cm_team_id, $role, $message) {
        global $wpdb;
        
        // Store in post meta of the WC invitation
        update_post_meta($wc_invitation_id, '_cm_team_id', $cm_team_id);
        update_post_meta($wc_invitation_id, '_cm_role', $role);
        update_post_meta($wc_invitation_id, '_cm_message', $message);
    }
    
    /**
     * Remove user from WooCommerce team.
     */
    private function remove_from_wc_team($cm_team_id, $user_id) {
        $wc_team = $this->get_wc_team_for_cm_team($cm_team_id, get_current_user_id());
        
        if (!$wc_team) {
            return false;
        }
        
        $member = $wc_team->get_member($user_id);
        if ($member && method_exists($member, 'delete')) {
            try {
                $member->delete();
                return true;
            } catch (Exception $e) {
                error_log('Club Manager: Failed to remove member from WC Team: ' . $e->getMessage());
            }
        }
        
        return false;
    }
    
    /**
     * Customize invitation email subject.
     */
    public function customize_invitation_subject($subject, $invitation, $team) {
        // Get our custom data
        $inviter_id = get_post_meta($invitation->get_id(), '_sender_id', true);
        $inviter = get_user_by('id', $inviter_id);
        
        if ($inviter) {
            $subject = sprintf('[%s] %s heeft je uitgenodigd als trainer', get_bloginfo('name'), $inviter->display_name);
        }
        
        return $subject;
    }
    
    /**
     * Customize invitation email body.
     */
    public function customize_invitation_body($body, $invitation, $team) {
        // Get our custom data
        $message = get_post_meta($invitation->get_id(), '_cm_message', true);
        $role = get_post_meta($invitation->get_id(), '_cm_role', true);
        $inviter_id = get_post_meta($invitation->get_id(), '_sender_id', true);
        $inviter = get_user_by('id', $inviter_id);
        
        // Get our custom accept URL
        global $wpdb;
        $page_id = $wpdb->get_var(
            "SELECT ID FROM {$wpdb->posts} 
             WHERE post_content LIKE '%[club_manager_accept_invitation]%' 
             AND post_status = 'publish' 
             AND post_type = 'page' 
             LIMIT 1"
        );
        
        if ($page_id) {
            $accept_url = add_query_arg([
                'wc_invite' => $invitation->get_token()
            ], get_permalink($page_id));
        } else {
            // Fallback to original URL
            $accept_url = $invitation->get_accept_url();
        }
        
        // Build custom email body
        $body = "Hallo,\n\n";
        
        if ($inviter) {
            $body .= sprintf("%s heeft je uitgenodigd om trainer te worden bij %s.\n\n", 
                $inviter->display_name, 
                $team->get_name()
            );
        }
        
        if (!empty($message)) {
            $body .= "Persoonlijk bericht:\n" . $message . "\n\n";
        }
        
        $body .= "Klik op de onderstaande link om de uitnodiging te accepteren:\n";
        $body .= $accept_url . "\n\n";
        $body .= "Deze uitnodiging verloopt over 7 dagen.\n\n";
        $body .= "Met vriendelijke groet,\n" . get_bloginfo('name');
        
        return $body;
    }
}