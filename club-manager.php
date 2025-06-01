<?php
/**
 * Plugin Name: Club Manager
 * Plugin URI: https://example.com/club-manager
 * Description: A comprehensive club management system for hockey trainers
 * Version: 1.0.0
 * Author: Tim Boudewijns
 * License: GPL v2 or later
 * Text Domain: club-manager
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CLUB_MANAGER_VERSION', '1.0.0');
define('CLUB_MANAGER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CLUB_MANAGER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CLUB_MANAGER_PLUGIN_FILE', __FILE__);

// Include the main plugin class
require_once CLUB_MANAGER_PLUGIN_DIR . 'includes/core/class-club-manager.php';

// Initialize the plugin
function club_manager_init() {
    $plugin = new Club_Manager();
    $plugin->run();
}

// Hook initialization
add_action('plugins_loaded', 'club_manager_init'); 
