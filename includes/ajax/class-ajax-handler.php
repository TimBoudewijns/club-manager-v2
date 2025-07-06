<?php

/**
 * Base AJAX handler class.
 */
abstract class Club_Manager_Ajax_Handler {
    
    /**
     * Verify nonce and user permissions.
     */
    protected function verify_request() {
        // Check nonce
        if (!check_ajax_referer('club_manager_nonce', 'nonce', false)) {
            wp_send_json_error('Invalid security token');
            exit;
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error('You must be logged in');
            exit;
        }
        
        return get_current_user_id();
    }
    
    /**
     * Verify team ownership.
     */
    protected function verify_team_ownership($team_id, $user_id) {
        global $wpdb;
        
        $table = Club_Manager_Database::get_table_name('teams');
        $owner = $wpdb->get_var($wpdb->prepare(
            "SELECT created_by FROM $table WHERE id = %d",
            $team_id
        ));
        
        if ($owner != $user_id) {
            wp_send_json_error('Unauthorized access to team');
            exit;
        }
        
        return true;
    }
    
    /**
     * Verify team access - for both owners and assigned trainers.
     */
    protected function verify_team_access($team_id, $user_id) {
        global $wpdb;
        
        // First check if user owns the team
        $teams_table = Club_Manager_Database::get_table_name('teams');
        $owner = $wpdb->get_var($wpdb->prepare(
            "SELECT created_by FROM $teams_table WHERE id = %d",
            $team_id
        ));
        
        if ($owner == $user_id) {
            return true; // User owns the team
        }
        
        // Check if user is assigned as trainer to this team
        $trainers_table = Club_Manager_Database::get_table_name('team_trainers');
        $trainer_access = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $trainers_table 
            WHERE team_id = %d AND trainer_id = %d AND is_active = 1",
            $team_id, $user_id
        ));
        
        if ($trainer_access) {
            return true; // User is assigned as trainer
        }
        
        wp_send_json_error('Unauthorized access to team');
        exit;
    }
    
    /**
     * Verify player ownership.
     */
    protected function verify_player_ownership($player_id, $user_id) {
        global $wpdb;
        
        $table = Club_Manager_Database::get_table_name('players');
        $owner = $wpdb->get_var($wpdb->prepare(
            "SELECT created_by FROM $table WHERE id = %d",
            $player_id
        ));
        
        if ($owner != $user_id) {
            wp_send_json_error('Unauthorized access to player');
            exit;
        }
        
        return true;
    }
    
    /**
     * Get and sanitize POST data.
     */
    protected function get_post_data($key, $type = 'text', $default = '') {
        if (!isset($_POST[$key])) {
            return $default;
        }
        
        switch ($type) {
            case 'int':
                return intval($_POST[$key]);
            case 'float':
                return floatval($_POST[$key]);
            case 'email':
                return sanitize_email($_POST[$key]);
            case 'textarea':
                return sanitize_textarea_field($_POST[$key]);
            case 'text':
            default:
                return sanitize_text_field($_POST[$key]);
        }
    }
    
    /**
     * Register AJAX actions.
     */
    abstract public function init();
}