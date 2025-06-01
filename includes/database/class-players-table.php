<?php

/**
 * Players and team_players table creation and management.
 */
class Club_Manager_Players_Table {
    
    /**
     * Create players and team_players tables.
     */
    public static function create_table($charset_collate) {
        global $wpdb;
        
        // Players table
        $players_table = Club_Manager_Database::get_table_name('players');
        
        $sql_players = "CREATE TABLE $players_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            first_name varchar(255) NOT NULL,
            last_name varchar(255) NOT NULL,
            birth_date date NOT NULL,
            email varchar(255) NOT NULL,
            created_by bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY email (email),
            KEY created_by (created_by)
        ) $charset_collate;";
        
        dbDelta($sql_players);
        
        // Team players relationship table
        $team_players_table = Club_Manager_Database::get_table_name('team_players');
        
        $sql_team_players = "CREATE TABLE $team_players_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            team_id mediumint(9) NOT NULL,
            player_id mediumint(9) NOT NULL,
            position varchar(100) DEFAULT NULL,
            jersey_number int(3) DEFAULT NULL,
            notes text DEFAULT NULL,
            season varchar(20) NOT NULL,
            PRIMARY KEY (id),
            KEY team_id (team_id),
            KEY player_id (player_id),
            KEY season (season),
            UNIQUE KEY team_player_season (team_id, player_id, season)
        ) $charset_collate;";
        
        dbDelta($sql_team_players);
    }
    
    /**
     * Get players table name.
     */
    public static function get_table_name() {
        return Club_Manager_Database::get_table_name('players');
    }
    
    /**
     * Get team_players table name.
     */
    public static function get_team_players_table_name() {
        return Club_Manager_Database::get_table_name('team_players');
    }
} 
