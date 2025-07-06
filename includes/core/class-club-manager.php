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
        
        // Helpers
        $helpers_file = CLUB_MANAGER_PLUGIN_DIR . 'includes/helpers/class-teams-helper.php';
        if (file_exists($helpers_file)) {
            require_once $helpers_file;
        } else {
            error_log('Club Manager: Teams helper file missing at ' . $helpers_file);
        }
        
        // User Permissions Helper
        $permissions_file = CLUB_MANAGER_PLUGIN_DIR . 'includes/helpers/class-user-permissions-helper.php';
        if (file_exists($permissions_file)) {
            require_once $permissions_file;
        } else {
            error_log('Club Manager: User permissions helper file missing at ' . $permissions_file);
        }
        
        // Database
        require_once CLUB_MANAGER_PLUGIN_DIR . 'includes/database/class-database.php';
        require_once CLUB_MANAGER_PLUGIN_DIR . 'includes/database/class-teams-table.php';
        require_once CLUB_MANAGER_PLUGIN_DIR . 'includes/database/class-players-table.php';
        require_once CLUB_MANAGER_PLUGIN_DIR . 'includes/database/class-evaluations-table.php';
        
        // Check if trainers table file exists before including
        $trainers_table_file = CLUB_MANAGER_PLUGIN_DIR . 'includes/database/class-trainers-table.php';
        if (file_exists($trainers_table_file)) {
            require_once $trainers_table_file;
        } else {
            error_log('Club Manager: Trainers table file missing at ' . $trainers_table_file);
        }
        
        // Models
        require_once CLUB_MANAGER_PLUGIN_DIR . 'includes/models/class-team-model.php';
        require_once CLUB_MANAGER_PLUGIN_DIR . 'includes/models/class-player-model.php';
        require_once CLUB_MANAGER_PLUGIN_DIR . 'includes/models/class-evaluation-model.php';
        
        // Logger
        require_once CLUB_MANAGER_PLUGIN_DIR . 'includes/class-logger.php';
        
        // Import/Export classes
        require_once CLUB_MANAGER_PLUGIN_DIR . 'includes/import-export/class-csv-parser.php';
        require_once CLUB_MANAGER_PLUGIN_DIR . 'includes/import-export/class-data-validator.php';
        require_once CLUB_MANAGER_PLUGIN_DIR . 'includes/import-export/class-import-handler.php';
        require_once CLUB_MANAGER_PLUGIN_DIR . 'includes/import-export/class-export-handler.php';
        require_once CLUB_MANAGER_PLUGIN_DIR . 'includes/import-export/class-import-queue.php';
        
        // AJAX
        require_once CLUB_MANAGER_PLUGIN_DIR . 'includes/ajax/class-ajax-handler.php';
        require_once CLUB_MANAGER_PLUGIN_DIR . 'includes/ajax/class-team-ajax.php';
        require_once CLUB_MANAGER_PLUGIN_DIR . 'includes/ajax/class-player-ajax.php';
        require_once CLUB_MANAGER_PLUGIN_DIR . 'includes/ajax/class-evaluation-ajax.php';
        require_once CLUB_MANAGER_PLUGIN_DIR . 'includes/ajax/class-ai-ajax.php';
        require_once CLUB_MANAGER_PLUGIN_DIR . 'includes/ajax/class-club-ajax.php';
        require_once CLUB_MANAGER_PLUGIN_DIR . 'includes/ajax/class-trainer-ajax.php';
        require_once CLUB_MANAGER_PLUGIN_DIR . 'includes/ajax/class-import-export-ajax.php';
        
        // AI
        require_once CLUB_MANAGER_PLUGIN_DIR . 'includes/ai/class-ai-manager.php';
        require_once CLUB_MANAGER_PLUGIN_DIR . 'includes/ai/class-openai-client.php';
        
        // Frontend
        require_once CLUB_MANAGER_PLUGIN_DIR . 'includes/frontend/class-shortcode.php';
        require_once CLUB_MANAGER_PLUGIN_DIR . 'includes/frontend/class-assets.php';
        require_once CLUB_MANAGER_PLUGIN_DIR . 'includes/frontend/class-trainer-invitation-handler.php';
        
        $this->loader = new Club_Manager_Loader();
    }
    
    /**
     * Register all of the hooks related to the admin area functionality.
     */
    private function define_admin_hooks() {
        // Activation/Deactivation hooks
        register_activation_hook(CLUB_MANAGER_PLUGIN_FILE, array('Club_Manager_Activator', 'activate'));
        register_deactivation_hook(CLUB_MANAGER_PLUGIN_FILE, array('Club_Manager_Activator', 'deactivate'));
        
        // Add download export action
        add_action('wp_ajax_cm_download_export', array($this, 'handle_export_download'));
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
        
        // AJAX Handlers - Initialize directly for proper WordPress AJAX hook registration
        $team_ajax = new Club_Manager_Team_Ajax();
        $player_ajax = new Club_Manager_Player_Ajax();
        $evaluation_ajax = new Club_Manager_Evaluation_Ajax();
        $ai_ajax = new Club_Manager_AI_Ajax();
        $club_ajax = new Club_Manager_Club_Ajax();
        $trainer_ajax = new Club_Manager_Trainer_Ajax();
        $import_export_ajax = new Club_Manager_Import_Export_Ajax();
        
        // Initialize AJAX handlers immediately
        $team_ajax->init();
        $player_ajax->init();
        $evaluation_ajax->init();
        $ai_ajax->init();
        $club_ajax->init();
        $trainer_ajax->init();
        $import_export_ajax->init();
        
        // AI Manager
        $ai_manager = new Club_Manager_AI_Manager();
        $this->loader->add_action('init', $ai_manager, 'init');
        
        // Trainer Invitation Handler
        $invitation_handler = new Club_Manager_Trainer_Invitation_Handler();
        $this->loader->add_action('init', $invitation_handler, 'init');
        
        // Check for database updates
        $this->loader->add_action('init', $this, 'check_database_version');
    }
    
    /**
     * Check database version and run updates if needed.
     */
    public function check_database_version() {
        $current_db_version = get_option('club_manager_db_version', '1.0.0');
        
        // If database version is less than 2.0.0, we need to update
        if (version_compare($current_db_version, '2.0.0', '<')) {
            Club_Manager_Database::create_tables();
            error_log('Club Manager: Database updated to version 2.0.0');
        }
    }
    
    /**
     * Handle export file downloads.
     */
    public function handle_export_download() {
        // Verify nonce
        if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'cm_download_' . $_GET['file'])) {
            wp_die('Invalid request');
        }
        
        // Check permissions
        if (!is_user_logged_in() || !Club_Manager_User_Permissions_Helper::can_import_export(get_current_user_id())) {
            wp_die('Unauthorized');
        }
        
        $file = sanitize_file_name($_GET['file']);
        $upload_dir = wp_upload_dir();
        $export_dir = $upload_dir['basedir'] . '/club-manager-exports';
        $file_path = $export_dir . '/' . $file;
        
        // Security check - ensure file is in export directory
        if (!file_exists($file_path) || strpos(realpath($file_path), realpath($export_dir)) !== 0) {
            wp_die('File not found');
        }
        
        // Determine content type
        $file_extension = pathinfo($file, PATHINFO_EXTENSION);
        if ($file_extension === 'csv') {
            $content_type = 'text/csv';
        } else {
            $content_type = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        }
        
        // Send file
        header('Content-Type: ' . $content_type);
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        header('Content-Length: ' . filesize($file_path));
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        readfile($file_path);
        
        // Delete file after download
        unlink($file_path);
        
        exit;
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