<?php

/**
 * Handle the [club_manager] shortcode.
 */
class Club_Manager_Shortcode {
    
    private $plugin_name;
    private $version;
    
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }
    
    /**
     * Register the shortcode.
     */
    public function register_shortcode() {
        add_shortcode('club_manager', array($this, 'render_shortcode'));
    }
    
    /**
     * Render the shortcode.
     */
    public function render_shortcode($atts) {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return $this->render_login_required();
        }
        
        // Start output buffering
        ob_start();
        
        // Include main template
        include CLUB_MANAGER_PLUGIN_DIR . 'templates/main-dashboard.php';
        
        return ob_get_clean();
    }
    
    /**
     * Render login required message.
     */
    private function render_login_required() {
        return '<div class="bg-gradient-to-r from-orange-50 to-amber-50 rounded-xl p-8 shadow-lg border border-orange-200">
            <div class="flex items-center space-x-4">
                <div class="bg-orange-100 rounded-full p-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-xl font-bold text-gray-900">Login Required</h3>
                    <p class="text-gray-600 mt-1">Please log in to access the Club Manager dashboard.</p>
                </div>
            </div>
        </div>';
    }
} 
