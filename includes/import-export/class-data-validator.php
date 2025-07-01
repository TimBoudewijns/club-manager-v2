<?php

/**
 * Data validator for import operations.
 */
class Club_Manager_Data_Validator {
    
    private $options = array(
        'duplicateHandling' => 'skip',
        'validateEmails' => true,
        'dateFormat' => 'DD-MM-YYYY'
    );
    
    /**
     * Set validation options.
     */
    public function setOptions($options) {
        $this->options = array_merge($this->options, $options);
    }
    
    /**
     * Validate a single row of data based on type.
     * 
     * @param array $data The mapped data (field => value)
     * @param string $type Import type
     * @param int $row_index Row index for error reporting
     * @return array Validation result
     */
    public function validateRow($data, $type, $row_index = 0) {
        $errors = array();
        $validated_data = array();
        
        // Remove type suffixes for validation
        $base_type = str_replace(array('-with-players', '-with-assignments'), '', $type);
        
        switch ($base_type) {
            case 'teams':
                $result = $this->validateTeam($data, $row_index);
                break;
                
            case 'players':
                $result = $this->validatePlayer($data, $row_index);
                break;
                
            case 'trainers':
                $result = $this->validateTrainer($data, $row_index);
                break;
                
            default:
                return array(
                    'valid' => false,
                    'data' => $data,
                    'errors' => array(array(
                        'row' => $row_index + 1,
                        'field' => 'type',
                        'message' => 'Unknown import type: ' . $type
                    ))
                );
        }
        
        return $result;
    }
    
    /**
     * Validate team data.
     */
    private function validateTeam($data, $row_index) {
        $errors = array();
        $validated = array();
        
        // Validate team name
        if (empty($data['name'])) {
            $errors[] = array(
                'row' => $row_index + 1,
                'field' => 'name',
                'message' => 'Team name is required'
            );
        } else {
            $validated['name'] = sanitize_text_field($data['name']);
        }
        
        // Validate coach
        if (empty($data['coach'])) {
            $errors[] = array(
                'row' => $row_index + 1,
                'field' => 'coach',
                'message' => 'Coach name is required'
            );
        } else {
            $validated['coach'] = sanitize_text_field($data['coach']);
        }
        
        // Validate season
        if (empty($data['season'])) {
            $errors[] = array(
                'row' => $row_index + 1,
                'field' => 'season',
                'message' => 'Season is required'
            );
        } else {
            // Validate season format (e.g., 2024-2025)
            $season = sanitize_text_field($data['season']);
            if (!preg_match('/^\d{4}-\d{4}$/', $season)) {
                $errors[] = array(
                    'row' => $row_index + 1,
                    'field' => 'season',
                    'message' => 'Invalid season format. Use YYYY-YYYY (e.g., 2024-2025)'
                );
            } else {
                $validated['season'] = $season;
            }
        }
        
        // Check for duplicate if needed
        if ($this->options['duplicateHandling'] === 'skip' && empty($errors)) {
            global $wpdb;
            $teams_table = Club_Manager_Database::get_table_name('teams');
            
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $teams_table WHERE name = %s AND season = %s",
                $validated['name'],
                $validated['season']
            ));
            
            if ($existing) {
                $errors[] = array(
                    'row' => $row_index + 1,
                    'field' => 'name',
                    'message' => 'Team already exists for this season'
                );
            }
        }
        
        return array(
            'valid' => empty($errors),
            'data' => $validated,
            'errors' => $errors
        );
    }
    
    /**
     * Validate player data.
     */
    private function validatePlayer($data, $row_index) {
        $errors = array();
        $validated = array();
        
        // Validate first name
        if (empty($data['first_name'])) {
            $errors[] = array(
                'row' => $row_index + 1,
                'field' => 'first_name',
                'message' => 'First name is required'
            );
        } else {
            $validated['first_name'] = sanitize_text_field($data['first_name']);
        }
        
        // Validate last name
        if (empty($data['last_name'])) {
            $errors[] = array(
                'row' => $row_index + 1,
                'field' => 'last_name',
                'message' => 'Last name is required'
            );
        } else {
            $validated['last_name'] = sanitize_text_field($data['last_name']);
        }
        
        // Validate email
        if (empty($data['email'])) {
            $errors[] = array(
                'row' => $row_index + 1,
                'field' => 'email',
                'message' => 'Email is required'
            );
        } else {
            $email = sanitize_email($data['email']);
            if ($this->options['validateEmails'] && !is_email($email)) {
                $errors[] = array(
                    'row' => $row_index + 1,
                    'field' => 'email',
                    'message' => 'Invalid email address'
                );
            } else {
                $validated['email'] = $email;
            }
        }
        
        // Validate birth date
        if (empty($data['birth_date'])) {
            $errors[] = array(
                'row' => $row_index + 1,
                'field' => 'birth_date',
                'message' => 'Birth date is required'
            );
        } else {
            $date = $this->parseDate($data['birth_date']);
            if (!$date) {
                $errors[] = array(
                    'row' => $row_index + 1,
                    'field' => 'birth_date',
                    'message' => 'Invalid date format. Use ' . $this->options['dateFormat']
                );
            } else {
                $validated['birth_date'] = $date->format('Y-m-d');
            }
        }
        
        // Optional fields
        if (!empty($data['position'])) {
            $validated['position'] = sanitize_text_field($data['position']);
        }
        
        if (!empty($data['jersey_number'])) {
            $jersey = intval($data['jersey_number']);
            if ($jersey < 0 || $jersey > 999) {
                $errors[] = array(
                    'row' => $row_index + 1,
                    'field' => 'jersey_number',
                    'message' => 'Jersey number must be between 0 and 999'
                );
            } else {
                $validated['jersey_number'] = $jersey;
            }
        }
        
        if (!empty($data['team_name'])) {
            $validated['team_name'] = sanitize_text_field($data['team_name']);
        }
        
        // Check for duplicate if needed
        if ($this->options['duplicateHandling'] === 'skip' && empty($errors)) {
            global $wpdb;
            $players_table = Club_Manager_Database::get_table_name('players');
            
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $players_table WHERE email = %s",
                $validated['email']
            ));
            
            if ($existing) {
                $errors[] = array(
                    'row' => $row_index + 1,
                    'field' => 'email',
                    'message' => 'Player with this email already exists'
                );
            }
        }
        
        return array(
            'valid' => empty($errors),
            'data' => $validated,
            'errors' => $errors
        );
    }
    
    /**
     * Validate trainer data.
     */
    private function validateTrainer($data, $row_index) {
        $errors = array();
        $validated = array();
        
        // Validate email
        if (empty($data['email'])) {
            $errors[] = array(
                'row' => $row_index + 1,
                'field' => 'email',
                'message' => 'Email is required'
            );
        } else {
            $email = sanitize_email($data['email']);
            if ($this->options['validateEmails'] && !is_email($email)) {
                $errors[] = array(
                    'row' => $row_index + 1,
                    'field' => 'email',
                    'message' => 'Invalid email address'
                );
            } else {
                $validated['email'] = $email;
            }
        }
        
        // Validate team names (optional)
        if (!empty($data['team_names'])) {
            // Split by comma and clean
            $team_names = array_map('trim', explode(',', $data['team_names']));
            $team_names = array_filter($team_names); // Remove empty values
            
            if (!empty($team_names)) {
                $validated['team_names'] = $team_names;
            }
        }
        
        return array(
            'valid' => empty($errors),
            'data' => $validated,
            'errors' => $errors
        );
    }
    
    /**
     * Parse date based on configured format.
     */
    private function parseDate($date_string) {
        $date_string = trim($date_string);
        
        // Try different date formats
        $formats = array(
            'DD-MM-YYYY' => 'd-m-Y',
            'MM-DD-YYYY' => 'm-d-Y',
            'YYYY-MM-DD' => 'Y-m-d',
            'DD/MM/YYYY' => 'd/m/Y',
            'MM/DD/YYYY' => 'm/d/Y'
        );
        
        // First try the configured format
        $format = $formats[$this->options['dateFormat']] ?? 'd-m-Y';
        $date = DateTime::createFromFormat($format, $date_string);
        
        if ($date && $date->format($format) === $date_string) {
            return $date;
        }
        
        // Try other formats
        foreach ($formats as $format) {
            $date = DateTime::createFromFormat($format, $date_string);
            if ($date && $date->format($format) === $date_string) {
                return $date;
            }
        }
        
        return false;
    }
}