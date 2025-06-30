<?php

/**
 * Handle data import operations.
 */
class Club_Manager_Import_Handler {
    
    private $options = array();
    private $validator;
    private $errors = array();
    
    public function __construct() {
        $this->validator = new Club_Manager_Data_Validator();
    }
    
    /**
     * Set import options.
     */
    public function setOptions($options) {
        $this->options = wp_parse_args($options, array(
            'duplicateHandling' => 'skip',
            'sendInvitations' => true,
            'validateEmails' => true,
            'dateFormat' => 'DD-MM-YYYY'
        ));
        
        $this->validator->setOptions($this->options);
    }
    
    /**
     * Process a batch of rows.
     */
    public function processBatch($rows, $type, $mapping, $start_index, $user_id) {
        $results = array(
            'successful' => 0,
            'failed' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => array(),
            'trainers_to_invite' => array()
        );
        
        foreach ($rows as $index => $row) {
            $row_number = $start_index + $index + 1;
            
            try {
                // Validate row
                $validation = $this->validator->validateRow($row, $mapping, $type, $row_number);
                
                if (!$validation['valid']) {
                    $results['failed']++;
                    $results['errors'] = array_merge($results['errors'], $validation['errors']);
                    continue;
                }
                
                // Process based on type
                switch ($type) {
                    case 'teams':
                        $result = $this->importTeam($validation['data'], $user_id);
                        break;
                        
                    case 'players':
                        $result = $this->importPlayer($validation['data'], $user_id);
                        break;
                        
                    case 'teams-with-players':
                        $result = $this->importTeamWithPlayers($validation['data'], $user_id);
                        break;
                        
                    case 'trainers':
                        $result = $this->importTrainer($validation['data'], $user_id);
                        if ($result['success'] && $result['action'] === 'created' && $result['needs_invitation']) {
                            $results['trainers_to_invite'][] = $result['trainer_data'];
                        }
                        break;
                        
                    case 'trainers-with-assignments':
                        $result = $this->importTrainerWithAssignments($validation['data'], $user_id);
                        if ($result['success'] && $result['action'] === 'created' && $result['needs_invitation']) {
                            $results['trainers_to_invite'][] = $result['trainer_data'];
                        }
                        break;
                        
                    default:
                        throw new Exception('Invalid import type');
                }
                
                if ($result['success']) {
                    $results['successful']++;
                    $results[$result['action']]++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = array(
                        'row' => $row_number,
                        'message' => $result['error']
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
     * Import a team.
     */
    private function importTeam($data, $user_id) {
        global $wpdb;
        
        // Check for duplicates
        $teams_table = Club_Manager_Database::get_table_name('teams');
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $teams_table WHERE name = %s AND season = %s AND created_by = %d",
            $data['name'], $data['season'], $user_id
        ));
        
        if ($existing) {
            switch ($this->options['duplicateHandling']) {
                case 'skip':
                    return array('success' => true, 'action' => 'skipped');
                    
                case 'update':
                    $team_model = new Club_Manager_Team_Model();
                    $updated = $team_model->update($existing->id, array(
                        'coach' => $data['coach']
                    ));
                    return array(
                        'success' => $updated !== false,
                        'action' => 'updated',
                        'error' => $updated === false ? 'Failed to update team' : null
                    );
                    
                case 'create':
                    // Continue to create new team with modified name
                    $data['name'] = $this->generateUniqueName($data['name'], $teams_table, 'name', array(
                        'season' => $data['season'],
                        'created_by' => $user_id
                    ));
                    break;
            }
        }
        
        // Create team
        $team_model = new Club_Manager_Team_Model();
        $team_id = $team_model->create(array(
            'name' => $data['name'],
            'coach' => $data['coach'],
            'season' => $data['season'],
            'created_by' => $user_id
        ));
        
        return array(
            'success' => $team_id !== false,
            'action' => 'created',
            'team_id' => $team_id,
            'error' => $team_id === false ? 'Failed to create team' : null
        );
    }
    
    /**
     * Import a player.
     */
    private function importPlayer($data, $user_id) {
        global $wpdb;
        
        // Check for duplicates
        $players_table = Club_Manager_Database::get_table_name('players');
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $players_table WHERE email = %s AND created_by = %d",
            $data['email'], $user_id
        ));
        
        if ($existing) {
            switch ($this->options['duplicateHandling']) {
                case 'skip':
                    // If team is specified, add player to team
                    if (!empty($data['team_name'])) {
                        $this->addPlayerToTeam($existing->id, $data, $user_id);
                    }
                    return array('success' => true, 'action' => 'skipped');
                    
                case 'update':
                    // Update player info
                    $updated = $wpdb->update(
                        $players_table,
                        array(
                            'first_name' => $data['first_name'],
                            'last_name' => $data['last_name'],
                            'birth_date' => $data['birth_date']
                        ),
                        array('id' => $existing->id),
                        array('%s', '%s', '%s'),
                        array('%d')
                    );
                    
                    // Add to team if specified
                    if (!empty($data['team_name'])) {
                        $this->addPlayerToTeam($existing->id, $data, $user_id);
                    }
                    
                    return array(
                        'success' => $updated !== false,
                        'action' => 'updated',
                        'error' => $updated === false ? 'Failed to update player' : null
                    );
                    
                case 'create':
                    // Create new player with modified email
                    $base_email = $data['email'];
                    $counter = 1;
                    while ($wpdb->get_var($wpdb->prepare(
                        "SELECT id FROM $players_table WHERE email = %s AND created_by = %d",
                        $data['email'], $user_id
                    ))) {
                        $parts = explode('@', $base_email);
                        $data['email'] = $parts[0] . '+' . $counter . '@' . $parts[1];
                        $counter++;
                    }
                    break;
            }
        }
        
        // Create player
        $player_model = new Club_Manager_Player_Model();
        
        // If team is specified, create player with team
        if (!empty($data['team_name'])) {
            // Find team
            $teams_table = Club_Manager_Database::get_table_name('teams');
            $team = $wpdb->get_row($wpdb->prepare(
                "SELECT id, season FROM $teams_table WHERE name = %s AND created_by = %d",
                $data['team_name'], $user_id
            ));
            
            if (!$team) {
                return array(
                    'success' => false,
                    'action' => 'failed',
                    'error' => 'Team not found: ' . $data['team_name']
                );
            }
            
            $player_id = $player_model->create_with_team(
                array(
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'email' => $data['email'],
                    'birth_date' => $data['birth_date']
                ),
                $team->id,
                array(
                    'position' => $data['position'] ?? null,
                    'jersey_number' => $data['jersey_number'] ?? null,
                    'season' => $team->season
                ),
                $user_id
            );
        } else {
            // Create player without team
            $result = $wpdb->insert(
                $players_table,
                array(
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'email' => $data['email'],
                    'birth_date' => $data['birth_date'],
                    'created_by' => $user_id,
                    'created_at' => current_time('mysql')
                ),
                array('%s', '%s', '%s', '%s', '%d', '%s')
            );
            
            $player_id = $result ? $wpdb->insert_id : false;
        }
        
        return array(
            'success' => $player_id !== false,
            'action' => 'created',
            'player_id' => $player_id,
            'error' => $player_id === false ? 'Failed to create player' : null
        );
    }
    
    /**
     * Import team with players.
     */
    private function importTeamWithPlayers($data, $user_id) {
        // First import the team
        $team_result = $this->importTeam(array(
            'name' => $data['name'],
            'coach' => $data['coach'],
            'season' => $data['season']
        ), $user_id);
        
        if (!$team_result['success'] || $team_result['action'] === 'skipped') {
            return $team_result;
        }
        
        // Import players if team was created or updated
        if (!empty($data['players']) && is_array($data['players'])) {
            foreach ($data['players'] as $player_data) {
                $player_data['team_name'] = $data['name'];
                $this->importPlayer($player_data, $user_id);
            }
        }
        
        return $team_result;
    }
    
    /**
     * Import trainer.
     */
    private function importTrainer($data, $user_id) {
        global $wpdb;
        
        // Check if user exists
        $user = get_user_by('email', $data['email']);
        
        if ($user) {
            // User exists
            switch ($this->options['duplicateHandling']) {
                case 'skip':
                    return array('success' => true, 'action' => 'skipped');
                    
                case 'update':
                    // Update user meta
                    update_user_meta($user->ID, 'first_name', $data['first_name']);
                    update_user_meta($user->ID, 'last_name', $data['last_name']);
                    
                    return array('success' => true, 'action' => 'updated');
                    
                case 'create':
                    // Can't create duplicate user with same email
                    return array('success' => true, 'action' => 'skipped');
            }
        }
        
        // User doesn't exist - prepare for invitation
        return array(
            'success' => true,
            'action' => 'created',
            'needs_invitation' => true,
            'trainer_data' => array(
                'email' => $data['email'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'role' => $data['role'] ?? 'trainer',
                'team_ids' => array()
            )
        );
    }
    
    /**
     * Import trainer with team assignments.
     */
    private function importTrainerWithAssignments($data, $user_id) {
        // First handle trainer
        $trainer_result = $this->importTrainer($data, $user_id);
        
        if (!$trainer_result['success']) {
            return $trainer_result;
        }
        
        // Parse team assignments
        if (!empty($data['team_names'])) {
            $team_names = array_map('trim', explode(',', $data['team_names']));
            $team_ids = array();
            
            global $wpdb;
            $teams_table = Club_Manager_Database::get_table_name('teams');
            
            foreach ($team_names as $team_name) {
                $team = $wpdb->get_row($wpdb->prepare(
                    "SELECT id FROM $teams_table WHERE name = %s AND created_by = %d",
                    $team_name, $user_id
                ));
                
                if ($team) {
                    $team_ids[] = $team->id;
                }
            }
            
            if ($trainer_result['needs_invitation']) {
                $trainer_result['trainer_data']['team_ids'] = $team_ids;
            } else {
                // User exists, add to teams
                $user = get_user_by('email', $data['email']);
                if ($user && !empty($team_ids)) {
                    $this->assignTrainerToTeams($user->ID, $team_ids, $data['role'] ?? 'trainer', $user_id);
                }
            }
        }
        
        return $trainer_result;
    }
    
    /**
     * Add player to team.
     */
    private function addPlayerToTeam($player_id, $data, $user_id) {
        global $wpdb;
        
        // Find team
        $teams_table = Club_Manager_Database::get_table_name('teams');
        $team = $wpdb->get_row($wpdb->prepare(
            "SELECT id, season FROM $teams_table WHERE name = %s AND created_by = %d",
            $data['team_name'], $user_id
        ));
        
        if (!$team) {
            return false;
        }
        
        // Check if already in team
        $team_players_table = Club_Manager_Database::get_table_name('team_players');
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $team_players_table WHERE team_id = %d AND player_id = %d AND season = %s",
            $team->id, $player_id, $team->season
        ));
        
        if ($existing) {
            // Update position/jersey if provided
            if (!empty($data['position']) || !empty($data['jersey_number'])) {
                $wpdb->update(
                    $team_players_table,
                    array(
                        'position' => $data['position'] ?? null,
                        'jersey_number' => $data['jersey_number'] ?? null
                    ),
                    array('id' => $existing),
                    array('%s', '%d'),
                    array('%d')
                );
            }
            return true;
        }
        
        // Add to team
        return $wpdb->insert(
            $team_players_table,
            array(
                'team_id' => $team->id,
                'player_id' => $player_id,
                'position' => $data['position'] ?? null,
                'jersey_number' => $data['jersey_number'] ?? null,
                'season' => $team->season
            ),
            array('%d', '%d', '%s', '%d', '%s')
        );
    }
    
    /**
     * Assign trainer to teams.
     */
    private function assignTrainerToTeams($trainer_id, $team_ids, $role, $added_by) {
        global $wpdb;
        $trainers_table = Club_Manager_Database::get_table_name('team_trainers');
        
        foreach ($team_ids as $team_id) {
            // Check if already assigned
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $trainers_table WHERE team_id = %d AND trainer_id = %d",
                $team_id, $trainer_id
            ));
            
            if (!$existing) {
                $wpdb->insert(
                    $trainers_table,
                    array(
                        'team_id' => $team_id,
                        'trainer_id' => $trainer_id,
                        'role' => $role,
                        'is_active' => 1,
                        'added_by' => $added_by,
                        'added_at' => current_time('mysql')
                    ),
                    array('%d', '%d', '%s', '%d', '%d', '%s')
                );
            }
        }
    }
    
    /**
     * Generate unique name.
     */
    private function generateUniqueName($base_name, $table, $column, $conditions = array()) {
        global $wpdb;
        
        $name = $base_name;
        $counter = 1;
        
        while (true) {
            $query = "SELECT id FROM $table WHERE $column = %s";
            $params = array($name);
            
            foreach ($conditions as $key => $value) {
                $query .= " AND $key = %s";
                $params[] = $value;
            }
            
            $exists = $wpdb->get_var($wpdb->prepare($query, ...$params));
            
            if (!$exists) {
                return $name;
            }
            
            $counter++;
            $name = $base_name . ' (' . $counter . ')';
        }
    }
}