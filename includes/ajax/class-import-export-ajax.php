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
        
        // Debug logging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Import AJAX - Files array: ' . print_r($_FILES, true));
        }
        
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $error_message = 'No file uploaded';
            if (isset($_FILES['file']['error'])) {
                switch ($_FILES['file']['error']) {
                    case UPLOAD_ERR_INI_SIZE:
                        $error_message = 'File exceeds upload_max_filesize';
                        break;
                    case UPLOAD_ERR_FORM_SIZE:
                        $error_message = 'File exceeds MAX_FILE_SIZE';
                        break;
                    case UPLOAD_ERR_PARTIAL:
                        $error_message = 'File was only partially uploaded';
                        break;
                    case UPLOAD_ERR_NO_FILE:
                        $error_message = 'No file was uploaded';
                        break;
                    case UPLOAD_ERR_NO_TMP_DIR:
                        $error_message = 'Missing temporary folder';
                        break;
                    case UPLOAD_ERR_CANT_WRITE:
                        $error_message = 'Failed to write file to disk';
                        break;
                    default:
                        $error_message = 'Unknown upload error';
                }
            }
            wp_send_json_error($error_message);
            return;
        }
        
        $file = $_FILES['file'];
        $type = $this->get_post_data('type');
        
        // Debug logging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Import AJAX - File name: ' . $file['name']);
            error_log('Import AJAX - File type: ' . $file['type']);
            error_log('Import AJAX - File size: ' . $file['size']);
            error_log('Import AJAX - Temp name: ' . $file['tmp_name']);
            error_log('Import AJAX - Import type: ' . $type);
        }
        
        // Check file extension
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($file_extension !== 'csv') {
            wp_send_json_error('Invalid file type. Only CSV files are allowed. Your file has extension: ' . $file_extension);
            return;
        }
        
        try {
            // Check if parser class exists
            if (!class_exists('Club_Manager_CSV_Parser')) {
                error_log('Club Manager: CSV Parser class not found');
                wp_send_json_error('CSV Parser not available. Please contact support.');
                return;
            }
            
            $parser = new Club_Manager_CSV_Parser();
            
            // Parse the file - pass mime type and original filename
            $data = $parser->parse($file['tmp_name'], $file['type'], $file['name']);
            
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
            error_log('Club Manager Import Error: ' . $e->getMessage());
            wp_send_json_error($e->getMessage());
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
            
            // Debug logging for troubleshooting
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Import Validation - Total rows in temp_data: ' . count($temp_data['rows']));
                error_log('Import Validation - Validating first 10 rows for preview');
                if (count($temp_data['rows']) >= 12) {
                    error_log('Import Validation - Row 12 data: ' . json_encode($temp_data['rows'][11]));
                }
            }
            
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
            
            // Debug logging for batch processing
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Import Batch - Processing batch ' . $session_data['progress']['current_batch'] . ', start_index: ' . $start_index . ', rows in batch: ' . count($rows_to_process));
                // Check if row 12 (index 11) is in this batch
                $row_12_index = 11 - $start_index;
                if ($row_12_index >= 0 && $row_12_index < count($rows_to_process)) {
                    error_log('Import Batch - Row 12 is in this batch at index ' . $row_12_index . ': ' . json_encode($rows_to_process[$row_12_index]));
                }
            }
            
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
            foreach ($rows_to_process as $batch_index => $row) {
                $mapped_data = array();
                foreach ($session_data['mapping'] as $field => $column_index) {
                    if ($column_index !== '' && $column_index !== null && isset($row[$column_index])) {
                        $mapped_data[$field] = trim($row[$column_index]);
                    } else {
                        $mapped_data[$field] = '';
                    }
                }
                $mapped_rows[] = $mapped_data;
                
                // Debug logging for row 12 specifically
                if (($start_index + $batch_index) === 11 && defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Import Batch - Row 12 mapped data: ' . json_encode($mapped_data));
                }
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
                wp_send_json_error('Only CSV export format is supported.');
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
                // Check if invitation already exists for this email
                $existing_invitation = $this->checkExistingInvitation($trainer_data['email'], $wc_team->get_id());
                if ($existing_invitation) {
                    Club_Manager_Logger::log('Invitation already exists, skipping', 'info', array(
                        'email' => $trainer_data['email'],
                        'existing_invitation_id' => $existing_invitation
                    ));
                    continue;
                }
                
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
                update_post_meta($invitation->get_id(), '_cm_message', 'You have been invited to join as a trainer through bulk import. Welcome to our team!');
                
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
                    'You have been invited to join as a trainer through bulk import. Welcome to our team!'
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
        $club_name = get_bloginfo('name');
        
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
        
        // Build email subject
        $subject = sprintf('[%s] Trainer Invitation', $club_name);
        
        // Build professional HTML email
        $body = $this->get_trainer_invitation_email_template([
            'club_name' => $club_name,
            'inviter_name' => $inviter ? $inviter->display_name : 'Club Administrator',
            'team_names' => $team_names,
            'message' => $message,
            'accept_url' => $accept_url,
            'email' => $email
        ]);
        
        // Add headers for HTML email
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $club_name . ' <' . get_option('admin_email') . '>'
        );
        
        // Send email
        $sent = wp_mail($email, $subject, $body, $headers);
        
        if (!$sent) {
            error_log('Club Manager: Failed to send invitation email to ' . $email);
        }
        
        return $sent;
    }
    
    /**
     * Get professional HTML email template for trainer invitation
     */
    private function get_trainer_invitation_email_template($data) {
        $club_name = esc_html($data['club_name']);
        $inviter_name = esc_html($data['inviter_name']);
        $team_names = $data['team_names'];
        $message = $data['message'];
        $accept_url = esc_url($data['accept_url']);
        
        // Build team list
        $team_list_html = '';
        if (!empty($team_names)) {
            $team_list_html = '<div style="margin: 20px 0;">';
            $team_list_html .= '<p style="margin: 0 0 10px 0; font-weight: 600; color: #374151;">You will have access to the following team(s):</p>';
            $team_list_html .= '<ul style="margin: 0; padding-left: 20px; color: #6B7280;">';
            foreach ($team_names as $team_name) {
                $team_list_html .= '<li style="margin: 5px 0;">' . esc_html($team_name) . '</li>';
            }
            $team_list_html .= '</ul>';
            $team_list_html .= '</div>';
        }
        
        // Build personal message
        $message_html = '';
        if (!empty($message)) {
            $message_html = '<div style="background-color: #F9FAFB; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #8B5CF6;">';
            $message_html .= '<p style="margin: 0 0 10px 0; font-weight: 600; color: #374151;">Personal Message:</p>';
            $message_html .= '<p style="margin: 0; color: #6B7280; font-style: italic;">' . esc_html($message) . '</p>';
            $message_html .= '</div>';
        }
        
        $html = '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trainer Invitation - ' . $club_name . '</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, \'Helvetica Neue\', Arial, sans-serif; line-height: 1.6; color: #374151; background-color: #F3F4F6;">
    <div style="max-width: 600px; margin: 20px auto; background-color: #FFFFFF; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); overflow: hidden;">
        
        <!-- Header -->
        <div style="background: linear-gradient(135deg, #8B5CF6 0%, #7C3AED 100%); padding: 40px 30px; text-align: center;">
            <h1 style="margin: 0; color: #FFFFFF; font-size: 28px; font-weight: 700;">
                🏒 Trainer Invitation
            </h1>
            <p style="margin: 10px 0 0 0; color: #E5E7EB; font-size: 16px;">
                ' . $club_name . '
            </p>
        </div>
        
        <!-- Content -->
        <div style="padding: 40px 30px;">
            <h2 style="margin: 0 0 20px 0; color: #1F2937; font-size: 24px; font-weight: 600;">
                Hello!
            </h2>
            
            <p style="margin: 0 0 20px 0; color: #6B7280; font-size: 16px;">
                <strong>' . $inviter_name . '</strong> has invited you to become a trainer at <strong>' . $club_name . '</strong>.
            </p>
            
            ' . $team_list_html . '
            
            ' . $message_html . '
            
            <!-- Call to Action -->
            <div style="text-align: center; margin: 40px 0;">
                <a href="' . $accept_url . '" style="display: inline-block; background: linear-gradient(135deg, #8B5CF6 0%, #7C3AED 100%); color: #FFFFFF; text-decoration: none; padding: 16px 32px; border-radius: 8px; font-weight: 600; font-size: 16px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); transition: all 0.2s;">
                    Accept Invitation
                </a>
            </div>
            
            <div style="background-color: #FEF3C7; border: 1px solid #F59E0B; border-radius: 8px; padding: 16px; margin: 30px 0;">
                <p style="margin: 0; color: #92400E; font-size: 14px;">
                    <strong>⏰ Important:</strong> This invitation will expire in 7 days. Please accept it as soon as possible.
                </p>
            </div>
            
            <p style="margin: 20px 0 0 0; color: #9CA3AF; font-size: 14px;">
                If you\'re having trouble with the button above, copy and paste the following link into your browser:
                <br><a href="' . $accept_url . '" style="color: #8B5CF6; word-break: break-all;">' . $accept_url . '</a>
            </p>
        </div>
        
        <!-- Footer -->
        <div style="background-color: #F9FAFB; padding: 30px; text-align: center; border-top: 1px solid #E5E7EB;">
            <p style="margin: 0; color: #6B7280; font-size: 14px;">
                Best regards,<br>
                <strong>' . $club_name . '</strong>
            </p>
            <p style="margin: 15px 0 0 0; color: #9CA3AF; font-size: 12px;">
                This is an automated message. Please do not reply to this email.
            </p>
        </div>
    </div>
</body>
</html>';
        
        return $html;
    }
    
    /**
     * Check if an invitation already exists for this email and team.
     * 
     * @param string $email Email address to check
     * @param int $wc_team_id WC Team ID
     * @return int|false Invitation post ID if exists, false otherwise
     */
    private function checkExistingInvitation($email, $wc_team_id) {
        global $wpdb;
        
        // Check for existing WC Teams invitations
        // Note: WC Teams uses specific status 'wcmti-pending' for pending invitations
        $invitation_id = $wpdb->get_var($wpdb->prepare(
            "SELECT p.ID 
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm_email ON p.ID = pm_email.post_id 
             WHERE p.post_type = 'wc_team_invitation'
             AND p.post_status IN ('wcmti-pending', 'publish', 'pending')
             AND p.post_parent = %d
             AND (
                 (pm_email.meta_key = '_email' AND pm_email.meta_value = %s) OR
                 (pm_email.meta_key = '_recipient_email' AND pm_email.meta_value = %s) OR
                 (pm_email.meta_key = '_invitation_email' AND pm_email.meta_value = %s) OR
                 p.post_title = %s
             )
             LIMIT 1",
            $wc_team_id,
            $email,
            $email,
            $email,
            $email
        ));
        
        if ($invitation_id) {
            return intval($invitation_id);
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
                return true; // Return true to indicate there's a pending invitation
            }
        }
        
        return false;
    }
}

// Add cleanup action for expired sessions
add_action('cm_cleanup_import_session', function($session_id) {
    delete_option('cm_import_session_' . $session_id);
});