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
        
        try {
            $parser = new Club_Manager_CSV_Parser();
            $data = $parser->parse($file['tmp_name'], $file['type']);
            
            if (empty($data['headers']) || empty($data['rows'])) {
                wp_send_json_error('File is empty or invalid format');
                return;
            }
            
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
        $_POST = stripslashes_deep($_POST); // Remove slashes added by WordPress
        $user_id = $this->verify_request();
        
        if (!Club_Manager_User_Permissions_Helper::can_import_export($user_id)) {
            wp_send_json_error('You do not have permission to import data');
            return;
        }
        
        $type = $this->get_post_data('type');
        $mapping = isset($_POST['mapping']) ? json_decode($_POST['mapping'], true) : array();
        $options = isset($_POST['options']) ? json_decode($_POST['options'], true) : array();
        $raw_sample_data = isset($_POST['sample_data']) ? $_POST['sample_data'] : array();

        $sample_data = [];
        if (!empty($raw_sample_data) && is_array($raw_sample_data)) {
            foreach ($raw_sample_data as $row_string) {
                 if (is_string($row_string)) {
                    $sample_data[] = str_getcsv($row_string);
                 } else {
                    $sample_data[] = $row_string;
                 }
            }
        }
        
        if (empty($mapping) || empty($sample_data)) {
            wp_send_json_error('Missing mapping or sample data');
            return;
        }
        
        try {
            $validator = new Club_Manager_Data_Validator();
            $validator->setOptions($options);
            
            $preview = array();
            
            foreach ($sample_data as $index => $row) {
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
            
            wp_send_json_success(array('preview' => $preview));
            
        } catch (Exception $e) {
            wp_send_json_error('Validation error: ' . $e->getMessage());
        }
    }
    
    /**
     * Initialize import session.
     */
    public function init_import_session() {
        $_POST = stripslashes_deep($_POST); // Remove slashes added by WordPress
        $user_id = $this->verify_request();
        
        if (!Club_Manager_User_Permissions_Helper::can_import_export($user_id)) {
            wp_send_json_error('You do not have permission to import data');
            return;
        }
        
        $type = $this->get_post_data('type');
        $mapping = isset($_POST['mapping']) ? json_decode($_POST['mapping'], true) : array();
        $options = isset($_POST['options']) ? json_decode($_POST['options'], true) : array();
        $file_data = isset($_POST['file_data']) ? json_decode($_POST['file_data'], true) : array();
        
        if (empty($type) || empty($mapping) || empty($file_data)) {
            wp_send_json_error('Missing required data');
            return;
        }
        
        try {
            $session_id = wp_generate_uuid4();
            
            $session_data = array(
                'user_id' => $user_id,
                'type' => $type,
                'mapping' => $mapping,
                'options' => $options,
                'file_data' => $file_data,
                'status' => 'initialized',
                'progress' => ['total' => count($file_data['rows']), 'processed' => 0, 'successful' => 0, 'failed' => 0, 'current_batch' => 0],
                'results' => ['created' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0, 'errors' => []],
                'created_at' => current_time('mysql')
            );
            
            update_option('cm_import_session_' . $session_id, $session_data, false);
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
            $rows_to_process = array_slice($session_data['file_data']['rows'], $start_index, $batch_size);
            
            if(empty($rows_to_process)){
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

            $mapped_rows = array();
            foreach ($rows_to_process as $row) {
                $mapped_data = array();
                foreach ($session_data['mapping'] as $field => $column_index) {
                    if (isset($row[$column_index])) {
                        $mapped_data[$field] = trim($row[$column_index]);
                    }
                }
                $mapped_rows[] = $mapped_data;
            }
            
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
                $session_data['trainers_to_invite'] = array_merge($session_data['trainers_to_invite'] ?? [], $batch_results['trainers_to_invite']);
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
        $filters = isset($_POST['filters']) ? $_POST['filters'] : array();
        
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
        
        $trainer_ajax = new Club_Manager_Trainer_Ajax();
        
        foreach ($trainers as $trainer_data) {
            try {
                $_POST = [
                    'email' => $trainer_data['email'],
                    'teams' => $trainer_data['team_ids'],
                    'role' => $trainer_data['role'] ?? 'trainer',
                    'message' => 'You have been invited to join as a trainer through bulk import.',
                    'nonce' => wp_create_nonce('club_manager_nonce')
                ];
                
                ob_start();
                $trainer_ajax->invite_trainer();
                ob_end_clean();
                
            } catch (Exception $e) {
                Club_Manager_Logger::log('Exception during trainer invitation: ' . $e->getMessage(), 'error', ['email' => $trainer_data['email']]);
            }
        }
    }
}

// Add cleanup action for expired sessions
add_action('cm_cleanup_import_session', function($session_id) {
    delete_option('cm_import_session_' . $session_id);
});