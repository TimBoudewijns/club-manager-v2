<?php

/**
 * Export handler for data exports.
 */
class Club_Manager_Export_Handler {
    
    private $user_id;
    private $filters = array();
    
    /**
     * Set user ID.
     */
    public function setUserId($user_id) {
        $this->user_id = $user_id;
    }
    
    /**
     * Set export filters.
     */
    public function setFilters($filters) {
        $this->filters = $filters;
    }
    
    /**
     * Get export data based on type.
     */
    public function getExportData($type) {
        switch ($type) {
            case 'teams':
                return $this->getTeamsData();
            case 'players':
                return $this->getPlayersData();
            case 'trainers':
                return $this->getTrainersData();
            default:
                return array();
        }
    }
    
    /**
     * Get teams data for export.
     */
    private function getTeamsData() {
        global $wpdb;
        $teams_table = Club_Manager_Database::get_table_name('teams');
        
        $query = "SELECT name, coach, season FROM $teams_table WHERE 1=1";
        $params = array();
        
        if (!empty($this->filters['season'])) {
            $query .= " AND season = %s";
            $params[] = $this->filters['season'];
        }
        
        if (!Club_Manager_User_Permissions_Helper::is_club_owner_or_manager($this->user_id)) {
            $query .= " AND created_by = %d";
            $params[] = $this->user_id;
        }
        
        $query .= " ORDER BY name";
        
        if (!empty($params)) {
            return $wpdb->get_results($wpdb->prepare($query, ...$params), ARRAY_A);
        }
        return $wpdb->get_results($query, ARRAY_A);
    }
    
    /**
     * Get players data for export.
     */
    private function getPlayersData() {
        global $wpdb;
        $players_table = Club_Manager_Database::get_table_name('players');
        $team_players_table = Club_Manager_Database::get_table_name('team_players');
        $teams_table = Club_Manager_Database::get_table_name('teams');
        
        $query = "SELECT 
                    p.first_name, 
                    p.last_name, 
                    p.email, 
                    p.birth_date, 
                    tp.position, 
                    tp.jersey_number, 
                    t.name as team_name
                  FROM $players_table p
                  LEFT JOIN $team_players_table tp ON p.id = tp.player_id
                  LEFT JOIN $teams_table t ON tp.team_id = t.id
                  WHERE 1=1";
        
        $params = array();
        
        if (!empty($this->filters['teamIds']) && is_array($this->filters['teamIds'])) {
            $placeholders = implode(',', array_fill(0, count($this->filters['teamIds']), '%d'));
            $query .= " AND t.id IN ($placeholders)";
            $params = array_merge($params, $this->filters['teamIds']);
        }
        
        if (!empty($this->filters['season'])) {
            $query .= " AND tp.season = %s";
            $params[] = $this->filters['season'];
        }
        
        if (!Club_Manager_User_Permissions_Helper::is_club_owner_or_manager($this->user_id)) {
            $query .= " AND p.created_by = %d";
            $params[] = $this->user_id;
        }
        
        $query .= " ORDER BY p.last_name, p.first_name";

        if (!empty($params)) {
            return $wpdb->get_results($wpdb->prepare($query, ...$params), ARRAY_A);
        }
        return $wpdb->get_results($query, ARRAY_A);
    }
    
    /**
     * Get trainers data for export.
     */
    private function getTrainersData() {
        global $wpdb;
        $trainers_table = Club_Manager_Database::get_table_name('team_trainers');
        $teams_table = Club_Manager_Database::get_table_name('teams');
        
        $query = "SELECT DISTINCT 
                    u.user_email as email, 
                    GROUP_CONCAT(t.name SEPARATOR ';') as team_names
                  FROM $trainers_table tt
                  INNER JOIN {$wpdb->users} u ON tt.trainer_id = u.ID
                  INNER JOIN $teams_table t ON tt.team_id = t.id
                  WHERE tt.is_active = 1";
        
        $params = array();
        
        if (!empty($this->filters['season'])) {
            $query .= " AND t.season = %s";
            $params[] = $this->filters['season'];
        }
        
        if (!Club_Manager_User_Permissions_Helper::is_club_owner_or_manager($this->user_id)) {
            $query .= " AND t.created_by = %d";
            $params[] = $this->user_id;
        }
        
        $query .= " GROUP BY u.ID ORDER BY u.user_email";

        if (!empty($params)) {
            return $wpdb->get_results($wpdb->prepare($query, ...$params), ARRAY_A);
        }
        return $wpdb->get_results($query, ARRAY_A);
    }
    
    /**
     * Generate CSV content.
     */
    public function generateCSV($data, $type) {
        if (empty($data)) return '';
        
        // Get headers based on type
        $headers = $this->getHeaders($type);
        
        // Start output buffering
        ob_start();
        $output = fopen('php://output', 'w');
        
        // Add UTF-8 BOM for spreadsheet compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Write headers
        fputcsv($output, array_keys($headers));
        
        // Write data rows
        foreach ($data as $row) {
            $csv_row = array();
            foreach ($headers as $field => $label) {
                $csv_row[] = isset($row[$field]) ? $row[$field] : '';
            }
            fputcsv($output, $csv_row);
        }
        
        fclose($output);
        return ob_get_clean();
    }
    
    /**
     * Get headers for export type - MUST match import field keys.
     */
    private function getHeaders($type) {
        switch ($type) {
            case 'teams':
                return array(
                    'name' => 'name',
                    'coach' => 'coach',
                    'season' => 'season'
                );
                
            case 'players':
                return array(
                    'first_name' => 'first_name',
                    'last_name' => 'last_name',
                    'email' => 'email',
                    'birth_date' => 'birth_date',
                    'position' => 'position',
                    'jersey_number' => 'jersey_number',
                    'team_name' => 'team_name'
                );
                
            case 'trainers':
                return array(
                    'email' => 'email',
                    'team_names' => 'team_names'
                );
                
            default:
                return array();
        }
    }
}