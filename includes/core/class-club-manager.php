<?php

/**
 * The core plugin class.
 */
class Club_Manager {
    
    /**
     * The loader that's responsible for maintaining and registering all hooks.
     */
    protected $loader;
    
    /**
     * The unique identifier of this plugin.
     */
    protected $plugin_name;
    
    /**
     * The current version of the plugin.
     */
    protected $version;
    
    /**
     * Define the core functionality of the plugin.
     */
    public function __construct() {
        $this->version = CLUB_MANAGER_VERSION;
        $this->plugin_name = 'club-manager';
        
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }
    
    /**
     * Load the required dependencies for this plugin.
     */
    private function load_dependencies() {
        // Core
        require_once CLUB_MANAGER_PLUGIN_DIR . 'includes/core/class-loader.php';
        require_once CLUB_MANAGER_PLUGIN_DIR . 'includes/core/class-activator.php';
        
        // Database
        require_once CLUB_MANAGER_PLUGIN_DIR . 'includes/database/class-database.php';
        require_once CLUB_MANAGER_PLUGIN_DIR . 'includes/database/class-teams-table.php';
        require_once CLUB_MANAGER_PLUGIN_DIR . 'includes/database/class-players-table.php';
        require_once CLUB_MANAGER_PLUGIN_DIR . 'includes/database/class-evaluations-table.php';
        
        // Models
        require_once CLUB_MANAGER_PLUGIN_DIR . 'includes/models/class-team-model.php';
        require_once CLUB_MANAGER_PLUGIN_DIR . 'includes/models/class-player-model.php';
        require_once CLUB_MANAGER_PLUGIN_DIR . 'includes/models/class-evaluation-model.php';
        
        // AJAX
        require_once CLUB_MANAGER_PLUGIN_DIR . 'includes/ajax/class-ajax-handler.php';
        require_once CLUB_MANAGER_PLUGIN_DIR . 'includes/ajax/class-team-ajax.php';
        require_once CLUB_MANAGER_PLUGIN_DIR . 'includes/ajax/class-player-ajax.php';
        require_once CLUB_MANAGER_PLUGIN_DIR . 'includes/ajax/class-evaluation-ajax.php';
        require_once CLUB_MANAGER_PLUGIN_DIR . 'includes/ajax/class-ai-ajax.php';
        
        // AI
        require_once CLUB_MANAGER_PLUGIN_DIR . 'includes/ai/class-ai-manager.php';
        require_once CLUB_MANAGER_PLUGIN_DIR . 'includes/ai/class-openai-client.php';
        
        // Frontend
        require_once CLUB_MANAGER_PLUGIN_DIR . 'includes/frontend/class-shortcode.php';
        require_once CLUB_MANAGER_PLUGIN_DIR . 'includes/frontend/class-assets.php';
        
        $this->loader = new Club_Manager_Loader();
    }
    
    /**
     * Register all of the hooks related to the admin area functionality.
     */
    private function define_admin_hooks() {
        // Activation/Deactivation hooks
        register_activation_hook(CLUB_MANAGER_PLUGIN_FILE, array('Club_Manager_Activator', 'activate'));
        register_deactivation_hook(CLUB_MANAGER_PLUGIN_FILE, array('Club_Manager_Activator', 'deactivate'));
    }
    
    /**
     * Register all of the hooks related to the public-facing functionality.
     */
    private function define_public_hooks() {
        $plugin_public = new Club_Manager_Assets($this->get_plugin_name(), $this->get_version());
        $plugin_shortcode = new Club_Manager_Shortcode($this->get_plugin_name(), $this->get_version());
        
        // Assets
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
        
        // Shortcode
        $this->loader->add_action('init', $plugin_shortcode, 'register_shortcode');
        
        // AJAX Handlers
        $team_ajax = new Club_Manager_Team_Ajax();
        $player_ajax = new Club_Manager_Player_Ajax();
        $evaluation_ajax = new Club_Manager_Evaluation_Ajax();
        $ai_ajax = new Club_Manager_AI_Ajax();
        
        // Initialize AJAX handlers
        $this->loader->add_action('init', $team_ajax, 'init');
        $this->loader->add_action('init', $player_ajax, 'init');
        $this->loader->add_action('init', $evaluation_ajax, 'init');
        $this->loader->add_action('init', $ai_ajax, 'init');
        
        // AI Manager
        $ai_manager = new Club_Manager_AI_Manager();
        $this->loader->add_action('init', $ai_manager, 'init');
    }
    
    /**
     * Run the loader to execute all of the hooks with WordPress.
     */
    public function run() {
        $this->loader->run();
    }
    
    /**
     * The name of the plugin used to uniquely identify it.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }
    
    /**
     * The reference to the class that orchestrates the hooks.
     */
    public function get_loader() {
        return $this->loader;
    }
    
    /**
     * Retrieve the version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }
} 
