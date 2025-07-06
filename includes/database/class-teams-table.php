<?php

/**
 * Teams table creation and management.
 */
class Club_Manager_Teams_Table {
    
    /**
     * Create teams table.
     */
    public static function create_table($charset_collate) {
        global $wpdb;
        
        $table_name = Club_Manager_Database::get_table_name('teams');
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            coach varchar(255) NOT NULL,
            season varchar(20) NOT NULL,
            created_by bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY created_by (created_by),
            KEY season (season)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Get teams table name.
     */
    public static function get_table_name() {
        return Club_Manager_Database::get_table_name('teams');
    }
} 
