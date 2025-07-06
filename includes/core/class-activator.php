<?php

/**
 * Fired during plugin activation and deactivation.
 */
class Club_Manager_Activator {
    
    /**
     * Plugin activation.
     */
    public static function activate() {
        // Create database tables
        Club_Manager_Database::create_tables();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Set default options
        add_option('club_manager_version', CLUB_MANAGER_VERSION);
        add_option('club_manager_db_version', '1.0.0');
    }
    
    /**
     * Plugin deactivation.
     */
    public static function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Clear any scheduled hooks
        wp_clear_scheduled_hook('cm_generate_player_advice');
    }
} 
