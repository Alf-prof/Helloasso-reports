<?php
/**
 * Plugin Name: HelloAsso Events Reports
 * Plugin URI: https://example.com/helloasso-events
 * Description: Affiche les événements HelloAsso avec le nombre de places vendues
 * Version: 2.0.0
 * Author: Alain Fiala
 * License: GPL v2 or later
 * Text Domain: helloasso-events
 */

// Sécurité : empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

// Définir les constantes du plugin
define('HELLOASSO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('HELLOASSO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('HELLOASSO_VERSION', '2.0.0');

// Charger les fichiers du plugin
require_once HELLOASSO_PLUGIN_DIR . 'includes/class-helloasso-api.php';
require_once HELLOASSO_PLUGIN_DIR . 'includes/class-helloasso-email.php';
require_once HELLOASSO_PLUGIN_DIR . 'includes/class-helloasso-admin.php';
require_once HELLOASSO_PLUGIN_DIR . 'includes/class-helloasso-shortcode.php';

/**
 * Classe principale du plugin
 */
class HelloAsso_Events_Reports {
    
    private static $instance = null;
    public $api;
    public $email;
    public $admin;
    public $shortcode;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Initialiser les composants
        $this->api = new HelloAsso_API();
        $this->email = new HelloAsso_Email($this->api);
        $this->admin = new HelloAsso_Admin($this->api, $this->email);
        $this->shortcode = new HelloAsso_Shortcode($this->api);
        
        // Hooks d'activation/désactivation
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Charger les assets frontend
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
    }
    
    public function activate() {
        // Vérifier que les constantes sont définies
        if (!defined('HELLOASSO_CLIENT_ID') || !defined('HELLOASSO_CLIENT_SECRET') || !defined('HELLOASSO_ORGANIZATION_SLUG')) {
            wp_die('Veuillez ajouter les constantes HELLOASSO_CLIENT_ID, HELLOASSO_CLIENT_SECRET et HELLOASSO_ORGANIZATION_SLUG dans votre fichier wp-config.php');
        }
        
        // Initialiser les options
        $this->email->init_options();
    }
    
    public function deactivate() {
        // Nettoyer les transients
        delete_transient('helloasso_access_token');
        delete_transient('helloasso_events_cache');
    }
    
    public function enqueue_frontend_assets() {
        if (file_exists(HELLOASSO_PLUGIN_DIR . 'assets/style.css')) {
            wp_enqueue_style('helloasso-events', HELLOASSO_PLUGIN_URL . 'assets/style.css', array(), HELLOASSO_VERSION);
        }
    }
}

// Initialiser le plugin
function helloasso_init() {
    return HelloAsso_Events_Reports::get_instance();
}
add_action('plugins_loaded', 'helloasso_init');
