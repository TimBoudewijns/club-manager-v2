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
        
        // Check permissions
        if (!Club_Manager_User_Permissions_Helper::can_import_export($user_id)) {
            wp_send_json_error('You do not have permission to import data');
            return;
        }
        
        // Check file upload
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error('No file uploaded or upload error');
            return;
        }
        
        $file = $_FILES['file'];
        $type = $this->get_post_data('type');
        
        // Validate file type
        $allowed_types = array('text/csv', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $file_type = wp_check_filetype($file['name']);
        
        if (!in_array($file['type'], $allowed_types) && !in_array($file_type['type'], $allowed_types)) {
            wp_send_json_error('Invalid file type. Please upload a CSV or Excel file.');
            return;
        }
        
        // Validate file size (max 10MB)
        if ($file['size'] > 10 * 1024 * 1024) {
            wp_send_json_error('File size must be less than 10MB');
            return;
        }
        
        try {
            // Parse file
            $parser = new Club_Manager_CSV_Parser();
            $data = $parser->parse($file['tmp_name'], $file['type']);
            
            if (empty($data['headers']) || empty($data['rows'])) {
                wp_send_json_error('File is empty or invalid format');
                return;
            }
            
            // Return parsed data
            wp_send_json_success(array(
                'headers' => $data['headers'],
                'rows' => $data['rows'],
                'total_rows' => count($data['rows'])
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
        $mapping = isset($_POST['mapping']) ? $_POST['mapping'] : array();
        $options = isset($_POST['options']) ? $_POST['options'] : array();
        $sample_data = isset($_POST['sample_data']) ? $_POST['sample_data'] : array();
        
        if (empty($mapping) || empty($sample_data)) {
            wp_send_json_error('Missing mapping or sample data');
            return;
        }
        
        try {
            $validator = new Club_Manager_Data_Validator();
            $validator->setOptions($options);
            
            // Validate sample data
            $preview = array();
            $errors = array();
            
            foreach ($sample_data as $index => $row) {
                $result = $validator->validateRow($row, $mapping, $type, $index);
                
                if ($result['valid']) {
                    $preview[] = array(
                        'row' => $index + 1,
                        'data' => $result['data'],
                        'status' => 'valid'
                    );
                } else {
                    $preview[] = array(
                        'row' => $index + 1,
                        'data' => $result['data'],
                        'status' => 'error',
                        'errors' => $result['errors']
                    );
                    $errors = array_merge($errors, $result['errors']);
                }
            }
            
            wp_send_json_success(array(
                'preview' => $preview,
                'total_rows' => count($sample_data),
                'has_errors' => !empty($errors),
                'error_summary' => $this->summarizeErrors($errors)
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
        $mapping = isset($_POST['mapping']) ? $_POST['mapping'] : array();
        $options = isset($_POST['options']) ? $_POST['options'] : array();
        $file_data = isset($_POST['file_data']) ? $_POST['file_data'] : array();
        
        if (empty($type) || empty($mapping) || empty($file_data)) {
            wp_send_json_error('Missing required data');
            return;
        }
        
        try {
            // Generate session ID
            $session_id = wp_generate_uuid4();
            
            // Store session data in transient (valid for 1 hour)
            $session_data = array(
                'user_id' => $user_id,
                'type' => $type,
                'mapping' => $mapping,
                'options' => $options,
                'file_data' => $file_data,
                'status' => 'initialized',
                'progress' => array(
                    'total' => count($file_data['rows']),
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
                    'errors' => array()
                )
            );
            
            set_transient('cm_import_session_' . $session_id, $session_data, HOUR_IN_SECONDS);
            
            wp_send_json_success(array(
                'session_id' => $session_id,
                'total_rows' => count($file_data['rows'])
            ));
            
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
        
        // Get session data
        $session_data = get_transient('cm_import_session_' . $session_id);
        
        if (!$session_data) {
            wp_send_json_error('Invalid or expired session');
            return;
        }
        
        // Verify user owns this session
        if ($session_data['user_id'] != $user_id) {
            wp_send_json_error('Unauthorized access to import session');
            return;
        }
        
        try {
            // Process batch
            $handler = new Club_Manager_Import_Handler();
            $handler->setOptions($session_data['options']);
            
            $batch_size = 25; // Process 25 rows at a time
            $start_index = $session_data['progress']['current_batch'] * $batch_size;
            $rows_to_process = array_slice($session_data['file_data']['rows'], $start_index, $batch_size);
            
            $batch_results = $handler->processBatch(
                $rows_to_process,
                $session_data['type'],
                $session_data['mapping'],
                $start_index,
                $user_id
            );
            
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
                $session_data['results']['errors'] = array_merge(
                    $session_data['results']['errors'],
                    $batch_results['errors']
                );
            }
            
            // Check if complete
            $complete = $session_data['progress']['processed'] >= $session_data['progress']['total'];
            
            if ($complete) {
                $session_data['status'] = 'completed';
                
                // Send trainer invitations if enabled
                if ($session_data['type'] === 'trainers' || $session_data['type'] === 'trainers-with-assignments') {
                    if (!empty($session_data['options']['sendInvitations']) && !empty($batch_results['trainers_to_invite'])) {
                        $this->sendTrainerInvitations($batch_results['trainers_to_invite'], $user_id);
                    }
                }
            }
            
            // Update transient
            set_transient('cm_import_session_' . $session_id, $session_data, HOUR_IN_SECONDS);
            
            // Return progress
            wp_send_json_success(array(
                'processed' => $session_data['progress']['processed'],
                'successful' => $session_data['progress']['successful'],
                'failed' => $session_data['progress']['failed'],
                'errors' => array_slice($batch_results['errors'], 0, 10), // Return last 10 errors
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
            delete_transient('cm_import_session_' . $session_id);
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
        $filters = isset($_POST['filters']) ? $_POST['filters'] : array();
        
        if (empty($type) || empty($format)) {
            wp_send_json_error('Missing export type or format');
            return;
        }
        
        try {
            $handler = new Club_Manager_Export_Handler();
            $handler->setUserId($user_id);
            $handler->setFilters($filters);
            
            // Get export data
            $data = $handler->getExportData($type);
            
            if (empty($data)) {
                wp_send_json_error('No data to export');
                return;
            }
            
            // Generate file content
            if ($format === 'csv') {
                $content = $handler->generateCSV($data, $type);
                $filename = 'club_manager_' . $type . '_' . date('Y-m-d_H-i-s') . '.csv';
                $mime_type = 'text/csv';
            } else {
                $content = $handler->generateExcel($data, $type);
                $filename = 'club_manager_' . $type . '_' . date('Y-m-d_H-i-s') . '.xlsx';
                $mime_type = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
            }
            
            // For small files, return data directly
            if (strlen($content) < 1024 * 1024) { // Less than 1MB
                wp_send_json_success(array(
                    'data' => $content,
                    'filename' => $filename,
                    'mime_type' => $mime_type
                ));
            } else {
                // For larger files, save to uploads and provide download URL
                $upload_dir = wp_upload_dir();
                $export_dir = $upload_dir['basedir'] . '/club-manager-exports';
                
                if (!file_exists($export_dir)) {
                    wp_mkdir_p($export_dir);
                }
                
                $file_path = $export_dir . '/' . $filename;
                file_put_contents($file_path, $content);
                
                // Generate secure download URL
                $download_url = admin_url('admin-ajax.php') . '?' . http_build_query(array(
                    'action' => 'cm_download_export',
                    'file' => basename($file_path),
                    'nonce' => wp_create_nonce('cm_download_' . basename($file_path))
                ));
                
                wp_send_json_success(array(
                    'download_url' => $download_url
                ));
            }
            
        } catch (Exception $e) {
            wp_send_json_error('Export error: ' . $e->getMessage());
        }
    }
    
    /**
     * Summarize validation errors.
     */
    private function summarizeErrors($errors) {
        $summary = array();
        
        foreach ($errors as $error) {
            $field = $error['field'] ?? 'general';
            if (!isset($summary[$field])) {
                $summary[$field] = 0;
            }
            $summary[$field]++;
        }
        
        return $summary;
    }
    
    /**
     * Send trainer invitations.
     */
    private function sendTrainerInvitations($trainers, $inviter_id) {
        if (empty($trainers)) return;
        
        foreach ($trainers as $trainer_data) {
            try {
                // Use existing trainer invitation system
                $trainer_ajax = new Club_Manager_Trainer_Ajax();
                
                // Format data for invitation
                $_POST['email'] = $trainer_data['email'];
                $_POST['teams'] = $trainer_data['team_ids'];
                $_POST['role'] = $trainer_data['role'] ?? 'trainer';
                $_POST['message'] = 'You have been invited to join as a trainer through bulk import.';
                
                // Note: This would need to be refactored to not use $_POST directly
                // For now, we'll just log that invitations should be sent
                error_log('Club Manager: Trainer invitation needed for ' . $trainer_data['email']);
                
            } catch (Exception $e) {
                error_log('Club Manager: Failed to send trainer invitation: ' . $e->getMessage());
            }
        }
    }
}