<?php
/**
 * Classe principale de gestion des emails
 * Version refactorisée avec générateurs séparés
 */

if (!defined('ABSPATH')) {
    exit;
}

class HelloAsso_Email {
    
    private $api;
    private $csv_generator;
    private $html_generator;
    private $option_name = 'helloasso_email_settings';
    
    public function __construct($api) {
        $this->api = $api;
        
        // Charger les générateurs
        require_once HELLOASSO_PLUGIN_DIR . 'includes/class-helloasso-csv-generator.php';
        require_once HELLOASSO_PLUGIN_DIR . 'includes/class-helloasso-html-generator.php';
        
        $this->csv_generator = new HelloAsso_CSV_Generator($api);
        $this->html_generator = new HelloAsso_HTML_Generator($api);
        
        // AJAX endpoint pour le cron
        add_action('wp_ajax_nopriv_helloasso_cron', array($this, 'handle_cron_request'));
        add_action('wp_ajax_helloasso_cron', array($this, 'handle_cron_request'));
    }
    
    /**
     * Initialiser les options
     */
    public function init_options() {
        if (!get_option($this->option_name)) {
            add_option($this->option_name, array(
                'enable_email' => false,
                'email_recipients' => get_option('admin_email'),
                'schedules' => array()
            ));
        }
    }
    
    /**
     * Récupérer les paramètres
     */
    public function get_settings() {
        return get_option($this->option_name);
    }
    
    /**
     * Mettre à jour les paramètres
     */
    public function update_settings($settings) {
        return update_option($this->option_name, $settings);
    }
    
    /**
     * Handler pour les requêtes cron externes
     */
    public function handle_cron_request() {
        error_log('HelloAsso CRON: Vérification à ' . current_time('Y-m-d H:i:s'));
        
        $settings = $this->get_settings();
        
        if (empty($settings['enable_email'])) {
            error_log('HelloAsso CRON: Rapports désactivés');
            wp_die('Rapports désactivés', 200);
        }
        
        $schedules = $settings['schedules'] ?? array();
        if (empty($schedules)) {
            error_log('HelloAsso CRON: Aucun envoi programmé');
            wp_die('Aucun envoi programmé', 200);
        }
        
        $now = current_time('timestamp');
        $updated = false;
        $sent_count = 0;
        $one_day = 86400;
        
        foreach ($schedules as $index => $schedule) {
            $scheduled_time = strtotime($schedule['datetime']);
            $is_sent = $schedule['sent'] ?? false;
            $event_slugs = $schedule['event_slugs'] ?? array();
            $recipients = $schedule['recipients'] ?? $settings['email_recipients'];
            $format = $schedule['format'] ?? 'html';
            
            $time_diff = $now - $scheduled_time;
            
            error_log("HelloAsso CRON: Schedule $index - Prévu: " . date('Y-m-d H:i:s', $scheduled_time) . " | Format: $format | Envoyé: " . ($is_sent ? 'OUI' : 'NON'));
            
            if (!$is_sent && $scheduled_time <= $now && $time_diff < $one_day) {
                error_log("HelloAsso CRON: Envoi du schedule $index à $recipients (format: $format)");
                
                $result = $this->send_report_for_events($event_slugs, $recipients, false, $format);
                
                if ($result) {
                    $schedules[$index]['sent'] = true;
                    $updated = true;
                    $sent_count++;
                    error_log("HelloAsso CRON: Email envoyé avec succès pour schedule $index");
                } else {
                    error_log("HelloAsso CRON: Échec de l'envoi pour schedule $index");
                }
            } elseif ($time_diff >= $one_day && !$is_sent) {
                error_log("HelloAsso CRON: Schedule $index expiré (> 24h)");
            }
        }
        
        if ($updated) {
            $settings['schedules'] = $schedules;
            $this->update_settings($settings);
            error_log("HelloAsso CRON: $sent_count email(s) envoyé(s)");
            wp_die("$sent_count email(s) envoyé(s)", 200);
        }
        
        error_log('HelloAsso CRON: Aucun envoi à effectuer');
        wp_die('Aucun envoi nécessaire', 200);
    }
    
    /**
     * Envoyer le rapport pour des événements spécifiques
     * 
     * @param array $event_slugs Slugs des événements
     * @param string $recipients Destinataires (séparés par virgules)
     * @param bool $is_test Mode test
     * @param string $format Format (html ou csv)
     * @return bool Résultat de l'envoi
     */
    public function send_report_for_events($event_slugs, $recipients, $is_test = false, $format = 'html') {
        try {
            // Validation
            if (empty($recipients)) {
                throw new Exception('Aucun destinataire configuré');
            }
            
            if (empty($event_slugs)) {
                throw new Exception('Aucun événement sélectionné');
            }
            
            // Récupérer et filtrer les événements
            $selected_events = $this->get_filtered_events($event_slugs, $is_test);
            
            if (empty($selected_events)) {
                if ($is_test) {
                    throw new Exception('Aucun des événements sélectionnés n\'a été trouvé');
                }
                return false;
            }
            
            // Préparer les destinataires
            $recipients_array = array_map('trim', explode(',', $recipients));
            
            // Construire le sujet
            $subject = $is_test ? '[TEST] ' : '';
            $subject .= 'Rapport HelloAsso - ' . count($selected_events) . ' événement(s) - ' . date_i18n('d/m/Y');
            
            // Envoyer selon le format
            if ($format === 'csv') {
                $result = $this->send_csv_email($selected_events, $recipients_array, $subject, $is_test);
            } else {
                $result = $this->send_html_email($selected_events, $recipients_array, $subject, $is_test);
            }
            
            if ($is_test) {
                error_log('HelloAsso: Envoi terminé - Format: ' . $format . ' - Résultat: ' . ($result ? 'SUCCESS' : 'FAILED'));
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log('HelloAsso Email Error: ' . $e->getMessage());
            if ($is_test) {
                throw $e;
            }
            return false;
        }
    }
    
    /**
     * Envoyer le rapport par email (pour les tests - toujours HTML)
     */
    public function send_report($is_test = false) {
        try {
            $settings = $this->get_settings();
            
            if (empty($settings['email_recipients'])) {
                throw new Exception('Aucun destinataire configuré');
            }
            
            $events_data = $this->api->get_events();
            
            if (!$events_data || !isset($events_data['data']) || empty($events_data['data'])) {
                throw new Exception('Aucun événement trouvé');
            }
            
            // Trier par date
            $events = $events_data['data'];
            usort($events, function($a, $b) {
                $date_a = isset($a['startDate']) ? strtotime($a['startDate']) : 0;
                $date_b = isset($b['startDate']) ? strtotime($b['startDate']) : 0;
                return $date_a - $date_b;
            });
            
            $recipients = array_map('trim', explode(',', $settings['email_recipients']));
            $subject = $is_test ? '[TEST] ' : '';
            $subject .= 'Rapport HelloAsso - ' . date_i18n('d/m/Y');
            
            $result = $this->send_html_email($events, $recipients, $subject, $is_test);
            
            if ($is_test) {
                error_log('HelloAsso Test: Résultat = ' . ($result ? 'SUCCESS' : 'FAILED'));
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log('HelloAsso Email Error: ' . $e->getMessage());
            if ($is_test) {
                throw $e;
            }
            return false;
        }
    }
    
    /**
     * Récupérer et filtrer les événements
     */
    private function get_filtered_events($event_slugs, $is_test = false) {
        $events_data = $this->api->get_events();
        
        if (!$events_data || !isset($events_data['data']) || empty($events_data['data'])) {
            if ($is_test) {
                throw new Exception('Aucun événement trouvé dans HelloAsso');
            }
            return array();
        }
        
        // Filtrer uniquement les événements sélectionnés
        $selected_events = array();
        foreach ($events_data['data'] as $event) {
            if (in_array($event['formSlug'], $event_slugs)) {
                $selected_events[] = $event;
            }
        }
        
        // Trier par date
        usort($selected_events, function($a, $b) {
            $date_a = isset($a['startDate']) ? strtotime($a['startDate']) : 0;
            $date_b = isset($b['startDate']) ? strtotime($b['startDate']) : 0;
            return $date_a - $date_b;
        });
        
        return $selected_events;
    }
    
    /**
     * Envoyer un email HTML
     */
    private function send_html_email($events, $recipients_array, $subject, $is_test = false) {
        $message = $this->html_generator->generate($events);
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );
        
        $result = wp_mail($recipients_array, $subject, $message, $headers);
        
        if (!$result && $is_test) {
            global $phpmailer;
            if (isset($phpmailer) && $phpmailer->ErrorInfo) {
                throw new Exception('Erreur PHPMailer: ' . $phpmailer->ErrorInfo);
            }
        }
        
        return $result;
    }
    
    /**
     * Envoyer un email CSV
     */
    private function send_csv_email($events, $recipients_array, $subject, $is_test = false) {
        if ($is_test) {
            error_log('HelloAsso CSV: === DÉBUT GÉNÉRATION CSV ===');
        }
        
        // Générer le contenu CSV
        $csv_content = $this->csv_generator->generate($events);
        
        if ($is_test) {
            error_log('HelloAsso CSV: Contenu généré, taille: ' . strlen($csv_content) . ' caractères');
        }
        
        // Créer le fichier temporaire
        $temp_file = $this->csv_generator->create_temp_file($csv_content, $is_test);
        
        // Message du corps de l'email
        $message = "Bonjour,\n\n";
        $message .= "Veuillez trouver ci-joint le rapport HelloAsso pour " . count($events) . " événement(s).\n\n";
        $message .= "Date du rapport : " . date_i18n('d/m/Y à H:i') . "\n\n";
        $message .= "Cordialement,\n";
        $message .= get_bloginfo('name');
        
        $headers = array(
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );
        
        $attachments = array($temp_file);
        
        if ($is_test) {
            error_log('HelloAsso CSV: Envoi de l\'email avec pièce jointe...');
        }
        
        // Envoyer l'email
        $result = wp_mail($recipients_array, $subject, $message, $headers, $attachments);
        
        if ($is_test) {
            error_log('HelloAsso CSV: Résultat wp_mail: ' . ($result ? 'SUCCESS' : 'FAILED'));
            if (!$result) {
                global $phpmailer;
                if (isset($phpmailer) && $phpmailer->ErrorInfo) {
                    error_log('HelloAsso CSV: PHPMailer ErrorInfo: ' . $phpmailer->ErrorInfo);
                }
            }
        }
        
        // Supprimer le fichier temporaire
        $this->csv_generator->delete_temp_file($temp_file, $is_test);
        
        if ($is_test) {
            error_log('HelloAsso CSV: === FIN GÉNÉRATION CSV ===');
        }
        
        if (!$result && $is_test) {
            throw new Exception('Échec de l\'envoi de l\'email CSV');
        }
        
        return $result;
    }
}