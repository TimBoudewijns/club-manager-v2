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
     * Check if user can view club teams
     * 
     * @param int $user_id User ID
     * @return bool
     */
    public static function can_view_club_teams($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return false;
        }
        
        // Check if Teams for WooCommerce Memberships is active
        if (!function_exists('wc_memberships_for_teams')) {
            return false;
        }
        
        // Get user's teams
        $teams = wc_memberships_for_teams_get_user_teams($user_id);
        
        if (empty($teams)) {
            return false;
        }
        
        // Check each team for owner or manager role
        foreach ($teams as $team) {
            $member = wc_memberships_for_teams_get_team_member($team, $user_id);
            
            if ($member) {
                $role = $member->get_role();
                
                // Check if user is owner or manager
                if (in_array($role, array('owner', 'manager'))) {
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
        if (!function_exists('wc_memberships_for_teams')) {
            return array();
        }
        
        $managed_teams = array();
        
        // Get user's teams
        $teams = wc_memberships_for_teams_get_user_teams($user_id);
        
        if (empty($teams)) {
            return array();
        }
        
        // Filter teams where user is owner or manager
        foreach ($teams as $team) {
            $member = wc_memberships_for_teams_get_team_member($team, $user_id);
            
            if ($member) {
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
        if (!function_exists('wc_memberships_for_teams')) {
            return false;
        }
        
        $team = wc_memberships_for_teams_get_team($team_id);
        
        if (!$team) {
            return false;
        }
        
        $member = wc_memberships_for_teams_get_team_member($team, $user_id);
        
        if ($member) {
            return $member->get_role();
        }
        
        return false;
    }
}