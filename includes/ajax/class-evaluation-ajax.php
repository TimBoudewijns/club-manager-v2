<?php

/**
 * Handle evaluation-related AJAX requests.
 */
class Club_Manager_Evaluation_Ajax extends Club_Manager_Ajax_Handler {
    
    /**
     * Initialize AJAX actions.
     */
    public function init() {
        add_action('wp_ajax_cm_save_evaluation', array($this, 'save_evaluation'));
        add_action('wp_ajax_cm_get_evaluations', array($this, 'get_evaluations'));
    }
    
    /**
     * Save player evaluation.
     */
    public function save_evaluation() {
        $user_id = $this->verify_request();
        
        $team_id = $this->get_post_data('team_id', 'int');
        $player_id = $this->get_post_data('player_id', 'int');
        
        $this->verify_team_ownership($team_id, $user_id);
        
        // Verify player is in team
        $player_model = new Club_Manager_Player_Model();
        $season = $this->get_post_data('season');
        
        if (!$player_model->is_in_team($player_id, $team_id, $season)) {
            wp_send_json_error('Player not in this team');
            return;
        }
        
        $evaluation_data = [
            'player_id' => $player_id,
            'team_id' => $team_id,
            'season' => $season,
            'category' => $this->get_post_data('category'),
            'subcategory' => $this->get_post_data('subcategory'),
            'score' => $this->get_post_data('score', 'float'),
            'notes' => $this->get_post_data('notes', 'textarea'),
            'evaluated_by' => $user_id
        ];
        
        if (empty($evaluation_data['subcategory'])) {
            $evaluation_data['subcategory'] = null;
        }
        
        $evaluation_model = new Club_Manager_Evaluation_Model();
        $result = $evaluation_model->save_evaluation($evaluation_data);
        
        if ($result) {
            // Trigger advice generation
            wp_schedule_single_event(time() + 1, 'cm_generate_player_advice', [$player_id, $team_id, $season]);
            
            wp_send_json_success(['message' => 'Evaluation saved successfully']);
        } else {
            wp_send_json_error('Failed to save evaluation');
        }
    }
    
    /**
     * Get player evaluations.
     */
    public function get_evaluations() {
        $user_id = $this->verify_request();
        
        $team_id = $this->get_post_data('team_id', 'int');
        $player_id = $this->get_post_data('player_id', 'int');
        $season = $this->get_post_data('season');
        
        $this->verify_team_ownership($team_id, $user_id);
        
        $evaluation_model = new Club_Manager_Evaluation_Model();
        $evaluations = $evaluation_model->get_player_evaluations($player_id, $team_id, $season);
        $averages = $evaluation_model->get_category_averages($player_id, $team_id, $season);
        
        wp_send_json_success([
            'evaluations' => $evaluations,
            'averages' => $averages
        ]);
    }
} 
