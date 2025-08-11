<?php
/**
 * Season management helper
 * 
 * @package Club_Manager
 * @subpackage Helpers
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Helper class for season management
 */
class Club_Manager_Season_Helper {
    
    /**
     * Get all available seasons
     * 
     * @return array
     */
    public static function get_available_seasons() {
        $seasons = get_option('cm_available_seasons', []);
        
        // Als geen seasons zijn opgeslagen, maak default seasons
        if (empty($seasons)) {
            $seasons = self::get_default_seasons();
            update_option('cm_available_seasons', $seasons);
        }
        
        // Sorteer op aflopende volgorde (nieuwste eerst)
        arsort($seasons);
        
        return $seasons;
    }
    
    /**
     * Get current season (most recent)
     * 
     * @return string
     */
    public static function get_current_season() {
        $seasons = self::get_available_seasons();
        return !empty($seasons) ? array_keys($seasons)[0] : '2024-2025';
    }
    
    /**
     * Add a new season
     * 
     * @param string $season_name
     * @return bool
     */
    public static function add_season($season_name) {
        if (empty($season_name)) {
            return false;
        }
        
        $seasons = self::get_available_seasons();
        $seasons[$season_name] = [
            'name' => $season_name,
            'created_at' => current_time('mysql'),
            'is_active' => true
        ];
        
        return update_option('cm_available_seasons', $seasons);
    }
    
    /**
     * Remove a season (only if no data exists)
     * 
     * @param string $season_name
     * @return bool|WP_Error
     */
    public static function remove_season($season_name) {
        // Check if season has data
        if (self::season_has_data($season_name)) {
            return new WP_Error('season_has_data', 'Cannot delete season with existing data');
        }
        
        $seasons = self::get_available_seasons();
        
        if (count($seasons) <= 1) {
            return new WP_Error('last_season', 'Cannot delete the last season');
        }
        
        unset($seasons[$season_name]);
        
        return update_option('cm_available_seasons', $seasons);
    }
    
    /**
     * Check if season has any data (teams, players, evaluations)
     * 
     * @param string $season_name
     * @return bool
     */
    public static function season_has_data($season_name) {
        global $wpdb;
        
        // Check teams
        $teams_table = Club_Manager_Database::get_table_name('teams');
        $team_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $teams_table WHERE season = %s",
            $season_name
        ));
        
        if ($team_count > 0) {
            return true;
        }
        
        // Check evaluations
        $evaluations_table = Club_Manager_Database::get_table_name('evaluations');
        $eval_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $evaluations_table WHERE season = %s",
            $season_name
        ));
        
        return $eval_count > 0;
    }
    
    /**
     * Get default seasons
     * 
     * @return array
     */
    private static function get_default_seasons() {
        $current_year = date('Y');
        $next_year = $current_year + 1;
        
        return [
            "$current_year-$next_year" => [
                'name' => "$current_year-$next_year",
                'created_at' => current_time('mysql'),
                'is_active' => true
            ],
            ($current_year - 1) . "-$current_year" => [
                'name' => ($current_year - 1) . "-$current_year",
                'created_at' => current_time('mysql'),
                'is_active' => false
            ]
        ];
    }
    
    /**
     * Get user's preferred season or fallback to current
     * 
     * @param int $user_id
     * @return string
     */
    public static function get_user_preferred_season($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $preferred = get_user_meta($user_id, 'cm_preferred_season', true);
        
        // Check if preferred season still exists
        if (!empty($preferred)) {
            $available_seasons = self::get_available_seasons();
            if (isset($available_seasons[$preferred])) {
                return $preferred;
            }
        }
        
        // Fallback to current season
        return self::get_current_season();
    }
    
    /**
     * Check if this is user's first login (no season preference set)
     * 
     * @param int $user_id
     * @return bool
     */
    public static function is_first_season_selection($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $preferred = get_user_meta($user_id, 'cm_preferred_season', true);
        return empty($preferred);
    }
}