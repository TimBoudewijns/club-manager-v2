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
        
        // Trainer invitations table
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
            PRIMARY KEY (id),
            KEY team_id (team_id),
            KEY email (email),
            KEY token (token),
            KEY status (status)
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
}