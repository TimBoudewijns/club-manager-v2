<?php

/**
 * Handle frontend assets.
 */
class Club_Manager_Assets {
    
    private $plugin_name;
    private $version;
    private $can_view_club_teams_cache = null;
    
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        
        // Hook to check teams access after everything is loaded
        add_action('init', array($this, 'check_teams_access'), 999);
    }
    
    /**
     * Check teams access after init
     */
    public function check_teams_access() {
        if (is_user_logged_in() && class_exists('Club_Manager_Teams_Helper')) {
            $user_id = get_current_user_id();
            $this->can_view_club_teams_cache = Club_Manager_Teams_Helper::can_view_club_teams($user_id);
            error_log('Club Manager Assets (after init): Can view club teams = ' . ($this->can_view_club_teams_cache ? 'yes' : 'no'));
        }
    }
    
    /**
     * Register the stylesheets for the public-facing side.
     */
    public function enqueue_styles() {
        global $post;
        
        // Only enqueue if shortcode is present
        if (!is_a($post, 'WP_Post') || !has_shortcode($post->post_content, 'club_manager')) {
            return;
        }
        
        // Inline CSS for theme override
        wp_add_inline_style('wp-block-library', $this->get_theme_override_css());
        
        // Enqueue DaisyUI
        wp_enqueue_style(
            'daisyui',
            'https://cdn.jsdelivr.net/npm/daisyui@4.6.0/dist/full.min.css',
            array(),
            '4.6.0'
        );
        
        // Enqueue custom CSS
        wp_enqueue_style(
            $this->plugin_name,
            CLUB_MANAGER_PLUGIN_URL . 'assets/css/club-manager-styles.css',
            array('daisyui'),
            $this->version
        );
    }
    
    /**
     * Register the JavaScript for the public-facing side.
     */
    public function enqueue_scripts() {
        global $post;
        
        // Only enqueue if shortcode is present
        if (!is_a($post, 'WP_Post') || !has_shortcode($post->post_content, 'club_manager')) {
            return;
        }
        
        // Enqueue Tailwind CSS
        wp_enqueue_script(
            'tailwind-css',
            'https://cdn.tailwindcss.com',
            array(),
            '3.4.0',
            false
        );
        
        // Add Tailwind configuration
        wp_add_inline_script('tailwind-css', $this->get_tailwind_config(), 'after');
        
        // Enqueue Chart.js
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js',
            array(),
            '4.4.0',
            true
        );
        
        // Enqueue jsPDF
        wp_enqueue_script(
            'jspdf',
            'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js',
            array(),
            '2.5.1',
            true
        );
        
        // Enqueue main JS file
        wp_enqueue_script(
            $this->plugin_name,
            CLUB_MANAGER_PLUGIN_URL . 'assets/js/club-manager.js',
            array('jquery'),
            $this->version,
            true
        );
        
        // Localize script
        wp_localize_script($this->plugin_name, 'clubManagerAjax', $this->get_localize_data());
        
        // Enqueue Alpine.js
        wp_enqueue_script(
            'alpinejs',
            'https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js',
            array(),
            '3.x.x',
            true
        );
    }
    
    /**
     * Get theme override CSS.
     */
    private function get_theme_override_css() {
        return '
            :root {
                --p: 25 95% 53%;
                --pf: 27 96% 48%;
                --pc: 0 0% 100%;
                --s: 25 95% 53%;
                --sf: 27 96% 48%;
                --sc: 0 0% 100%;
                --a: 25 95% 53%;
                --af: 27 96% 48%;
                --ac: 0 0% 100%;
            }
            
            [data-theme="light"] {
                --p: 25 95% 53%;
                --pf: 27 96% 48%;
                --pc: 0 0% 100%;
            }
            
            .btn-primary {
                background-color: #f97316 !important;
                border-color: #f97316 !important;
            }
            
            .btn-primary:hover {
                background-color: #ea580c !important;
                border-color: #ea580c !important;
            }
        ';
    }
    
    /**
     * Get Tailwind configuration.
     */
    private function get_tailwind_config() {
        return '
            function configureTailwind() {
                if (typeof tailwind !== "undefined") {
                    tailwind.config = {
                        darkMode: "class",
                        theme: {
                            extend: {
                                colors: {
                                    orange: {
                                        50: "#fff7ed",
                                        100: "#ffedd5",
                                        200: "#fed7aa",
                                        300: "#fdba74",
                                        400: "#fb923c",
                                        500: "#f97316",
                                        600: "#ea580c",
                                        700: "#c2410c",
                                        800: "#9a3412",
                                        900: "#7c2d12",
                                        950: "#431407"
                                    }
                                }
                            }
                        }
                    };
                } else {
                    setTimeout(configureTailwind, 100);
                }
            }
            configureTailwind();
        ';
    }
    
    /**
     * Get localization data.
     */
    private function get_localize_data() {
        $user_id = get_current_user_id();
        
        // Use cached value if available, otherwise check now
        $can_view_club_teams = false;
        if ($this->can_view_club_teams_cache !== null) {
            $can_view_club_teams = $this->can_view_club_teams_cache;
        } elseif (class_exists('Club_Manager_Teams_Helper')) {
            // Fallback check
            $can_view_club_teams = Club_Manager_Teams_Helper::can_view_club_teams($user_id);
        }
        
        return array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('club_manager_nonce'),
            'user_id' => $user_id,
            'is_logged_in' => is_user_logged_in(),
            'preferred_season' => get_user_meta($user_id, 'cm_preferred_season', true) ?: '2024-2025',
            'can_view_club_teams' => $can_view_club_teams
        );
    }
}