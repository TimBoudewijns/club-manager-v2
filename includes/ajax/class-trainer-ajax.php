<?php

/**
 * Handle trainer-related AJAX requests using official WC Teams invitations.
 */
class Club_Manager_Trainer_Ajax extends Club_Manager_Ajax_Handler {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Hook into WC Teams invitation emails with the correct hook names
        add_filter('wc_memberships_for_teams_team_member_invitation_email_subject', array($this, 'customize_invitation_subject'), 10, 2);
        add_filter('wc_memberships_for_teams_team_member_invitation_email_body', array($this, 'customize_invitation_body'), 10, 2);
    }
    
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
        
        // Get WC team for this user
        $wc_team = $this->get_wc_team_for_cm_team(null, $user_id);
        
        if ($wc_team) {
            // Get pending invitations using the official API
            $invitations_instance = wc_memberships_for_teams()->get_invitations_instance();
            
            if ($invitations_instance && method_exists($invitations_instance, 'get_invitations')) {
                $team_invitations = $invitations_instance->get_invitations($wc_team->get_id(), array(
                    'status' => 'pending'
                ));
                
                foreach ($team_invitations as $invitation) {
                    $email = $invitation->get_email();
                    $cm_team_ids = get_post_meta($invitation->get_id(), '_cm_team_ids', true);
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
                        'id' => $invitation->get_id(),
                        'email' => $email,
                        'team_name' => !empty($team_names) ? implode(', ', $team_names) : $wc_team->get_name(),
                        'team_id' => $wc_team->get_id(),
                        'created_at' => $invitation->get_date('Y-m-d H:i:s'),
                        'role' => get_post_meta($invitation->get_id(), '_cm_role', true) ?: 'trainer'
                    );
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
        
        // Get the WC Team (the club)
        $wc_team = $this->get_wc_team_for_cm_team(null, $user_id);
        
        if (!$wc_team) {
            $debug_info = $this->debug_wc_team_access($user_id);
            wp_send_json_error('Geen WooCommerce Teams gevonden. ' . $debug_info);
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
            
            // Send invitation email
            $invitation->send();
            
            wp_send_json_success([
                'message' => 'Uitnodiging succesvol verzonden',
                'invitation_id' => $invitation->get_id()
            ]);
            
        } catch (Exception $e) {
            wp_send_json_error('Error creating invitation: ' . $e->getMessage());
        }
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
            $wc_team = $this->get_wc_team_for_cm_team(null, $user_id);
            if (!$wc_team || $invitation->get_team_id() != $wc_team->get_id()) {
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
        
        // Remove from WooCommerce team (once - same team for all)
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
     * Get WC team for a Club Manager team.
     * Note: All CM teams belong to the same WC Team (the club)
     */
    private function get_wc_team_for_cm_team($cm_team_id, $user_id) {
        if (!function_exists('wc_memberships_for_teams_get_user_teams')) {
            return false;
        }
        
        // Get all teams for the user
        $teams = wc_memberships_for_teams_get_user_teams($user_id);
        
        if (!empty($teams)) {
            foreach ($teams as $team) {
                if (!is_object($team)) continue;
                
                // Check if user can manage this team
                $can_manage = false;
                
                // Check if user is post author
                $team_post = get_post($team->get_id());
                if ($team_post && $team_post->post_author == $user_id) {
                    $can_manage = true;
                }
                
                // Check member role
                if (!$can_manage && method_exists($team, 'get_member')) {
                    $member = $team->get_member($user_id);
                    if ($member && method_exists($member, 'get_role')) {
                        $role = $member->get_role();
                        if (in_array($role, ['owner', 'manager'])) {
                            $can_manage = true;
                        }
                    }
                }
                
                if ($can_manage) {
                    return $team;
                }
            }
        }
        
        // Fallback: try direct database query
        global $wpdb;
        
        $team_id = $wpdb->get_var($wpdb->prepare(
            "SELECT ID 
            FROM {$wpdb->posts}
            WHERE post_type = 'wc_memberships_team'
            AND post_status = 'publish'
            AND post_author = %d
            ORDER BY ID DESC
            LIMIT 1",
            $user_id
        ));
        
        if ($team_id && function_exists('wc_memberships_for_teams_get_team')) {
            $team = wc_memberships_for_teams_get_team($team_id);
            if ($team && is_object($team)) {
                return $team;
            }
        }
        
        return false;
    }
    
    /**
     * Remove user from WooCommerce team.
     */
    private function remove_from_wc_team($trainer_id, $owner_id) {
        $wc_team = $this->get_wc_team_for_cm_team(null, $owner_id);
        
        if (!$wc_team) {
            return false;
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
        
        return false;
    }
    
    /**
     * Customize invitation email subject.
     */
    public function customize_invitation_subject($subject, $invitation) {
        // Get invitation ID from the invitation object
        $invitation_id = is_object($invitation) ? $invitation->get_id() : $invitation;
        
        // Get inviter info
        $inviter_id = get_post_meta($invitation_id, '_sender_id', true);
        if (!$inviter_id && is_object($invitation) && method_exists($invitation, 'get_sender_id')) {
            $inviter_id = $invitation->get_sender_id();
        }
        
        $inviter = get_user_by('id', $inviter_id);
        
        if ($inviter) {
            $subject = sprintf('[%s] %s heeft je uitgenodigd als trainer', get_bloginfo('name'), $inviter->display_name);
        }
        
        return $subject;
    }
    
    /**
     * Customize invitation email body.
     */
    public function customize_invitation_body($body, $invitation) {
        // Get invitation ID from the invitation object
        $invitation_id = is_object($invitation) ? $invitation->get_id() : $invitation;
        
        // Get invitation data
        if (is_object($invitation)) {
            $email = method_exists($invitation, 'get_email') ? $invitation->get_email() : '';
            $token = method_exists($invitation, 'get_token') ? $invitation->get_token() : '';
            $team_id = method_exists($invitation, 'get_team_id') ? $invitation->get_team_id() : '';
            $inviter_id = method_exists($invitation, 'get_sender_id') ? $invitation->get_sender_id() : '';
        } else {
            $email = get_post_meta($invitation_id, '_email', true);
            $token = get_post_meta($invitation_id, '_token', true);
            $team_id = get_post_meta($invitation_id, '_team_id', true);
            $inviter_id = get_post_meta($invitation_id, '_sender_id', true);
        }
        
        $message = get_post_meta($invitation_id, '_cm_message', true);
        $inviter = get_user_by('id', $inviter_id);
        $cm_team_ids = get_post_meta($invitation_id, '_cm_team_ids', true);
        
        // Get WC team
        if (function_exists('wc_memberships_for_teams_get_team')) {
            $team = wc_memberships_for_teams_get_team($team_id);
            $team_name = $team ? $team->get_name() : 'de club';
        } else {
            $team_name = 'de club';
        }
        
        // Get CM team names
        $team_names = [];
        if ($cm_team_ids && is_array($cm_team_ids)) {
            global $wpdb;
            $teams_table = Club_Manager_Database::get_table_name('teams');
            foreach ($cm_team_ids as $cm_team_id) {
                $cm_team_name = $wpdb->get_var($wpdb->prepare(
                    "SELECT name FROM $teams_table WHERE id = %d",
                    $cm_team_id
                ));
                if ($cm_team_name) {
                    $team_names[] = $cm_team_name;
                }
            }
        }
        
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
        
        // Build email body
        $body = "Hallo,\n\n";
        
        if ($inviter) {
            $body .= sprintf("%s heeft je uitgenodigd om trainer te worden bij %s.\n\n", 
                $inviter->display_name, 
                $team_name
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
        
        return $body;
    }
    
    /**
     * Debug helper to understand why WC team is not found.
     */
    private function debug_wc_team_access($user_id) {
        global $wpdb;
        
        // Check if WC Teams plugin is active
        if (!function_exists('wc_memberships_for_teams')) {
            return "WooCommerce Teams for Memberships plugin is niet actief.";
        }
        
        // Check if any WC teams exist
        $team_count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} 
             WHERE post_type = 'wc_memberships_team' 
             AND post_status = 'publish'"
        );
        
        if ($team_count == 0) {
            return "Er zijn geen WooCommerce Teams aangemaakt. Maak eerst een Team aan in WooCommerce > Teams.";
        }
        
        // Check if user is author of any team
        $authored_teams = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} 
             WHERE post_type = 'wc_memberships_team' 
             AND post_status = 'publish'
             AND post_author = %d",
            $user_id
        ));
        
        if ($authored_teams > 0) {
            return "Debug: User is author of $authored_teams teams but function failed to retrieve them.";
        }
        
        // Check if user is member of any team
        $member_records = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} 
             WHERE meta_key = '_member_id' 
             AND meta_value = %d",
            $user_id
        ));
        
        if ($member_records > 0) {
            return "Debug: User has $member_records membership records but is not owner/manager.";
        }
        
        return "Je bent geen lid van een WooCommerce Team. Vraag de clubeigenaar om je toe te voegen aan het Team.";
    }
}