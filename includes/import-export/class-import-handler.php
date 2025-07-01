<?php

/**
 * Import handler for processing data imports.
 */
class Club_Manager_Import_Handler {
    
    private $options = array(
        'duplicateHandling' => 'skip',
        'sendInvitations' => true,
        'validateEmails' => true
    );
    
    /**
     * Set import options.
     */
    public function setOptions($options) {
        $this->options = array_merge($this->options, $options);
    }
    
    /**
     * Process a batch of import rows.
     * 
     * @param array $rows Mapped data rows to process
     * @param string $type Import type
     * @param int $start_index Starting index for error reporting
     * @param int $user_id User performing the import
     * @return array Processing results
     */
    public function processBatch($rows, $type, $start_index = 0, $user_id = 0) {
        $results = array(
            'successful' => 0,
            'failed' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => array(),
            'trainers_to_invite' => array()
        );
        
        // Remove type suffixes for processing
        $base_type = str_replace(array('-with-players', '-with-assignments'), '', $type);
        
        foreach ($rows as $index => $row) {
            $row_number = $start_index + $index + 1;
            
            try {
                switch ($base_type) {
                    case 'teams':
                        $result = $this->processTeam($row, $user_id, $row_number);
                        break;
                        
                    case 'players':
                        $result = $this->processPlayer($row, $user_id, $row_number);
                        break;
                        
                    case 'trainers':
                        $result = $this->processTrainer($row, $user_id, $row_number);
                        if ($result['success'] && !empty($result['trainer_to_invite'])) {
                            $results['trainers_to_invite'][] = $result['trainer_to_invite'];
                        }
                        break;
                        
                    default:
                        throw new Exception('Unknown import type: ' . $type);
                }
                
                if ($result['success']) {
                    $results['successful']++;
                    
                    if ($result['action'] === 'created') {
                        $results['created']++;
                    } elseif ($result['action'] === 'updated') {
                        $results['updated']++;
                    } elseif ($result['action'] === 'skipped') {
                        $results['skipped']++;
                    }
                } else {
                    $results['failed']++;
                    $results['errors'][] = array(
                        'row' => $row_number,
                        'message' => $result['error'] ?? 'Unknown error'
                    );
                }
                
            } catch (Exception $e) {
                $results['failed']++;
                $results['errors'][] = array(
                    'row' => $row_number,
                    'message' => $e->getMessage()
                );
            }
        }
        
        return $results;
    }
    
    /**
     * Process team import.
     */
    private function processTeam($data, $user_id, $row_number) {
        global $wpdb;
        $teams_table = Club_Manager_Database::get_table_name('teams');
        
        // Check if team exists
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $teams_table WHERE name = %s AND season = %s",
            $data['name'],
            $data['season']
        ));
        
        if ($existing) {
            if ($this->options['duplicateHandling'] === 'skip') {
                return array(
                    'success' => true,
                    'action' => 'skipped',
                    'id' => $existing->id
                );
            } elseif ($this->options['duplicateHandling'] === 'update') {
                // Update existing team
                $result = $wpdb->update(
                    $teams_table,
                    array(
                        'coach' => $data['coach']
                    ),
                    array('id' => $existing->id),
                    array('%s'),
                    array('%d')
                );
                
                if ($result === false) {
                    return array(
                        'success' => false,
                        'error' => 'Failed to update team: ' . $wpdb->last_error
                    );
                }
                
                return array(
                    'success' => true,
                    'action' => 'updated',
                    'id' => $existing->id
                );
            }
        }
        
        // Create new team
        $result = $wpdb->insert(
            $teams_table,
            array(
                'name' => $data['name'],
                'coach' => $data['coach'],
                'season' => $data['season'],
                'created_by' => $user_id,
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%d', '%s')
        );
        
        if ($result === false) {
            return array(
                'success' => false,
                'error' => 'Failed to create team: ' . $wpdb->last_error
            );
        }
        
        return array(
            'success' => true,
            'action' => 'created',
            'id' => $wpdb->insert_id
        );
    }
    
    /**
     * Process player import.
     */
    private function processPlayer($data, $user_id, $row_number) {
        global $wpdb;
        $players_table = Club_Manager_Database::get_table_name('players');
        $team_players_table = Club_Manager_Database::get_table_name('team_players');
        
        // Check if player exists by email
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $players_table WHERE email = %s",
            $data['email']
        ));
        
        if ($existing) {
            if ($this->options['duplicateHandling'] === 'skip') {
                return array(
                    'success' => true,
                    'action' => 'skipped',
                    'id' => $existing->id
                );
            } elseif ($this->options['duplicateHandling'] === 'update') {
                // Update existing player
                $update_data = array(
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'birth_date' => $data['birth_date']
                );
                
                $result = $wpdb->update(
                    $players_table,
                    $update_data,
                    array('id' => $existing->id),
                    array('%s', '%s', '%s'),
                    array('%d')
                );
                
                if ($result === false) {
                    return array(
                        'success' => false,
                        'error' => 'Failed to update player: ' . $wpdb->last_error
                    );
                }
                
                $player_id = $existing->id;
                $action = 'updated';
            } else {
                // Create anyway
                $player_id = null;
            }
        } else {
            $player_id = null;
        }
        
        // Create new player if needed
        if (!$player_id) {
            $result = $wpdb->insert(
                $players_table,
                array(
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'birth_date' => $data['birth_date'],
                    'email' => $data['email'],
                    'created_by' => $user_id,
                    'created_at' => current_time('mysql')
                ),
                array('%s', '%s', '%s', '%s', '%d', '%s')
            );
            
            if ($result === false) {
                return array(
                    'success' => false,
                    'error' => 'Failed to create player: ' . $wpdb->last_error
                );
            }
            
            $player_id = $wpdb->insert_id;
            $action = 'created';
        }
        
        // Assign to team if specified
        if (!empty($data['team_name'])) {
            // Get current season from user preference
            $season = get_user_meta($user_id, 'cm_preferred_season', true) ?: '2024-2025';
            
            // Find team by name and season
            $teams_table = Club_Manager_Database::get_table_name('teams');
            $team = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM $teams_table WHERE name = %s AND season = %s",
                $data['team_name'],
                $season
            ));
            
            if ($team) {
                // Check if already assigned
                $existing_assignment = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM $team_players_table 
                     WHERE team_id = %d AND player_id = %d AND season = %s",
                    $team->id,
                    $player_id,
                    $season
                ));
                
                if (!$existing_assignment) {
                    // Assign player to team
                    $assignment_data = array(
                        'team_id' => $team->id,
                        'player_id' => $player_id,
                        'season' => $season
                    );
                    
                    if (!empty($data['position'])) {
                        $assignment_data['position'] = $data['position'];
                    }
                    
                    if (isset($data['jersey_number'])) {
                        $assignment_data['jersey_number'] = $data['jersey_number'];
                    }
                    
                    $wpdb->insert(
                        $team_players_table,
                        $assignment_data,
                        array('%d', '%d', '%s', '%s', '%d')
                    );
                }
            }
        }
        
        return array(
            'success' => true,
            'action' => $action,
            'id' => $player_id
        );
    }
    
    /**
     * Process trainer import.
     */
    private function processTrainer($data, $user_id, $row_number) {
        // Check if user exists
        $user = get_user_by('email', $data['email']);
        
        if ($user) {
            // User exists - just assign to teams if specified
            if (!empty($data['team_names'])) {
                $this->assignTrainerToTeams($user->ID, $data['team_names'], $user_id);
            }
            
            return array(
                'success' => true,
                'action' => 'skipped', // User already exists
                'id' => $user->ID
            );
        }
        
        // Prepare trainer data for invitation
        $trainer_to_invite = array(
            'email' => $data['email'],
            'team_ids' => array(),
            'role' => 'trainer'
        );
        
        // Find team IDs if team names provided
        if (!empty($data['team_names'])) {
            global $wpdb;
            $teams_table = Club_Manager_Database::get_table_name('teams');
            $season = get_user_meta($user_id, 'cm_preferred_season', true) ?: '2024-2025';
            
            foreach ($data['team_names'] as $team_name) {
                $team_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM $teams_table WHERE name = %s AND season = %s",
                    $team_name,
                    $season
                ));
                
                if ($team_id) {
                    $trainer_to_invite['team_ids'][] = $team_id;
                }
            }
        }
        
        return array(
            'success' => true,
            'action' => 'created',
            'trainer_to_invite' => $trainer_to_invite
        );
    }
    
    /**
     * Assign trainer to teams.
     */
    private function assignTrainerToTeams($trainer_id, $team_names, $added_by) {
        global $wpdb;
        $teams_table = Club_Manager_Database::get_table_name('teams');
        $trainers_table = Club_Manager_Database::get_table_name('team_trainers');
        $season = get_user_meta($added_by, 'cm_preferred_season', true) ?: '2024-2025';
        
        foreach ($team_names as $team_name) {
            // Find team
            $team_id = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $teams_table WHERE name = %s AND season = %s",
                $team_name,
                $season
            ));
            
            if (!$team_id) {
                continue;
            }
            
            // Check if already assigned
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $trainers_table WHERE team_id = %d AND trainer_id = %d",
                $team_id,
                $trainer_id
            ));
            
            if (!$existing) {
                // Assign trainer to team
                $wpdb->insert(
                    $trainers_table,
                    array(
                        'team_id' => $team_id,
                        'trainer_id' => $trainer_id,
                        'role' => 'trainer',
                        'is_active' => 1,
                        'added_by' => $added_by,
                        'added_at' => current_time('mysql')
                    ),
                    array('%d', '%d', '%s', '%d', '%d', '%s')
                );
            }
        }
    }
}