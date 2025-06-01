<?php

/**
 * Player model for database operations.
 */
class Club_Manager_Player_Model {
    
    private $table_name;
    private $team_players_table;
    
    public function __construct() {
        $this->table_name = Club_Manager_Database::get_table_name('players');
        $this->team_players_table = Club_Manager_Database::get_table_name('team_players');
    }
    
    /**
     * Create a new player and add to team.
     */
    public function create_with_team($player_data, $team_id, $team_data, $user_id) {
        global $wpdb;
        
        // Check if player already exists
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$this->table_name} WHERE email = %s AND created_by = %d",
            $player_data['email'], $user_id
        ));
        
        if ($existing) {
            $player_id = $existing->id;
        } else {
            // Create new player
            $player_data['created_by'] = $user_id;
            $result = $wpdb->insert($this->table_name, $player_data);
            
            if (!$result) {
                return false;
            }
            
            $player_id = $wpdb->insert_id;
        }
        
        // Add to team
        $team_data['team_id'] = $team_id;
        $team_data['player_id'] = $player_id;
        
        return $this->add_to_team($team_data) ? $player_id : false;
    }
    
    /**
     * Get player by ID.
     */
    public function get($player_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $player_id
        ));
    }
    
    /**
     * Get team players.
     */
    public function get_team_players($team_id, $season) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT p.*, tp.position, tp.jersey_number, tp.notes
            FROM {$this->team_players_table} tp
            JOIN {$this->table_name} p ON tp.player_id = p.id
            WHERE tp.team_id = %d AND tp.season = %s
            ORDER BY p.last_name, p.first_name",
            $team_id, $season
        ));
    }
    
    /**
     * Search available players.
     */
    public function search_available_players($search, $team_id, $season, $user_id) {
        global $wpdb;
        
        $search_term = '%' . $wpdb->esc_like($search) . '%';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT p.*
            FROM {$this->table_name} p
            WHERE p.created_by = %d
            AND (p.first_name LIKE %s OR p.last_name LIKE %s OR p.email LIKE %s)
            AND p.id NOT IN (
                SELECT player_id FROM {$this->team_players_table} 
                WHERE team_id = %d AND season = %s
            )
            LIMIT 10",
            $user_id, $search_term, $search_term, $search_term, $team_id, $season
        ));
    }
    
    /**
     * Add player to team.
     */
    public function add_to_team($data) {
        global $wpdb;
        
        return $wpdb->insert($this->team_players_table, $data);
    }
    
    /**
     * Remove player from team.
     */
    public function remove_from_team($team_id, $player_id, $season) {
        global $wpdb;
        
        return $wpdb->delete(
            $this->team_players_table,
            [
                'team_id' => $team_id,
                'player_id' => $player_id,
                'season' => $season
            ],
            ['%d', '%d', '%s']
        );
    }
    
    /**
     * Check if player is in team.
     */
    public function is_in_team($player_id, $team_id, $season) {
        global $wpdb;
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->team_players_table}
            WHERE player_id = %d AND team_id = %d AND season = %s",
            $player_id, $team_id, $season
        ));
        
        return $count > 0;
    }
    
    /**
     * Get player history across all teams.
     */
    public function get_player_history($player_id) {
        global $wpdb;
        
        $teams_table = Club_Manager_Database::get_table_name('teams');
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT 
                t.name as team_name,
                t.season,
                tp.position,
                tp.jersey_number,
                tp.notes,
                t.created_at
            FROM {$this->team_players_table} tp
            JOIN $teams_table t ON tp.team_id = t.id
            WHERE tp.player_id = %d
            ORDER BY t.season DESC, t.created_at DESC",
            $player_id
        ));
    }
} 
