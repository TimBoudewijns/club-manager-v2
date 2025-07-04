<?php

/**
 * Handle import/export AJAX requests.
 */
class Club_Manager_Import_Export_Ajax extends Club_Manager_Ajax_Handler {
    
    /**
     * Initialize AJAX actions.
     */
    public function init() {
        add_action('wp_ajax_cm_parse_import_file', array($this, 'parse_import_file'));
        add_action('wp_ajax_cm_validate_import_data', array($this, 'validate_import_data'));
        add_action('wp_ajax_cm_init_import_session', array($this, 'init_import_session'));
        add_action('wp_ajax_cm_process_import_batch', array($this, 'process_import_batch'));
        add_action('wp_ajax_cm_cancel_import_session', array($this, 'cancel_import_session'));
        add_action('wp_ajax_cm_export_data', array($this, 'export_data'));
    }
    
    /**
     * Parse uploaded import file.
     */
    public function parse_import_file() {
        $this->verify_request(true);
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(['message' => 'No file uploaded or upload error.']);
        }
        
        try {
            $parser = new Club_Manager_CSV_Parser();
            $data = $parser->parse($_FILES['file']['tmp_name'], $_FILES['file']['type']);
            wp_send_json_success($data);
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'Error parsing file: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Decode JSON data from POST.
     */
    private function get_json_post_data($key) {
        $value = $this->get_post_data($key);
        if (empty($value)) return [];
        $decoded = json_decode(stripslashes($value), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error(['message' => 'Invalid JSON format for key: ' . $key]);
        }
        return $decoded;
    }

    /**
     * Validate import data.
     */
    public function validate_import_data() {
        $this->verify_request(true);
        $type = $this->get_post_data('type');
        $mapping = $this->get_json_post_data('mapping');
        $options = $this->get_json_post_data('options');
        $sample_data = $this->get_json_post_data('sample_data');

        $validator = new Club_Manager_Data_Validator();
        $validator->setOptions($options);
        $preview = [];

        foreach ($sample_data as $index => $row) {
            $mapped_data = [];
            foreach ($mapping as $field => $col_index) {
                $mapped_data[$field] = $row[$col_index] ?? '';
            }
            $result = $validator->validateRow($mapped_data, $type, $index);
            $preview[] = ['row' => $index + 1, 'data' => $result['data'], 'status' => $result['valid'] ? 'valid' : 'error', 'errors' => $result['errors']];
        }
        
        wp_send_json_success(['preview' => $preview]);
    }
    
    /**
     * Initialize import session.
     */
    public function init_import_session() {
        $user_id = $this->verify_request(true);
        $session_id = wp_generate_uuid4();
        $file_data = $this->get_json_post_data('file_data');

        $session_data = [
            'user_id' => $user_id,
            'type' => $this->get_post_data('type'),
            'mapping' => $this->get_json_post_data('mapping'),
            'options' => $this->get_json_post_data('options'),
            'file_data' => $file_data,
            'status' => 'initialized',
            'progress' => ['total' => count($file_data['rows'] ?? []), 'processed' => 0, 'successful' => 0, 'failed' => 0],
            'results' => ['created' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0, 'errors' => []],
            'trainers_to_invite' => []
        ];
        
        set_transient('cm_import_session_' . $session_id, $session_data, DAY_IN_SECONDS);
        wp_send_json_success(['session_id' => $session_id]);
    }
    
    /**
     * Process import batch.
     */
    public function process_import_batch() {
        $user_id = $this->verify_request(true);
        $session_id = $this->get_post_data('session_id');
        $session_data = get_transient('cm_import_session_' . $session_id);

        if (!$session_data || $session_data['user_id'] != $user_id) {
            wp_send_json_error(['message' => 'Invalid or unauthorized session.']);
        }
        
        $handler = new Club_Manager_Import_Handler();
        $handler->setOptions($session_data['options']);
        
        $batch_size = 50;
        $start_index = $session_data['progress']['processed'];
        $rows_to_process = array_slice($session_data['file_data']['rows'], $start_index, $batch_size);
        
        if (empty($rows_to_process)) {
            $this->finalize_import($session_id, $session_data);
            return;
        }

        $mapped_rows = [];
        foreach ($rows_to_process as $row) {
            $mapped_data = [];
            foreach ($session_data['mapping'] as $field => $col_index) {
                $mapped_data[$field] = $row[$col_index] ?? '';
            }
            $mapped_rows[] = $mapped_data;
        }

        $batch_results = $handler->processBatch($mapped_rows, $session_data['type'], $start_index, $user_id);
        
        // Update session
        $session_data['progress']['processed'] += count($rows_to_process);
        foreach (['successful', 'failed', 'created', 'updated', 'skipped'] as $key) {
            $session_data['progress'][$key] = ($session_data['progress'][$key] ?? 0) + $batch_results[$key];
            $session_data['results'][$key] = ($session_data['results'][$key] ?? 0) + $batch_results[$key];
        }
        $session_data['results']['errors'] = array_merge($session_data['results']['errors'], $batch_results['errors']);
        if (!empty($batch_results['trainers_to_invite'])) {
            $session_data['trainers_to_invite'] = array_merge($session_data['trainers_to_invite'], $batch_results['trainers_to_invite']);
        }
        
        set_transient('cm_import_session_' . $session_id, $session_data, DAY_IN_SECONDS);

        $complete = $session_data['progress']['processed'] >= $session_data['progress']['total'];
        if ($complete) {
            $this->finalize_import($session_id, $session_data);
            return;
        }
        
        wp_send_json_success([
            'processed' => $session_data['progress']['processed'],
            'successful' => $session_data['progress']['successful'],
            'failed' => $session_data['progress']['failed'],
            'errors' => $batch_results['errors'],
            'complete' => false,
        ]);
    }

    private function finalize_import($session_id, $session_data) {
        $session_data['status'] = 'completed';
        if (!empty($session_data['options']['sendInvitations']) && !empty($session_data['trainers_to_invite'])) {
            wp_schedule_single_event(time() + 5, 'cm_send_bulk_trainer_invitations', [$session_data['trainers_to_invite'], $session_data['user_id'], $session_id]);
        }
        set_transient('cm_import_session_' . $session_id, $session_data, DAY_IN_SECONDS);
        wp_send_json_success([
            'processed' => $session_data['progress']['processed'],
            'successful' => $session_data['progress']['successful'],
            'failed' => $session_data['progress']['failed'],
            'complete' => true,
            'results' => $session_data['results']
        ]);
    }
    
    /**
     * Cancel import session.
     */
    public function cancel_import_session() {
        $this->verify_request();
        delete_transient('cm_import_session_' . $this->get_post_data('session_id'));
        wp_send_json_success();
    }
    
    /**
     * Export data.
     */
    public function export_data() {
        $user_id = $this->verify_request(true);
        $type = $this->get_post_data('type');
        $format = $this->get_post_data('format');
        $filters = $this->get_json_post_data('filters');

        try {
            $handler = new Club_Manager_Export_Handler();
            $handler->setUserId($user_id);
            $handler->setFilters($filters);
            $data = $handler->getExportData($type);
            $content = $handler->generateCSV($data, $type);
            $filename = 'club_manager_' . $type . '_' . date('Y-m-d') . '.csv';
            wp_send_json_success(['data' => $content, 'filename' => $filename]);
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'Export error: ' . $e->getMessage()]);
        }
    }
}
