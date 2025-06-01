<?php

/**
 * Team model for database operations.
 */
class Club_Manager_Team_Model {
    
    private $table_name;
    
    public function __construct() {
        $this->table_name = Club_Manager_Database::get_table_name('teams');
    }
    
    /**
     * Create a new team.
     */
    public function create($data) {
        global $wpdb;
        
        $result = $wpdb->insert(
            $this->table_name,
            $data,
            ['%s', '%s', '%s', '%d']
        );
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Get team by ID.
     */
    public function get($team_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $team_id
        ));
    }
    
    /**
     * Get teams for a user and season.
     */
    public function get_user_teams($user_id, $season = '') {
        global $wpdb;
        
        $query = "SELECT * FROM {$this->table_name} WHERE created_by = %d";
        $params = [$user_id];
        
        if (!empty($season)) {
            $query .= " AND season = %s";
            $params[] = $season;
        }
        
        $query .= " ORDER BY name";
        
        return $wpdb->get_results($wpdb->prepare($query, ...$params));
    }
    
    /**
     * Update team.
     */
    public function update($team_id, $data) {
        global $wpdb;
        
        return $wpdb->update(
            $this->table_name,
            $data,
            ['id' => $team_id]
        );
    }
    
    /**
     * Delete team.
     */
    public function delete($team_id) {
        global $wpdb;
        
        return $wpdb->delete(
            $this->table_name,
            ['id' => $team_id],
            ['%d']
        );
    }
    
    /**
     * Check if user owns team.
     */
    public function is_owner($team_id, $user_id) {
        global $wpdb;
        
        $owner = $wpdb->get_var($wpdb->prepare(
            "SELECT created_by FROM {$this->table_name} WHERE id = %d",
            $team_id
        ));
        
        return $owner == $user_id;
    }
} 
