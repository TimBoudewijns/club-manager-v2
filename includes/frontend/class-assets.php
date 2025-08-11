<?php

/**
 * Handle frontend assets.
 */
class Club_Manager_Assets {
    
    private $plugin_name;
    private $version;
    
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
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
        
        // Enqueue alle module bestanden
        wp_enqueue_script(
            'cm-team-module',
            CLUB_MANAGER_PLUGIN_URL . 'assets/js/modules/team-module.js',
            array('jquery'),
            $this->version,
            true
        );
        
        wp_enqueue_script(
            'cm-player-module',
            CLUB_MANAGER_PLUGIN_URL . 'assets/js/modules/player-module.js',
            array('jquery'),
            $this->version,
            true
        );
        
        wp_enqueue_script(
            'cm-evaluation-module',
            CLUB_MANAGER_PLUGIN_URL . 'assets/js/modules/evaluation-module.js',
            array('jquery'),
            $this->version,
            true
        );
        
        wp_enqueue_script(
            'cm-trainer-module',
            CLUB_MANAGER_PLUGIN_URL . 'assets/js/modules/trainer-module.js',
            array('jquery'),
            $this->version,
            true
        );
        
        wp_enqueue_script(
            'cm-team-management-module',
            CLUB_MANAGER_PLUGIN_URL . 'assets/js/modules/team-management-module.js',
            array('jquery'),
            $this->version,
            true
        );
        
        wp_enqueue_script(
            'cm-player-card-module',
            CLUB_MANAGER_PLUGIN_URL . 'assets/js/modules/player-card-module.js',
            array('jquery'),
            $this->version,
            true
        );
        
        wp_enqueue_script(
            'cm-club-teams-module',
            CLUB_MANAGER_PLUGIN_URL . 'assets/js/modules/club-teams-module.js',
            array('jquery'),
            $this->version,
            true
        );
        
        wp_enqueue_script(
            'cm-import-export-module',
            CLUB_MANAGER_PLUGIN_URL . 'assets/js/modules/import-export-module.js',
            array('jquery'),
            $this->version,
            true
        );
        
        // Enqueue main JS file
        wp_enqueue_script(
            $this->plugin_name . '-main',
            CLUB_MANAGER_PLUGIN_URL . 'assets/js/club-manager-init.js',
            array(
                'jquery',
                'cm-team-module',
                'cm-player-module',
                'cm-evaluation-module',
                'cm-trainer-module',
                'cm-team-management-module',
                'cm-player-card-module',
                'cm-club-teams-module',
                'cm-import-export-module'
            ),
            $this->version,
            true
        );
        
        // Localize script
        wp_localize_script($this->plugin_name . '-main', 'clubManagerAjax', $this->get_localize_data());
        
        // Enqueue Alpine.js
        wp_enqueue_script(
            'alpinejs',
            'https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js',
            array($this->plugin_name . '-main'),
            '3.x.x',
            true
        );
        
        // Add defer attribute to Alpine.js
        add_filter('script_loader_tag', function($tag, $handle) {
            if ($handle === 'alpinejs') {
                return str_replace('<script', '<script defer', $tag);
            }
            return $tag;
        }, 10, 2);
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
            
            /* Purple theme for import/export */
            .checkbox-purple:checked {
                background-color: #a855f7 !important;
                border-color: #a855f7 !important;
            }
            
            .radio-purple:checked {
                background-color: #a855f7 !important;
                border-color: #a855f7 !important;
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
                                    },
                                    purple: {
                                        50: "#faf5ff",
                                        100: "#f3e8ff",
                                        200: "#e9d5ff",
                                        300: "#d8b4fe",
                                        400: "#c084fc",
                                        500: "#a855f7",
                                        600: "#9333ea",
                                        700: "#7e22ce",
                                        800: "#6b21a8",
                                        900: "#581c87",
                                        950: "#3b0764"
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
        
        // Get user permissions
        $permissions = array();
        if (class_exists('Club_Manager_User_Permissions_Helper')) {
            $permissions = Club_Manager_User_Permissions_Helper::get_frontend_permissions($user_id);
        }
        
        // Get trainer limit from WooCommerce Teams
        $trainer_limit = null;
        if (function_exists('wc_memberships_for_teams') && isset($permissions['is_owner_or_manager']) && $permissions['is_owner_or_manager']) {
            // Get managed teams
            $managed_teams = Club_Manager_Teams_Helper::get_user_managed_teams($user_id);
            
            if (!empty($managed_teams)) {
                $wc_team_id = $managed_teams[0]['team_id'];
                $wc_team = wc_memberships_for_teams_get_team($wc_team_id);
                
                if ($wc_team && is_object($wc_team)) {
                    // Get team seat count
                    $seat_count = 0;
                    if (method_exists($wc_team, 'get_seat_count')) {
                        $seat_count = $wc_team->get_seat_count();
                    }
                    
                    if ($seat_count > 0) {
                        // Get used seats
                        $used_seats = 0;
                        if (method_exists($wc_team, 'get_used_seat_count')) {
                            $used_seats = $wc_team->get_used_seat_count();
                        }
                        
                        // Calculate available seats
                        $trainer_limit = max(0, $seat_count - $used_seats);
                    } else {
                        // No seat limit = unlimited
                        $trainer_limit = 999;
                    }
                }
            }
        }
        
        return array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('club_manager_nonce'),
            'plugin_url' => CLUB_MANAGER_PLUGIN_URL,
            'user_id' => $user_id,
            'is_logged_in' => is_user_logged_in(),
            'preferred_season' => Club_Manager_Season_Helper::get_user_preferred_season($user_id),
            'available_seasons' => Club_Manager_Season_Helper::get_available_seasons(),
            'is_first_season_selection' => Club_Manager_Season_Helper::is_first_season_selection($user_id),
            'permissions' => $permissions,
            'trainer_limit' => $trainer_limit
        );
    }
}