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
        
        // Check if Teams plugin function exists
        if (!function_exists('wc_memberships_for_teams_get_teams')) {
            error_log('Club Manager: wc_memberships_for_teams_get_teams function not found');
            return false;
        }
        
        // Get teams where user is owner or manager
        $teams = wc_memberships_for_teams_get_teams($user_id, array(
            'role' => 'owner,manager'
        ));
        
        error_log('Club Manager: Found teams for user ' . $user_id . ': ' . print_r($teams, true));
        
        return !empty($teams);
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
        
        // Check if Teams plugin function exists
        if (!function_exists('wc_memberships_for_teams_get_teams')) {
            return array();
        }
        
        $managed_teams = array();
        
        // Get teams where user is owner or manager
        $teams = wc_memberships_for_teams_get_teams($user_id, array(
            'role' => 'owner,manager'
        ));
        
        if (!empty($teams)) {
            foreach ($teams as $team) {
                // Get the team object
                if (is_object($team) && method_exists($team, 'get_id') && method_exists($team, 'get_name')) {
                    // Get user's role in this team
                    $member = $team->get_member($user_id);
                    $role = $member ? $member->get_role() : 'member';
                    
                    $managed_teams[] = array(
                        'team_id' => $team->get_id(),
                        'team_name' => $team->get_name(),
                        'role' => $role
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
        
        // Check if function exists
        if (!function_exists('wc_memberships_for_teams_get_team')) {
            return false;
        }
        
        // Get team object
        $team = wc_memberships_for_teams_get_team($team_id);
        
        if (!$team || !is_object($team)) {
            return false;
        }
        
        // Get team member
        if (method_exists($team, 'get_member')) {
            $member = $team->get_member($user_id);
            
            if ($member && method_exists($member, 'get_role')) {
                return $member->get_role();
            }
        }
        
        return false;
    }
}