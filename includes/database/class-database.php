<?php

/**
 * Main database class that coordinates all table creation.
 */
class Club_Manager_Database {
    
    /**
     * Create all plugin tables.
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Create teams table
        Club_Manager_Teams_Table::create_table($charset_collate);
        
        // Create players table
        Club_Manager_Players_Table::create_table($charset_collate);
        
        // Create evaluations table
        Club_Manager_Evaluations_Table::create_table($charset_collate);
        
        // Create trainers table
        if (class_exists('Club_Manager_Trainers_Table')) {
            Club_Manager_Trainers_Table::create_table($charset_collate);
            // Cleanup old invitations table if exists
            Club_Manager_Trainers_Table::cleanup_old_tables();
        }
        
        // Update database version
        update_option('club_manager_db_version', '2.0.0');
    }
    
    /**
     * Get table name with prefix.
     */
    public static function get_table_name($table) {
        global $wpdb;
        return $wpdb->prefix . 'cm_' . $table;
    }
    
    /**
     * Check if tables exist.
     */
    public static function tables_exist() {
        global $wpdb;
        
        $tables = array(
            self::get_table_name('teams'),
            self::get_table_name('players'),
            self::get_table_name('team_players'),
            self::get_table_name('player_evaluations'),
            self::get_table_name('player_advice'),
            self::get_table_name('team_trainers')
            // Note: trainer_invitations table removed - we use WC Teams invitations
        );
        
        foreach ($tables as $table) {
            if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Drop all plugin tables (for uninstall).
     */
    public static function drop_tables() {
        global $wpdb;
        
        $tables = array(
            self::get_table_name('team_trainers'),
            self::get_table_name('player_advice'),
            self::get_table_name('player_evaluations'),
            self::get_table_name('team_players'),
            self::get_table_name('players'),
            self::get_table_name('teams')
        );
        
        // Drop in reverse order to respect foreign key constraints
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
        
        // Also drop old invitations table if it exists
        $invitations_table = self::get_table_name('trainer_invitations');
        $wpdb->query("DROP TABLE IF EXISTS $invitations_table");
        
        // Remove options
        delete_option('club_manager_version');
        delete_option('club_manager_db_version');
    }
    
    /**
     * Run database upgrades.
     */
    public static function upgrade() {
        $current_db_version = get_option('club_manager_db_version', '1.0.0');
        
        // Upgrade to 2.0.0 - Remove invitations table, use WC Teams
        if (version_compare($current_db_version, '2.0.0', '<')) {
            self::create_tables();
            
            // Migrate any pending invitations to WC Teams if needed
            self::migrate_invitations_to_wc_teams();
        }
    }
    
    /**
     * Migrate old invitations to WC Teams (if any exist).
     */
    private static function migrate_invitations_to_wc_teams() {
        global $wpdb;
        
        $invitations_table = self::get_table_name('trainer_invitations');
        
        // Check if old table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$invitations_table'") !== $invitations_table) {
            return;
        }
        
        // Get pending invitations
        $old_invitations = $wpdb->get_results("
            SELECT * FROM $invitations_table 
            WHERE status = 'pending'
        ");
        
        if (empty($old_invitations) || !function_exists('wc_memberships_for_teams')) {
            return;
        }
        
        // Migrate each invitation
        foreach ($old_invitations as $old_inv) {
            // Find WC team for this Club Manager team
            $wc_team = self::find_wc_team_for_cm_team($old_inv->team_id);
            
            if ($wc_team && method_exists($wc_team, 'invite_member')) {
                try {
                    $invitation = $wc_team->invite_member($old_inv->email, array(
                        'sender_id' => $old_inv->invited_by,
                        'role' => 'member'
                    ));
                    
                    if ($invitation) {
                        // Store Club Manager data
                        update_post_meta($invitation->get_id(), '_cm_team_id', $old_inv->team_id);
                        update_post_meta($invitation->get_id(), '_cm_role', $old_inv->role);
                        update_post_meta($invitation->get_id(), '_cm_message', $old_inv->message);
                    }
                } catch (Exception $e) {
                    error_log('Club Manager: Failed to migrate invitation: ' . $e->getMessage());
                }
            }
        }
    }
    
    /**
     * Helper to find WC team for Club Manager team.
     */
    private static function find_wc_team_for_cm_team($cm_team_id) {
        global $wpdb;
        $teams_table = self::get_table_name('teams');
        
        // Get team owner
        $team_owner_id = $wpdb->get_var($wpdb->prepare(
            "SELECT created_by FROM $teams_table WHERE id = %d",
            $cm_team_id
        ));
        
        if (!$team_owner_id || !function_exists('wc_memberships_for_teams_get_user_teams')) {
            return false;
        }
        
        // Find WC team
        $teams = wc_memberships_for_teams_get_user_teams($team_owner_id);
        
        foreach ($teams as $team) {
            if (is_object($team)) {
                $member = $team->get_member($team_owner_id);
                if ($member && in_array($member->get_role(), ['owner', 'manager'])) {
                    return $team;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Get database version.
     */
    public static function get_db_version() {
        return get_option('club_manager_db_version', '1.0.0');
    }
    
    /**
     * Check if specific table exists.
     */
    public static function table_exists($table_name) {
        global $wpdb;
        $full_table_name = self::get_table_name($table_name);
        return $wpdb->get_var("SHOW TABLES LIKE '$full_table_name'") === $full_table_name;
    }
    
    /**
     * Get table charset.
     */
    public static function get_charset_collate() {
        global $wpdb;
        return $wpdb->get_charset_collate();
    }
    
    /**
     * Run custom query with error handling.
     */
    public static function query($sql) {
        global $wpdb;
        
        $result = $wpdb->query($sql);
        
        if ($result === false) {
            error_log('Club Manager Database Error: ' . $wpdb->last_error);
            error_log('Query: ' . $sql);
        }
        
        return $result;
    }
    
    /**
     * Get last insert ID.
     */
    public static function insert_id() {
        global $wpdb;
        return $wpdb->insert_id;
    }
    
    /**
     * Escape string for database.
     */
    public static function escape($string) {
        global $wpdb;
        return $wpdb->esc_like($string);
    }
}