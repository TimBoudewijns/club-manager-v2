<?php

/**
 * Handle player-related AJAX requests.
 */
class Club_Manager_Player_Ajax extends Club_Manager_Ajax_Handler {
    
    /**
     * Initialize AJAX actions.
     */
    public function init() {
        add_action('wp_ajax_cm_create_player', array($this, 'create_player'));
        add_action('wp_ajax_cm_get_team_players', array($this, 'get_team_players'));
        add_action('wp_ajax_cm_search_players', array($this, 'search_players'));
        add_action('wp_ajax_cm_add_player_to_team', array($this, 'add_player_to_team'));
        add_action('wp_ajax_cm_remove_player_from_team', array($this, 'remove_player_from_team'));
        add_action('wp_ajax_cm_get_player_history', array($this, 'get_player_history'));
    }
    
    /**
     * Create a new player.
     */
    public function create_player() {
        $user_id = $this->verify_request();
        
        $team_id = $this->get_post_data('team_id', 'int');
        $this->verify_team_ownership($team_id, $user_id);
        
        $player_data = [
            'first_name' => $this->get_post_data('first_name'),
            'last_name' => $this->get_post_data('last_name'),
            'birth_date' => $this->get_post_data('birth_date'),
            'email' => $this->get_post_data('email', 'email')
        ];
        
        $team_data = [
            'position' => $this->get_post_data('position'),
            'jersey_number' => $this->get_post_data('jersey_number', 'int'),
            'notes' => $this->get_post_data('notes', 'textarea'),
            'season' => $this->get_post_data('season')
        ];
        
        $player_model = new Club_Manager_Player_Model();
        $result = $player_model->create_with_team($player_data, $team_id, $team_data, $user_id);
        
        if ($result) {
            wp_send_json_success([
                'player_id' => $result,
                'message' => 'Player added successfully'
            ]);
        } else {
            wp_send_json_error('Failed to create player');
        }
    }
    
    /**
     * Get players for a team.
     */
    public function get_team_players() {
        $user_id = $this->verify_request();
        
        $team_id = $this->get_post_data('team_id', 'int');
        $season = $this->get_post_data('season');
        
        // Use verify_team_access instead of verify_team_ownership for trainers
        $this->verify_team_access($team_id, $user_id);
        
        $player_model = new Club_Manager_Player_Model();
        $players = $player_model->get_team_players($team_id, $season);
        
        wp_send_json_success($players);
    }
    
    /**
     * Search players.
     */
    public function search_players() {
        $user_id = $this->verify_request();
        
        $search = $this->get_post_data('search');
        $team_id = $this->get_post_data('team_id', 'int');
        $season = $this->get_post_data('season');
        
        $this->verify_team_ownership($team_id, $user_id);
        
        if (strlen($search) < 2) {
            wp_send_json_success([]);
            return;
        }
        
        $player_model = new Club_Manager_Player_Model();
        $players = $player_model->search_available_players($search, $team_id, $season, $user_id);
        
        wp_send_json_success($players);
    }
    
    /**
     * Add existing player to team.
     */
    public function add_player_to_team() {
        $user_id = $this->verify_request();
        
        $team_id = $this->get_post_data('team_id', 'int');
        $player_id = $this->get_post_data('player_id', 'int');
        
        $this->verify_team_ownership($team_id, $user_id);
        $this->verify_player_ownership($player_id, $user_id);
        
        $team_data = [
            'team_id' => $team_id,
            'player_id' => $player_id,
            'position' => $this->get_post_data('position'),
            'jersey_number' => $this->get_post_data('jersey_number', 'int'),
            'notes' => $this->get_post_data('notes', 'textarea'),
            'season' => $this->get_post_data('season')
        ];
        
        $player_model = new Club_Manager_Player_Model();
        $result = $player_model->add_to_team($team_data);
        
        if ($result) {
            wp_send_json_success(['message' => 'Player added to team successfully']);
        } else {
            wp_send_json_error('Failed to add player to team');
        }
    }
    
    /**
     * Remove player from team.
     */
    public function remove_player_from_team() {
        $user_id = $this->verify_request();
        
        $team_id = $this->get_post_data('team_id', 'int');
        $player_id = $this->get_post_data('player_id', 'int');
        $season = $this->get_post_data('season');
        
        // Use new permission check - only club managers or independent trainers can delete
        $this->verify_player_ownership_or_independent($player_id, $user_id);
        
        $player_model = new Club_Manager_Player_Model();
        $result = $player_model->remove_from_team($team_id, $player_id, $season);
        
        if ($result !== false) {
            wp_send_json_success(['message' => 'Player removed from team successfully']);
        } else {
            wp_send_json_error('Failed to remove player from team');
        }
    }
    
    /**
     * Get player history.
     */
    public function get_player_history() {
        $user_id = $this->verify_request();
        
        $player_id = $this->get_post_data('player_id', 'int');
        $this->verify_player_access($player_id, $user_id);
        
        $player_model = new Club_Manager_Player_Model();
        $player = $player_model->get($player_id);
        $history = $player_model->get_player_history($player_id);
        
        wp_send_json_success([
            'player' => $player,
            'history' => $history
        ]);
    }
}