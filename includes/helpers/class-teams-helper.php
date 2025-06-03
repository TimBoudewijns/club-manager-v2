<?php
/**
 * Helper class for Teams for WooCommerce Memberships integration
 * 
 * @package Club_Manager
 * @subpackage Helpers
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Helper class for Teams for WooCommerce Memberships integration
 */
class Club_Manager_Teams_Helper {
    
    /**
     * Alternative method to check teams membership
     */
    private static function check_teams_alternative($user_id) {
        error_log('Club Manager: Trying alternative method for user ' . $user_id);
        
        // Method 1: Check via user meta
        global $wpdb;
        $meta_key = '_wc_memberships_for_teams_team_member_id';
        $team_member_ids = get_user_meta($user_id, $meta_key, false);
        
        if (!empty($team_member_ids)) {
            error_log('Club Manager: Found team member IDs in user meta: ' . print_r($team_member_ids, true));
        }
        
        // Method 2: Direct database query
        $teams_table = $wpdb->prefix . 'wc_memberships_for_teams_teams';
        $team_members_table = $wpdb->prefix . 'wc_memberships_for_teams_team_members';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$team_members_table'") === $team_members_table) {
            $query = $wpdb->prepare(
                "SELECT tm.*, t.name as team_name 
                FROM $team_members_table tm 
                LEFT JOIN $teams_table t ON tm.team_id = t.id 
                WHERE tm.user_id = %d",
                $user_id
            );
            
            $results = $wpdb->get_results($query);
            error_log('Club Manager: Direct DB query results: ' . print_r($results, true));
            
            if (!empty($results)) {
                foreach ($results as $member) {
                    if (isset($member->role) && in_array($member->role, array('owner', 'manager'))) {
                        error_log('Club Manager: Found user as ' . $member->role . ' in team');
                        return true;
                    }
                }
            }
        } else {
            error_log('Club Manager: Teams tables not found in database');
        }
        
        error_log('Club Manager: No access found via normal method');
        
        // Try alternative method
        return self::check_teams_alternative($user_id);
    }
    
    /**
     * Check if user can view club teams
     * 
     * @param int $user_id User ID
     * @return bool
     */
    public static function can_view_club_teams($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        error_log('Club Manager: Checking teams access for user ID: ' . $user_id);
        
        if (!$user_id) {
            error_log('Club Manager: No user ID found');
            return false;
        }
        
        // Check if Teams for WooCommerce Memberships is active
        if (!class_exists('WC_Memberships_For_Teams_Loader')) {
            error_log('Club Manager: WC_Memberships_For_Teams_Loader class not found');
            return false;
        }
        
        error_log('Club Manager: Teams plugin class found');
        
        // Check if the function exists before calling it
        if (!function_exists('wc_memberships_for_teams_get_user_teams')) {
            error_log('Club Manager: wc_memberships_for_teams_get_user_teams function not found');
            
            // Try to load the teams functions
            if (function_exists('wc_memberships_for_teams')) {
                error_log('Club Manager: Trying to initialize teams plugin');
                wc_memberships_for_teams();
            }
            
            // If still not available, return false
            if (!function_exists('wc_memberships_for_teams_get_user_teams')) {
                error_log('Club Manager: Still no function after init attempt');
                return false;
            }
        }
        
        error_log('Club Manager: Function exists, getting teams...');
        
        // Get user's teams
        $teams = wc_memberships_for_teams_get_user_teams($user_id);
        
        error_log('Club Manager: Found ' . count($teams) . ' teams for user');
        
        if (empty($teams)) {
            return false;
        }
        
        // Check each team for owner or manager role
        foreach ($teams as $team) {
            // Check if function exists
            if (!function_exists('wc_memberships_for_teams_get_team_member')) {
                continue;
            }
            
            $member = wc_memberships_for_teams_get_team_member($team, $user_id);
            
            if ($member && method_exists($member, 'get_role')) {
                $role = $member->get_role();
                error_log('Club Manager: User role in team ' . $team->get_id() . ': ' . $role);
                
                // Check if user is owner or manager
                if (in_array($role, array('owner', 'manager'))) {
                    error_log('Club Manager: User has access - returning true');
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Get user's teams where they are owner or manager
     * 
     * @param int $user_id User ID
     * @return array
     */
    public static function get_user_managed_teams($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return array();
        }
        
        // Check if Teams for WooCommerce Memberships is active
        if (!class_exists('WC_Memberships_For_Teams_Loader')) {
            return array();
        }
        
        // Check if the function exists before calling it
        if (!function_exists('wc_memberships_for_teams_get_user_teams')) {
            // Try to load the teams functions
            if (function_exists('wc_memberships_for_teams')) {
                wc_memberships_for_teams();
            }
            
            // If still not available, return empty array
            if (!function_exists('wc_memberships_for_teams_get_user_teams')) {
                return array();
            }
        }
        
        $managed_teams = array();
        
        // Get user's teams
        $teams = wc_memberships_for_teams_get_user_teams($user_id);
        
        if (empty($teams)) {
            return array();
        }
        
        // Filter teams where user is owner or manager
        foreach ($teams as $team) {
            // Check if function exists
            if (!function_exists('wc_memberships_for_teams_get_team_member')) {
                continue;
            }
            
            $member = wc_memberships_for_teams_get_team_member($team, $user_id);
            
            if ($member && method_exists($member, 'get_role')) {
                $role = $member->get_role();
                
                if (in_array($role, array('owner', 'manager'))) {
                    $managed_teams[] = array(
                        'team_id' => $team->get_id(),
                        'team_name' => $team->get_name(),
                        'role' => $role,
                        'team_object' => $team
                    );
                }
            }
        }
        
        return $managed_teams;
    }
    
    /**
     * Get user's role in a specific team
     * 
     * @param int $team_id Team ID
     * @param int $user_id User ID
     * @return string|false
     */
    public static function get_user_team_role($team_id, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id || !$team_id) {
            return false;
        }
        
        // Check if Teams for WooCommerce Memberships is active
        if (!class_exists('WC_Memberships_For_Teams_Loader')) {
            return false;
        }
        
        // Check if the function exists before calling it
        if (!function_exists('wc_memberships_for_teams_get_team')) {
            // Try to load the teams functions
            if (function_exists('wc_memberships_for_teams')) {
                wc_memberships_for_teams();
            }
            
            // If still not available, return false
            if (!function_exists('wc_memberships_for_teams_get_team')) {
                return false;
            }
        }
        
        $team = wc_memberships_for_teams_get_team($team_id);
        
        if (!$team) {
            return false;
        }
        
        // Check if function exists
        if (!function_exists('wc_memberships_for_teams_get_team_member')) {
            return false;
        }
        
        $member = wc_memberships_for_teams_get_team_member($team, $user_id);
        
        if ($member && method_exists($member, 'get_role')) {
            return $member->get_role();
        }
        
        return false;
    }
}