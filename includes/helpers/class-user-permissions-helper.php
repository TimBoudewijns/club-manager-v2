<?php
/**
 * Helper class for user permissions and role checking
 * 
 * @package Club_Manager
 * @subpackage Helpers
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Helper class for user permissions
 */
class Club_Manager_User_Permissions_Helper {
    
    /**
     * Check if user is part of a club (has a WC Team membership)
     * 
     * @param int $user_id User ID
     * @return bool
     */
    public static function is_club_member($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return false;
        }
        
        // Check if user is member of any WC team
        return Club_Manager_Teams_Helper::is_team_member($user_id);
    }
    
    /**
     * Check if user is a club owner or manager
     * 
     * @param int $user_id User ID
     * @return bool
     */
    public static function is_club_owner_or_manager($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        return Club_Manager_Teams_Helper::can_view_club_teams($user_id);
    }
    
    /**
     * Check if user is a trainer (member but not owner/manager)
     * 
     * @param int $user_id User ID
     * @return bool
     */
    public static function is_trainer($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!self::is_club_member($user_id)) {
            return false;
        }
        
        // Is member but not owner/manager
        return !self::is_club_owner_or_manager($user_id);
    }
    
    /**
     * Check if user has individual subscription (not part of club)
     * 
     * @param int $user_id User ID
     * @return bool
     */
    public static function has_individual_subscription($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        // User is logged in but not part of any club
        return $user_id && !self::is_club_member($user_id);
    }
    
    /**
     * Check if user can create teams
     * 
     * @param int $user_id User ID
     * @return bool
     */
    public static function can_create_teams($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        // Only individual subscribers and club owners/managers can create teams
        return self::has_individual_subscription($user_id) || self::is_club_owner_or_manager($user_id);
    }
    
    /**
     * Get user's role in the system
     * 
     * @param int $user_id User ID
     * @return string 'owner', 'manager', 'trainer', 'individual', or 'none'
     */
    public static function get_user_role($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return 'none';
        }
        
        // Check WC Teams role
        $managed_teams = Club_Manager_Teams_Helper::get_user_managed_teams($user_id);
        
        if (!empty($managed_teams)) {
            // Check if owner or manager
            foreach ($managed_teams as $team) {
                if ($team['role'] === 'owner') {
                    return 'owner';
                }
            }
            return 'manager';
        }
        
        // Check if trainer
        if (self::is_trainer($user_id)) {
            return 'trainer';
        }
        
        // Check if individual subscriber
        if (self::has_individual_subscription($user_id)) {
            return 'individual';
        }
        
        return 'none';
    }
    
    /**
     * Get teams for "My Teams" tab based on user role
     * 
     * @param int $user_id User ID
     * @param string $season Season
     * @return array
     */
    public static function get_my_teams($user_id = null, $season = '') {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        global $wpdb;
        $teams_table = Club_Manager_Database::get_table_name('teams');
        $trainers_table = Club_Manager_Database::get_table_name('team_trainers');
        
        $user_role = self::get_user_role($user_id);
        
        switch ($user_role) {
            case 'individual':
                // Get teams created by the user
                $query = "SELECT * FROM $teams_table WHERE created_by = %d";
                $params = [$user_id];
                
                if (!empty($season)) {
                    $query .= " AND season = %s";
                    $params[] = $season;
                }
                
                $query .= " ORDER BY name";
                
                return $wpdb->get_results($wpdb->prepare($query, ...$params));
                
            case 'trainer':
                // Get teams assigned to the trainer
                $query = "SELECT t.* FROM $teams_table t
                         INNER JOIN $trainers_table tt ON t.id = tt.team_id
                         WHERE tt.trainer_id = %d AND tt.is_active = 1";
                $params = [$user_id];
                
                if (!empty($season)) {
                    $query .= " AND t.season = %s";
                    $params[] = $season;
                }
                
                $query .= " ORDER BY t.name";
                
                return $wpdb->get_results($wpdb->prepare($query, ...$params));
                
            case 'owner':
            case 'manager':
                // For owners/managers, they see their created teams in My Teams
                // They manage all club teams in Team Management tab
                $query = "SELECT * FROM $teams_table WHERE created_by = %d";
                $params = [$user_id];
                
                if (!empty($season)) {
                    $query .= " AND season = %s";
                    $params[] = $season;
                }
                
                $query .= " ORDER BY name";
                
                return $wpdb->get_results($wpdb->prepare($query, ...$params));
                
            default:
                return [];
        }
    }
    
    /**
     * Get available tabs for user
     * 
     * @param int $user_id User ID
     * @return array
     */
    public static function get_available_tabs($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $tabs = ['my-teams']; // Everyone gets My Teams
        
        $user_role = self::get_user_role($user_id);
        
        switch ($user_role) {
            case 'owner':
            case 'manager':
                $tabs[] = 'team-management';
                $tabs[] = 'club-teams';
                $tabs[] = 'trainer-management';
                break;
                
            case 'trainer':
                // Trainers only see My Teams
                break;
                
            case 'individual':
                // Individuals only see My Teams
                break;
        }
        
        return $tabs;
    }
    
    /**
     * Get permissions data for frontend
     * 
     * @param int $user_id User ID
     * @return array
     */
    public static function get_frontend_permissions($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $user_role = self::get_user_role($user_id);
        
        return [
            'user_role' => $user_role,
            'is_club_member' => self::is_club_member($user_id),
            'is_owner_or_manager' => self::is_club_owner_or_manager($user_id),
            'is_trainer' => self::is_trainer($user_id),
            'has_individual_subscription' => self::has_individual_subscription($user_id),
            'can_create_teams' => self::can_create_teams($user_id),
            'can_view_club_teams' => self::is_club_owner_or_manager($user_id),
            'can_manage_teams' => self::is_club_owner_or_manager($user_id),
            'can_manage_trainers' => self::is_club_owner_or_manager($user_id),
            'available_tabs' => self::get_available_tabs($user_id)
        ];
    }
}