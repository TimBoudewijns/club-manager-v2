<?php

/**
 * Handle AI-related AJAX requests.
 */
class Club_Manager_AI_Ajax extends Club_Manager_Ajax_Handler {
    
    /**
     * Initialize AJAX actions.
     */
    public function init() {
        add_action('wp_ajax_cm_get_player_advice', array($this, 'get_player_advice'));
        add_action('wp_ajax_cm_generate_player_advice', array($this, 'generate_player_advice'));
    }
    
    /**
     * Get player advice.
     */
    public function get_player_advice() {
        $user_id = $this->verify_request();
        
        $team_id = $this->get_post_data('team_id', 'int');
        $player_id = $this->get_post_data('player_id', 'int');
        $season = $this->get_post_data('season');
        
        $this->verify_team_ownership($team_id, $user_id);
        
        global $wpdb;
        $advice_table = Club_Manager_Database::get_table_name('player_advice');
        
        $advice = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $advice_table 
            WHERE player_id = %d AND team_id = %d AND season = %s
            ORDER BY generated_at DESC
            LIMIT 1",
            $player_id, $team_id, $season
        ));
        
        if ($advice) {
            wp_send_json_success([
                'advice' => $advice->advice,
                'generated_at' => $advice->generated_at,
                'status' => $advice->status
            ]);
        } else {
            // Check if player has evaluations
            $evaluation_model = new Club_Manager_Evaluation_Model();
            $evaluations = $evaluation_model->get_player_evaluations($player_id, $team_id, $season);
            
            $status = empty($evaluations) ? 'no_evaluations' : 'no_advice_yet';
            
            wp_send_json_success([
                'advice' => null,
                'status' => $status
            ]);
        }
    }
    
    /**
     * Generate player advice.
     */
    public function generate_player_advice() {
        $user_id = $this->verify_request();
        
        $team_id = $this->get_post_data('team_id', 'int');
        $player_id = $this->get_post_data('player_id', 'int');
        $season = $this->get_post_data('season');
        
        $this->verify_team_ownership($team_id, $user_id);
        
        // Schedule the advice generation
        wp_schedule_single_event(time() + 1, 'cm_generate_player_advice', [$player_id, $team_id, $season]);
        
        wp_send_json_success(['message' => 'Advice generation started']);
    }
} 
