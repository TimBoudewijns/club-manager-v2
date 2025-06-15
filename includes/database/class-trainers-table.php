<?php

/**
 * Trainers table creation and management.
 * No longer needs invitations table as we use WC Teams invitations.
 */
class Club_Manager_Trainers_Table {
    
    /**
     * Create trainers table only.
     */
    public static function create_table($charset_collate) {
        global $wpdb;
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Team trainers table - tracks which trainers belong to which Club Manager teams
        $trainers_table = Club_Manager_Database::get_table_name('team_trainers');
        
        $sql_trainers = "CREATE TABLE $trainers_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            team_id mediumint(9) NOT NULL,
            trainer_id bigint(20) NOT NULL,
            role varchar(50) DEFAULT 'trainer',
            is_active tinyint(1) DEFAULT 1,
            added_at datetime DEFAULT CURRENT_TIMESTAMP,
            added_by bigint(20) NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY team_trainer (team_id, trainer_id),
            KEY trainer_id (trainer_id),
            KEY role (role)
        ) $charset_collate;";
        
        dbDelta($sql_trainers);
    }
    
    /**
     * Get trainers table name.
     */
    public static function get_table_name() {
        return Club_Manager_Database::get_table_name('team_trainers');
    }
    
    /**
     * Drop tables (for uninstall).
     */
    public static function drop_tables() {
        global $wpdb;
        
        $trainers_table = self::get_table_name();
        
        // Drop old invitations table if it exists (cleanup from old version)
        $invitations_table = Club_Manager_Database::get_table_name('trainer_invitations');
        $wpdb->query("DROP TABLE IF EXISTS $invitations_table");
        
        // Drop trainers table
        $wpdb->query("DROP TABLE IF EXISTS $trainers_table");
    }
    
    /**
     * Check if tables exist.
     */
    public static function tables_exist() {
        global $wpdb;
        
        $trainers_table = self::get_table_name();
        
        return $wpdb->get_var("SHOW TABLES LIKE '$trainers_table'") === $trainers_table;
    }
    
    /**
     * Cleanup old invitations table if exists.
     */
    public static function cleanup_old_tables() {
        global $wpdb;
        
        // Remove old invitations table if it exists
        $invitations_table = Club_Manager_Database::get_table_name('trainer_invitations');
        if ($wpdb->get_var("SHOW TABLES LIKE '$invitations_table'") === $invitations_table) {
            $wpdb->query("DROP TABLE $invitations_table");
        }
    }
}