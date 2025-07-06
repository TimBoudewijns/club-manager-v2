<?php

/**
 * Handle import/export AJAX requests.
 */
class Club_Manager_Import_Export_Ajax extends Club_Manager_Ajax_Handler {
    
    /**
     * Initialize AJAX actions.
     */
    public function init() {
        // Import actions
        add_action('wp_ajax_cm_parse_import_file', array($this, 'parse_import_file'));
        add_action('wp_ajax_cm_validate_import_data', array($this, 'validate_import_data'));
        add_action('wp_ajax_cm_init_import_session', array($this, 'init_import_session'));
        add_action('wp_ajax_cm_process_import_batch', array($this, 'process_import_batch'));
        add_action('wp_ajax_cm_cancel_import_session', array($this, 'cancel_import_session'));
        
        // Export actions
        add_action('wp_ajax_cm_export_data', array($this, 'export_data'));
    }
    
    /**
     * Parse uploaded import file.
     */
    public function parse_import_file() {
        $user_id = $this->verify_request();
        
        if (!Club_Manager_User_Permissions_Helper::can_import_export($user_id)) {
            wp_send_json_error('You do not have permission to import data');
            return;
        }
        
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error('No file uploaded or upload error');
            return;
        }
        
        $file = $_FILES['file'];
        $type = $this->get_post_data('type');
        
        try {
            $parser = new Club_Manager_CSV_Parser();
            $data = $parser->parse($file['tmp_name'], $file['type']);
            
            if (empty($data['headers']) || empty($data['rows'])) {
                wp_send_json_error('File is empty or invalid format');
                return;
            }
            
            // Store file data temporarily
            $temp_key = 'cm_import_temp_' . wp_generate_uuid4();
            set_transient($temp_key, array(
                'headers' => $data['headers'],
                'rows' => $data['rows'],
                'type' => $type,
                'user_id' => $user_id
            ), HOUR_IN_SECONDS);
            
            wp_send_json_success(array(
                'headers' => $data['headers'],
                'rows' => array_slice($data['rows'], 0, 10), // Only send first 10 rows for preview
                'total_rows' => count($data['rows']),
                'temp_key' => $temp_key
            ));
            
        } catch (Exception $e) {
            wp_send_json_error('Error parsing file: ' . $e->getMessage());
        }
    }
    
    /**
     * Validate import data.
     */
    public function validate_import_data() {
        $user_id = $this->verify_request();
        
        if (!Club_Manager_User_Permissions_Helper::can_import_export($user_id)) {
            wp_send_json_error('You do not have permission to import data');
            return;
        }
        
        $type = $this->get_post_data('type');
        $mapping = isset($_POST['mapping']) ? json_decode(stripslashes($_POST['mapping']), true) : array();
        $options = isset($_POST['options']) ? json_decode(stripslashes($_POST['options']), true) : array();
        $temp_key = $this->get_post_data('temp_key');
        
        if (empty($mapping) || empty($temp_key)) {
            wp_send_json_error('Missing mapping or temporary data key');
            return;
        }
        
        // Get stored file data
        $temp_data = get_transient($temp_key);
        if (!$temp_data) {
            wp_send_json_error('Temporary data expired. Please re-upload the file.');
            return;
        }
        
        try {
            $validator = new Club_Manager_Data_Validator();
            $validator->setOptions($options);
            
            $preview = array();
            $rows_to_validate = array_slice($temp_data['rows'], 0, 10); // Validate first 10 rows
            
            foreach ($rows_to_validate as $index => $row) {
                $mapped_data = array();
                foreach ($mapping as $field => $column_index) {
                    if ($column_index !== '' && $column_index !== null && isset($row[$column_index])) {
                        $mapped_data[$field] = $row[$column_index];
                    } else {
                        $mapped_data[$field] = '';
                    }
                }
                
                $result = $validator->validateRow($mapped_data, $type, $index);
                
                $status = $result['valid'] ? 'valid' : 'error';
                
                $preview[] = array(
                    'row' => $index + 1,
                    'data' => $result['data'],
                    'status' => $status,
                    'errors' => $result['errors'] ?? []
                );
            }
            
            // Update temp data with mapping
            $temp_data['mapping'] = $mapping;
            $temp_data['options'] = $options;
            set_transient($temp_key, $temp_data, HOUR_IN_SECONDS);
            
            wp_send_json_success(array(
                'preview' => $preview,
                'total_rows' => count($temp_data['rows'])
            ));
            
        } catch (Exception $e) {
            wp_send_json_error('Validation error: ' . $e->getMessage());
        }
    }
    
    /**
     * Initialize import session.
     */
    public function init_import_session() {
        $user_id = $this->verify_request();
        
        if (!Club_Manager_User_Permissions_Helper::can_import_export($user_id)) {
            wp_send_json_error('You do not have permission to import data');
            return;
        }
        
        $type = $this->get_post_data('type');
        $mapping = isset($_POST['mapping']) ? json_decode(stripslashes($_POST['mapping']), true) : array();
        $options = isset($_POST['options']) ? json_decode(stripslashes($_POST['options']), true) : array();
        $temp_key = $this->get_post_data('temp_key');
        
        if (empty($type) || empty($mapping) || empty($temp_key)) {
            wp_send_json_error('Missing required data');
            return;
        }
        
        // Get stored file data
        $temp_data = get_transient($temp_key);
        if (!$temp_data) {
            wp_send_json_error('Temporary data expired. Please re-upload the file.');
            return;
        }
        
        try {
            $session_id = wp_generate_uuid4();
            
            $session_data = array(
                'user_id' => $user_id,
                'type' => $type,
                'mapping' => $mapping,
                'options' => $options,
                'rows' => $temp_data['rows'], // Store all rows
                'status' => 'initialized',
                'progress' => array(
                    'total' => count($temp_data['rows']), 
                    'processed' => 0, 
                    'successful' => 0, 
                    'failed' => 0, 
                    'current_batch' => 0
                ),
                'results' => array(
                    'created' => 0, 
                    'updated' => 0, 
                    'skipped' => 0, 
                    'failed' => 0, 
                    'errors' => []
                ),
                'created_at' => current_time('mysql')
            );
            
            update_option('cm_import_session_' . $session_id, $session_data, false);
            
            // Clean up temp data
            delete_transient($temp_key);
            
            // Schedule cleanup
            wp_schedule_single_event(time() + HOUR_IN_SECONDS, 'cm_cleanup_import_session', array($session_id));
            
            wp_send_json_success(array('session_id' => $session_id));
            
        } catch (Exception $e) {
            wp_send_json_error('Failed to initialize import session: ' . $e->getMessage());
        }
    }
    
    /**
     * Process import batch.
     */
    public function process_import_batch() {
        $user_id = $this->verify_request();
        
        if (!Club_Manager_User_Permissions_Helper::can_import_export($user_id)) {
            wp_send_json_error('You do not have permission to import data');
            return;
        }
        
        $session_id = $this->get_post_data('session_id');
        
        if (empty($session_id)) {
            wp_send_json_error('Missing session ID');
            return;
        }
        
        $session_data = get_option('cm_import_session_' . $session_id);
        
        if (!$session_data || $session_data['user_id'] != $user_id) {
            wp_send_json_error('Invalid or unauthorized session');
            return;
        }
        
        try {
            $handler = new Club_Manager_Import_Handler();
            $handler->setOptions($session_data['options']);
            
            $batch_size = 25;
            $start_index = $session_data['progress']['current_batch'] * $batch_size;
            $rows_to_process = array_slice($session_data['rows'], $start_index, $batch_size);
            
            if (empty($rows_to_process)) {
                // Mark as complete if no more rows
                $session_data['status'] = 'completed';
                update_option('cm_import_session_' . $session_id, $session_data, false);
                wp_send_json_success(array(
                    'processed' => $session_data['progress']['processed'],
                    'successful' => $session_data['progress']['successful'],
                    'failed' => $session_data['progress']['failed'],
                    'errors' => [],
                    'complete' => true,
                    'results' => $session_data['results']
                ));
                return;
            }

            // Map the raw rows to field names using the stored mapping
            $mapped_rows = array();
            foreach ($rows_to_process as $row) {
                $mapped_data = array();
                foreach ($session_data['mapping'] as $field => $column_index) {
                    if ($column_index !== '' && $column_index !== null && isset($row[$column_index])) {
                        $mapped_data[$field] = trim($row[$column_index]);
                    } else {
                        $mapped_data[$field] = '';
                    }
                }
                $mapped_rows[] = $mapped_data;
            }
            
            // Process the batch
            $batch_results = $handler->processBatch($mapped_rows, $session_data['type'], $start_index, $user_id);
            
            // Update session data
            $session_data['progress']['processed'] += count($rows_to_process);
            $session_data['progress']['successful'] += $batch_results['successful'];
            $session_data['progress']['failed'] += $batch_results['failed'];
            $session_data['progress']['current_batch']++;
            $session_data['results']['created'] += $batch_results['created'];
            $session_data['results']['updated'] += $batch_results['updated'];
            $session_data['results']['skipped'] += $batch_results['skipped'];
            $session_data['results']['failed'] += $batch_results['failed'];
            
            if (!empty($batch_results['errors'])) {
                $session_data['results']['errors'] = array_merge($session_data['results']['errors'], $batch_results['errors']);
            }
            
            if (!empty($batch_results['trainers_to_invite'])) {
                if (!isset($session_data['trainers_to_invite'])) {
                    $session_data['trainers_to_invite'] = array();
                }
                $session_data['trainers_to_invite'] = array_merge($session_data['trainers_to_invite'], $batch_results['trainers_to_invite']);
            }
            
            $complete = $session_data['progress']['processed'] >= $session_data['progress']['total'];
            if ($complete) {
                $session_data['status'] = 'completed';
                if (!empty($session_data['options']['sendInvitations']) && !empty($session_data['trainers_to_invite'])) {
                    $this->sendBulkTrainerInvitations($session_data['trainers_to_invite'], $user_id);
                }
            }
            
            update_option('cm_import_session_' . $session_id, $session_data, false);
            
            wp_send_json_success(array(
                'processed' => $session_data['progress']['processed'],
                'successful' => $session_data['progress']['successful'],
                'failed' => $session_data['progress']['failed'],
                'errors' => array_slice($batch_results['errors'], 0, 10),
                'complete' => $complete,
                'results' => $complete ? $session_data['results'] : null
            ));
            
        } catch (Exception $e) {
            wp_send_json_error('Import processing error: ' . $e->getMessage());
        }
    }
    
    /**
     * Cancel import session.
     */
    public function cancel_import_session() {
        $user_id = $this->verify_request();
        $session_id = $this->get_post_data('session_id');
        if ($session_id) {
            delete_option('cm_import_session_' . $session_id);
        }
        wp_send_json_success();
    }
    
    /**
     * Export data.
     */
    public function export_data() {
        $user_id = $this->verify_request();
        
        if (!Club_Manager_User_Permissions_Helper::can_import_export($user_id)) {
            wp_send_json_error('You do not have permission to export data');
            return;
        }
        
        $type = $this->get_post_data('type');
        $format = $this->get_post_data('format');
        $filters = isset($_POST['filters']) ? json_decode(stripslashes($_POST['filters']), true) : array();
        
        try {
            $handler = new Club_Manager_Export_Handler();
            $handler->setUserId($user_id);
            $handler->setFilters($filters);
            
            $data = $handler->getExportData($type);
            
            if (empty($data)) {
                wp_send_json_error('No data found for the selected filters.');
                return;
            }
            
            if ($format === 'csv') {
                $content = $handler->generateCSV($data, $type);
                $filename = 'club_manager_' . $type . '_' . date('Y-m-d') . '.csv';
            } else {
                wp_send_json_error('Excel export is not yet supported. Please choose CSV.');
                return;
            }
            
            wp_send_json_success(array('data' => $content, 'filename' => $filename));
            
        } catch (Exception $e) {
            wp_send_json_error('Export error: ' . $e->getMessage());
        }
    }
    
    /**
     * Send bulk trainer invitations.
     */
    private function sendBulkTrainerInvitations($trainers, $inviter_id) {
        if (empty($trainers)) return;
        
        // Get managed teams to find WC Team ID
        $managed_teams = Club_Manager_Teams_Helper::get_user_managed_teams($inviter_id);
        if (empty($managed_teams)) {
            Club_Manager_Logger::log('No managed teams found for bulk trainer invitations', 'error', array('inviter_id' => $inviter_id));
            return;
        }
        
        $wc_team_id = $managed_teams[0]['team_id'];
        $wc_team = wc_memberships_for_teams_get_team($wc_team_id);
        
        if (!$wc_team) {
            Club_Manager_Logger::log('No WC Team found for bulk trainer invitations', 'error', array('wc_team_id' => $wc_team_id));
            return;
        }
        
        foreach ($trainers as $trainer_data) {
            try {
                // Create invitation directly using WC Teams API
                $invitations_instance = wc_memberships_for_teams()->get_invitations_instance();
                
                if (!$invitations_instance || !method_exists($invitations_instance, 'create_invitation')) {
                    Club_Manager_Logger::log('Could not access invitations system', 'error');
                    continue;
                }
                
                // Create invitation
                $invitation = $invitations_instance->create_invitation(array(
                    'team_id' => $wc_team->get_id(),
                    'email' => $trainer_data['email'],
                    'sender_id' => $inviter_id,
                    'role' => 'member' // WC Teams only supports 'member' role
                ));
                
                if (!$invitation || is_wp_error($invitation)) {
                    $error_message = is_wp_error($invitation) ? $invitation->get_error_message() : 'Could not create invitation';
                    Club_Manager_Logger::log('Failed to create invitation', 'error', array(
                        'email' => $trainer_data['email'],
                        'error' => $error_message
                    ));
                    continue;
                }
                
                // Store Club Manager specific data
                update_post_meta($invitation->get_id(), '_cm_team_ids', $trainer_data['team_ids']);
                update_post_meta($invitation->get_id(), '_cm_role', $trainer_data['role'] ?? 'trainer');
                update_post_meta($invitation->get_id(), '_cm_message', 'You have been invited to join as a trainer through bulk import.');
                
                // Get team names for email
                $team_names = [];
                global $wpdb;
                $teams_table = Club_Manager_Database::get_table_name('teams');
                foreach ($trainer_data['team_ids'] as $team_id) {
                    $team_name = $wpdb->get_var($wpdb->prepare(
                        "SELECT name FROM $teams_table WHERE id = %d",
                        $team_id
                    ));
                    if ($team_name) {
                        $team_names[] = $team_name;
                    }
                }
                
                // Send custom email
                $this->sendTrainerInvitationEmail(
                    $trainer_data['email'],
                    $invitation->get_token(),
                    $inviter_id,
                    $team_names,
                    'You have been invited to join as a trainer through bulk import.'
                );
                
                Club_Manager_Logger::log('Trainer invitation sent successfully', 'info', array(
                    'email' => $trainer_data['email'],
                    'teams' => $trainer_data['team_ids'],
                    'invitation_id' => $invitation->get_id()
                ));
                
            } catch (Exception $e) {
                Club_Manager_Logger::log('Exception during trainer invitation: ' . $e->getMessage(), 'error', array('email' => $trainer_data['email']));
            }
        }
    }
    
    /**
     * Send custom trainer invitation email
     */
    private function sendTrainerInvitationEmail($email, $token, $inviter_id, $team_names, $message) {
        $inviter = get_user_by('id', $inviter_id);
        
        // Get accept URL
        global $wpdb;
        $page_id = $wpdb->get_var(
            "SELECT ID FROM {$wpdb->posts} 
             WHERE post_content LIKE '%[club_manager_accept_invitation]%' 
             AND post_status = 'publish' 
             AND post_type = 'page' 
             LIMIT 1"
        );
        
        if ($page_id && $token) {
            $accept_url = add_query_arg([
                'wc_invite' => $token
            ], get_permalink($page_id));
        } else {
            // Fallback
            $accept_url = home_url('/invitation/?wc_invite=' . $token);
        }
        
        // Build email
        $subject = sprintf('[%s] %s heeft je uitgenodigd als trainer', 
            get_bloginfo('name'), 
            $inviter ? $inviter->display_name : 'Een beheerder'
        );
        
        $body = "Hallo,\n\n";
        
        if ($inviter) {
            $body .= sprintf("%s heeft je uitgenodigd om trainer te worden bij %s.\n\n", 
                $inviter->display_name, 
                get_bloginfo('name')
            );
        }
        
        if (!empty($team_names)) {
            $body .= "Je krijgt toegang tot de volgende teams:\n";
            foreach ($team_names as $tn) {
                $body .= "- " . $tn . "\n";
            }
            $body .= "\n";
        }
        
        if (!empty($message)) {
            $body .= "Persoonlijk bericht:\n" . $message . "\n\n";
        }
        
        $body .= "Klik op de onderstaande link om de uitnodiging te accepteren:\n";
        $body .= $accept_url . "\n\n";
        $body .= "Deze uitnodiging verloopt over 7 dagen.\n\n";
        $body .= "Met vriendelijke groet,\n" . get_bloginfo('name');
        
        // Add headers for better deliverability
        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );
        
        // Send email
        $sent = wp_mail($email, $subject, $body, $headers);
        
        if (!$sent) {
            error_log('Club Manager: Failed to send invitation email to ' . $email);
        }
        
        return $sent;
    }
}

// Add cleanup action for expired sessions
add_action('cm_cleanup_import_session', function($session_id) {
    delete_option('cm_import_session_' . $session_id);
});