<?php

/**
 * Evaluation model for database operations.
 */
class Club_Manager_Evaluation_Model {
    
    private $table_name;
    
    public function __construct() {
        $this->table_name = Club_Manager_Database::get_table_name('player_evaluations');
    }
    
    /**
     * Save evaluation.
     */
    public function save_evaluation($data) {
        global $wpdb;
        
        $data['evaluated_at'] = current_time('mysql');
        
        return $wpdb->insert($this->table_name, $data);
    }
    
    /**
     * Get player evaluations.
     */
    public function get_player_evaluations($player_id, $team_id, $season) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_name}
            WHERE player_id = %d AND team_id = %d AND season = %s
            ORDER BY evaluated_at DESC, category, subcategory",
            $player_id, $team_id, $season
        ));
    }
    
    /**
     * Get category averages.
     */
    public function get_category_averages($player_id, $team_id, $season) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT category, AVG(score) as average 
            FROM {$this->table_name}
            WHERE player_id = %d AND team_id = %d AND season = %s
            GROUP BY category",
            $player_id, $team_id, $season
        ));
    }
    
    /**
     * Get latest evaluations by category.
     */
    public function get_latest_by_category($player_id, $team_id, $season) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT e1.* FROM {$this->table_name} e1
            INNER JOIN (
                SELECT category, subcategory, MAX(evaluated_at) as max_date
                FROM {$this->table_name}
                WHERE player_id = %d AND team_id = %d AND season = %s
                GROUP BY category, subcategory
            ) e2 ON e1.category = e2.category 
                AND (e1.subcategory = e2.subcategory OR (e1.subcategory IS NULL AND e2.subcategory IS NULL))
                AND e1.evaluated_at = e2.max_date
            WHERE e1.player_id = %d AND e1.team_id = %d AND e1.season = %s",
            $player_id, $team_id, $season, $player_id, $team_id, $season
        ));
    }
    
    /**
     * Get evaluations for a specific date.
     */
    public function get_evaluations_by_date($player_id, $team_id, $season, $date) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_name}
            WHERE player_id = %d AND team_id = %d AND season = %s
            AND DATE(evaluated_at) = %s
            ORDER BY category, subcategory",
            $player_id, $team_id, $season, $date
        ));
    }
    
    /**
     * Get unique evaluation dates.
     */
    public function get_evaluation_dates($player_id, $team_id, $season) {
        global $wpdb;
        
        return $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT DATE(evaluated_at) as eval_date
            FROM {$this->table_name}
            WHERE player_id = %d AND team_id = %d AND season = %s
            ORDER BY eval_date DESC",
            $player_id, $team_id, $season
        ));
    }
} 
