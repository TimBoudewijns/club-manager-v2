<?php

/**
 * Handle season management AJAX requests.
 */
class Club_Manager_Season_Ajax extends Club_Manager_Ajax_Handler {
    
    /**
     * Initialize AJAX actions.
     */
    public function init() {
        add_action('wp_ajax_cm_get_available_seasons', array($this, 'get_available_seasons'));
        add_action('wp_ajax_cm_add_season', array($this, 'add_season'));
        add_action('wp_ajax_cm_remove_season', array($this, 'remove_season'));
    }
    
    /**
     * Get all available seasons.
     */
    public function get_available_seasons() {
        $user_id = $this->verify_request();
        
        $seasons = Club_Manager_Season_Helper::get_available_seasons();
        
        wp_send_json_success([
            'seasons' => $seasons,
            'current_season' => Club_Manager_Season_Helper::get_current_season()
        ]);
    }
    
    /**
     * Add a new season (admin only).
     */
    public function add_season() {
        $user_id = $this->verify_request();
        
        // Check if user is WordPress administrator
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Only site administrators can add seasons');
            return;
        }
        
        $season_name = $this->get_post_data('season_name');
        
        if (empty($season_name)) {
            wp_send_json_error('Season name is required');
            return;
        }
        
        // Validate season format (YYYY-YYYY)
        if (!preg_match('/^\d{4}-\d{4}$/', $season_name)) {
            wp_send_json_error('Season must be in format YYYY-YYYY (e.g., 2024-2025)');
            return;
        }
        
        // Check if season already exists
        $existing_seasons = Club_Manager_Season_Helper::get_available_seasons();
        if (isset($existing_seasons[$season_name])) {
            wp_send_json_error('Season already exists');
            return;
        }
        
        $result = Club_Manager_Season_Helper::add_season($season_name);
        
        if ($result) {
            wp_send_json_success([
                'message' => 'Season added successfully',
                'season' => $season_name
            ]);
        } else {
            wp_send_json_error('Failed to add season');
        }
    }
    
    /**
     * Remove a season (admin only).
     */
    public function remove_season() {
        $user_id = $this->verify_request();
        
        // Check if user is WordPress administrator
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Only site administrators can remove seasons');
            return;
        }
        
        $season_name = $this->get_post_data('season_name');
        
        if (empty($season_name)) {
            wp_send_json_error('Season name is required');
            return;
        }
        
        $result = Club_Manager_Season_Helper::remove_season($season_name);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } elseif ($result) {
            wp_send_json_success([
                'message' => 'Season removed successfully'
            ]);
        } else {
            wp_send_json_error('Failed to remove season');
        }
    }
}