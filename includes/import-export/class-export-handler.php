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
        
        $query = "SELECT * FROM $teams_table WHERE 1=1";
        $params = array();
        
        // Apply season filter
        if (!empty($this->filters['season'])) {
            $query .= " AND season = %s";
            $params[] = $this->filters['season'];
        }
        
        // Apply user filter based on permissions
        if (!Club_Manager_User_Permissions_Helper::is_club_owner_or_manager($this->user_id)) {
            $query .= " AND created_by = %d";
            $params[] = $this->user_id;
        }
        
        $query .= " ORDER BY name";
        
        if (!empty($params)) {
            $teams = $wpdb->get_results($wpdb->prepare($query, ...$params), ARRAY_A);
        } else {
            $teams = $wpdb->get_results($query, ARRAY_A);
        }
        
        return $teams;
    }
    
    /**
     * Get players data for export.
     */
    private function getPlayersData() {
        global $wpdb;
        $players_table = Club_Manager_Database::get_table_name('players');
        $team_players_table = Club_Manager_Database::get_table_name('team_players');
        $teams_table = Club_Manager_Database::get_table_name('teams');
        
        $query = "SELECT DISTINCT p.*, tp.position, tp.jersey_number, t.name as team_name
                  FROM $players_table p
                  LEFT JOIN $team_players_table tp ON p.id = tp.player_id
                  LEFT JOIN $teams_table t ON tp.team_id = t.id
                  WHERE 1=1";
        
        $params = array();
        
        // Apply team filter
        if (!empty($this->filters['teamIds']) && is_array($this->filters['teamIds'])) {
            $placeholders = implode(',', array_fill(0, count($this->filters['teamIds']), '%d'));
            $query .= " AND t.id IN ($placeholders)";
            $params = array_merge($params, $this->filters['teamIds']);
        }
        
        // Apply season filter
        if (!empty($this->filters['season'])) {
            $query .= " AND tp.season = %s";
            $params[] = $this->filters['season'];
        }
        
        $query .= " ORDER BY p.last_name, p.first_name";
        
        if (!empty($params)) {
            $players = $wpdb->get_results($wpdb->prepare($query, ...$params), ARRAY_A);
        } else {
            $players = $wpdb->get_results($query, ARRAY_A);
        }
        
        return $players;
    }
    
    /**
     * Get trainers data for export.
     */
    private function getTrainersData() {
        global $wpdb;
        $trainers_table = Club_Manager_Database::get_table_name('team_trainers');
        $teams_table = Club_Manager_Database::get_table_name('teams');
        
        $query = "SELECT DISTINCT u.user_email as email, 
                  GROUP_CONCAT(t.name SEPARATOR ', ') as team_names
                  FROM $trainers_table tt
                  INNER JOIN {$wpdb->users} u ON tt.trainer_id = u.ID
                  INNER JOIN $teams_table t ON tt.team_id = t.id
                  WHERE tt.is_active = 1";
        
        $params = array();
        
        // Apply season filter
        if (!empty($this->filters['season'])) {
            $query .= " AND t.season = %s";
            $params[] = $this->filters['season'];
        }
        
        $query .= " GROUP BY u.ID ORDER BY u.user_email";
        
        if (!empty($params)) {
            $trainers = $wpdb->get_results($wpdb->prepare($query, ...$params), ARRAY_A);
        } else {
            $trainers = $wpdb->get_results($query, ARRAY_A);
        }
        
        return $trainers;
    }
    
    /**
     * Generate CSV content.
     */
    public function generateCSV($data, $type) {
        if (empty($data)) {
            return '';
        }
        
        // Get headers based on type
        $headers = $this->getHeaders($type);
        
        // Start output buffering
        ob_start();
        
        // Create file handle
        $output = fopen('php://output', 'w');
        
        // Add UTF-8 BOM for Excel compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Write headers
        fputcsv($output, $headers);
        
        // Write data
        foreach ($data as $row) {
            $csv_row = array();
            foreach ($headers as $header) {
                $field = $this->getFieldFromHeader($header, $type);
                $csv_row[] = isset($row[$field]) ? $row[$field] : '';
            }
            fputcsv($output, $csv_row);
        }
        
        fclose($output);
        
        return ob_get_clean();
    }
    
    /**
     * Generate Excel content (would need PHPSpreadsheet).
     */
    public function generateExcel($data, $type) {
        // For now, just generate CSV
        return $this->generateCSV($data, $type);
    }
    
    /**
     * Get headers for export type.
     */
    private function getHeaders($type) {
        switch ($type) {
            case 'teams':
                return array('name', 'coach', 'season');
            case 'players':
                return array('first_name', 'last_name', 'email', 'birth_date', 'position', 'jersey_number', 'team_name');
            case 'trainers':
                return array('email', 'team_names');
            default:
                return array();
        }
    }
    
    /**
     * Map header to field name.
     */
    private function getFieldFromHeader($header, $type) {
        // Direct mapping for most fields
        return $header;
    }
}