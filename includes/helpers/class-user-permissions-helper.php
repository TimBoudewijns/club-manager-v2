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
     * Check if user can import/export data
     * 
     * @param int $user_id User ID
     * @return bool
     */
    public static function can_import_export($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        // Only owners/managers can import/export
        return self::is_club_owner_or_manager($user_id);
    }
    
    /**
     * Check if user can manage teams (add/remove players)
     * 
     * @param int $user_id User ID
     * @param int $team_id Team ID to check access for
     * @return bool
     */
    public static function can_manage_team_players($user_id = null, $team_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$team_id) {
            return false;
        }
        
        global $wpdb;
        
        // Check if user owns the team
        $teams_table = Club_Manager_Database::get_table_name('teams');
        $owner = $wpdb->get_var($wpdb->prepare(
            "SELECT created_by FROM $teams_table WHERE id = %d",
            $team_id
        ));
        
        if ($owner == $user_id) {
            return true; // User owns the team
        }
        
        // Individual subscribers can manage their own teams, trainers cannot add/remove players
        return false;
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
     * Get teams for "My Teams" section based on user role
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
     * Get available tabs for user based on new schema
     * 
     * @param int $user_id User ID
     * @return array
     */
    public static function get_available_tabs($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $tabs = []; // Start empty
        $user_type = self::get_user_type($user_id);
        
        switch ($user_type) {
            case 'owner_manager_trainer':
                // Owner/Manager who also trains - gets all tabs
                $tabs[] = 'player-management';
                $tabs[] = 'team-management';
                $tabs[] = 'trainer-management';
                $tabs[] = 'import-export';
                break;
                
            case 'club_trainer':
                // Club trainer - only Player Management tab
                $tabs[] = 'player-management';
                break;
                
            case 'independent_trainer':
                // Independent trainer - only Player Management tab
                $tabs[] = 'player-management';
                break;
                
            default:
                // No access
                break;
        }
        
        return $tabs;
    }
    
    /**
     * Get permissions data for frontend based on the new schema
     * 
     * @param int $user_id User ID
     * @return array
     */
    public static function get_frontend_permissions($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $user_role = self::get_user_role($user_id);
        
        // Determine user type based on the new schema
        $user_type = self::get_user_type($user_id);
        
        $permissions = [
            'user_role' => $user_role,
            'user_type' => $user_type,
            'is_club_member' => self::is_club_member($user_id),
            'is_owner_or_manager' => self::is_club_owner_or_manager($user_id),
            'is_trainer' => self::is_trainer($user_id),
            'has_individual_subscription' => self::has_individual_subscription($user_id),
            'available_tabs' => self::get_available_tabs($user_id)
        ];
        
        // Apply schema-based permissions
        switch ($user_type) {
            case 'owner_manager_trainer':
                // Owner/Manager who also trains
                $permissions = array_merge($permissions, [
                    'can_see_player_management' => true,
                    'can_see_club_teams_in_player_mgmt' => true,
                    'can_see_team_management' => true,
                    'can_make_evaluations' => true,
                    'can_add_teams_player_mgmt' => true,
                    'can_manage_team_roster' => true,
                    'can_view_all_club_teams' => true,
                    'can_create_teams' => true,
                    'can_import_export' => true,
                    'can_manage_trainers' => true,
                    'can_delete_players' => true
                ]);
                break;
                
            case 'club_trainer':
                // Trainer assigned to club teams
                $permissions = array_merge($permissions, [
                    'can_see_player_management' => true,
                    'can_see_club_teams_in_player_mgmt' => false,
                    'can_see_team_management' => false,
                    'can_make_evaluations' => true,
                    'can_add_teams_player_mgmt' => false,
                    'can_manage_team_roster' => false,
                    'can_view_all_club_teams' => false,
                    'can_create_teams' => false,
                    'can_import_export' => false,
                    'can_manage_trainers' => false,
                    'can_delete_players' => false
                ]);
                break;
                
            case 'independent_trainer':
                // Independent trainer with own teams
                $permissions = array_merge($permissions, [
                    'can_see_player_management' => true,
                    'can_see_club_teams_in_player_mgmt' => false,
                    'can_see_team_management' => false,
                    'can_make_evaluations' => true,
                    'can_add_teams_player_mgmt' => true,
                    'can_manage_team_roster' => true,
                    'can_view_all_club_teams' => false,
                    'can_create_teams' => true,
                    'can_import_export' => false,
                    'can_manage_trainers' => false,
                    'can_delete_players' => true
                ]);
                break;
                
            default:
                // Default permissions (no access)
                $permissions = array_merge($permissions, [
                    'can_see_player_management' => false,
                    'can_see_club_teams_in_player_mgmt' => false,
                    'can_see_team_management' => false,
                    'can_make_evaluations' => false,
                    'can_add_teams_player_mgmt' => false,
                    'can_manage_team_roster' => false,
                    'can_view_all_club_teams' => false,
                    'can_create_teams' => false,
                    'can_import_export' => false,
                    'can_manage_trainers' => false,
                    'can_delete_players' => false
                ]);
        }
        
        // Legacy permissions for backward compatibility
        $permissions['can_view_club_teams'] = $permissions['can_view_all_club_teams'];
        $permissions['can_manage_teams'] = $permissions['can_see_team_management'];
        $permissions['can_manage_trainers'] = $permissions['can_see_team_management']; // Same as team management access
        
        return $permissions;
    }
    
    /**
     * Get user type based on the schema
     * 
     * @param int $user_id User ID
     * @return string 'owner_manager_trainer', 'club_trainer', 'independent_trainer', or 'none'
     */
    public static function get_user_type($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return 'none';
        }
        
        // Check if user is owner/manager
        if (self::is_club_owner_or_manager($user_id)) {
            // Check if they also train (have trainer assignments)
            global $wpdb;
            $trainers_table = Club_Manager_Database::get_table_name('team_trainers');
            
            $is_also_trainer = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $trainers_table WHERE trainer_id = %d AND is_active = 1",
                $user_id
            ));
            
            if ($is_also_trainer > 0) {
                return 'owner_manager_trainer'; // Owner/Manager who also trains
            }
            
            return 'owner_manager_trainer'; // Still owner/manager (same permissions)
        }
        
        // Check if user is trainer (part of a club)
        if (self::is_trainer($user_id)) {
            return 'club_trainer';
        }
        
        // Check if user has individual subscription (not part of club)
        if (self::has_individual_subscription($user_id)) {
            return 'independent_trainer';
        }
        
        return 'none';
    }
}