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
            // Check for upgrades
            Club_Manager_Trainers_Table::maybe_upgrade_tables();
        }
        
        // Update database version
        update_option('club_manager_db_version', '1.2.0');
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
            self::get_table_name('team_trainers'),
            self::get_table_name('trainer_invitations')
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
            self::get_table_name('trainer_invitations'),
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
        
        // Remove options
        delete_option('club_manager_version');
        delete_option('club_manager_db_version');
    }
    
    /**
     * Run database upgrades.
     */
    public static function upgrade() {
        $current_db_version = get_option('club_manager_db_version', '1.0.0');
        
        // Upgrade from 1.0.0 to 1.1.0 - Add trainer tables
        if (version_compare($current_db_version, '1.1.0', '<')) {
            self::create_tables();
        }
        
        // Upgrade from 1.1.0 to 1.2.0 - Add WC Teams sync
        if (version_compare($current_db_version, '1.2.0', '<')) {
            if (class_exists('Club_Manager_Trainers_Table')) {
                Club_Manager_Trainers_Table::maybe_upgrade_tables();
            }
            update_option('club_manager_db_version', '1.2.0');
        }
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