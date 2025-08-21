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
     * Check if Teams plugin is active
     */
    public static function is_teams_plugin_active() {
        return function_exists('wc_memberships_for_teams');
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
        
        if (!$user_id) {
            return false;
        }
        
        // Check if Teams plugin is active
        if (!self::is_teams_plugin_active()) {
            error_log('Club Manager: Teams for WooCommerce Memberships is not active');
            return false;
        }
        
        // First check if Teams for WooCommerce Memberships is active
        if (!post_type_exists('wc_memberships_team')) {
            return false;
        }
        
        // Method 1: Try the official function if it exists
        if (function_exists('wc_memberships_for_teams_get_user_teams')) {
            try {
                // Get all teams for user
                $teams = wc_memberships_for_teams_get_user_teams($user_id);
                
                if (!empty($teams)) {
                    foreach ($teams as $team) {
                        if (is_object($team)) {
                            // Try multiple ways to check member role
                            $is_owner_or_manager = false;
                            
                            // Check method 1: get_member
                            if (method_exists($team, 'get_member')) {
                                $member = $team->get_member($user_id);
                                if ($member && method_exists($member, 'get_role')) {
                                    $role = $member->get_role();
                                    if (in_array($role, array('owner', 'manager'))) {
                                        return true;
                                    }
                                }
                            }
                            
                            // Check method 2: Post author
                            if (method_exists($team, 'get_id')) {
                                $team_post = get_post($team->get_id());
                                if ($team_post && $team_post->post_author == $user_id) {
                                    return true;
                                }
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                error_log('Club Manager Teams Helper Error: ' . $e->getMessage());
            }
        }
        
        // Alternative method using different function
        if (function_exists('wc_memberships_for_teams_get_teams')) {
            try {
                $teams = wc_memberships_for_teams_get_teams($user_id, array(
                    'role' => 'owner,manager'
                ));
                
                if (!empty($teams)) {
                    return true;
                }
            } catch (Exception $e) {
                error_log('Club Manager Teams Helper Error: ' . $e->getMessage());
            }
        }
        
        // Method 2: Direct database check as fallback
        global $wpdb;
        
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
        // We need to check if the user is a manager, not just a member
        $meta_query = $wpdb->prepare(
            "SELECT pm1.post_id 
             FROM {$wpdb->postmeta} pm1
             INNER JOIN {$wpdb->postmeta} pm2 
                ON pm1.post_id = pm2.post_id 
                AND pm2.meta_key = '_role' 
                AND pm2.meta_value IN ('owner', 'manager')
             WHERE pm1.meta_key = '_member_id' 
             AND pm1.meta_value = %d
             LIMIT 1",
            $user_id
        );
        
        $result = $wpdb->get_var($meta_query);
        
        return !empty($result);
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
            error_log('Club Manager: get_user_managed_teams - No user ID provided');
            return array();
        }
        
        // Check if Teams plugin is active
        if (!self::is_teams_plugin_active()) {
            error_log('Club Manager: get_user_managed_teams - Teams plugin not active');
            return array();
        }
        
        error_log('Club Manager: get_user_managed_teams - Starting for user ID: ' . $user_id);
        $managed_teams = array();
        
        // Method 1: Try the official function
        if (function_exists('wc_memberships_for_teams_get_user_teams')) {
            try {
                // Get all teams for user
                $teams = wc_memberships_for_teams_get_user_teams($user_id);
                
                if (!empty($teams)) {
                    foreach ($teams as $team) {
                        if (is_object($team)) {
                            // Get user's member object
                            if (method_exists($team, 'get_member') && method_exists($team, 'get_id') && method_exists($team, 'get_name')) {
                                $member = $team->get_member($user_id);
                                if ($member && method_exists($member, 'get_role')) {
                                    $role = $member->get_role();
                                    if (in_array($role, array('owner', 'manager'))) {
                                        $managed_teams[] = array(
                                            'team_id' => $team->get_id(),
                                            'team_name' => $team->get_name(),
                                            'role' => $role
                                        );
                                    }
                                }
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                error_log('Club Manager Teams Helper Error: ' . $e->getMessage());
            }
        }
        
        // Alternative method
        if (empty($managed_teams) && function_exists('wc_memberships_for_teams_get_teams')) {
            try {
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
            } catch (Exception $e) {
                error_log('Club Manager Teams Helper Error: ' . $e->getMessage());
            }
        }
        
        // Method 2: Direct database query if nothing found yet
        if (empty($managed_teams)) {
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
            
            // Also get teams where user is a manager (not owner)
            $manager_teams = $wpdb->get_results($wpdb->prepare(
                "SELECT p.ID, p.post_title 
                 FROM {$wpdb->posts} p
                 INNER JOIN {$wpdb->postmeta} pm1 
                    ON p.ID = pm1.post_id 
                    AND pm1.meta_key = '_member_id' 
                    AND pm1.meta_value = %d
                 INNER JOIN {$wpdb->postmeta} pm2 
                    ON p.ID = pm2.post_id 
                    AND pm2.meta_key = '_role' 
                    AND pm2.meta_value = 'manager'
                 WHERE p.post_type = 'wc_memberships_team' 
                 AND p.post_status = 'publish'
                 AND p.post_author != %d",
                $user_id, $user_id
            ));
            
            foreach ($manager_teams as $team) {
                $managed_teams[] = array(
                    'team_id' => $team->ID,
                    'team_name' => $team->post_title,
                    'role' => 'manager'
                );
            }
        }
        
        error_log('Club Manager: get_user_managed_teams - Returning ' . count($managed_teams) . ' managed teams for user ' . $user_id);
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
        
        // Check if Teams plugin is active
        if (!self::is_teams_plugin_active()) {
            return false;
        }
        
        // Method 1: Try official function
        if (function_exists('wc_memberships_for_teams_get_team')) {
            try {
                $team = wc_memberships_for_teams_get_team($team_id);
                
                if ($team && is_object($team) && method_exists($team, 'get_member')) {
                    $member = $team->get_member($user_id);
                    
                    if ($member && method_exists($member, 'get_role')) {
                        return $member->get_role();
                    }
                }
            } catch (Exception $e) {
                error_log('Club Manager Teams Helper Error: ' . $e->getMessage());
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
        
        // Method 3: Check postmeta for role
        $role = $wpdb->get_var($wpdb->prepare(
            "SELECT pm2.meta_value 
             FROM {$wpdb->postmeta} pm1
             INNER JOIN {$wpdb->postmeta} pm2 
                ON pm1.post_id = pm2.post_id 
                AND pm2.meta_key = '_role'
             WHERE pm1.post_id = %d 
             AND pm1.meta_key = '_member_id' 
             AND pm1.meta_value = %d",
            $team_id, $user_id
        ));
        
        return $role ?: false;
    }
    
    /**
     * Check if user is member of any WC team
     * 
     * @param int $user_id User ID
     * @return bool
     */
    public static function is_team_member($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return false;
        }
        
        // Check if Teams plugin is active
        if (!self::is_teams_plugin_active()) {
            return false;
        }
        
        // Try official function first
        if (function_exists('wc_memberships_for_teams_get_user_teams')) {
            try {
                $teams = wc_memberships_for_teams_get_user_teams($user_id);
                return !empty($teams);
            } catch (Exception $e) {
                error_log('Club Manager Teams Helper Error: ' . $e->getMessage());
            }
        }
        
        // Fallback to database check
        global $wpdb;
        
        $is_member = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) 
             FROM {$wpdb->postmeta} 
             WHERE meta_key = '_member_id' 
             AND meta_value = %d",
            $user_id
        ));
        
        return $is_member > 0;
    }
    
    /**
     * Get all team IDs where user is owner or manager
     * 
     * @param int $user_id User ID
     * @return array Array of team IDs
     */
    public static function get_managed_team_ids($user_id = null) {
        $managed_teams = self::get_user_managed_teams($user_id);
        return array_column($managed_teams, 'team_id');
    }
}