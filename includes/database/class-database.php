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
        }
        
        // Update database version
        update_option('club_manager_db_version', '1.1.0');
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
}