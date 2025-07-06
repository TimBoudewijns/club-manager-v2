<?php

/**
 * Base AJAX handler class.
 */
abstract class Club_Manager_Ajax_Handler {
    
    /**
     * Verify nonce and user permissions.
     */
    protected function verify_request() {
        // Debug logging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Club Manager AJAX Request: ' . (isset($_POST['action']) ? $_POST['action'] : 'No action') . ' - User: ' . get_current_user_id());
        }
        
        // Check nonce
        if (!check_ajax_referer('club_manager_nonce', 'nonce', false)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Club Manager AJAX: Nonce check failed');
            }
            wp_send_json_error('Invalid security token. Please refresh the page and try again.');
            exit;
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Club Manager AJAX: User not logged in');
            }
            wp_send_json_error('You must be logged in to perform this action.');
            exit;
        }
        
        return get_current_user_id();
    }
    
    /**
     * Verify team ownership.
     */
    protected function verify_team_ownership($team_id, $user_id) {
        global $wpdb;
        
        if (!$team_id || !$user_id) {
            wp_send_json_error('Invalid team or user ID');
            exit;
        }
        
        $table = Club_Manager_Database::get_table_name('teams');
        $owner = $wpdb->get_var($wpdb->prepare(
            "SELECT created_by FROM $table WHERE id = %d",
            $team_id
        ));
        
        if ($owner != $user_id) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Club Manager: User $user_id tried to access team $team_id owned by $owner");
            }
            wp_send_json_error('You do not have permission to access this team.');
            exit;
        }
        
        return true;
    }
    
    /**
     * Verify team access - for both owners and assigned trainers.
     */
    protected function verify_team_access($team_id, $user_id) {
        global $wpdb;
        
        if (!$team_id || !$user_id) {
            wp_send_json_error('Invalid team or user ID');
            exit;
        }
        
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
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Club Manager: User $user_id tried to access team $team_id without permission");
        }
        
        wp_send_json_error('You do not have permission to access this team.');
        exit;
    }
    
    /**
     * Verify player ownership.
     */
    protected function verify_player_ownership($player_id, $user_id) {
        global $wpdb;
        
        if (!$player_id || !$user_id) {
            wp_send_json_error('Invalid player or user ID');
            exit;
        }
        
        $table = Club_Manager_Database::get_table_name('players');
        $owner = $wpdb->get_var($wpdb->prepare(
            "SELECT created_by FROM $table WHERE id = %d",
            $player_id
        ));
        
        if ($owner != $user_id) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Club Manager: User $user_id tried to access player $player_id owned by $owner");
            }
            wp_send_json_error('You do not have permission to access this player.');
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
            case 'array':
                return is_array($_POST[$key]) ? array_map('sanitize_text_field', $_POST[$key]) : array();
            case 'text':
            default:
                return sanitize_text_field($_POST[$key]);
        }
    }
    
    /**
     * Log AJAX errors for debugging.
     */
    protected function log_error($message, $context = array()) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $log_message = 'Club Manager AJAX Error: ' . $message;
            if (!empty($context)) {
                $log_message .= ' - Context: ' . json_encode($context);
            }
            error_log($log_message);
        }
    }
    
    /**
     * Send success response with data.
     */
    protected function send_success($data = null, $message = '') {
        $response = array();
        
        if ($message) {
            $response['message'] = $message;
        }
        
        if ($data !== null) {
            $response = is_array($data) ? array_merge($response, $data) : $data;
        }
        
        wp_send_json_success($response);
    }
    
    /**
     * Send error response.
     */
    protected function send_error($message = 'An error occurred', $data = null) {
        $this->log_error($message, array('data' => $data));
        
        if ($data !== null) {
            wp_send_json_error(array(
                'message' => $message,
                'data' => $data
            ));
        } else {
            wp_send_json_error($message);
        }
    }
    
    /**
     * Register AJAX actions.
     */
    abstract public function init();
}