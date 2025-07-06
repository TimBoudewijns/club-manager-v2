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
     * @param array $rows Pre-mapped rows with field names as keys
     * @param string $type Import type
     * @param int $start_index Starting row index for error reporting
     * @param int $user_id User performing the import
     * @return array Results array
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

        $base_type = str_replace(array('-with-players', '-with-assignments'), '', $type);
        
        foreach ($rows as $index => $row) {
            $row_number = $start_index + $index + 1;
            
            try {
                // Row is already mapped, so we can use it directly
                switch ($base_type) {
                    case 'teams':
                        $result = $this->processTeam($row, $user_id);
                        break;
                        
                    case 'players':
                        $result = $this->processPlayer($row, $user_id);
                        break;
                        
                    case 'trainers':
                        $result = $this->processTrainer($row, $user_id);
                        if ($result['success'] && !empty($result['trainer_to_invite'])) {
                            $results['trainers_to_invite'][] = $result['trainer_to_invite'];
                        }
                        break;
                        
                    default:
                        throw new Exception('Unknown import type: ' . $type);
                }
                
                if ($result['success']) {
                    $results['successful']++;
                    if ($result['action'] === 'created') $results['created']++;
                    elseif ($result['action'] === 'updated') $results['updated']++;
                    elseif ($result['action'] === 'skipped') $results['skipped']++;
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
    private function processTeam($data, $user_id) {
        global $wpdb;
        $teams_table = Club_Manager_Database::get_table_name('teams');

        // Ensure required data is present
        if (empty($data['name']) || empty($data['coach']) || empty($data['season'])) {
            return array('success' => false, 'error' => 'Missing required fields: Team Name, Coach, and Season.');
        }

        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $teams_table WHERE name = %s AND season = %s AND created_by = %d",
            $data['name'], $data['season'], $user_id
        ));

        if ($existing) {
            if ($this->options['duplicateHandling'] === 'update') {
                $updated = $wpdb->update($teams_table, array('coach' => $data['coach']), array('id' => $existing->id));
                if ($updated === false) {
                    return array('success' => false, 'error' => 'Failed to update existing team.');
                }
                return array('success' => true, 'action' => 'updated', 'id' => $existing->id);
            }
            return array('success' => true, 'action' => 'skipped', 'id' => $existing->id);
        }

        $inserted = $wpdb->insert($teams_table, array(
            'name'       => $data['name'],
            'coach'      => $data['coach'],
            'season'     => $data['season'],
            'created_by' => $user_id,
            'created_at' => current_time('mysql')
        ));

        if ($inserted === false) {
            return array('success' => false, 'error' => 'Database error: Could not insert team. Last error: ' . $wpdb->last_error);
        }

        return array('success' => true, 'action' => 'created', 'id' => $wpdb->insert_id);
    }
    
    /**
     * Process player import.
     */
    private function processPlayer($data, $user_id) {
        global $wpdb;
        $players_table = Club_Manager_Database::get_table_name('players');
        $team_players_table = Club_Manager_Database::get_table_name('team_players');
        
        $existing_player = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $players_table WHERE email = %s AND created_by = %d",
            $data['email'],
            $user_id
        ));
        $player_id = $existing_player ? $existing_player->id : null;
        $action = 'created';

        if ($player_id) {
            if ($this->options['duplicateHandling'] === 'skip') {
                // Player exists, but we might still need to add them to a team.
                // Don't return here.
            } elseif ($this->options['duplicateHandling'] === 'update') {
                $update_data = array(
                    'first_name' => $data['first_name'],
                    'last_name'  => $data['last_name'],
                );
                if (!empty($data['birth_date'])) {
                    $update_data['birth_date'] = $data['birth_date'];
                }
                $wpdb->update($players_table, $update_data, array('id' => $player_id));
                $action = 'updated';
            }
        } else {
            // Create new player if they don't exist
            $inserted = $wpdb->insert($players_table, array(
                'first_name' => $data['first_name'],
                'last_name'  => $data['last_name'],
                'birth_date' => $data['birth_date'],
                'email'      => $data['email'],
                'created_by' => $user_id,
                'created_at' => current_time('mysql')
            ));
            if ($inserted === false) {
                return array('success' => false, 'error' => 'Could not create new player.');
            }
            $player_id = $wpdb->insert_id;
        }

        if ($player_id && !empty($data['team_name'])) {
            $teams_table = Club_Manager_Database::get_table_name('teams');
            $season = !empty($data['season']) ? $data['season'] : (get_user_meta($user_id, 'cm_preferred_season', true) ?: '2024-2025');

            $team_id = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $teams_table WHERE name = %s AND season = %s AND created_by = %d",
                $data['team_name'], $season, $user_id
            ));

            // If team does not exist, create it
            if (!$team_id) {
                $wpdb->insert($teams_table, array(
                    'name' => $data['team_name'],
                    'coach' => 'N/A', // Set default coach
                    'season' => $season,
                    'created_by' => $user_id,
                    'created_at' => current_time('mysql')
                ));
                $team_id = $wpdb->insert_id;
            }

            if ($team_id) {
                $wpdb->replace($team_players_table, array(
                    'team_id' => $team_id,
                    'player_id' => $player_id,
                    'season' => $season,
                    'position' => $data['position'] ?? null,
                    'jersey_number' => $data['jersey_number'] ?? null,
                    'notes' => $data['notes'] ?? null,
                ));
            }
        }

        return array('success' => true, 'action' => $action, 'id' => $player_id);
    }

    /**
     * Process trainer import.
     */
    private function processTrainer($data, $user_id) {
        $user = get_user_by('email', $data['email']);
        
        if ($user) {
            if (!empty($data['team_names'])) {
                $this->assignTrainerToTeams($user->ID, $data['team_names'], $user_id);
            }
            return array('success' => true, 'action' => 'skipped', 'id' => $user->ID);
        }
        
        $trainer_to_invite = array(
            'email' => $data['email'],
            'team_ids' => array(),
            'role' => 'trainer'
        );
        
        if (!empty($data['team_names'])) {
            global $wpdb;
            $teams_table = Club_Manager_Database::get_table_name('teams');
            $season = get_user_meta($user_id, 'cm_preferred_season', true) ?: '2024-2025';
            
            foreach ($data['team_names'] as $team_name) {
                $team_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM $teams_table WHERE name = %s AND season = %s AND created_by = %d",
                    $team_name, $season, $user_id
                ));

                // If team does not exist, create it
                if (!$team_id) {
                    $wpdb->insert($teams_table, array(
                        'name' => $team_name,
                        'coach' => 'N/A',
                        'season' => $season,
                        'created_by' => $user_id,
                        'created_at' => current_time('mysql')
                    ));
                    $team_id = $wpdb->insert_id;
                }

                if ($team_id) {
                    $trainer_to_invite['team_ids'][] = $team_id;
                }
            }
        }
        
        return array('success' => true, 'action' => 'created', 'trainer_to_invite' => $trainer_to_invite);
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
            $team_id = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $teams_table WHERE name = %s AND season = %s AND created_by = %d",
                $team_name, $season, $added_by
            ));

            // If team does not exist, create it
            if (!$team_id) {
                $wpdb->insert($teams_table, array(
                    'name' => $team_name,
                    'coach' => 'N/A',
                    'season' => $season,
                    'created_by' => $added_by,
                    'created_at' => current_time('mysql')
                ));
                $team_id = $wpdb->insert_id;
            }
            
            if ($team_id) {
                $wpdb->replace($trainers_table, array(
                    'team_id' => $team_id,
                    'trainer_id' => $trainer_id,
                    'role' => 'trainer',
                    'is_active' => 1,
                    'added_by' => $added_by,
                    'added_at' => current_time('mysql')
                ));
            }
        }
    }
}