<?php

/**
 * Handle team-related AJAX requests.
 */
class Club_Manager_Team_Ajax extends Club_Manager_Ajax_Handler {
    
    /**
     * Initialize AJAX actions.
     */
    public function init() {
        add_action('wp_ajax_cm_create_team', array($this, 'create_team'));
        add_action('wp_ajax_cm_get_teams', array($this, 'get_teams'));
        add_action('wp_ajax_cm_save_season_preference', array($this, 'save_season_preference'));
    }
    
    /**
     * Create a new team.
     */
    public function create_team() {
        $user_id = $this->verify_request();
        
        $name = $this->get_post_data('name');
        $coach = $this->get_post_data('coach');
        $season = $this->get_post_data('season');
        
        if (empty($name) || empty($coach) || empty($season)) {
            wp_send_json_error('Missing required fields');
            return;
        }
        
        $team_model = new Club_Manager_Team_Model();
        $result = $team_model->create([
            'name' => $name,
            'coach' => $coach,
            'season' => $season,
            'created_by' => $user_id
        ]);
        
        if ($result) {
            wp_send_json_success([
                'id' => $result,
                'message' => 'Team created successfully'
            ]);
        } else {
            wp_send_json_error('Failed to create team');
        }
    }
    
    /**
     * Get teams for current user and season.
     */
    public function get_teams() {
        $user_id = $this->verify_request();
        
        $season = $this->get_post_data('season');
        
        $team_model = new Club_Manager_Team_Model();
        $teams = $team_model->get_user_teams($user_id, $season);
        
        wp_send_json_success($teams);
    }
    
    /**
     * Save season preference.
     */
    public function save_season_preference() {
        $user_id = $this->verify_request();
        
        $season = $this->get_post_data('season');
        update_user_meta($user_id, 'cm_preferred_season', $season);
        
        wp_send_json_success();
    }
} 
