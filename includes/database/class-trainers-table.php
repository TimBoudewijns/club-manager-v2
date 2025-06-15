<?php

/**
 * Trainers and invitations tables creation and management.
 */
class Club_Manager_Trainers_Table {
    
    /**
     * Create trainers and invitations tables.
     */
    public static function create_table($charset_collate) {
        global $wpdb;
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Team trainers table
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
        
        // Trainer invitations table with WC Teams synchronization
        $invitations_table = Club_Manager_Database::get_table_name('trainer_invitations');
        
        $sql_invitations = "CREATE TABLE $invitations_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            team_id mediumint(9) NOT NULL,
            email varchar(255) NOT NULL,
            role varchar(50) DEFAULT 'trainer',
            token varchar(255) NOT NULL,
            message text DEFAULT NULL,
            status varchar(20) DEFAULT 'pending',
            invited_by bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            accepted_at datetime DEFAULT NULL,
            wc_invitation_id bigint(20) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY team_id (team_id),
            KEY email (email),
            KEY token (token),
            KEY status (status),
            KEY wc_invitation_id (wc_invitation_id)
        ) $charset_collate;";
        
        dbDelta($sql_invitations);
    }
    
    /**
     * Get trainers table name.
     */
    public static function get_table_name() {
        return Club_Manager_Database::get_table_name('team_trainers');
    }
    
    /**
     * Get invitations table name.
     */
    public static function get_invitations_table_name() {
        return Club_Manager_Database::get_table_name('trainer_invitations');
    }
    
    /**
     * Drop tables (for uninstall).
     */
    public static function drop_tables() {
        global $wpdb;
        
        $trainers_table = self::get_table_name();
        $invitations_table = self::get_invitations_table_name();
        
        $wpdb->query("DROP TABLE IF EXISTS $trainers_table");
        $wpdb->query("DROP TABLE IF EXISTS $invitations_table");
    }
    
    /**
     * Check if tables exist.
     */
    public static function tables_exist() {
        global $wpdb;
        
        $trainers_table = self::get_table_name();
        $invitations_table = self::get_invitations_table_name();
        
        $trainers_exists = $wpdb->get_var("SHOW TABLES LIKE '$trainers_table'") === $trainers_table;
        $invitations_exists = $wpdb->get_var("SHOW TABLES LIKE '$invitations_table'") === $invitations_table;
        
        return $trainers_exists && $invitations_exists;
    }
    
    /**
     * Get table structure for upgrades.
     */
    public static function get_table_structure() {
        global $wpdb;
        
        $invitations_table = self::get_invitations_table_name();
        
        // Check if wc_invitation_id column exists
        $column_exists = $wpdb->get_results($wpdb->prepare(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = %s 
            AND TABLE_NAME = %s 
            AND COLUMN_NAME = 'wc_invitation_id'",
            DB_NAME,
            $invitations_table
        ));
        
        return array(
            'has_wc_invitation_id' => !empty($column_exists)
        );
    }
    
    /**
     * Upgrade tables if needed.
     */
    public static function maybe_upgrade_tables() {
        $structure = self::get_table_structure();
        
        if (!$structure['has_wc_invitation_id']) {
            global $wpdb;
            $invitations_table = self::get_invitations_table_name();
            
            // Add wc_invitation_id column
            $wpdb->query("ALTER TABLE $invitations_table 
                         ADD COLUMN wc_invitation_id bigint(20) DEFAULT NULL,
                         ADD KEY wc_invitation_id (wc_invitation_id)");
        }
    }
}