<?php

/**
 * Evaluations and advice tables creation and management.
 */
class Club_Manager_Evaluations_Table {
    
    /**
     * Create evaluations and advice tables.
     */
    public static function create_table($charset_collate) {
        global $wpdb;
        
        // Player evaluations table
        $evaluations_table = Club_Manager_Database::get_table_name('player_evaluations');
        
        $sql_evaluations = "CREATE TABLE $evaluations_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            player_id mediumint(9) NOT NULL,
            team_id mediumint(9) NOT NULL,
            season varchar(20) NOT NULL,
            category varchar(50) NOT NULL,
            subcategory varchar(100) DEFAULT NULL,
            score decimal(3,1) NOT NULL,
            notes text DEFAULT NULL,
            evaluated_by bigint(20) NOT NULL,
            evaluated_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY player_team_season (player_id, team_id, season),
            KEY category (category),
            KEY evaluated_by (evaluated_by)
        ) $charset_collate;";
        
        dbDelta($sql_evaluations);
        
        // Player AI advice table
        $advice_table = Club_Manager_Database::get_table_name('player_advice');
        
        $sql_advice = "CREATE TABLE $advice_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            player_id mediumint(9) NOT NULL,
            team_id mediumint(9) NOT NULL,
            season varchar(20) NOT NULL,
            advice text NOT NULL,
            status varchar(20) DEFAULT 'pending',
            generated_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY player_team_season (player_id, team_id, season),
            KEY status (status)
        ) $charset_collate;";
        
        dbDelta($sql_advice);
    }
    
    /**
     * Get evaluations table name.
     */
    public static function get_table_name() {
        return Club_Manager_Database::get_table_name('player_evaluations');
    }
    
    /**
     * Get advice table name.
     */
    public static function get_advice_table_name() {
        return Club_Manager_Database::get_table_name('player_advice');
    }
} 
