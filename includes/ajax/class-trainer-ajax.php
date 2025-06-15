<?php

/**
 * Handle trainer-related AJAX requests using official WC Teams invitations.
 */
class Club_Manager_Trainer_Ajax extends Club_Manager_Ajax_Handler {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Hook into WC Teams invitation emails early to ensure they're registered before the emails are sent
        add_filter('wc_memberships_for_teams_team_member_invitation_email_subject', array($this, 'customize_invitation_subject'), 10, 3);
        add_filter('wc_memberships_for_teams_team_member_invitation_email_body', array($this, 'customize_invitation_body'), 10, 3);
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
        add_action('wp_ajax_cm_test_wc_team_access', array($this, 'test_wc_team_access'));
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
            global $wpdb;
            
            // Query invitations directly
            $invitation_posts = $wpdb->get_results($wpdb->prepare(
                "SELECT p.ID, p.post_parent, p.post_date
                FROM {$wpdb->posts} p
                WHERE p.post_type = 'wc_team_invitation'
                AND p.post_status = 'wcm-pending'
                AND p.post_parent = %d
                ORDER BY p.post_date DESC",
                $wc_team->get_id()
            ));
            
            foreach ($invitation_posts as $invitation_post) {
                $email = get_post_meta($invitation_post->ID, '_email', true);
                $cm_team_ids = get_post_meta($invitation_post->ID, '_cm_team_ids', true);
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
                    'id' => $invitation_post->ID,
                    'email' => $email,
                    'team_name' => !empty($team_names) ? implode(', ', $team_names) : $wc_team->get_name(),
                    'team_id' => $wc_team->get_id(),
                    'created_at' => $invitation_post->post_date,
                    'role' => get_post_meta($invitation_post->ID, '_cm_role', true) ?: 'trainer'
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
        
        // Get the WC Team (the club)
        $wc_team = $this->get_wc_team_for_cm_team(null, $user_id);
        
        if (!$wc_team) {
            $debug_info = $this->debug_wc_team_access($user_id);
            wp_send_json_error('Geen WooCommerce Teams gevonden. ' . $debug_info);
            return;
        }
        
        $success_count = 0;
        $errors = [];
        
        try {
            // Gebruik de correcte WC Teams API
            $team_id = $wc_team->get_id();
            
            // Maak invitation data array
            $invitation_data = array(
                'team_id' => $team_id,
                'email' => $email,
                'sender_id' => $user_id,
                'role' => 'member', // WC Teams kent alleen 'member' role
                'message' => '' // WC Teams standaard message
            );
            
            // Maak de invitation via de Team class
            $invitations_handler = $wc_team->get_invitations_instance();
            
            if ($invitations_handler) {
                // Create invitation
                $invitation = $invitations_handler->create_invitation($invitation_data);
                
                if ($invitation && !is_wp_error($invitation)) {
                    $success_count++;
                    
                    // Store Club Manager specific data
                    $this->store_cm_invitation_data($invitation->get_id(), $team_ids, $role, $message);
                    
                    // Store sender_id for email customization
                    update_post_meta($invitation->get_id(), '_sender_id', $user_id);
                    
                    // Send invitation email
                    $invitations_handler->send_invitation_email($invitation);
                } else {
                    $error_message = is_wp_error($invitation) ? $invitation->get_error_message() : 'Kon geen uitnodiging aanmaken';
                    $errors[] = $error_message;
                }
            } else {
                // Fallback: Direct via post creation
                $invitation_id = wp_insert_post(array(
                    'post_type' => 'wc_team_invitation',
                    'post_status' => 'wcm-pending',
                    'post_parent' => $team_id,
                    'post_author' => $user_id,
                    'post_title' => sprintf(__('Invitation for %s', 'woocommerce-memberships-for-teams'), $email)
                ));
                
                if ($invitation_id && !is_wp_error($invitation_id)) {
                    // Set invitation meta
                    update_post_meta($invitation_id, '_email', $email);
                    update_post_meta($invitation_id, '_role', 'member');
                    update_post_meta($invitation_id, '_sender_id', $user_id);
                    update_post_meta($invitation_id, '_token', wp_generate_password(32, false));
                    
                    // Store Club Manager specific data
                    $this->store_cm_invitation_data($invitation_id, $team_ids, $role, $message);
                    
                    // Trigger invitation email
                    do_action('wc_memberships_for_teams_team_invitation_created', $invitation_id);
                    
                    $success_count++;
                } else {
                    $errors[] = 'Kon geen uitnodiging aanmaken';
                }
            }
            
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
        
        if ($success_count === 0) {
            $error_message = !empty($errors) ? implode(' ', $errors) : 'Uitnodiging mislukt';
            wp_send_json_error($error_message);
            return;
        }
        
        wp_send_json_success([
            'message' => 'Uitnodiging succesvol verzonden',
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
        
        // Verify invitation exists and user has permission
        $invitation_post = get_post($invitation_id);
        
        if (!$invitation_post || $invitation_post->post_type !== 'wc_team_invitation') {
            wp_send_json_error('Invitation not found');
            return;
        }
        
        // Get the team and verify permission
        $wc_team = $this->get_wc_team_for_cm_team(null, $user_id);
        if (!$wc_team || $invitation_post->post_parent != $wc_team->get_id()) {
            wp_send_json_error('Unauthorized access');
            return;
        }
        
        // Delete the invitation
        $result = wp_delete_post($invitation_id, true);
        
        if ($result) {
            wp_send_json_success(['message' => 'Invitation cancelled']);
        } else {
            wp_send_json_error('Failed to cancel invitation');
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
        global $wpdb;
        
        // Debug logging
        error_log('Club Manager: Looking for WC team for user ID: ' . $user_id);
        
        // Method 1: Try the official function first
        if (function_exists('wc_memberships_for_teams_get_user_teams')) {
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
                        error_log('Club Manager: Found manageable WC team ID: ' . $team->get_id());
                        return $team;
                    }
                }
            }
        }
        
        // Method 2: Direct database query for teams where user is author
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
                error_log('Club Manager: Found WC team by post author: ' . $team_id);
                return $team;
            }
        }
        
        // Method 3: Check for any team where user is owner/manager
        $team_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT p.ID 
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id 
            INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id
            WHERE p.post_type = 'wc_memberships_team'
            AND p.post_status = 'publish'
            AND pm1.meta_key = '_member_id' 
            AND pm1.meta_value = %d
            AND pm2.meta_key = '_role'
            AND pm2.meta_value IN ('owner', 'manager')",
            $user_id
        ));
        
        if (!empty($team_ids) && function_exists('wc_memberships_for_teams_get_team')) {
            foreach ($team_ids as $team_id) {
                $team = wc_memberships_for_teams_get_team($team_id);
                if ($team && is_object($team)) {
                    error_log('Club Manager: Found WC team where user is owner/manager: ' . $team_id);
                    return $team;
                }
            }
        }
        
        error_log('Club Manager: No WC team found for user ID: ' . $user_id);
        return false;
    }
    
    /**
     * Store Club Manager specific invitation data.
     */
    private function store_cm_invitation_data($wc_invitation_id, $cm_team_ids, $role, $message) {
        global $wpdb;
        
        // Store in post meta of the WC invitation
        update_post_meta($wc_invitation_id, '_cm_team_ids', $cm_team_ids); // Array of team IDs
        update_post_meta($wc_invitation_id, '_cm_role', $role);
        update_post_meta($wc_invitation_id, '_cm_message', $message);
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
        $cm_team_ids = get_post_meta($invitation->get_id(), '_cm_team_ids', true);
        
        // Get team names
        $team_names = [];
        if ($cm_team_ids && is_array($cm_team_ids)) {
            global $wpdb;
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
        
        if (!empty($team_names)) {
            $body .= "Je krijgt toegang tot de volgende teams:\n";
            foreach ($team_names as $team_name) {
                $body .= "- " . $team_name . "\n";
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
     * Test function to debug WC team access.
     */
    public function test_wc_team_access() {
        $user_id = $this->verify_request();
        
        global $wpdb;
        $debug_info = array();
        
        // Test 1: Check if plugin is active
        $debug_info['plugin_active'] = function_exists('wc_memberships_for_teams');
        
        // Test 2: Check teams by post author
        $authored_teams = $wpdb->get_results($wpdb->prepare(
            "SELECT ID, post_title, post_status 
             FROM {$wpdb->posts} 
             WHERE post_type = 'wc_memberships_team' 
             AND post_author = %d",
            $user_id
        ));
        $debug_info['authored_teams'] = $authored_teams;
        
        // Test 3: Check team memberships
        $memberships = $wpdb->get_results($wpdb->prepare(
            "SELECT post_id, meta_key, meta_value 
             FROM {$wpdb->postmeta} 
             WHERE meta_key IN ('_member_id', '_role') 
             AND (meta_value = %d OR post_id IN (
                 SELECT post_id FROM {$wpdb->postmeta} 
                 WHERE meta_key = '_member_id' AND meta_value = %d
             ))
             ORDER BY post_id",
            $user_id, $user_id
        ));
        $debug_info['memberships'] = $memberships;
        
        // Test 4: Try official function
        if (function_exists('wc_memberships_for_teams_get_user_teams')) {
            $teams = wc_memberships_for_teams_get_user_teams($user_id);
            $debug_info['official_function_count'] = count($teams);
            $debug_info['official_function_teams'] = array();
            
            foreach ($teams as $team) {
                if (is_object($team)) {
                    $team_info = array(
                        'id' => method_exists($team, 'get_id') ? $team->get_id() : 'unknown',
                        'name' => method_exists($team, 'get_name') ? $team->get_name() : 'unknown'
                    );
                    
                    if (method_exists($team, 'get_member')) {
                        $member = $team->get_member($user_id);
                        if ($member && method_exists($member, 'get_role')) {
                            $team_info['role'] = $member->get_role();
                        }
                    }
                    
                    $debug_info['official_function_teams'][] = $team_info;
                }
            }
        }
        
        // Test 5: Can view club teams
        $debug_info['can_view_club_teams'] = Club_Manager_Teams_Helper::can_view_club_teams($user_id);
        
        // Test 6: Try to get WC team
        $wc_team = $this->get_wc_team_for_cm_team(null, $user_id);
        $debug_info['wc_team_found'] = $wc_team ? true : false;
        
        wp_send_json_success($debug_info);
    }
    
    /**
     * Debug helper to understand why WC team is not found.
     */
    private function debug_wc_team_access($user_id) {
        global $wpdb;
        
        $debug_messages = [];
        
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