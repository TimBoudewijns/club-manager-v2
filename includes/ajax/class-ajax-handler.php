<?php

/**
 * Base AJAX handler class.
 */
abstract class Club_Manager_Ajax_Handler {
    
    /**
     * Verify nonce and user permissions.
     * The permission check is now optional to support all AJAX calls.
     * @param bool $check_import_permissions Whether to check for import/export capabilities.
     */
    protected function verify_request($check_import_permissions = false) {
        if (!check_ajax_referer('club_manager_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'Invalid security token']);
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'You must be logged in']);
        }

        $user_id = get_current_user_id();

        // Only check for specific import/export permissions when required.
        if ($check_import_permissions && !Club_Manager_User_Permissions_Helper::can_import_export($user_id)) {
            wp_send_json_error(['message' => 'You do not have permission for this action']);
        }
        
        return $user_id;
    }
    
    /**
     * Get and sanitize POST data.
     * This function is now robust enough to handle plain values, arrays, and JSON strings.
     */
    protected function get_post_data($key, $type = 'text', $default = '') {
        if (!isset($_POST[$key])) {
            return $default;
        }

        $value = stripslashes_deep($_POST[$key]);

        // If the client sent a JSON string, decode it first.
        if (is_string($value) && (strpos($value, '[') === 0 || strpos($value, '{') === 0)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                // It's valid JSON, so we use the decoded array/object.
                // Further sanitization should happen where this data is used.
                return $decoded;
            }
        }

        // If it's not JSON or is a plain array from form data (e.g., key[]), handle it.
        if (is_array($value)) {
            // Sanitize each item in the array recursively.
            return array_map('sanitize_text_field', $value);
        }

        // For simple, non-JSON string values, sanitize based on type.
        switch ($type) {
            case 'int':
                return intval($value);
            case 'float':
                return floatval($value);
            case 'email':
                return sanitize_email($value);
            case 'textarea':
                return sanitize_textarea_field($value);
            case 'text':
            default:
                return sanitize_text_field($value);
        }
    }
    
    /**
     * Verify team ownership.
     */
    protected function verify_team_ownership($team_id, $user_id) {
        global $wpdb;
        $table = Club_Manager_Database::get_table_name('teams');
        $owner = $wpdb->get_var($wpdb->prepare("SELECT created_by FROM $table WHERE id = %d", $team_id));
        if ($owner != $user_id) {
            wp_send_json_error(['message' => 'Unauthorized access to team']);
        }
        return true;
    }
    
    /**
     * Verify team access - for both owners and assigned trainers.
     */
    protected function verify_team_access($team_id, $user_id) {
        global $wpdb;
        $teams_table = Club_Manager_Database::get_table_name('teams');
        $owner = $wpdb->get_var($wpdb->prepare("SELECT created_by FROM $teams_table WHERE id = %d", $team_id));
        if ($owner == $user_id) return true;

        $trainers_table = Club_Manager_Database::get_table_name('team_trainers');
        $trainer_access = $wpdb->get_var($wpdb->prepare("SELECT id FROM $trainers_table WHERE team_id = %d AND trainer_id = %d AND is_active = 1", $team_id, $user_id));
        if ($trainer_access) return true;
        
        wp_send_json_error(['message' => 'Unauthorized access to team']);
    }
    
    /**
     * Verify player ownership.
     */
    protected function verify_player_ownership($player_id, $user_id) {
        global $wpdb;
        $table = Club_Manager_Database::get_table_name('players');
        $owner = $wpdb->get_var($wpdb->prepare("SELECT created_by FROM $table WHERE id = %d", $player_id));
        if ($owner != $user_id) {
            wp_send_json_error(['message' => 'Unauthorized access to player']);
        }
        return true;
    }
    
    /**
     * Register AJAX actions.
     */
    abstract public function init();
}
