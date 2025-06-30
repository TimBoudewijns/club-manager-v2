<?php

/**
 * Validate import data.
 */
class Club_Manager_Data_Validator {
    
    private $options = array();
    private $errors = array();
    
    /**
     * Set validation options.
     */
    public function setOptions($options) {
        $this->options = wp_parse_args($options, array(
            'validateEmails' => true,
            'dateFormat' => 'DD-MM-YYYY'
        ));
    }
    
    /**
     * Validate a single row.
     */
    public function validateRow($row, $mapping, $type, $row_number) {
        $this->errors = array();
        $data = array();
        
        // Map row data to fields
        foreach ($mapping as $field => $column_index) {
            if (isset($row[$column_index])) {
                $data[$field] = $row[$column_index];
            } else {
                $data[$field] = '';
            }
        }
        
        // Validate based on type
        switch ($type) {
            case 'teams':
                $this->validateTeam($data, $row_number);
                break;
                
            case 'players':
                $this->validatePlayer($data, $row_number);
                break;
                
            case 'teams-with-players':
                $this->validateTeamWithPlayers($data, $row_number);
                break;
                
            case 'trainers':
                $this->validateTrainer($data, $row_number);
                break;
                
            case 'trainers-with-assignments':
                $this->validateTrainerWithAssignments($data, $row_number);
                break;
        }
        
        return array(
            'valid' => empty($this->errors),
            'data' => $data,
            'errors' => $this->errors
        );
    }
    
    /**
     * Validate team data.
     */
    private function validateTeam(&$data, $row_number) {
        // Validate required fields
        if (empty($data['name'])) {
            $this->addError('name', 'Team name is required', $row_number);
        } else {
            $data['name'] = sanitize_text_field($data['name']);
        }
        
        if (empty($data['coach'])) {
            $this->addError('coach', 'Coach name is required', $row_number);
        } else {
            $data['coach'] = sanitize_text_field($data['coach']);
        }
        
        if (empty($data['season'])) {
            $this->addError('season', 'Season is required', $row_number);
        } else {
            $data['season'] = $this->validateSeason($data['season'], $row_number);
        }
    }
    
    /**
     * Validate player data.
     */
    private function validatePlayer(&$data, $row_number) {
        // Validate required fields
        if (empty($data['first_name'])) {
            $this->addError('first_name', 'First name is required', $row_number);
        } else {
            $data['first_name'] = sanitize_text_field($data['first_name']);
        }
        
        if (empty($data['last_name'])) {
            $this->addError('last_name', 'Last name is required', $row_number);
        } else {
            $data['last_name'] = sanitize_text_field($data['last_name']);
        }
        
        if (empty($data['email'])) {
            $this->addError('email', 'Email is required', $row_number);
        } else {
            $data['email'] = $this->validateEmail($data['email'], $row_number);
        }
        
        if (empty($data['birth_date'])) {
            $this->addError('birth_date', 'Birth date is required', $row_number);
        } else {
            $data['birth_date'] = $this->validateDate($data['birth_date'], $row_number);
        }
        
        // Validate optional fields
        if (!empty($data['position'])) {
            $data['position'] = sanitize_text_field($data['position']);
        }
        
        if (!empty($data['jersey_number'])) {
            $data['jersey_number'] = $this->validateJerseyNumber($data['jersey_number'], $row_number);
        }
        
        if (!empty($data['team_name'])) {
            $data['team_name'] = sanitize_text_field($data['team_name']);
        }
    }
    
    /**
     * Validate team with players data.
     */
    private function validateTeamWithPlayers(&$data, $row_number) {
        // First validate team data
        $this->validateTeam($data, $row_number);
        
        // Players would be validated separately in the import handler
        if (isset($data['players']) && is_array($data['players'])) {
            foreach ($data['players'] as $index => &$player) {
                $player_errors = count($this->errors);
                $this->validatePlayer($player, $row_number);
                
                // If player validation added errors, note which player
                if (count($this->errors) > $player_errors) {
                    $this->errors[count($this->errors) - 1]['player_index'] = $index;
                }
            }
        }
    }
    
    /**
     * Validate trainer data.
     */
    private function validateTrainer(&$data, $row_number) {
        // Validate required fields
        if (empty($data['email'])) {
            $this->addError('email', 'Email is required', $row_number);
        } else {
            $data['email'] = $this->validateEmail($data['email'], $row_number);
        }
        
        if (empty($data['first_name'])) {
            $this->addError('first_name', 'First name is required', $row_number);
        } else {
            $data['first_name'] = sanitize_text_field($data['first_name']);
        }
        
        if (empty($data['last_name'])) {
            $this->addError('last_name', 'Last name is required', $row_number);
        } else {
            $data['last_name'] = sanitize_text_field($data['last_name']);
        }
        
        // Validate optional fields
        if (!empty($data['role'])) {
            $data['role'] = $this->validateTrainerRole($data['role'], $row_number);
        } else {
            $data['role'] = 'trainer'; // Default role
        }
    }
    
    /**
     * Validate trainer with assignments data.
     */
    private function validateTrainerWithAssignments(&$data, $row_number) {
        // First validate trainer data
        $this->validateTrainer($data, $row_number);
        
        // Validate team assignments
        if (!empty($data['team_names'])) {
            $data['team_names'] = sanitize_text_field($data['team_names']);
        }
    }
    
    /**
     * Validate email address.
     */
    private function validateEmail($email, $row_number) {
        $email = sanitize_email($email);
        
        if ($this->options['validateEmails'] && !is_email($email)) {
            $this->addError('email', 'Invalid email address: ' . $email, $row_number);
        }
        
        return $email;
    }
    
    /**
     * Validate date.
     */
    private function validateDate($date, $row_number) {
        // Try to parse date based on format
        $parsed_date = $this->parseDate($date);
        
        if (!$parsed_date) {
            $this->addError('birth_date', 'Invalid date format: ' . $date, $row_number);
            return $date;
        }
        
        // Convert to MySQL format
        return $parsed_date->format('Y-m-d');
    }
    
    /**
     * Parse date based on configured format.
     */
    private function parseDate($date) {
        $date = trim($date);
        if (empty($date)) {
            return false;
        }
        
        // Try different date formats
        $formats = array();
        
        switch ($this->options['dateFormat']) {
            case 'DD-MM-YYYY':
                $formats[] = 'd-m-Y';
                $formats[] = 'd/m/Y';
                $formats[] = 'd.m.Y';
                break;
                
            case 'MM-DD-YYYY':
                $formats[] = 'm-d-Y';
                $formats[] = 'm/d/Y';
                $formats[] = 'm.d.Y';
                break;
                
            case 'YYYY-MM-DD':
                $formats[] = 'Y-m-d';
                $formats[] = 'Y/m/d';
                $formats[] = 'Y.m.d';
                break;
        }
        
        // Add additional common formats
        $formats[] = 'Y-m-d';
        $formats[] = 'd-m-Y';
        $formats[] = 'm/d/Y';
        
        foreach ($formats as $format) {
            $parsed = DateTime::createFromFormat($format, $date);
            if ($parsed && $parsed->format($format) === $date) {
                return $parsed;
            }
        }
        
        // Try PHP's strtotime as last resort
        $timestamp = strtotime($date);
        if ($timestamp !== false) {
            return new DateTime('@' . $timestamp);
        }
        
        return false;
    }
    
    /**
     * Validate season format.
     */
    private function validateSeason($season, $row_number) {
        $season = trim($season);
        
        // Check format YYYY-YYYY
        if (!preg_match('/^\d{4}-\d{4}$/', $season)) {
            $this->addError('season', 'Invalid season format. Use YYYY-YYYY (e.g., 2024-2025)', $row_number);
            return $season;
        }
        
        // Check years are consecutive
        list($year1, $year2) = explode('-', $season);
        if (intval($year2) !== intval($year1) + 1) {
            $this->addError('season', 'Season years must be consecutive (e.g., 2024-2025)', $row_number);
        }
        
        return $season;
    }
    
    /**
     * Validate jersey number.
     */
    private function validateJerseyNumber($number, $row_number) {
        $number = trim($number);
        
        if (!is_numeric($number) || intval($number) < 0 || intval($number) > 999) {
            $this->addError('jersey_number', 'Invalid jersey number: ' . $number, $row_number);
            return null;
        }
        
        return intval($number);
    }
    
    /**
     * Validate trainer role.
     */
    private function validateTrainerRole($role, $row_number) {
        $role = strtolower(trim($role));
        
        $valid_roles = array('trainer', 'assistant_trainer', 'analyst');
        
        if (!in_array($role, $valid_roles)) {
            // Try to match common variations
            $role_map = array(
                'assistant' => 'assistant_trainer',
                'asst' => 'assistant_trainer',
                'asst_trainer' => 'assistant_trainer',
                'coach' => 'trainer',
                'head_trainer' => 'trainer'
            );
            
            if (isset($role_map[$role])) {
                $role = $role_map[$role];
            } else {
                $this->addError('role', 'Invalid role. Valid roles: trainer, assistant_trainer, analyst', $row_number);
                $role = 'trainer'; // Default
            }
        }
        
        return $role;
    }
    
    /**
     * Add validation error.
     */
    private function addError($field, $message, $row_number) {
        $this->errors[] = array(
            'row' => $row_number,
            'field' => $field,
            'message' => $message
        );
    }
}