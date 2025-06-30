<?php

/**
 * Logger class for Club Manager - WordPress Debug Logging Integration
 */
class Club_Manager_Logger {
    
    /**
     * Log a message with context.
     * 
     * @param string $message The message to log
     * @param string $type Log level: info, warning, error, debug
     * @param array $context Additional context data
     */
    public static function log($message, $type = 'info', $context = array()) {
        // Only log if WP_DEBUG is enabled
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $formatted_message = sprintf(
                '[Club Manager][%s][%s] %s',
                date('Y-m-d H:i:s'),
                strtoupper($type),
                $message
            );
            
            // Add context if provided
            if (!empty($context)) {
                $formatted_message .= ' ' . json_encode($context);
            }
            
            // Log to WordPress debug log
            error_log($formatted_message);
        }
        
        // Store critical errors in database for admin dashboard
        if ($type === 'error') {
            self::store_error($message, $context);
        }
    }
    
    /**
     * Store critical errors in database.
     */
    private static function store_error($message, $context = array()) {
        $errors = get_option('club_manager_errors', array());
        
        // Keep only last 100 errors
        if (count($errors) >= 100) {
            array_shift($errors);
        }
        
        $errors[] = array(
            'timestamp' => current_time('mysql'),
            'message' => $message,
            'context' => $context,
            'user_id' => get_current_user_id()
        );
        
        update_option('club_manager_errors', $errors, false);
    }
    
    /**
     * Log import operation.
     */
    public static function log_import($type, $status, $details = array()) {
        $message = sprintf('Import operation: %s - Status: %s', $type, $status);
        self::log($message, 'info', $details);
    }
    
    /**
     * Log export operation.
     */
    public static function log_export($type, $status, $details = array()) {
        $message = sprintf('Export operation: %s - Status: %s', $type, $status);
        self::log($message, 'info', $details);
    }
    
    /**
     * Log trainer invitation.
     */
    public static function log_invitation($email, $status, $details = array()) {
        $message = sprintf('Trainer invitation to %s - Status: %s', $email, $status);
        self::log($message, 'info', array_merge(array('email' => $email), $details));
    }
    
    /**
     * Log API errors.
     */
    public static function log_api_error($endpoint, $error, $details = array()) {
        $message = sprintf('API Error at %s: %s', $endpoint, $error);
        self::log($message, 'error', $details);
    }
    
    /**
     * Get stored errors for admin display.
     */
    public static function get_errors($limit = 50) {
        $errors = get_option('club_manager_errors', array());
        
        // Sort by timestamp descending
        usort($errors, function($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });
        
        // Return limited number
        return array_slice($errors, 0, $limit);
    }
    
    /**
     * Clear stored errors.
     */
    public static function clear_errors() {
        delete_option('club_manager_errors');
    }
    
    /**
     * Log debug information.
     */
    public static function debug($message, $data = null) {
        if (defined('WP_DEBUG') && WP_DEBUG && defined('CLUB_MANAGER_DEBUG') && CLUB_MANAGER_DEBUG) {
            self::log($message, 'debug', $data ? array('data' => $data) : array());
        }
    }
}