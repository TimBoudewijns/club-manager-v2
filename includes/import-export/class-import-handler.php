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
                // Debug logging
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Import Handler - Processing row ' . $row_number . ': ' . json_encode($row));
                }
                
                // Row is already mapped, so we can use it directly
                switch ($type) {
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
                    elseif ($result['action'] === 'team_added') $results['updated']++; // Count team additions as updates
                    elseif ($result['action'] === 'existing_player') $results['skipped']++; // Existing player, no changes
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

        // Check for existing team
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $teams_table WHERE name = %s AND season = %s AND created_by = %d",
            $data['name'], $data['season'], $user_id
        ));

        if ($existing) {
            if ($this->options['duplicateHandling'] === 'update') {
                $updated = $wpdb->update(
                    $teams_table, 
                    array('coach' => $data['coach']), 
                    array('id' => $existing->id),
                    array('%s'),
                    array('%d')
                );
                
                if ($updated === false) {
                    return array('success' => false, 'error' => 'Failed to update existing team: ' . $wpdb->last_error);
                }
                return array('success' => true, 'action' => 'updated', 'id' => $existing->id);
            } elseif ($this->options['duplicateHandling'] === 'skip') {
                return array('success' => true, 'action' => 'skipped', 'id' => $existing->id);
            }
            // For 'create', continue to create a new record
        }

        // Create new team
        $inserted = $wpdb->insert($teams_table, array(
            'name' => $data['name'],
            'coach' => $data['coach'],
            'season' => $data['season'],
            'created_by' => $user_id,
            'created_at' => current_time('mysql')
        ), array('%s', '%s', '%s', '%d', '%s'));

        if ($inserted === false) {
            return array('success' => false, 'error' => 'Database error: ' . $wpdb->last_error);
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
        $teams_table = Club_Manager_Database::get_table_name('teams');
        
        // Ensure required fields
        if (empty($data['first_name']) || empty($data['last_name']) || empty($data['email'])) {
            return array('success' => false, 'error' => 'Missing required fields: First Name, Last Name, or Email.');
        }
        
        // Check for existing player
        $existing_player = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $players_table WHERE email = %s AND created_by = %d",
            $data['email'], $user_id
        ));
        
        $player_id = $existing_player ? $existing_player->id : null;
        $action = 'created';
        
        // Debug logging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            if ($existing_player) {
                error_log('Club Manager Import: Found existing player ID ' . $player_id . ' for email ' . $data['email']);
            } else {
                error_log('Club Manager Import: Creating new player for email ' . $data['email']);
            }
        }

        if ($player_id) {
            // Player exists - handle player info update based on duplicate handling
            if ($this->options['duplicateHandling'] === 'update') {
                $update_data = array(
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                );
                if (!empty($data['birth_date'])) {
                    // Parse date if necessary
                    $date = $this->parseDate($data['birth_date']);
                    if ($date) {
                        $update_data['birth_date'] = $date->format('Y-m-d');
                    }
                }
                $wpdb->update($players_table, $update_data, array('id' => $player_id));
                $action = 'updated';
            } else {
                // For 'skip' or 'create' options, don't update player info
                // Action will be determined by team assignment logic below
                $action = 'existing_player';
            }
        } else {
            // Create new player
            $insert_data = array(
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'created_by' => $user_id,
                'created_at' => current_time('mysql')
            );
            
            if (!empty($data['birth_date'])) {
                $date = $this->parseDate($data['birth_date']);
                if ($date) {
                    $insert_data['birth_date'] = $date->format('Y-m-d');
                }
            }
            
            $inserted = $wpdb->insert($players_table, $insert_data);
            if ($inserted === false) {
                return array('success' => false, 'error' => 'Could not create new player: ' . $wpdb->last_error);
            }
            $player_id = $wpdb->insert_id;
        }

        // Handle team assignment if team name is provided - ALWAYS execute this for existing players too
        if ($player_id && !empty($data['team_name'])) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Club Manager Import: Processing team assignment for player ID ' . $player_id . ' (existing: ' . ($existing_player ? 'YES' : 'NO') . ') to team "' . $data['team_name'] . '", current action: ' . $action);
            }
            $season = $this->getCurrentSeason($user_id);

            // Find or create team
            $team_id = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $teams_table WHERE name = %s AND season = %s AND created_by = %d",
                $data['team_name'], $season, $user_id
            ));

            // If team does not exist, create it
            if (!$team_id) {
                $wpdb->insert($teams_table, array(
                    'name' => $data['team_name'],
                    'coach' => 'TBD', // Default coach
                    'season' => $season,
                    'created_by' => $user_id,
                    'created_at' => current_time('mysql')
                ));
                $team_id = $wpdb->insert_id;
            }

            if ($team_id) {
                // Check if player is already in this specific team
                $existing_assignment = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM $team_players_table WHERE team_id = %d AND player_id = %d AND season = %s",
                    $team_id, $player_id, $season
                ));
                
                if (!$existing_assignment) {
                    // Add player to this team (they can be in multiple teams)
                    $inserted = $wpdb->insert($team_players_table, array(
                        'team_id' => $team_id,
                        'player_id' => $player_id,
                        'season' => $season,
                        'position' => $data['position'] ?? null,
                        'jersey_number' => !empty($data['jersey_number']) ? intval($data['jersey_number']) : null,
                        'notes' => $data['notes'] ?? null,
                    ));
                    
                    // Debug logging
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('Club Manager Import: Team assignment result for player ID ' . $player_id . ': ' . ($inserted !== false ? 'SUCCESS' : 'FAILED'));
                        if ($inserted === false) {
                            error_log('Club Manager Import: Database error: ' . $wpdb->last_error);
                        }
                    }
                    
                    // Update action based on what happened - only if insertion was successful
                    if ($inserted !== false) {
                        if ($existing_player) {
                            // Existing player was added to new team
                            if ($action === 'updated') {
                                // Player info was updated AND added to team, keep as 'updated'
                            } else {
                                // Player info was not updated, but added to team
                                $action = 'team_added';
                            }
                        }
                        // If new player, action remains 'created'
                        
                        if (defined('WP_DEBUG') && WP_DEBUG) {
                            error_log('Club Manager Import: Set action to "' . $action . '" for player ID ' . $player_id);
                        }
                    } else {
                        // Team assignment failed
                        return array('success' => false, 'error' => 'Failed to assign player to team: ' . $wpdb->last_error);
                    }
                    
                } else {
                    // Player already in this team
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('Club Manager Import: Player ID ' . $player_id . ' already in team "' . $data['team_name'] . '"');
                    }
                    
                    // Update team-specific info if we have update permission
                    if ($existing_player && $this->options['duplicateHandling'] === 'update') {
                        $team_update_data = array();
                        if (!empty($data['position'])) {
                            $team_update_data['position'] = $data['position'];
                        }
                        if (!empty($data['jersey_number'])) {
                            $team_update_data['jersey_number'] = intval($data['jersey_number']);
                        }
                        if (!empty($data['notes'])) {
                            $team_update_data['notes'] = $data['notes'];
                        }
                        
                        if (!empty($team_update_data)) {
                            $wpdb->update($team_players_table, $team_update_data, array('id' => $existing_assignment));
                            // Keep action as 'updated' since we updated team info
                        } else {
                            // No team info to update, mark as skipped
                            $action = 'skipped';
                        }
                    } else {
                        // Player already in team and no updates allowed, mark as skipped
                        $action = 'skipped';
                    }
                }
            }
        } else {
            // No team assignment needed or no team name provided
            if ($existing_player && $action === 'existing_player') {
                // Existing player with no team changes, mark as skipped
                $action = 'skipped';
            }
        }
        
        // Additional debug logging to track the final action
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Club Manager Import: Final action determined for player ID ' . $player_id . ': ' . $action . ' (existing player: ' . ($existing_player ? 'YES' : 'NO') . ')');
        }

        // Final debug log
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Club Manager Import: Final action for player ID ' . $player_id . ': ' . $action);
        }

        return array('success' => true, 'action' => $action, 'id' => $player_id);
    }

    /**
     * Process trainer import - FIXED VERSION.
     */
    private function processTrainer($data, $user_id) {
        global $wpdb;
        
        // Check if email exists
        if (empty($data['email'])) {
            return array('success' => false, 'error' => 'Email is required for trainer import.');
        }
        
        // STAP 1: Check of gebruiker al bestaat
        $user = get_user_by('email', $data['email']);
        
        if ($user) {
            // User exists, assign to teams if specified
            if (!empty($data['team_names'])) {
                $team_names = $this->parseTeamNames($data['team_names']);
                $this->assignTrainerToTeams($user->ID, $team_names, $user_id);
            }
            return array('success' => true, 'action' => 'updated', 'id' => $user->ID, 'reason' => 'User already exists, updated team assignments');
        }
        
        // STAP 2: Check of er al een pending invitation is (WC Teams of eigen tabel)
        $existing_invitation = $this->checkExistingTrainerInvitation($data['email']);
        
        if ($existing_invitation) {
            // Log for debugging
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Club Manager Import: Skipping trainer invitation for ' . $data['email'] . ' - invitation already exists');
            }
            
            // Er is al een uitnodiging
            if ($this->options['duplicateHandling'] === 'skip') {
                return array('success' => true, 'action' => 'skipped', 'reason' => 'Invitation already sent');
            }
            // Als niet skip, dan kunnen we update doen of nieuwe sturen (maar voor nu skippen we toch)
            return array('success' => true, 'action' => 'skipped', 'reason' => 'Invitation already exists');
        }
        
        // STAP 3: Geen bestaande user, geen pending invitation (of we willen update/create)
        // Prepare trainer for invitation
        $trainer_to_invite = array(
            'email' => $data['email'],
            'team_ids' => array(),
            'role' => 'trainer'
        );
        
        if (!empty($data['team_names'])) {
            $teams_table = Club_Manager_Database::get_table_name('teams');
            $season = $this->getCurrentSeason($user_id);
            
            $team_names = $this->parseTeamNames($data['team_names']);
            
            foreach ($team_names as $team_name) {
                $team_name = trim($team_name);
                if (empty($team_name)) continue;
                
                $team_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM $teams_table WHERE name = %s AND season = %s AND created_by = %d",
                    $team_name, $season, $user_id
                ));

                // If team does not exist, create it
                if (!$team_id) {
                    $wpdb->insert($teams_table, array(
                        'name' => $team_name,
                        'coach' => 'TBD',
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
     * Parse team names from string - UPDATED to use semicolon.
     */
    private function parseTeamNames($team_names_string) {
        if (is_array($team_names_string)) {
            return $team_names_string;
        }
        
        $team_names = array();
        
        // Use semicolon as primary separator
        if (strpos($team_names_string, ';') !== false) {
            $team_names = explode(';', $team_names_string);
        }
        // If no semicolon, treat as single team
        else {
            $team_names = array($team_names_string);
        }
        
        // Clean up team names
        return array_map('trim', array_filter($team_names));
    }
    
    /**
     * Assign trainer to teams.
     */
    private function assignTrainerToTeams($trainer_id, $team_names, $added_by) {
        global $wpdb;
        $teams_table = Club_Manager_Database::get_table_name('teams');
        $trainers_table = Club_Manager_Database::get_table_name('team_trainers');
        $season = $this->getCurrentSeason($added_by);
        
        foreach ($team_names as $team_name) {
            $team_name = trim($team_name);
            if (empty($team_name)) continue;
            
            $team_id = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $teams_table WHERE name = %s AND season = %s AND created_by = %d",
                $team_name, $season, $added_by
            ));

            // If team does not exist, create it
            if (!$team_id) {
                $wpdb->insert($teams_table, array(
                    'name' => $team_name,
                    'coach' => 'TBD',
                    'season' => $season,
                    'created_by' => $added_by,
                    'created_at' => current_time('mysql')
                ));
                $team_id = $wpdb->insert_id;
            }
            
            if ($team_id) {
                // Check if already assigned
                $existing = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM $trainers_table WHERE team_id = %d AND trainer_id = %d",
                    $team_id, $trainer_id
                ));
                
                if (!$existing) {
                    $wpdb->insert($trainers_table, array(
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
    
    /**
     * Get current season for user.
     */
    private function getCurrentSeason($user_id) {
        $season = get_user_meta($user_id, 'cm_preferred_season', true);
        return $season ?: '2024-2025';
    }
    
    /**
     * Parse date from various formats.
     */
    private function parseDate($date_string) {
        $date_string = trim($date_string);
        
        // Try different date formats
        $formats = array(
            'd-m-Y', 'm-d-Y', 'Y-m-d',
            'd/m/Y', 'm/d/Y', 'Y/m/d',
            'd.m.Y', 'm.d.Y', 'Y.m.d'
        );
        
        foreach ($formats as $format) {
            $date = DateTime::createFromFormat($format, $date_string);
            if ($date && $date->format($format) === $date_string) {
                return $date;
            }
        }
        
        // Try strtotime as fallback
        $timestamp = strtotime($date_string);
        if ($timestamp) {
            $date = new DateTime();
            $date->setTimestamp($timestamp);
            return $date;
        }
        
        return false;
    }
    
    /**
     * Check if a trainer invitation already exists for this email.
     * 
     * @param string $email Email address to check
     * @return bool True if invitation exists, false otherwise
     */
    private function checkExistingTrainerInvitation($email) {
        global $wpdb;
        
        // Debug logging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Club Manager Import: Checking existing invitation for ' . $email);
        }
        
        // Check for existing WC Teams invitations
        // Note: WC Teams uses specific status 'wcmti-pending' for pending invitations
        $invitation_id = $wpdb->get_var($wpdb->prepare(
            "SELECT p.ID 
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm_email ON p.ID = pm_email.post_id 
             WHERE p.post_type = 'wc_team_invitation'
             AND p.post_status IN ('wcmti-pending', 'publish', 'pending')
             AND (
                 (pm_email.meta_key = '_email' AND pm_email.meta_value = %s) OR
                 (pm_email.meta_key = '_recipient_email' AND pm_email.meta_value = %s) OR
                 (pm_email.meta_key = '_invitation_email' AND pm_email.meta_value = %s) OR
                 p.post_title = %s
             )
             LIMIT 1",
            $email,
            $email,
            $email,
            $email
        ));
        
        if ($invitation_id) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Club Manager Import: Found existing WC Teams invitation ID: ' . $invitation_id . ' for ' . $email);
            }
            return true;
        }
        
        // Also check our pending trainer assignments table as backup
        $pending_table = $wpdb->prefix . 'dfdcm_pending_trainer_assignments';
        
        // Check if table exists first
        if ($wpdb->get_var("SHOW TABLES LIKE '$pending_table'") === $pending_table) {
            $pending_invitation = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$pending_table} WHERE email = %s",
                $email
            ));
            
            if ($pending_invitation > 0) {
                return true;
            }
        }
        
        return false;
    }
}