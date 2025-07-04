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
        add_action('wp_ajax_cm_get_available_trainers', array($this, 'get_available_trainers'));
        add_action('wp_ajax_cm_invite_trainer', array($this, 'ajax_invite_trainer'));
        add_action('wp_ajax_cm_cancel_invitation', array($this, 'cancel_invitation'));
        add_action('wp_ajax_cm_update_trainer', array($this, 'update_trainer'));
        add_action('wp_ajax_cm_remove_trainer', array($this, 'remove_trainer'));
    }
    
    /**
     * Get teams managed by the current user.
     */
    public function get_managed_teams() {
        $user_id = $this->verify_request();
        
        if (!Club_Manager_User_Permissions_Helper::can_view_club_teams($user_id)) {
            wp_send_json_error('Unauthorized access');
        }
        
        $season = $this->get_post_data('season');
        global $wpdb;
        $teams_table = Club_Manager_Database::get_table_name('teams');
        
        $teams = $wpdb->get_results($wpdb->prepare(
            "SELECT id, name, season FROM $teams_table WHERE created_by = %d AND season = %s ORDER BY name",
            $user_id, $season
        ));
        
        wp_send_json_success($teams);
    }
    
    /**
     * Get pending trainer invitations from WC Teams.
     */
    public function get_pending_invitations() {
        $user_id = $this->verify_request();
        
        if (!Club_Manager_User_Permissions_Helper::can_view_club_teams($user_id)) {
            wp_send_json_error('Unauthorized access');
        }
        
        if (!function_exists('wc_memberships_for_teams')) {
            wp_send_json_success([]);
        }
        
        $managed_teams = Club_Manager_Teams_Helper::get_user_managed_teams($user_id);
        $wc_team_ids = wp_list_pluck($managed_teams, 'team_id');
        
        if (empty($wc_team_ids)) {
            wp_send_json_success([]);
        }

        $invitations = get_posts([
            'post_type' => 'wc_team_invitation',
            'post_status' => 'wcmti-pending',
            'post_parent__in' => $wc_team_ids,
            'posts_per_page' => -1,
        ]);

        $result = [];
        foreach ($invitations as $inv) {
            $cm_team_ids = get_post_meta($inv->ID, '_cm_team_ids', true);
            if (!$cm_team_ids) continue;

            $result[] = [
                'id' => $inv->ID,
                'email' => get_post_meta($inv->ID, '_email', true),
                'team_name' => 'Multiple Teams',
                'created_at' => $inv->post_date,
                'role' => get_post_meta($inv->ID, '_cm_role', true) ?: 'trainer'
            ];
        }
        
        wp_send_json_success($result);
    }
    
    /**
     * Get active trainers.
     */
    public function get_active_trainers() {
        $user_id = $this->verify_request();
        
        if (!Club_Manager_User_Permissions_Helper::is_club_owner_or_manager($user_id)) {
            wp_send_json_error('Unauthorized access');
        }
        
        global $wpdb;
        $trainers_table = Club_Manager_Database::get_table_name('team_trainers');
        $teams_table = Club_Manager_Database::get_table_name('teams');
        
        $trainers_data = $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT tt.trainer_id, u.display_name, u.user_email as email
            FROM $trainers_table tt
            INNER JOIN $teams_table t ON tt.team_id = t.id
            INNER JOIN {$wpdb->users} u ON tt.trainer_id = u.ID
            WHERE t.created_by = %d
            ORDER BY u.display_name",
            $user_id
        ));
        
        $trainers = [];
        foreach ($trainers_data as $trainer) {
            $trainer_id = $trainer->trainer_id;
            
            if (!isset($trainers[$trainer_id])) {
                 $user_info = get_userdata($trainer_id);
                 $trainers[$trainer_id] = [
                    'id' => $trainer_id,
                    'display_name' => $user_info->display_name,
                    'email' => $user_info->user_email,
                    'first_name' => $user_info->first_name,
                    'last_name' => $user_info->last_name,
                    'teams' => []
                ];
            }
            
            $trainer_teams = $wpdb->get_results($wpdb->prepare(
                "SELECT t.id, t.name FROM $trainers_table tt
                INNER JOIN $teams_table t ON tt.team_id = t.id
                WHERE tt.trainer_id = %d AND t.created_by = %d",
                $trainer_id, $user_id
            ));
            
            $trainers[$trainer_id]['teams'] = $trainer_teams;
        }
        
        wp_send_json_success(array_values($trainers));
    }

    /**
     * AJAX wrapper for inviting a trainer.
     */
    public function ajax_invite_trainer() {
        $user_id = $this->verify_request();
        $email = $this->get_post_data('email', 'email');
        $team_ids = isset($_POST['teams']) ? array_map('intval', $_POST['teams']) : [];
        $role = $this->get_post_data('role');
        $message = $this->get_post_data('message', 'textarea');

        $result = $this->invite_trainer($user_id, $email, $team_ids, $role, $message);

        if ($result['success']) {
            wp_send_json_success($result['data']);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * Invite a trainer using official WC Teams system.
     */
    public function invite_trainer($user_id, $email, $team_ids, $role, $message) {
        if (!class_exists('Club_Manager_Teams_Helper') || !Club_Manager_Teams_Helper::can_view_club_teams($user_id)) {
            return ['success' => false, 'message' => 'Unauthorized access'];
        }
        
        if (!function_exists('wc_memberships_for_teams')) {
            return ['success' => false, 'message' => 'WooCommerce Teams for Memberships is required'];
        }
        
        if (empty($email) || empty($team_ids)) {
            return ['success' => false, 'message' => 'Email and teams are required'];
        }
        
        $managed_teams = Club_Manager_Teams_Helper::get_user_managed_teams($user_id);
        if (empty($managed_teams)) {
            return ['success' => false, 'message' => 'You do not have a WooCommerce Team to which you can add trainers.'];
        }
        
        $wc_team_id = $managed_teams[0]['team_id'];
        $wc_team = wc_memberships_for_teams_get_team($wc_team_id);
        
        if (!$wc_team) {
            return ['success' => false, 'message' => 'No valid WooCommerce Team found.'];
        }
        
        try {
            $invitations_instance = wc_memberships_for_teams()->get_invitations_instance();
            if (!$invitations_instance) {
                return ['success' => false, 'message' => 'Could not access invitations system'];
            }
            
            $invitation = $invitations_instance->create_invitation([
                'team_id' => $wc_team->get_id(),
                'email' => $email,
                'sender_id' => $user_id,
                'role' => 'member'
            ]);
            
            if (is_wp_error($invitation)) {
                return ['success' => false, 'message' => $invitation->get_error_message()];
            }
            
            update_post_meta($invitation->get_id(), '_cm_team_ids', $team_ids);
            update_post_meta($invitation->get_id(), '_cm_role', $role);
            update_post_meta($invitation->get_id(), '_cm_message', $message);
            
            return [
                'success' => true,
                'message' => 'Invitation sent successfully',
                'data' => ['invitation_id' => $invitation->get_id()]
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error creating invitation: ' . $e->getMessage()];
        }
    }
}