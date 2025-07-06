<?php

/**
 * Import queue handler using WordPress Cron for batch processing.
 */
class Club_Manager_Import_Queue {
    
    /**
     * Initialize the queue system.
     */
    public static function init() {
        // Register cron hook
        add_action('cm_process_import_queue', array(__CLASS__, 'process_queue'));
        
        // Register cleanup hook
        add_action('cm_cleanup_import_sessions', array(__CLASS__, 'cleanup_old_sessions'));
        
        // Schedule daily cleanup if not already scheduled
        if (!wp_next_scheduled('cm_cleanup_import_sessions')) {
            wp_schedule_event(time(), 'daily', 'cm_cleanup_import_sessions');
        }
    }
    
    /**
     * Add import job to queue.
     */
    public static function add_job($session_id, $batch_data) {
        // Store job in wp_options
        $queue_key = 'cm_import_queue_' . $session_id;
        $jobs = get_option($queue_key, array());
        
        $jobs[] = array(
            'batch_id' => wp_generate_uuid4(),
            'data' => $batch_data,
            'status' => 'pending',
            'created_at' => current_time('mysql')
        );
        
        update_option($queue_key, $jobs, false);
        
        // Schedule processing
        wp_schedule_single_event(time() + 5, 'cm_process_import_queue', array($session_id));
        
        return true;
    }
    
    /**
     * Process import queue.
     */
    public static function process_queue($session_id) {
        // Get session data
        $session_data = get_option('cm_import_session_' . $session_id);
        
        if (!$session_data || $session_data['status'] === 'completed') {
            return;
        }
        
        // Check if processing is paused
        if (!empty($session_data['paused'])) {
            // Reschedule for later
            wp_schedule_single_event(time() + 300, 'cm_process_import_queue', array($session_id));
            return;
        }
        
        // Get queue
        $queue_key = 'cm_import_queue_' . $session_id;
        $jobs = get_option($queue_key, array());
        
        if (empty($jobs)) {
            return;
        }
        
        // Find next pending job
        $current_job = null;
        $job_index = null;
        
        foreach ($jobs as $index => $job) {
            if ($job['status'] === 'pending') {
                $current_job = $job;
                $job_index = $index;
                break;
            }
        }
        
        if (!$current_job) {
            return;
        }
        
        // Mark job as processing
        $jobs[$job_index]['status'] = 'processing';
        update_option($queue_key, $jobs, false);
        
        try {
            // Process the batch
            $handler = new Club_Manager_Import_Handler();
            $handler->setOptions($session_data['options']);
            
            $batch_results = $handler->processBatch(
                $current_job['data']['rows'],
                $session_data['type'],
                $session_data['mapping'],
                $current_job['data']['start_index'],
                $session_data['user_id']
            );
            
            // Update session progress
            $session_data['progress']['processed'] += count($current_job['data']['rows']);
            $session_data['progress']['successful'] += $batch_results['successful'];
            $session_data['progress']['failed'] += $batch_results['failed'];
            
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
            
            // Collect trainers to invite
            if (!empty($batch_results['trainers_to_invite'])) {
                if (!isset($session_data['trainers_to_invite'])) {
                    $session_data['trainers_to_invite'] = array();
                }
                $session_data['trainers_to_invite'] = array_merge(
                    $session_data['trainers_to_invite'],
                    $batch_results['trainers_to_invite']
                );
            }
            
            // Mark job as completed
            $jobs[$job_index]['status'] = 'completed';
            $jobs[$job_index]['completed_at'] = current_time('mysql');
            
            // Log success
            Club_Manager_Logger::log_import(
                $session_data['type'],
                'batch_completed',
                array(
                    'session_id' => $session_id,
                    'batch_id' => $current_job['batch_id'],
                    'processed' => count($current_job['data']['rows']),
                    'successful' => $batch_results['successful']
                )
            );
            
        } catch (Exception $e) {
            // Mark job as failed
            $jobs[$job_index]['status'] = 'failed';
            $jobs[$job_index]['error'] = $e->getMessage();
            
            // Log error
            Club_Manager_Logger::log_import(
                $session_data['type'],
                'batch_failed',
                array(
                    'session_id' => $session_id,
                    'batch_id' => $current_job['batch_id'],
                    'error' => $e->getMessage()
                )
            );
        }
        
        // Update queue
        update_option($queue_key, $jobs, false);
        
        // Check if all done
        $pending_jobs = array_filter($jobs, function($job) {
            return $job['status'] === 'pending';
        });
        
        if (empty($pending_jobs)) {
            // All jobs completed
            $session_data['status'] = 'completed';
            
            // Send trainer invitations if needed
            if (!empty($session_data['trainers_to_invite']) && 
                !empty($session_data['options']['sendInvitations'])) {
                self::schedule_trainer_invitations($session_data['trainers_to_invite'], $session_data['user_id']);
            }
            
            // Clean up queue
            delete_option($queue_key);
        } else {
            // Schedule next batch
            wp_schedule_single_event(time() + 5, 'cm_process_import_queue', array($session_id));
        }
        
        // Update session
        update_option('cm_import_session_' . $session_id, $session_data, false);
    }
    
    /**
     * Schedule trainer invitations.
     */
    private static function schedule_trainer_invitations($trainers, $inviter_id) {
        // Process in batches to avoid rate limits
        $batches = array_chunk($trainers, 10);
        $delay = 0;
        
        foreach ($batches as $batch) {
            wp_schedule_single_event(
                time() + $delay,
                'cm_send_trainer_invitations',
                array($batch, $inviter_id)
            );
            $delay += 60; // 1 minute between batches
        }
    }
    
    /**
     * Cleanup old import sessions.
     */
    public static function cleanup_old_sessions() {
        global $wpdb;
        
        // Get all import session options
        $sessions = $wpdb->get_results(
            "SELECT option_name, option_value 
             FROM {$wpdb->options} 
             WHERE option_name LIKE 'cm_import_session_%'"
        );
        
        $deleted = 0;
        
        foreach ($sessions as $session) {
            $data = maybe_unserialize($session->option_value);
            
            if (is_array($data) && isset($data['created_at'])) {
                // Delete sessions older than 24 hours
                $age = time() - strtotime($data['created_at']);
                
                if ($age > DAY_IN_SECONDS) {
                    delete_option($session->option_name);
                    
                    // Also delete associated queue
                    $session_id = str_replace('cm_import_session_', '', $session->option_name);
                    delete_option('cm_import_queue_' . $session_id);
                    
                    $deleted++;
                }
            }
        }
        
        if ($deleted > 0) {
            Club_Manager_Logger::log(
                'Cleaned up old import sessions',
                'info',
                array('deleted' => $deleted)
            );
        }
    }
    
    /**
     * Get queue status for a session.
     */
    public static function get_queue_status($session_id) {
        $queue_key = 'cm_import_queue_' . $session_id;
        $jobs = get_option($queue_key, array());
        
        if (empty($jobs)) {
            return null;
        }
        
        $status = array(
            'total' => count($jobs),
            'pending' => 0,
            'processing' => 0,
            'completed' => 0,
            'failed' => 0
        );
        
        foreach ($jobs as $job) {
            $status[$job['status']]++;
        }
        
        return $status;
    }
    
    /**
     * Pause import session.
     */
    public static function pause_session($session_id) {
        $session_data = get_option('cm_import_session_' . $session_id);
        
        if ($session_data) {
            $session_data['paused'] = true;
            update_option('cm_import_session_' . $session_id, $session_data, false);
            return true;
        }
        
        return false;
    }
    
    /**
     * Resume import session.
     */
    public static function resume_session($session_id) {
        $session_data = get_option('cm_import_session_' . $session_id);
        
        if ($session_data) {
            $session_data['paused'] = false;
            update_option('cm_import_session_' . $session_id, $session_data, false);
            
            // Reschedule processing
            wp_schedule_single_event(time() + 5, 'cm_process_import_queue', array($session_id));
            
            return true;
        }
        
        return false;
    }
}

// Initialize the queue system
add_action('init', array('Club_Manager_Import_Queue', 'init'));

// Add trainer invitation sender
add_action('cm_send_trainer_invitations', function($trainers, $inviter_id) {
    $trainer_ajax = new Club_Manager_Trainer_Ajax();
    
    foreach ($trainers as $trainer_data) {
        try {
            $_POST['email'] = $trainer_data['email'];
            $_POST['teams'] = $trainer_data['team_ids'];
            $_POST['role'] = $trainer_data['role'] ?? 'trainer';
            $_POST['message'] = 'You have been invited to join as a trainer.';
            $_POST['nonce'] = wp_create_nonce('club_manager_nonce');
            
            ob_start();
            $trainer_ajax->invite_trainer();
            ob_end_clean();
            
            // Small delay between invitations
            usleep(500000); // 0.5 seconds
            
        } catch (Exception $e) {
            Club_Manager_Logger::log(
                'Failed to send trainer invitation',
                'error',
                array(
                    'email' => $trainer_data['email'],
                    'error' => $e->getMessage()
                )
            );
        }
    }
}, 10, 2);