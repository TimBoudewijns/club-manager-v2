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
        
        // Sorteer op season key (name) in aflopende volgorde (nieuwste eerst)
        // krsort sorts by KEY in reverse order (maintains key-value associations)
        krsort($seasons);
        
        error_log("Club Manager Debug - Available seasons after sorting: " . print_r(array_keys($seasons), true));
        
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
        error_log("Club Manager Debug - get_user_preferred_season: User {$user_id}, preferred: {$preferred}");
        
        // Check if preferred season still exists
        if (!empty($preferred)) {
            $available_seasons = self::get_available_seasons();
            if (isset($available_seasons[$preferred])) {
                error_log("Club Manager Debug - Using preferred season: {$preferred}");
                return $preferred;
            } else {
                error_log("Club Manager Debug - Preferred season {$preferred} not found in available seasons: " . print_r(array_keys($available_seasons), true));
            }
        }
        
        // If this is the first time or preferred season doesn't exist anymore,
        // check if user has access to current season data, otherwise use their most recent season with data
        $available_seasons = self::get_available_seasons();
        $seasons_with_data = [];
        
        // Check which seasons have data for this user
        foreach ($available_seasons as $season_key => $season_data) {
            if (self::user_has_data_in_season($user_id, $season_key)) {
                $seasons_with_data[] = $season_key;
            }
        }
        
        // If user has data in multiple seasons, use the most recent one with data
        if (!empty($seasons_with_data)) {
            // seasons_with_data will be in the same order as available_seasons (newest first)
            error_log("Club Manager Debug - User has data in seasons: " . print_r($seasons_with_data, true) . ", using: {$seasons_with_data[0]}");
            return $seasons_with_data[0];
        }
        
        // Fallback to current season
        $current_season = self::get_current_season();
        error_log("Club Manager Debug - Using fallback current season: {$current_season}");
        return $current_season;
    }
    
    /**
     * Check if user has data in a specific season (teams, evaluations, etc.)
     * 
     * @param int $user_id
     * @param string $season_name
     * @return bool
     */
    public static function user_has_data_in_season($user_id, $season_name) {
        global $wpdb;
        
        // Check if user has teams in this season
        $teams_table = Club_Manager_Database::get_table_name('teams');
        $team_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $teams_table WHERE created_by = %d AND season = %s",
            $user_id, $season_name
        ));
        
        if ($team_count > 0) {
            return true;
        }
        
        // Check if user has evaluations in this season
        $evaluations_table = Club_Manager_Database::get_table_name('evaluations');
        $eval_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT e.id) FROM $evaluations_table e 
             INNER JOIN $teams_table t ON e.team_id = t.id 
             WHERE t.created_by = %d AND e.season = %s",
            $user_id, $season_name
        ));
        
        return $eval_count > 0;
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