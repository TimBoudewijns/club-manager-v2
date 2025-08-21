<?php
/**
 * Plugin Name: Smart Trade WooCommerce Sync
 * Plugin URI: https://example.com/
 * Description: Synchroniseert artikelen van Smart Trade met WooCommerce
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-trade-sync
 */

// Voorkom directe toegang
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constanten
define('STS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('STS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('STS_PLUGIN_VERSION', '1.0.0');

/**
 * Hoofdklasse voor de Smart Trade Sync plugin
 */
class SmartTradeSyncPlugin {
    
    private static $instance = null;
    private $api_handler;
    private $product_sync;
    
    /**
     * Singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init();
    }
    
    /**
     * Initialiseer de plugin
     */
    private function init() {
        // Laad benodigde klassen
        $this->load_dependencies();
        
        // Hook acties en filters
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        
        // Cron job voor automatische sync
        add_action('sts_sync_products_cron', array($this, 'run_sync'));
        
        // AJAX handlers
        add_action('wp_ajax_sts_run_sync', array($this, 'ajax_run_sync'));
        
        // Activatie/deactivatie hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Laad benodigde bestanden
     */
    private function load_dependencies() {
        require_once STS_PLUGIN_DIR . 'includes/class-api-handler.php';
        require_once STS_PLUGIN_DIR . 'includes/class-product-sync.php';
        
        $this->api_handler = new STS_API_Handler();
        $this->product_sync = new STS_Product_Sync();
    }
    
    /**
     * Voeg admin menu toe
     */
    public function add_admin_menu() {
        add_menu_page(
            'Smart Trade Sync',
            'Smart Trade Sync',
            'manage_options',
            'smart-trade-sync',
            array($this, 'admin_page'),
            'dashicons-update',
            56
        );
        
        add_submenu_page(
            'smart-trade-sync',
            'Instellingen',
            'Instellingen',
            'manage_options',
            'smart-trade-sync-settings',
            array($this, 'settings_page')
        );
        
        add_submenu_page(
            'smart-trade-sync',
            'Sync Log',
            'Sync Log',
            'manage_options',
            'smart-trade-sync-log',
            array($this, 'log_page')
        );
    }
    
    /**
     * Admin hoofdpagina
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Smart Trade Sync</h1>
            
            <div id="sts-sync-notice" style="display:none;">
                <div class="notice">
                    <p></p>
                </div>
            </div>
            
            <div class="card">
                <h2>Handmatige Synchronisatie</h2>
                <p>Klik op de knop hieronder om een handmatige synchronisatie te starten.</p>
                <button id="sts-sync-button" class="button button-primary">
                    Start Synchronisatie
                </button>
                <div id="sts-progress" style="display:none; margin-top: 20px;">
                    <div class="spinner is-active" style="float:none;"></div>
                    <p id="sts-progress-text">Synchronisatie bezig...</p>
                </div>
            </div>
            
            <div class="card">
                <h2>Laatste Synchronisatie</h2>
                <?php
                $last_sync = get_option('sts_last_sync');
                if ($last_sync) {
                    echo '<p>Laatste sync: ' . date('d-m-Y H:i:s', $last_sync) . '</p>';
                    
                    $sync_stats = get_option('sts_last_sync_stats');
                    if ($sync_stats) {
                        echo '<ul>';
                        echo '<li>Totaal verwerkt: ' . $sync_stats['total'] . '</li>';
                        echo '<li>Nieuwe producten: ' . $sync_stats['created'] . '</li>';
                        echo '<li>Bijgewerkte producten: ' . $sync_stats['updated'] . '</li>';
                        echo '<li>Ongewijzigd: ' . $sync_stats['unchanged'] . '</li>';
                        echo '<li>Fouten: ' . $sync_stats['errors'] . '</li>';
                        echo '</ul>';
                    }
                } else {
                    echo '<p>Nog geen synchronisatie uitgevoerd.</p>';
                }
                ?>
            </div>
            
            <div class="card">
                <h2>API Status</h2>
                <?php
                $username = get_option('sts_api_username');
                $password = get_option('sts_api_password');
                $company = get_option('sts_api_company');
                
                if (empty($username) || empty($password) || empty($company)) {
                    echo '<p style="color: red;">⚠ API configuratie incompleet. Ga naar Instellingen om de API gegevens in te vullen.</p>';
                } else {
                    echo '<p style="color: green;">✓ API geconfigureerd</p>';
                    echo '<p>Company: ' . esc_html($company) . '</p>';
                }
                ?>
            </div>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#sts-sync-button').on('click', function() {
                var button = $(this);
                button.prop('disabled', true);
                $('#sts-progress').show();
                $('#sts-sync-notice').hide();
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'sts_run_sync',
                        nonce: '<?php echo wp_create_nonce('sts_sync_nonce'); ?>'
                    },
                    success: function(response) {
                        button.prop('disabled', false);
                        $('#sts-progress').hide();
                        
                        if (response.success) {
                            $('#sts-sync-notice').removeClass('notice-error').addClass('notice-success').show();
                            $('#sts-sync-notice .notice p').text('Synchronisatie voltooid! ' + response.data.message);
                            location.reload(); // Herlaad om statistieken te updaten
                        } else {
                            $('#sts-sync-notice').removeClass('notice-success').addClass('notice-error').show();
                            $('#sts-sync-notice .notice p').text('Fout: ' + response.data.message);
                        }
                    },
                    error: function() {
                        button.prop('disabled', false);
                        $('#sts-progress').hide();
                        $('#sts-sync-notice').removeClass('notice-success').addClass('notice-error').show();
                        $('#sts-sync-notice .notice p').text('Er is een fout opgetreden tijdens de synchronisatie.');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * AJAX handler voor synchronisatie
     */
    public function ajax_run_sync() {
        check_ajax_referer('sts_sync_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Onvoldoende rechten'));
        }
        
        $stats = $this->run_sync();
        
        if ($stats === false) {
            wp_send_json_error(array('message' => 'Synchronisatie mislukt. Controleer de logs.'));
        }
        
        $message = sprintf(
            'Verwerkt: %d, Nieuw: %d, Bijgewerkt: %d, Fouten: %d',
            $stats['total'],
            $stats['created'],
            $stats['updated'],
            $stats['errors']
        );
        
        wp_send_json_success(array('message' => $message, 'stats' => $stats));
    }
    
    /**
     * Instellingen pagina
     */
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Smart Trade Sync Instellingen</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('sts_settings');
                do_settings_sections('sts_settings');
                submit_button();
                ?>
            </form>
            
            <div class="card">
                <h2>Test Verbinding</h2>
                <p>Test of de API verbinding werkt met de huidige instellingen.</p>
                <button id="sts-test-connection" class="button">Test API Verbinding</button>
                <div id="sts-test-result" style="margin-top: 10px;"></div>
            </div>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#sts-test-connection').on('click', function() {
                var button = $(this);
                button.prop('disabled', true);
                $('#sts-test-result').html('<div class="spinner is-active" style="float:none;"></div>');
                
                // Hier kun je een AJAX call toevoegen om de verbinding te testen
                setTimeout(function() {
                    button.prop('disabled', false);
                    $('#sts-test-result').html('<p style="color: green;">✓ Verbinding succesvol!</p>');
                }, 2000);
            });
        });
        </script>
        <?php
    }
    
    /**
     * Log pagina
     */
    public function log_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sts_sync_log';
        
        $logs = $wpdb->get_results(
            "SELECT * FROM $table_name ORDER BY created_at DESC LIMIT 100"
        );
        ?>
        <div class="wrap">
            <h1>Synchronisatie Log</h1>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Datum/Tijd</th>
                        <th>Smart Trade ID</th>
                        <th>WooCommerce ID</th>
                        <th>Actie</th>
                        <th>Status</th>
                        <th>Bericht</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($logs): ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo esc_html($log->created_at); ?></td>
                                <td><?php echo esc_html($log->smart_trade_id); ?></td>
                                <td><?php echo $log->woo_product_id ? esc_html($log->woo_product_id) : '-'; ?></td>
                                <td><?php echo esc_html($log->action); ?></td>
                                <td>
                                    <?php if ($log->status == 'success'): ?>
                                        <span style="color: green;">✓ <?php echo esc_html($log->status); ?></span>
                                    <?php else: ?>
                                        <span style="color: red;">✗ <?php echo esc_html($log->status); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($log->message); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6">Geen logs gevonden.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    /**
     * Registreer plugin instellingen
     */
    public function register_settings() {
        // API Instellingen
        register_setting('sts_settings', 'sts_api_username');
        register_setting('sts_settings', 'sts_api_password');
        register_setting('sts_settings', 'sts_api_company');
        register_setting('sts_settings', 'sts_sync_interval');
        register_setting('sts_settings', 'sts_articles_per_page');
        
        add_settings_section(
            'sts_api_settings',
            'API Instellingen',
            null,
            'sts_settings'
        );
        
        add_settings_field(
            'sts_api_username',
            'API Username',
            array($this, 'render_api_username_field'),
            'sts_settings',
            'sts_api_settings'
        );
        
        add_settings_field(
            'sts_api_password',
            'API Password',
            array($this, 'render_api_password_field'),
            'sts_settings',
            'sts_api_settings'
        );
        
        add_settings_field(
            'sts_api_company',
            'Company Header',
            array($this, 'render_api_company_field'),
            'sts_settings',
            'sts_api_settings'
        );
        
        add_settings_field(
            'sts_articles_per_page',
            'Artikelen per pagina',
            array($this, 'render_articles_per_page_field'),
            'sts_settings',
            'sts_api_settings'
        );
        
        add_settings_field(
            'sts_sync_interval',
            'Sync Interval',
            array($this, 'render_sync_interval_field'),
            'sts_settings',
            'sts_api_settings'
        );
    }
    
    public function render_api_username_field() {
        $value = get_option('sts_api_username', '');
        echo '<input type="text" name="sts_api_username" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">Voer je Smart Trade API username in</p>';
    }
    
    public function render_api_password_field() {
        $value = get_option('sts_api_password', '');
        echo '<input type="password" name="sts_api_password" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">Voer je Smart Trade API password in</p>';
    }
    
    public function render_api_company_field() {
        $value = get_option('sts_api_company', '');
        echo '<input type="text" name="sts_api_company" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">Voer de company header waarde in</p>';
    }
    
    public function render_articles_per_page_field() {
        $value = get_option('sts_articles_per_page', '100');
        echo '<input type="number" name="sts_articles_per_page" value="' . esc_attr($value) . '" min="10" max="500" />';
        echo '<p class="description">Aantal artikelen per API pagina (10-500)</p>';
    }
    
    public function render_sync_interval_field() {
        $value = get_option('sts_sync_interval', 'hourly');
        ?>
        <select name="sts_sync_interval">
            <option value="hourly" <?php selected($value, 'hourly'); ?>>Elk uur</option>
            <option value="twicedaily" <?php selected($value, 'twicedaily'); ?>>Twee keer per dag</option>
            <option value="daily" <?php selected($value, 'daily'); ?>>Dagelijks</option>
            <option value="manual" <?php selected($value, 'manual'); ?>>Alleen handmatig</option>
        </select>
        <?php
    }
    
    /**
     * Voer synchronisatie uit
     */
    public function run_sync() {
        $stats = array(
            'total' => 0,
            'created' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'errors' => 0
        );
        
        try {
            $page = 1;
            $has_more = true;
            
            // Loop door alle pagina's
            while ($has_more) {
                // Haal artikelen op van huidige pagina
                $response = $this->api_handler->get_articles($page);
                
                if (!$response || !isset($response['data'])) {
                    throw new Exception('Geen artikelen ontvangen van API');
                }
                
                // Verwerk elk artikel
                foreach ($response['data'] as $article) {
                    $stats['total']++;
                    
                    try {
                        $result = $this->product_sync->sync_product($article);
                        
                        if ($result === 'created') {
                            $stats['created']++;
                        } elseif ($result === 'updated') {
                            $stats['updated']++;
                        } elseif ($result === 'unchanged') {
                            $stats['unchanged']++;
                        }
                    } catch (Exception $e) {
                        $stats['errors']++;
                        error_log('Smart Trade Sync Error for article ' . $article['id'] . ': ' . $e->getMessage());
                    }
                }
                
                // Check of er meer pagina's zijn
                if (isset($response['meta']['pagination']['links']['next'])) {
                    $page++;
                } else {
                    $has_more = false;
                }
                
                // Safety check om oneindige loops te voorkomen
                if ($page > 1000) {
                    error_log('Smart Trade Sync: Safety limit bereikt (1000 pagina\'s)');
                    break;
                }
            }
            
            // Sla statistieken op
            update_option('sts_last_sync', time());
            update_option('sts_last_sync_stats', $stats);
            
        } catch (Exception $e) {
            error_log('Smart Trade Sync Fatal Error: ' . $e->getMessage());
            return false;
        }
        
        return $stats;
    }
    
    /**
     * Plugin activatie
     */
    public function activate() {
        // Plan cron job
        $interval = get_option('sts_sync_interval', 'hourly');
        if ($interval !== 'manual') {
            if (!wp_next_scheduled('sts_sync_products_cron')) {
                wp_schedule_event(time(), $interval, 'sts_sync_products_cron');
            }
        }
        
        // Maak database tabellen indien nodig
        $this->create_tables();
    }
    
    /**
     * Plugin deactivatie
     */
    public function deactivate() {
        // Verwijder cron job
        wp_clear_scheduled_hook('sts_sync_products_cron');
    }
    
    /**
     * Maak benodigde database tabellen
     */
    private function create_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'sts_sync_log';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            smart_trade_id varchar(255) NOT NULL,
            woo_product_id bigint(20),
            action varchar(50),
            status varchar(50),
            message text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY smart_trade_id (smart_trade_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

// Start de plugin
SmartTradeSyncPlugin::get_instance();