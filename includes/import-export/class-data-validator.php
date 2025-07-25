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
     * @param array $data The mapped data (field => value)
     * @param string $type Import type
     * @param int $row_index Row index for error reporting
     * @return array Validation result
     */
    public function validateRow($data, $type, $row_index = 0) {
        // Trim whitespace from all data fields
        $data = array_map(function($value) {
            return is_string($value) ? trim($value) : $value;
        }, $data);

        $errors = array();
        $validated_data = array();
        
        switch ($type) {
            case 'teams':
                $result = $this->validateTeam($data, $row_index);
                break;
                
            case 'players':
                $result = $this->validatePlayer($data, $row_index);
                break;
                
            case 'trainers':
                $result = $this->validateTrainer($data, $row_index);
                break;
                
            case 'teams-with-players':
                $result = $this->validateTeamWithPlayer($data, $row_index);
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
            $errors[] = array('row' => $row_index + 1, 'field' => 'name', 'message' => 'Team name is required');
        } else {
            $validated['name'] = sanitize_text_field($data['name']);
        }
        
        // Validate coach
        if (empty($data['coach'])) {
            $errors[] = array('row' => $row_index + 1, 'field' => 'coach', 'message' => 'Coach name is required');
        } else {
            $validated['coach'] = sanitize_text_field($data['coach']);
        }
        
        // Validate season
        if (empty($data['season'])) {
            $errors[] = array('row' => $row_index + 1, 'field' => 'season', 'message' => 'Season is required');
        } else {
            $season = sanitize_text_field($data['season']);
            if (!preg_match('/^\d{4}-\d{4}$/', $season)) {
                $errors[] = array('row' => $row_index + 1, 'field' => 'season', 'message' => 'Invalid season format. Use YYYY-YYYY (e.g., 2024-2025)');
            } else {
                $validated['season'] = $season;
            }
        }
        
        // Check for duplicate if creating new records
        if ($this->options['duplicateHandling'] !== 'update' && empty($errors)) {
            global $wpdb;
            $teams_table = Club_Manager_Database::get_table_name('teams');
            
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $teams_table WHERE name = %s AND season = %s AND created_by = %d",
                $validated['name'], $validated['season'], get_current_user_id()
            ));
            
            if ($existing && $this->options['duplicateHandling'] === 'skip') {
                $errors[] = array('row' => $row_index + 1, 'field' => 'name', 'message' => 'Team already exists and will be skipped');
            }
        }
        
        return array(
            'valid' => empty($errors),
            'data' => array_merge($data, $validated),
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
            $errors[] = array('row' => $row_index + 1, 'field' => 'first_name', 'message' => 'First name is required');
        } else {
            $validated['first_name'] = sanitize_text_field($data['first_name']);
        }
        
        // Validate last name
        if (empty($data['last_name'])) {
            $errors[] = array('row' => $row_index + 1, 'field' => 'last_name', 'message' => 'Last name is required');
        } else {
            $validated['last_name'] = sanitize_text_field($data['last_name']);
        }
        
        // Validate email
        if (empty($data['email'])) {
            $errors[] = array('row' => $row_index + 1, 'field' => 'email', 'message' => 'Email is required');
        } else {
            $email = sanitize_email($data['email']);
            if ($this->options['validateEmails'] && !is_email($email)) {
                $errors[] = array('row' => $row_index + 1, 'field' => 'email', 'message' => 'Invalid email address');
            } else {
                $validated['email'] = $email;
            }
        }
        
        // Validate birth date
        if (!empty($data['birth_date'])) {
            $date = $this->parseDate($data['birth_date']);
            if (!$date) {
                $errors[] = array('row' => $row_index + 1, 'field' => 'birth_date', 'message' => 'Invalid date format. Use DD-MM-YYYY');
            } else {
                $validated['birth_date'] = $date->format('Y-m-d');
            }
        }
        
        // Optional fields
        $validated['position'] = !empty($data['position']) ? sanitize_text_field($data['position']) : '';
        $validated['jersey_number'] = !empty($data['jersey_number']) ? intval($data['jersey_number']) : null;
        $validated['notes'] = !empty($data['notes']) ? sanitize_textarea_field($data['notes']) : '';
        $validated['team_name'] = !empty($data['team_name']) ? sanitize_text_field($data['team_name']) : '';
        
        return array(
            'valid' => empty($errors),
            'data' => array_merge($data, $validated),
            'errors' => $errors
        );
    }
    
    /**
     * Validate trainer data - UPDATED for semicolon separator and duplicate checking.
     */
    private function validateTrainer($data, $row_index) {
        $errors = array();
        $validated = array();
        
        // Validate email
        if (empty($data['email'])) {
            $errors[] = array('row' => $row_index + 1, 'field' => 'email', 'message' => 'Email is required');
        } else {
            $email = sanitize_email($data['email']);
            if ($this->options['validateEmails'] && !is_email($email)) {
                $errors[] = array('row' => $row_index + 1, 'field' => 'email', 'message' => 'Invalid email address');
            } else {
                $validated['email'] = $email;
                
                // Check if user already exists
                $existing_user = get_user_by('email', $email);
                if ($existing_user) {
                    if ($this->options['duplicateHandling'] === 'skip') {
                        $errors[] = array('row' => $row_index + 1, 'field' => 'email', 'message' => 'User already exists with this email and will be skipped');
                    }
                } else {
                    // Check for pending invitations
                    if ($this->hasPendingInvitation($email) && $this->options['duplicateHandling'] === 'skip') {
                        $errors[] = array('row' => $row_index + 1, 'field' => 'email', 'message' => 'Invitation already sent to this email and will be skipped');
                    }
                }
            }
        }
        
        // Validate team names (optional) - now with semicolon
        if (!empty($data['team_names'])) {
            // Keep the original string, parsing will be done during import
            $validated['team_names'] = $data['team_names'];
            
            // Optionally validate team names exist
            if (strpos($data['team_names'], ';') !== false) {
                $team_names = explode(';', $data['team_names']);
            } else {
                $team_names = array($data['team_names']);
            }
            
            $validated['parsed_team_names'] = array_map('trim', array_filter($team_names));
        }
        
        return array(
            'valid' => empty($errors),
            'data' => array_merge($data, $validated),
            'errors' => $errors
        );
    }
    
    /**
     * Check if there's a pending invitation for an email.
     * Simplified version for validation.
     */
    private function hasPendingInvitation($email) {
        global $wpdb;
        
        // Check for existing team_invitation posts with this email
        $query = $wpdb->prepare(
            "SELECT COUNT(*) 
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_type = 'team_invitation'
             AND p.post_status = 'publish'
             AND pm.meta_key = '_email'
             AND pm.meta_value = %s
             AND p.post_date > DATE_SUB(NOW(), INTERVAL 7 DAY)",
            $email
        );
        
        $count = $wpdb->get_var($query);
        
        return $count > 0;
    }
    
    /**
     * Validate teams-with-players data (combined import).
     */
    private function validateTeamWithPlayer($data, $row_index) {
        $errors = array();
        $validated = array();
        
        // Validate team fields
        if (empty($data['team_name'])) {
            $errors[] = array('row' => $row_index + 1, 'field' => 'team_name', 'message' => 'Team name is required');
        } else {
            $validated['team_name'] = sanitize_text_field($data['team_name']);
        }
        
        if (empty($data['coach'])) {
            $errors[] = array('row' => $row_index + 1, 'field' => 'coach', 'message' => 'Coach name is required');
        } else {
            $validated['coach'] = sanitize_text_field($data['coach']);
        }
        
        if (empty($data['season'])) {
            $errors[] = array('row' => $row_index + 1, 'field' => 'season', 'message' => 'Season is required');
        } else {
            $season = sanitize_text_field($data['season']);
            if (!preg_match('/^\d{4}-\d{4}$/', $season)) {
                $errors[] = array('row' => $row_index + 1, 'field' => 'season', 'message' => 'Invalid season format. Use YYYY-YYYY (e.g., 2024-2025)');
            } else {
                $validated['season'] = $season;
            }
        }
        
        // Validate player fields
        if (empty($data['first_name'])) {
            $errors[] = array('row' => $row_index + 1, 'field' => 'first_name', 'message' => 'Player first name is required');
        } else {
            $validated['first_name'] = sanitize_text_field($data['first_name']);
        }
        
        if (empty($data['last_name'])) {
            $errors[] = array('row' => $row_index + 1, 'field' => 'last_name', 'message' => 'Player last name is required');
        } else {
            $validated['last_name'] = sanitize_text_field($data['last_name']);
        }
        
        if (empty($data['email'])) {
            $errors[] = array('row' => $row_index + 1, 'field' => 'email', 'message' => 'Player email is required');
        } else {
            $email = sanitize_email($data['email']);
            if ($this->options['validateEmails'] && !is_email($email)) {
                $errors[] = array('row' => $row_index + 1, 'field' => 'email', 'message' => 'Invalid email address');
            } else {
                $validated['email'] = $email;
            }
        }
        
        // Validate birth date
        if (!empty($data['birth_date'])) {
            $date = $this->parseDate($data['birth_date']);
            if (!$date) {
                $errors[] = array('row' => $row_index + 1, 'field' => 'birth_date', 'message' => 'Invalid date format. Use DD-MM-YYYY');
            } else {
                $validated['birth_date'] = $date->format('Y-m-d');
            }
        }
        
        // Optional player fields
        $validated['position'] = !empty($data['position']) ? sanitize_text_field($data['position']) : '';
        $validated['jersey_number'] = !empty($data['jersey_number']) ? intval($data['jersey_number']) : null;
        
        return array(
            'valid' => empty($errors),
            'data' => array_merge($data, $validated),
            'errors' => $errors
        );
    }
    
    /**
     * Parse date based on configured format.
     */
    private function parseDate($date_string) {
        $date_string = trim($date_string);
        
        $formats = array(
            'd-m-Y', 'm-d-Y', 'Y-m-d',
            'd/m/Y', 'm/d/Y', 'Y/m/d'
        );
        
        foreach ($formats as $format) {
            $date = DateTime::createFromFormat($format, $date_string);
            if ($date && $date->format($format) === $date_string) {
                return $date;
            }
        }
        
        // Try to parse with strtotime as a fallback
        $timestamp = strtotime($date_string);
        if ($timestamp) {
            $date = new DateTime();
            $date->setTimestamp($timestamp);
            return $date;
        }
        
        return false;
    }
}