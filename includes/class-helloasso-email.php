<?php
/**
 * Classe de gestion des emails
 * Version avec option HTML ou CSV
 */

if (!defined('ABSPATH')) {
    exit;
}

class HelloAsso_Email {
    
    private $api;
    private $option_name = 'helloasso_email_settings';
    
    public function __construct($api) {
        $this->api = $api;
        
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
                'email_format' => 'html', // 'html' ou 'csv'
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
            
            $time_diff = $now - $scheduled_time;
            
            error_log("HelloAsso CRON: Schedule $index - Prévu: " . date('Y-m-d H:i:s', $scheduled_time) . " | Envoyé: " . ($is_sent ? 'OUI' : 'NON') . " | Diff: {$time_diff}s");
            
            if (!$is_sent && $scheduled_time <= $now && $time_diff < $one_day) {
                error_log("HelloAsso CRON: Envoi du schedule $index à $recipients");
                
                $result = $this->send_report_for_events($event_slugs, $recipients, false);
                
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
     * Envoyer le rapport pour des événements spécifiques avec destinataires personnalisés
     */
    public function send_report_for_events($event_slugs, $recipients, $is_test = false) {
        try {
            if (empty($recipients)) {
                throw new Exception('Aucun destinataire configuré');
            }
            
            if (empty($event_slugs)) {
                throw new Exception('Aucun événement sélectionné');
            }
            
            $events_data = $this->api->get_events();
            
            if (!$events_data || !isset($events_data['data']) || empty($events_data['data'])) {
                if ($is_test) {
                    throw new Exception('Aucun événement trouvé dans HelloAsso');
                }
                return false;
            }
            
            // Filtrer uniquement les événements sélectionnés
            $selected_events = array();
            foreach ($events_data['data'] as $event) {
                if (in_array($event['formSlug'], $event_slugs)) {
                    $selected_events[] = $event;
                }
            }
            
            if (empty($selected_events)) {
                if ($is_test) {
                    throw new Exception('Aucun des événements sélectionnés n\'a été trouvé');
                }
                return false;
            }
            
            // Trier par date
            usort($selected_events, function($a, $b) {
                $date_a = isset($a['startDate']) ? strtotime($a['startDate']) : 0;
                $date_b = isset($b['startDate']) ? strtotime($b['startDate']) : 0;
                return $date_a - $date_b;
            });
            
            $settings = $this->get_settings();
            $email_format = isset($settings['email_format']) ? $settings['email_format'] : 'html';
            
            // Construire l'email selon le format choisi
            $subject = $is_test ? '[TEST] ' : '';
            $subject .= 'Rapport HelloAsso - ' . count($selected_events) . ' événement(s) - ' . date_i18n('d/m/Y');
            
            $recipients_array = array_map('trim', explode(',', $recipients));
            
            if ($email_format === 'csv') {
                // Format CSV avec pièce jointe
                $csv_content = $this->generate_csv($selected_events);
                $csv_filename = 'rapport-helloasso-' . date('Y-m-d-His') . '.csv';
                
                // Message simple pour le corps de l'email
                $message = "Bonjour,\n\n";
                $message .= "Veuillez trouver ci-joint le rapport HelloAsso pour " . count($selected_events) . " événement(s).\n\n";
                $message .= "Date du rapport : " . date_i18n('d/m/Y à H:i') . "\n\n";
                $message .= "Cordialement,\n";
                $message .= get_bloginfo('name');
                
                $headers = array(
                    'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
                );
                
                // Sauvegarder temporairement le CSV
                $upload_dir = wp_upload_dir();
                $temp_file = $upload_dir['path'] . '/' . $csv_filename;
                file_put_contents($temp_file, $csv_content);
                
                $attachments = array($temp_file);
                
                $result = wp_mail($recipients_array, $subject, $message, $headers, $attachments);
                
                // Supprimer le fichier temporaire
                if (file_exists($temp_file)) {
                    unlink($temp_file);
                }
                
            } else {
                // Format HTML (par défaut)
                $message = $this->build_email_html($selected_events);
                
                $headers = array(
                    'Content-Type: text/html; charset=UTF-8',
                    'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
                );
                
                $result = wp_mail($recipients_array, $subject, $message, $headers);
            }
            
            if ($is_test) {
                error_log('HelloAsso Test Email - Recipients: ' . implode(', ', $recipients_array));
                error_log('HelloAsso Test Email - Format: ' . $email_format);
                error_log('HelloAsso Test Email - Events count: ' . count($selected_events));
            }
            
            if (!$result && $is_test) {
                global $phpmailer;
                if (isset($phpmailer) && $phpmailer->ErrorInfo) {
                    throw new Exception('Erreur PHPMailer: ' . $phpmailer->ErrorInfo);
                } else {
                    throw new Exception('wp_mail() a retourné false. Vérifiez la configuration email du serveur.');
                }
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
     * Envoyer le rapport par email (pour les tests)
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
            
            $email_format = isset($settings['email_format']) ? $settings['email_format'] : 'html';
            
            $subject = $is_test ? '[TEST] ' : '';
            $subject .= 'Rapport HelloAsso - ' . date_i18n('d/m/Y');
            
            $recipients = array_map('trim', explode(',', $settings['email_recipients']));
            
            if ($email_format === 'csv') {
                // Format CSV
                $csv_content = $this->generate_csv($events);
                $csv_filename = 'rapport-helloasso-' . date('Y-m-d-His') . '.csv';
                
                $message = "Bonjour,\n\n";
                $message .= "Veuillez trouver ci-joint le rapport HelloAsso.\n\n";
                $message .= "Date du rapport : " . date_i18n('d/m/Y à H:i') . "\n\n";
                $message .= "Cordialement,\n";
                $message .= get_bloginfo('name');
                
                $headers = array(
                    'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
                );
                
                $upload_dir = wp_upload_dir();
                $temp_file = $upload_dir['path'] . '/' . $csv_filename;
                file_put_contents($temp_file, $csv_content);
                
                $attachments = array($temp_file);
                
                $result = wp_mail($recipients, $subject, $message, $headers, $attachments);
                
                if (file_exists($temp_file)) {
                    unlink($temp_file);
                }
                
            } else {
                // Format HTML
                $message = $this->build_email_html($events);
                
                $headers = array(
                    'Content-Type: text/html; charset=UTF-8',
                    'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
                );
                
                $result = wp_mail($recipients, $subject, $message, $headers);
            }
            
            if ($is_test) {
                error_log('HelloAsso Test Email - Recipients: ' . implode(', ', $recipients));
                error_log('HelloAsso Test Email - Format: ' . $email_format);
                error_log('HelloAsso Test Email - Events count: ' . count($events));
            }
            
            if (!$result && $is_test) {
                global $phpmailer;
                if (isset($phpmailer) && $phpmailer->ErrorInfo) {
                    throw new Exception('Erreur PHPMailer: ' . $phpmailer->ErrorInfo);
                } else {
                    throw new Exception('wp_mail() a retourné false. Vérifiez la configuration email du serveur.');
                }
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
     * Générer le contenu CSV
     */
 private function generate_csv($events) {
    $csv_data = array();

    // En-têtes
    $csv_data[] = array(
        'Événement',
        'Date',
        'Heure',
        'État',
        'Catégorie',
        'Places vendues',
        'URL'
    );

    // Données
    foreach ($events as $event) {
        $sold_data = $this->api->get_event_sold_count($event['formSlug']);

        $title = $event['title'];
        $date = 'Non définie';
        $time = 'Non définie';
        if (!empty($event['startDate'])) {
            $dateTime = new DateTime($event['startDate']);
            $date = $dateTime->format('d/m/Y');
            $time = $dateTime->format('H:i');
        }
        $state = $event['state'] ?? 'N/A';
        $url = $event['url'] ?? '';

        // Si aucune catégorie, on ajoute une ligne avec "N/A"
        if (empty($sold_data['tiers'])) {
            $csv_data[] = array(
                $title,
                $date,
                $time,
                $state,
                'N/A',
                '0',
                $url
            );
            continue;
        }

        // Une ligne par catégorie
        foreach ($sold_data['tiers'] as $tier_name => $tier_count) {
            $csv_data[] = array(
                $title,
                $date,
                $time,
                $state,
                $tier_name,
                $tier_count,
                $url
            );
        }
    }

    // Convertir en CSV
    $output = fopen('php://temp', 'r+');

    // Ajouter le BOM UTF-8 pour Excel
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    foreach ($csv_data as $row) {
        fputcsv($output, $row, ';');
    }

    rewind($output);
    $csv_content = stream_get_contents($output);
    fclose($output);

    return $csv_content;
}

    /**
     * Construire le HTML de l'email
     */
    private function build_email_html($events) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #2196F3; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                .event { background: #f9f9f9; margin: 15px 0; padding: 20px; border-radius: 5px; border-left: 4px solid #2196F3; }
                .event-title { font-size: 1.3em; margin: 0 0 10px 0; color: #2196F3; }
                .event-date { color: #666; margin: 5px 0; }
                .tickets { background: white; padding: 15px; margin: 10px 0; border-radius: 4px; }
                .tickets-total { font-size: 1.2em; font-weight: bold; color: #2196F3; }
                .tier-detail { margin: 10px 0; padding: 8px; background: #e3f2fd; border-radius: 3px; }
                .footer { text-align: center; padding: 20px; color: #999; font-size: 0.9em; }
                .button { display: inline-block; padding: 10px 20px; background: #2196F3; color: white; text-decoration: none; border-radius: 5px; margin-top: 10px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>📊 Rapport HelloAsso</h1>
                    <p><?php echo count($events); ?> événement(s)</p>
                </div>
                
                <?php
                if (empty($events)) {
                    echo '<p style="padding: 20px; text-align: center;">Aucun événement à afficher</p>';
                } else {
                    foreach ($events as $event):
                        $sold_data = $this->api->get_event_sold_count($event['formSlug']);
                        $title = esc_html($event['title']);
                        $date = !empty($event['startDate']) ? date_i18n('d/m/Y à H:i', strtotime($event['startDate'])) : 'Date non définie';
                        $url = esc_url($event['url'] ?? '#');
                        ?>
                        <div class="event">
                            <h2 class="event-title"><?php echo $title; ?></h2>
                            <p class="event-date">📅 <strong>Date :</strong> <?php echo $date; ?></p>
                            
                            <?php if ($sold_data['sold'] > 0): ?>
                                <div class="tickets">
                                    <p class="tickets-total">🎟️ Total places vendues : <?php echo $sold_data['sold']; ?></p>
                                    
                                    <?php if (!empty($sold_data['tiers'])): ?>
                                        <p><strong>Détail par catégorie :</strong></p>
                                        <?php foreach ($sold_data['tiers'] as $tier_name => $tier_count): ?>
                                            <div class="tier-detail">
                                                <?php echo esc_html($tier_name); ?>: <strong><?php echo $tier_count; ?></strong>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <p style="color: #999; font-style: italic;">Aucune inscription pour le moment</p>
                            <?php endif; ?>
                            
                            <a href="<?php echo $url; ?>" class="button">Voir l'événement sur HelloAsso</a>
                        </div>
                        <?php
                    endforeach;
                }
                ?>
                
                <div class="footer">
                    <p>Ce rapport est envoyé automatiquement depuis <?php echo get_bloginfo('name'); ?></p>
                    <p><small>Pour modifier les paramètres, connectez-vous à l'administration WordPress</small></p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
}