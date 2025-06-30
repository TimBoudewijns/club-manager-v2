<?php

/**
 * Handle data export operations.
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
        $this->filters = wp_parse_args($filters, array(
            'season' => '',
            'teamIds' => array(),
            'includeEvaluations' => false
        ));
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
                throw new Exception('Invalid export type');
        }
    }
    
    /**
     * Get teams data for export.
     */
    private function getTeamsData() {
        global $wpdb;
        
        $teams_table = Club_Manager_Database::get_table_name('teams');
        
        // Base query
        $query = "SELECT * FROM $teams_table WHERE created_by = %d";
        $params = array($this->user_id);
        
        // Apply filters
        if (!empty($this->filters['season'])) {
            $query .= " AND season = %s";
            $params[] = $this->filters['season'];
        }
        
        if (!empty($this->filters['teamIds'])) {
            $placeholders = implode(',', array_fill(0, count($this->filters['teamIds']), '%d'));
            $query .= " AND id IN ($placeholders)";
            $params = array_merge($params, $this->filters['teamIds']);
        }
        
        $query .= " ORDER BY name";
        
        $teams = $wpdb->get_results($wpdb->prepare($query, ...$params), ARRAY_A);
        
        // Format for export
        $export_data = array();
        foreach ($teams as $team) {
            $export_data[] = array(
                'team_name' => $team['name'],
                'coach' => $team['coach'],
                'season' => $team['season'],
                'created_at' => $team['created_at']
            );
        }
        
        return $export_data;
    }
    
    /**
     * Get players data for export.
     */
    private function getPlayersData() {
        global $wpdb;
        
        $players_table = Club_Manager_Database::get_table_name('players');
        $team_players_table = Club_Manager_Database::get_table_name('team_players');
        $teams_table = Club_Manager_Database::get_table_name('teams');
        
        // Build query
        if (!empty($this->filters['teamIds'])) {
            // Get players from specific teams
            $placeholders = implode(',', array_fill(0, count($this->filters['teamIds']), '%d'));
            $query = "SELECT DISTINCT p.*, tp.position, tp.jersey_number, t.name as team_name, t.season
                     FROM $players_table p
                     INNER JOIN $team_players_table tp ON p.id = tp.player_id
                     INNER JOIN $teams_table t ON tp.team_id = t.id
                     WHERE t.id IN ($placeholders)
                     AND t.created_by = %d";
            
            $params = array_merge($this->filters['teamIds'], array($this->user_id));
            
            if (!empty($this->filters['season'])) {
                $query .= " AND t.season = %s";
                $params[] = $this->filters['season'];
            }
            
        } else {
            // Get all players created by user
            $query = "SELECT p.*, tp.position, tp.jersey_number, t.name as team_name, t.season
                     FROM $players_table p
                     LEFT JOIN $team_players_table tp ON p.id = tp.player_id
                     LEFT JOIN $teams_table t ON tp.team_id = t.id
                     WHERE p.created_by = %d";
            
            $params = array($this->user_id);
            
            if (!empty($this->filters['season'])) {
                $query .= " AND (t.season = %s OR t.season IS NULL)";
                $params[] = $this->filters['season'];
            }
        }
        
        $query .= " ORDER BY p.last_name, p.first_name";
        
        $players = $wpdb->get_results($wpdb->prepare($query, ...$params), ARRAY_A);
        
        // Format for export
        $export_data = array();
        foreach ($players as $player) {
            $row = array(
                'first_name' => $player['first_name'],
                'last_name' => $player['last_name'],
                'email' => $player['email'],
                'birth_date' => $player['birth_date'],
                'position' => $player['position'] ?? '',
                'jersey_number' => $player['jersey_number'] ?? '',
                'team_name' => $player['team_name'] ?? ''
            );
            
            // Add evaluations if requested
            if ($this->filters['includeEvaluations'] && !empty($player['team_name'])) {
                $evaluations = $this->getPlayerEvaluations($player['id'], $player['season']);
                if (!empty($evaluations)) {
                    $row['evaluations'] = json_encode($evaluations);
                }
            }
            
            $export_data[] = $row;
        }
        
        return $export_data;
    }
    
    /**
     * Get trainers data for export.
     */
    private function getTrainersData() {
        global $wpdb;
        
        $trainers_table = Club_Manager_Database::get_table_name('team_trainers');
        $teams_table = Club_Manager_Database::get_table_name('teams');
        
        // Get trainers for teams owned by user
        $query = "SELECT DISTINCT tt.trainer_id, tt.role, u.user_email as email,
                        um1.meta_value as first_name, um2.meta_value as last_name
                 FROM $trainers_table tt
                 INNER JOIN $teams_table t ON tt.team_id = t.id
                 INNER JOIN {$wpdb->users} u ON tt.trainer_id = u.ID
                 LEFT JOIN {$wpdb->usermeta} um1 ON u.ID = um1.user_id AND um1.meta_key = 'first_name'
                 LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = 'last_name'
                 WHERE t.created_by = %d";
        
        $params = array($this->user_id);
        
        if (!empty($this->filters['teamIds'])) {
            $placeholders = implode(',', array_fill(0, count($this->filters['teamIds']), '%d'));
            $query .= " AND t.id IN ($placeholders)";
            $params = array_merge($params, $this->filters['teamIds']);
        }
        
        $trainers = $wpdb->get_results($wpdb->prepare($query, ...$params), ARRAY_A);
        
        // Get team assignments for each trainer
        $export_data = array();
        foreach ($trainers as $trainer) {
            // Get assigned teams
            $team_query = "SELECT t.name
                          FROM $trainers_table tt
                          INNER JOIN $teams_table t ON tt.team_id = t.id
                          WHERE tt.trainer_id = %d AND t.created_by = %d";
            
            $team_params = array($trainer['trainer_id'], $this->user_id);
            
            if (!empty($this->filters['teamIds'])) {
                $placeholders = implode(',', array_fill(0, count($this->filters['teamIds']), '%d'));
                $team_query .= " AND t.id IN ($placeholders)";
                $team_params = array_merge($team_params, $this->filters['teamIds']);
            }
            
            $teams = $wpdb->get_col($wpdb->prepare($team_query, ...$team_params));
            
            $export_data[] = array(
                'email' => $trainer['email'],
                'first_name' => $trainer['first_name'] ?? '',
                'last_name' => $trainer['last_name'] ?? '',
                'role' => $trainer['role'],
                'team_names' => implode(', ', $teams)
            );
        }
        
        return $export_data;
    }
    
    /**
     * Get player evaluations.
     */
    private function getPlayerEvaluations($player_id, $season) {
        global $wpdb;
        
        $evaluations_table = Club_Manager_Database::get_table_name('player_evaluations');
        
        $evaluations = $wpdb->get_results($wpdb->prepare(
            "SELECT category, subcategory, score, notes, evaluated_at
             FROM $evaluations_table
             WHERE player_id = %d AND season = %s
             ORDER BY evaluated_at DESC",
            $player_id, $season
        ), ARRAY_A);
        
        return $evaluations;
    }
    
    /**
     * Generate CSV content.
     */
    public function generateCSV($data, $type) {
        if (empty($data)) {
            return '';
        }
        
        // Open output buffer
        ob_start();
        
        // Create file pointer connected to output buffer
        $output = fopen('php://output', 'w');
        
        // Add BOM for Excel UTF-8 compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Write headers
        $headers = array_keys($data[0]);
        fputcsv($output, $headers);
        
        // Write data
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
        
        // Get content
        $csv_content = ob_get_clean();
        
        return $csv_content;
    }
    
    /**
     * Generate Excel content.
     */
    public function generateExcel($data, $type) {
        // For now, we'll generate CSV with Excel-compatible formatting
        // In a production environment, you'd use a library like PhpSpreadsheet
        
        if (empty($data)) {
            return '';
        }
        
        // Generate CSV content
        $csv_content = $this->generateCSV($data, $type);
        
        // Note: In production, you would use PhpSpreadsheet here:
        /*
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Headers
        $headers = array_keys($data[0]);
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }
        
        // Data
        $row = 2;
        foreach ($data as $record) {
            $col = 'A';
            foreach ($record as $value) {
                $sheet->setCellValue($col . $row, $value);
                $col++;
            }
            $row++;
        }
        
        // Save to string
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        return ob_get_clean();
        */
        
        return $csv_content;
    }
}