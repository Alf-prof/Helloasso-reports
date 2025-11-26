<?php
/**
 * Classe de gestion de l'administration
 * Version avec menus réorganisés : Configuration & Tests fusionnés, Présentation développée, Shortcodes séparés
 */

if (!defined('ABSPATH')) {
    exit;
}

class HelloAsso_Admin {
    
    private $api;
    private $email;
    
    public function __construct($api, $email) {
        $this->api = $api;
        $this->email = $email;
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Enregistrer les handlers pour les actions POST
        add_action('admin_post_helloasso_save_email_settings', array($this, 'handle_save_settings'));
        add_action('admin_post_helloasso_add_schedule', array($this, 'handle_add_schedule'));
        add_action('admin_post_helloasso_add_auto_schedule', array($this, 'handle_add_auto_schedule'));
        add_action('admin_post_helloasso_delete_schedule', array($this, 'handle_delete_schedule'));
        add_action('admin_post_helloasso_delete_all_schedules', array($this, 'handle_delete_all_schedules'));
        add_action('admin_post_helloasso_clean_schedules', array($this, 'handle_clean_schedules'));
        
        // AJAX pour rafraîchir les événements
        add_action('wp_ajax_helloasso_refresh_events', array($this, 'ajax_refresh_events'));
    }
    
    /**
     * Traiter la sauvegarde des paramètres email
     */
    public function handle_save_settings() {
        if (!current_user_can('manage_options')) {
            wp_die('Accès non autorisé');
        }
        
        if (!isset($_POST['helloasso_settings_nonce']) || !wp_verify_nonce($_POST['helloasso_settings_nonce'], 'helloasso_save_email_settings')) {
            wp_die('Nonce invalide');
        }
        
        $email_settings = $this->email->get_settings();
        
        if (!is_array($email_settings)) {
            $email_settings = array(
                'enable_email' => false,
                'email_recipients' => '',
                'schedules' => array()
            );
        }
        
        $email_settings['enable_email'] = isset($_POST['enable_email']) ? true : false;
        $email_settings['email_recipients'] = sanitize_text_field($_POST['email_recipients']);
        
        if (!isset($email_settings['schedules'])) {
            $email_settings['schedules'] = array();
        }
        
        $exists = get_option('helloasso_email_settings', null);
        
        if ($exists === null || $exists === false) {
            $result = add_option('helloasso_email_settings', $email_settings, '', false);
        } else {
            $result = update_option('helloasso_email_settings', $email_settings, false);
        }
        
        if (!$result && $exists !== null) {
            delete_option('helloasso_email_settings');
            $result = add_option('helloasso_email_settings', $email_settings, '', false);
        }
        
        $redirect_url = add_query_arg(
            array(
                'page' => 'helloasso-email-reports',
                'message' => $result ? 'settings_saved' : 'settings_error'
            ),
            admin_url('admin.php')
        );
        
        wp_redirect($redirect_url);
        exit;
    }
    
    /**
     * Traiter l'ajout d'un schedule
     */
    public function handle_add_schedule() {
        if (!current_user_can('manage_options')) {
            wp_die('Accès non autorisé');
        }
        
        if (!isset($_POST['helloasso_schedule_nonce']) || !wp_verify_nonce($_POST['helloasso_schedule_nonce'], 'helloasso_add_schedule')) {
            wp_die('Nonce invalide');
        }
        
        if (empty($_POST['event_slugs']) || !is_array($_POST['event_slugs'])) {
            wp_redirect(add_query_arg(
                array('page' => 'helloasso-email-reports', 'message' => 'schedule_error'),
                admin_url('admin.php')
            ));
            exit;
        }
        
        $recipients_mode = isset($_POST['recipients_mode']) ? sanitize_text_field($_POST['recipients_mode']) : 'custom';
        
        if ($recipients_mode === 'default') {
            $email_settings = $this->email->get_settings();
            $recipients = isset($email_settings['email_recipients']) ? $email_settings['email_recipients'] : '';
        } else {
            $recipients = sanitize_text_field($_POST['schedule_recipients']);
        }
        
        if (empty($recipients)) {
            wp_redirect(add_query_arg(
                array('page' => 'helloasso-email-reports', 'message' => 'schedule_error'),
                admin_url('admin.php')
            ));
            exit;
        }
        
        $event_slugs = array_map('sanitize_text_field', $_POST['event_slugs']);
        $datetime = sanitize_text_field($_POST['new_datetime']);
        
        $email_settings = $this->email->get_settings();
        
        if (!isset($email_settings['schedules']) || !is_array($email_settings['schedules'])) {
            $email_settings['schedules'] = array();
        }
        
        $new_schedule = array(
            'datetime' => $datetime,
            'sent' => false,
            'event_slugs' => $event_slugs,
            'recipients' => $recipients
        );
        
        $email_settings['schedules'][] = $new_schedule;
        
        update_option('helloasso_email_settings', $email_settings, false);
        
        wp_redirect(add_query_arg(
            array('page' => 'helloasso-email-reports', 'message' => 'schedule_added'),
            admin_url('admin.php')
        ));
        exit;
    }
    
    /**
     * Traiter l'ajout d'un envoi automatique
     */
    public function handle_add_auto_schedule() {
        if (!current_user_can('manage_options')) {
            wp_die('Accès non autorisé');
        }
        
        if (!isset($_POST['helloasso_auto_schedule_nonce']) || !wp_verify_nonce($_POST['helloasso_auto_schedule_nonce'], 'helloasso_add_auto_schedule')) {
            wp_die('Nonce invalide');
        }
        
        $recipients_mode = isset($_POST['auto_recipients_mode']) ? sanitize_text_field($_POST['auto_recipients_mode']) : 'custom';
        
        if ($recipients_mode === 'default') {
            $email_settings = $this->email->get_settings();
            $recipients = isset($email_settings['email_recipients']) ? $email_settings['email_recipients'] : '';
        } else {
            $recipients = sanitize_text_field($_POST['auto_recipients']);
        }
        
        if (empty($recipients)) {
            wp_redirect(add_query_arg(
                array('page' => 'helloasso-email-reports', 'message' => 'schedule_error'),
                admin_url('admin.php')
            ));
            exit;
        }
        
        $time = sanitize_text_field($_POST['auto_time']);
        
        $email_settings = $this->email->get_settings();
        
        if (!isset($email_settings['schedules']) || !is_array($email_settings['schedules'])) {
            $email_settings['schedules'] = array();
        }
        
        try {
            $events_data = $this->api->get_events();
            $events = isset($events_data['data']) ? $events_data['data'] : array();
            
            if (empty($events)) {
                wp_redirect(add_query_arg(
                    array('page' => 'helloasso-email-reports', 'message' => 'schedule_error'),
                    admin_url('admin.php')
                ));
                exit;
            }
            
            $events_by_date = array();
            foreach ($events as $event) {
                if (!isset($event['startDate'])) continue;
                
                $event_date = date('Y-m-d', strtotime($event['startDate']));
                
                if (!isset($events_by_date[$event_date])) {
                    $events_by_date[$event_date] = array();
                }
                
                $events_by_date[$event_date][] = $event['formSlug'];
            }
            
            $added_count = 0;
            foreach ($events_by_date as $date => $slugs) {
                $datetime = $date . ' ' . $time . ':00';
                
                $scheduled_time = strtotime($datetime);
                if ($scheduled_time < current_time('timestamp')) {
                    continue;
                }
                
                $new_schedule = array(
                    'datetime' => $datetime,
                    'sent' => false,
                    'event_slugs' => $slugs,
                    'recipients' => $recipients,
                    'is_auto' => true
                );
                
                $email_settings['schedules'][] = $new_schedule;
                $added_count++;
            }
            
            update_option('helloasso_email_settings', $email_settings, false);
            
            wp_redirect(add_query_arg(
                array('page' => 'helloasso-email-reports', 'message' => 'schedule_added'),
                admin_url('admin.php')
            ));
            exit;
            
        } catch (Exception $e) {
            wp_redirect(add_query_arg(
                array('page' => 'helloasso-email-reports', 'message' => 'schedule_error'),
                admin_url('admin.php')
            ));
            exit;
        }
    }
    
    /**
     * Traiter la suppression d'un schedule
     */
    public function handle_delete_schedule() {
        if (!current_user_can('manage_options')) {
            wp_die('Accès non autorisé');
        }
        
        if (!isset($_POST['helloasso_delete_nonce']) || !wp_verify_nonce($_POST['helloasso_delete_nonce'], 'helloasso_delete_schedule')) {
            wp_die('Nonce invalide');
        }
        
        $email_settings = $this->email->get_settings();
        $schedules = isset($email_settings['schedules']) ? $email_settings['schedules'] : array();
        $index = intval($_POST['schedule_index']);
        
        if (isset($schedules[$index])) {
            unset($schedules[$index]);
            $schedules = array_values($schedules);
            $email_settings['schedules'] = $schedules;
            
            update_option('helloasso_email_settings', $email_settings, false);
        }
        
        wp_redirect(add_query_arg(
            array('page' => 'helloasso-email-reports', 'message' => 'schedule_deleted'),
            admin_url('admin.php')
        ));
        exit;
    }
    
    /**
     * Supprimer TOUS les schedules
     */
    public function handle_delete_all_schedules() {
        if (!current_user_can('manage_options')) {
            wp_die('Accès non autorisé');
        }
        
        if (!isset($_POST['helloasso_delete_all_nonce']) || !wp_verify_nonce($_POST['helloasso_delete_all_nonce'], 'helloasso_delete_all_schedules')) {
            wp_die('Nonce invalide');
        }
        
        $email_settings = $this->email->get_settings();
        $original_count = isset($email_settings['schedules']) ? count($email_settings['schedules']) : 0;
        
        $email_settings['schedules'] = array();
        
        update_option('helloasso_email_settings', $email_settings, false);
        
        wp_redirect(add_query_arg(
            array(
                'page' => 'helloasso-email-reports',
                'message' => 'schedules_cleaned',
                'count' => $original_count
            ),
            admin_url('admin.php')
        ));
        exit;
    }
    
    /**
     * Nettoyer les schedules envoyés et expirés
     */
    public function handle_clean_schedules() {
        if (!current_user_can('manage_options')) {
            wp_die('Accès non autorisé');
        }
        
        if (!isset($_POST['helloasso_clean_nonce']) || !wp_verify_nonce($_POST['helloasso_clean_nonce'], 'helloasso_clean_schedules')) {
            wp_die('Nonce invalide');
        }
        
        $email_settings = $this->email->get_settings();
        $schedules = isset($email_settings['schedules']) ? $email_settings['schedules'] : array();
        
        $now = current_time('timestamp');
        $one_day = 86400;
        $original_count = count($schedules);
        
        $schedules = array_filter($schedules, function($schedule) use ($now, $one_day) {
            $sent = isset($schedule['sent']) ? $schedule['sent'] : false;
            $scheduled_time = strtotime($schedule['datetime']);
            $time_diff = $now - $scheduled_time;
            $is_expired = $time_diff > $one_day;
            
            return !$sent && !$is_expired;
        });
        
        $schedules = array_values($schedules);
        $cleaned_count = $original_count - count($schedules);
        
        $email_settings['schedules'] = $schedules;
        update_option('helloasso_email_settings', $email_settings, false);
        
        wp_redirect(add_query_arg(
            array(
                'page' => 'helloasso-email-reports',
                'message' => 'schedules_cleaned',
                'count' => $cleaned_count
            ),
            admin_url('admin.php')
        ));
        exit;
    }
    
    /**
     * AJAX : Rafraîchir la liste des événements
     */
    public function ajax_refresh_events() {
        check_ajax_referer('helloasso_refresh_events', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Accès non autorisé');
        }
        
        delete_transient('helloasso_events_cache');
        
        try {
            $this->api->get_events(true);
            wp_send_json_success('Cache vidé');
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * Ajouter les menus d'administration - RÉORGANISÉS
     */
    public function add_admin_menu() {
        // Menu principal
        add_menu_page(
            'HelloAsso Events Reports',
            'HelloAsso Events Reports',
            'manage_options',
            'helloasso-events',
            array($this, 'overview_page'),
            'dashicons-tickets-alt',
            30
        );
        
        // Sous-menu : Présentation (développée)
        add_submenu_page(
            'helloasso-events',
            'Présentation',
            'Présentation',
            'manage_options',
            'helloasso-events',
            array($this, 'overview_page')
        );
        
        // Sous-menu : Shortcodes (NOUVEAU - séparé)
        add_submenu_page(
            'helloasso-events',
            'Shortcodes',
            'Shortcodes',
            'manage_options',
            'helloasso-shortcodes',
            array($this, 'shortcodes_page')
        );
        
        // Sous-menu : Rapports email
        add_submenu_page(
            'helloasso-events',
            'Rapports par email',
            'Rapports email',
            'manage_options',
            'helloasso-email-reports',
            array($this, 'email_reports_page')
        );
        
        // Sous-menu : Configuration & Tests (FUSIONNÉS)
        add_submenu_page(
            'helloasso-events',
            'Configuration & Tests',
            'Configuration & Tests',
            'manage_options',
            'helloasso-configuration',
            array($this, 'configuration_tests_page')
        );
    }
    
    /**
     * Page de présentation (développée) - Inclut le fichier externe
     */
    public function overview_page() {
        require_once HELLOASSO_PLUGIN_DIR . 'admin/overview-page.php';
    }
    
    /**
     * Page des shortcodes (NOUVELLE)
     */
    public function shortcodes_page() {
        require_once HELLOASSO_PLUGIN_DIR . 'admin/shortcodes-page.php';
    }
    
    /**
     * Page de rapports email
     */
    public function email_reports_page() {
        require_once HELLOASSO_PLUGIN_DIR . 'admin/email-reports-page.php';
    }
    
    /**
     * Page de configuration & tests (FUSIONNÉE)
     */
    public function configuration_tests_page() {
        require_once HELLOASSO_PLUGIN_DIR . 'admin/tests-page.php';
    }
}