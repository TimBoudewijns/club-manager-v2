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
        
        // Method 1: Try the official function if it exists
        if (function_exists('wc_memberships_for_teams_get_teams')) {
            $teams = wc_memberships_for_teams_get_teams($user_id, array(
                'role' => 'owner,manager'
            ));
            
            if (!empty($teams)) {
                return true;
            }
        }
        
        // Method 2: Direct database check as fallback
        global $wpdb;
        
        // Check if team post type exists
        if (!post_type_exists('wc_memberships_team')) {
            return false;
        }
        
        // Query for teams where user is author (owner)
        $query = $wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} 
             WHERE post_type = 'wc_memberships_team' 
             AND post_author = %d 
             AND post_status = 'publish'
             LIMIT 1",
            $user_id
        );
        
        $result = $wpdb->get_var($query);
        
        if ($result) {
            return true;
        }
        
        // Check meta for team members with manager role
        $meta_query = $wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
             WHERE meta_key = '_member_id' 
             AND meta_value = %d
             LIMIT 1",
            $user_id
        );
        
        $team_ids = $wpdb->get_col($meta_query);
        
        // For now, assume if user is a member, they might be a manager
        // This is a simplified check
        return !empty($team_ids);
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
        
        $managed_teams = array();
        
        // Method 1: Try the official function
        if (function_exists('wc_memberships_for_teams_get_teams')) {
            $teams = wc_memberships_for_teams_get_teams($user_id, array(
                'role' => 'owner,manager'
            ));
            
            if (!empty($teams)) {
                foreach ($teams as $team) {
                    if (is_object($team) && method_exists($team, 'get_id') && method_exists($team, 'get_name')) {
                        // Get user's role in this team
                        $role = 'member';
                        if (method_exists($team, 'get_member')) {
                            $member = $team->get_member($user_id);
                            if ($member && method_exists($member, 'get_role')) {
                                $role = $member->get_role();
                            }
                        }
                        
                        $managed_teams[] = array(
                            'team_id' => $team->get_id(),
                            'team_name' => $team->get_name(),
                            'role' => $role
                        );
                    }
                }
            }
        } else {
            // Method 2: Direct database query
            global $wpdb;
            
            // Get teams where user is owner
            $owned_teams = $wpdb->get_results($wpdb->prepare(
                "SELECT ID, post_title FROM {$wpdb->posts} 
                 WHERE post_type = 'wc_memberships_team' 
                 AND post_author = %d 
                 AND post_status = 'publish'",
                $user_id
            ));
            
            foreach ($owned_teams as $team) {
                $managed_teams[] = array(
                    'team_id' => $team->ID,
                    'team_name' => $team->post_title,
                    'role' => 'owner'
                );
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
        
        // Method 1: Try official function
        if (function_exists('wc_memberships_for_teams_get_team')) {
            $team = wc_memberships_for_teams_get_team($team_id);
            
            if ($team && is_object($team) && method_exists($team, 'get_member')) {
                $member = $team->get_member($user_id);
                
                if ($member && method_exists($member, 'get_role')) {
                    return $member->get_role();
                }
            }
        }
        
        // Method 2: Check if user is post author (owner)
        global $wpdb;
        
        $post_author = $wpdb->get_var($wpdb->prepare(
            "SELECT post_author FROM {$wpdb->posts} 
             WHERE ID = %d AND post_type = 'wc_memberships_team'",
            $team_id
        ));
        
        if ($post_author == $user_id) {
            return 'owner';
        }
        
        return false;
    }
}