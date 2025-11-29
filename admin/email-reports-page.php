<?php
/**
 * Page de configuration des rapports email
 * Version avec option CSV uniquement dans "Envoi à la demande"
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

// Récupérer l'instance du plugin
$plugin = HelloAsso_Events_Reports::get_instance();

// Afficher les messages de retour
if (isset($_GET['message'])) {
    if ($_GET['message'] === 'settings_saved') {
        echo '<div class="notice notice-success"><p>✓ Paramètres sauvegardés avec succès !</p></div>';
    } elseif ($_GET['message'] === 'settings_error') {
        echo '<div class="notice notice-error"><p>✗ Erreur lors de la sauvegarde des paramètres</p></div>';
    } elseif ($_GET['message'] === 'schedule_added') {
        echo '<div class="notice notice-success"><p>✓ Envoi programmé ajouté avec succès !</p></div>';
    } elseif ($_GET['message'] === 'schedule_error') {
        echo '<div class="notice notice-error"><p>✗ Erreur : Veuillez sélectionner au moins un événement et un destinataire</p></div>';
    } elseif ($_GET['message'] === 'schedule_deleted') {
        echo '<div class="notice notice-success"><p>✓ Envoi programmé supprimé avec succès !</p></div>';
    } elseif ($_GET['message'] === 'schedules_cleaned') {
        $count = isset($_GET['count']) ? intval($_GET['count']) : 0;
        echo '<div class="notice notice-success"><p>✓ ' . $count . ' envoi(s) nettoyé(s) avec succès !</p></div>';
    } elseif ($_GET['message'] === 'cache_cleared') {
        echo '<div class="notice notice-success"><p>✓ Cache des événements vidé ! La liste a été rafraîchie.</p></div>';
    }
}

// Récupérer les paramètres actuels
$email_settings = $plugin->email->get_settings();
$schedules = isset($email_settings['schedules']) ? $email_settings['schedules'] : array();

?>

<div class="wrap">
    <h1>📧 Rapports par Email</h1>
    
    <div class="notice notice-info">
        <p><strong>Configuration des rapports automatiques par email</strong></p>
        <p>Programmez l'envoi de rapports avec les statistiques de vos événements HelloAsso.</p>
    </div>

    <!-- SECTION 1 : Configuration générale -->
    <h2>⚙️ Configuration Générale</h2>
    
    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
        <?php wp_nonce_field('helloasso_save_email_settings', 'helloasso_settings_nonce'); ?>
        <input type="hidden" name="action" value="helloasso_save_email_settings">
        
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">Activer les rapports</th>
                    <td>
                        <label>
                            <input type="checkbox" name="enable_email" value="1" <?php checked($email_settings['enable_email'], true); ?>>
                            Envoyer des rapports par email
                        </label>
                        <p class="description">
                            <strong>⚠️ Important :</strong> Si cette option est désactivée, <strong>aucun rapport programmé ne sera envoyé</strong>, même si le CRON s'exécute.<br>
                            Cette option permet d'activer ou désactiver globalement tous les envois de rapports sans supprimer les programmations.
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Destinataires par défaut</th>
                    <td>
                        <input type="text" name="email_recipients" value="<?php echo esc_attr($email_settings['email_recipients']); ?>" class="regular-text">
                        <p class="description">Destinataires par défaut (séparés par des virgules). Vous pouvez définir des destinataires spécifiques pour chaque rapport ci-dessous.</p>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <p class="submit">
            <input type="submit" class="button button-primary" value="💾 Enregistrer les paramètres">
        </p>
    </form>

    <hr>

    <!-- SECTION 2 : Programmer un envoi -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
        <h2 style="margin: 0;">⏰ Programmer un Envoi</h2>
        <button type="button" id="refresh-events" class="button button-secondary">
            🔄 Rafraîchir la liste des événements
        </button>
    </div>
    
    <!-- Onglets pour choisir le type de programmation -->
    <div style="background: #f9f9f9; padding: 20px; border-radius: 5px; margin: 20px 0;">
        
        <div style="border-bottom: 2px solid #ddd; margin-bottom: 20px;">
            <button type="button" class="schedule-tab active" data-tab="auto" style="padding: 10px 20px; margin-right: 10px; background: #2196F3; color: white; border: none; border-radius: 5px 5px 0 0; cursor: pointer; font-size: 14px;">
                🔄 Envoi automatique le jour de l'événement
            </button>
            <button type="button" class="schedule-tab" data-tab="manual" style="padding: 10px 20px; background: #f0f0f0; color: #333; border: none; border-radius: 5px 5px 0 0; cursor: pointer; font-size: 14px;">
                📅 Envoi à la demande
            </button>
        </div>
        
        <!-- ONGLET 1 : Envoi automatique (HTML uniquement) -->
        <div id="tab-auto" class="schedule-tab-content">
            <div class="notice notice-info inline" style="margin-bottom: 20px;">
                <p><strong>📆 Envoi automatique</strong> : Configure l'envoi d'un rapport incluant tous les événements ayant lieu le jour même.</p>
                <p>Le rapport sera envoyé automatiquement chaque jour où au moins un événement est programmé, à l'heure que vous définissez.</p>
                <p><strong>Note :</strong> Les envois automatiques sont toujours au format HTML.</p>
            </div>
            
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" id="auto-schedule-form">
                <?php wp_nonce_field('helloasso_add_auto_schedule', 'helloasso_auto_schedule_nonce'); ?>
                <input type="hidden" name="action" value="helloasso_add_auto_schedule">
                
                <h3>📨 Destinataires</h3>
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row">Destinataires</th>
                            <td>
                                <div style="margin-bottom: 10px;">
                                    <label style="display: block; margin-bottom: 8px;">
                                        <input type="radio" name="auto_recipients_mode" value="default" class="auto-recipients-mode" <?php checked(!empty($email_settings['email_recipients']), true); ?>>
                                        Utiliser les destinataires par défaut
                                        <?php if (!empty($email_settings['email_recipients'])): ?>
                                            <span style="color: #666; font-weight: normal;">(<?php echo esc_html($email_settings['email_recipients']); ?>)</span>
                                        <?php endif; ?>
                                    </label>
                                    <label style="display: block;">
                                        <input type="radio" name="auto_recipients_mode" value="custom" class="auto-recipients-mode" <?php checked(empty($email_settings['email_recipients']), true); ?>>
                                        Spécifier des destinataires différents
                                    </label>
                                </div>
                                
                                <div id="auto-custom-recipients-field" style="<?php echo !empty($email_settings['email_recipients']) ? 'display: none;' : ''; ?>">
                                    <input type="text" name="auto_recipients" id="auto-recipients" class="regular-text" placeholder="email1@exemple.com, email2@exemple.com" value="">
                                    <p class="description">
                                        Ces destinataires recevront le rapport automatique des événements du jour.
                                    </p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <h3>🕐 Heure d'envoi</h3>
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row">Heure d'envoi quotidien</th>
                            <td>
                                <input type="time" name="auto_time" value="08:00" required style="font-size: 14px; padding: 5px;">
                                <p class="description">
                                    Chaque jour où des événements sont programmés, un rapport sera envoyé à cette heure.<br>
                                    <strong>Exemple :</strong> Si vous choisissez 08:00, le rapport sera envoyé à 8h du matin le jour de chaque événement.
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <p class="submit">
                    <input type="submit" class="button button-primary" value="🔄 Activer l'envoi automatique">
                </p>
            </form>
        </div>
        
        <!-- ONGLET 2 : Envoi à la demande (AVEC OPTION CSV) -->
        <div id="tab-manual" class="schedule-tab-content" style="display: none;">
        
        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" id="schedule-form">
            <?php wp_nonce_field('helloasso_add_schedule', 'helloasso_schedule_nonce'); ?>
            <input type="hidden" name="action" value="helloasso_add_schedule">
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3 style="margin: 0;">📅 Sélectionner les Événements</h3>
                <div>
                    <button type="button" id="select-all" class="button button-secondary" style="margin-right: 5px;">
                        ☑️ Tout sélectionner
                    </button>
                    <button type="button" id="select-none" class="button button-secondary">
                        ☐ Tout désélectionner
                    </button>
                </div>
            </div>
            
            <div style="margin-bottom: 15px;">
                <label style="display: inline-block; margin-right: 20px;">
                    <input type="radio" name="show_disabled" value="all" id="show-all">
                    Afficher tous les événements
                </label>
                <label style="display: inline-block;">
                    <input type="radio" name="show_disabled" value="active" id="show-active" checked>
                    Masquer les événements "Disabled"
                </label>
            </div>
            
            <?php
            // Récupérer tous les événements
            try {
                $events_data = $plugin->api->get_events();
                $events = isset($events_data['data']) ? $events_data['data'] : array();
                
                // Trier par date
                if (!empty($events)) {
                    usort($events, function($a, $b) {
                        $date_a = isset($a['startDate']) ? strtotime($a['startDate']) : 0;
                        $date_b = isset($b['startDate']) ? strtotime($b['startDate']) : 0;
                        return $date_a - $date_b;
                    });
                }
                
                if (empty($events)) {
                    echo '<div class="notice notice-warning inline"><p>Aucun événement trouvé. Vérifiez votre connexion API dans "HelloAsso > Tests".</p></div>';
                } else {
                    echo '<div id="events-list" style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 15px; background: #fff; border-radius: 3px;">';
                    
                    foreach ($events as $event) {
                        $event_slug = $event['formSlug'];
                        $event_title = esc_html($event['title']);
                        $event_date = isset($event['startDate']) ? date_i18n('d/m/Y', strtotime($event['startDate'])) : 'Date inconnue';
                        $event_state = isset($event['state']) ? $event['state'] : 'Unknown';
                        $is_disabled = strtolower($event_state) === 'disabled';
                        
                        $state_color = '#999';
                        if ($event_state === 'Public') $state_color = '#46b450';
                        elseif ($event_state === 'Private') $state_color = '#2196F3';
                        elseif ($event_state === 'Draft') $state_color = '#FFC107';
                        
                        echo '<label class="event-item' . ($is_disabled ? ' event-disabled' : '') . '" style="display: block; margin-bottom: 8px; padding: 8px; border-bottom: 1px solid #f0f0f0;" data-state="' . esc_attr(strtolower($event_state)) . '">';
                        echo '<input type="checkbox" name="event_slugs[]" value="' . esc_attr($event_slug) . '" style="margin-right: 8px;" class="event-checkbox">';
                        echo '<strong>' . $event_title . '</strong> ';
                        echo '<span style="color: #666;">(' . $event_date . ')</span> ';
                        echo '<span style="font-size: 0.85em; color: ' . $state_color . ';">[' . $event_state . ']</span>';
                        echo '</label>';
                    }
                    
                    echo '</div>';
                }
                
            } catch (Exception $e) {
                echo '<div class="notice notice-error inline"><p>Erreur lors du chargement des événements : ' . esc_html($e->getMessage()) . '</p></div>';
            }
            ?>
            
            <h3 style="margin-top: 25px;">📨 Destinataires de ce Rapport</h3>
            
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row">Destinataires</th>
                        <td>
                            <div style="margin-bottom: 10px;">
                                <label style="display: block; margin-bottom: 8px;">
                                    <input type="radio" name="recipients_mode" value="default" class="recipients-mode" <?php checked(!empty($email_settings['email_recipients']), true); ?>>
                                    Utiliser les destinataires par défaut
                                    <?php if (!empty($email_settings['email_recipients'])): ?>
                                        <span style="color: #666; font-weight: normal;">(<?php echo esc_html($email_settings['email_recipients']); ?>)</span>
                                    <?php endif; ?>
                                </label>
                                <label style="display: block;">
                                    <input type="radio" name="recipients_mode" value="custom" class="recipients-mode" <?php checked(empty($email_settings['email_recipients']), true); ?>>
                                    Spécifier des destinataires différents
                                </label>
                            </div>
                            
                            <div id="custom-recipients-field" style="<?php echo !empty($email_settings['email_recipients']) ? 'display: none;' : ''; ?>">
                                <input type="text" name="schedule_recipients" id="schedule-recipients" class="regular-text" placeholder="email1@exemple.com, email2@exemple.com" value="">
                                <p class="description">Séparez plusieurs adresses par des virgules.</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <h3 style="margin-top: 25px;">📄 Format du Rapport</h3>
            
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row">Format</th>
                        <td>
                            <label style="display: block; margin-bottom: 10px;">
                                <input type="radio" name="report_format" value="html" checked>
                                <strong>HTML</strong> - Rapport formaté dans le corps de l'email
                            </label>
                            <label style="display: block;">
                                <input type="radio" name="report_format" value="csv">
                                <strong>CSV</strong> - Fichier CSV en pièce jointe (compatible Excel)
                            </label>
                            <p class="description" style="margin-top: 10px;">
                                <strong>HTML :</strong> Email avec mise en page visuelle et couleurs (recommandé pour visualisation rapide)<br>
                                <strong>CSV :</strong> Fichier tableur (.csv) en pièce jointe avec séparateur point-virgule et encodage UTF-8 pour Excel (recommandé pour analyse des données)
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <h3 style="margin-top: 25px;">🕐 Date et Heure d'Envoi</h3>
            
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row">Date et heure (heure locale)</th>
                        <td>
                            <?php
                            // Obtenir l'heure locale actuelle
                            $local_time = current_time('Y-m-d\TH:i');
                            ?>
                            <input type="datetime-local" name="new_datetime" value="<?php echo $local_time; ?>" required style="font-size: 14px; padding: 5px;">
                            <p class="description">
                                Fuseau horaire actuel : <strong><?php echo wp_timezone_string(); ?></strong><br>
                                Heure locale actuelle : <strong><?php echo current_time('d/m/Y H:i:s'); ?></strong>
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <p class="submit">
                <input type="submit" class="button button-primary" value="⏰ Programmer l'envoi" <?php disabled(empty($events)); ?>>
            </p>
        </form>
        
        </div>
        
    </div>

    <hr>

    <!-- SECTION 3 : Envois programmés -->
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h2 style="margin: 0;">📋 Envois Programmés</h2>
        <?php if (!empty($schedules)): ?>
            <div>
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display: inline; margin-right: 10px;">
                    <?php wp_nonce_field('helloasso_delete_all_schedules', 'helloasso_delete_all_nonce'); ?>
                    <input type="hidden" name="action" value="helloasso_delete_all_schedules">
                    <button type="submit" class="button button-secondary" onclick="return confirm('⚠️ ATTENTION : Êtes-vous sûr de vouloir supprimer TOUS les envois programmés (<?php echo count($schedules); ?> envoi(s)) ?\n\nCette action est irréversible !');">
                        🗑️ Supprimer tous les envois
                    </button>
                </form>
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display: inline;">
                    <?php wp_nonce_field('helloasso_clean_schedules', 'helloasso_clean_nonce'); ?>
                    <input type="hidden" name="action" value="helloasso_clean_schedules">
                    <button type="submit" class="button button-secondary" onclick="return confirm('Êtes-vous sûr de vouloir nettoyer tous les envois expirés et envoyés ?');">
                        🧹 Nettoyer les envois terminés
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>
    
    <?php
    // Debug : afficher le contenu brut des schedules
    if (isset($_GET['debug_schedules'])) {
        echo '<div class="notice notice-info"><p><strong>DEBUG - Contenu brut :</strong></p>';
        echo '<pre style="background: #f0f0f0; padding: 10px; overflow: auto; max-height: 400px;">';
        print_r($email_settings);
        echo '</pre></div>';
    }
    ?>
    
    <?php if (empty($schedules)): ?>
        <div style="background: #f9f9f9; padding: 20px; text-align: center; border-radius: 5px; margin-top: 20px;">
            <p style="color: #666; margin: 0;">🔭 Aucun envoi programmé pour le moment</p>
            <p style="color: #999; font-size: 0.9em; margin: 10px 0 0 0;">Utilisez le formulaire ci-dessus pour programmer un envoi</p>
        </div>
    <?php else: ?>
        <div style="overflow-x: auto;">
        <table class="wp-list-table widefat fixed striped" style="margin-top: 20px; min-width: 1000px;">
            <thead>
                <tr>
                    <th style="width: 18%;">Événements</th>
                    <th style="width: 22%;">Destinataires</th>
                    <th style="width: 18%;">Date d'envoi (heure locale)</th>
                    <th style="width: 10%;">Format</th>
                    <th style="width: 10%;">Type</th>
                    <th style="width: 10%;">Statut</th>
                    <th style="width: 12%;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $now = current_time('timestamp');
                $one_day = 86400; // 24 heures en secondes
                
                foreach ($schedules as $index => $schedule):
                    $datetime = $schedule['datetime'];
                    $sent = isset($schedule['sent']) ? $schedule['sent'] : false;
                    $event_slugs = isset($schedule['event_slugs']) ? $schedule['event_slugs'] : array();
                    $recipients = isset($schedule['recipients']) ? $schedule['recipients'] : $email_settings['email_recipients'];
                    $is_auto = isset($schedule['is_auto']) && $schedule['is_auto'];
                    $format = isset($schedule['format']) ? $schedule['format'] : 'html';
                    
                    // Convertir en timestamp local
                    $scheduled_time = strtotime($datetime);
                    $time_diff = $now - $scheduled_time;
                    $is_expired = $time_diff > $one_day;
                    
                    // Déterminer le statut
                    if ($sent) {
                        $status = '<span style="color: #46b450;">✓ Envoyé</span>';
                        $row_class = 'schedule-sent';
                    } elseif ($is_expired) {
                        $status = '<span style="color: #d63638;">⚠️ Expiré</span>';
                        $row_class = 'schedule-expired';
                    } else {
                        $status = '<span style="color: #2271b1;">⏳ En attente</span>';
                        $row_class = 'schedule-pending';
                    }
                    
                    // Formater avec l'heure locale
                    $formatted_date = date_i18n('d/m/Y à H:i', $scheduled_time);
                    
                    // Formater les destinataires pour l'affichage
                    $recipients_display = $recipients;
                    if (strlen($recipients) > 40) {
                        $recipients_array = array_map('trim', explode(',', $recipients));
                        $recipients_display = count($recipients_array) . ' destinataire(s)';
                    }
                    ?>
                    <tr class="<?php echo $row_class; ?>">
                        <td>
                            <strong><?php echo count($event_slugs); ?> événement(s)</strong>
                            <?php if (isset($_GET['show_details'])): ?>
                                <br><small style="color: #666;"><?php echo esc_html(implode(', ', array_slice($event_slugs, 0, 3))); ?><?php echo count($event_slugs) > 3 ? '...' : ''; ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span title="<?php echo esc_attr($recipients); ?>" style="cursor: help;">
                                <?php echo esc_html($recipients_display); ?>
                            </span>
                            <?php if (isset($_GET['show_details']) && strlen($recipients) > 40): ?>
                                <br><small style="color: #666; word-break: break-all;"><?php echo esc_html($recipients); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html($formatted_date); ?></td>
                        <td>
                            <?php if ($format === 'csv'): ?>
                                <span style="color: #2196F3;" title="Fichier CSV en pièce jointe">📊 CSV</span>
                            <?php else: ?>
                                <span style="color: #666;" title="Email HTML formaté">📄 HTML</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($is_auto): ?>
                                <span style="color: #2196F3;" title="Envoi automatique le jour de l'événement">🔄 Auto</span>
                            <?php else: ?>
                                <span style="color: #666;" title="Envoi programmé à la demande">📅 Manuel</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $status; ?></td>
                        <td>
                            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display: inline;">
                                <?php wp_nonce_field('helloasso_delete_schedule', 'helloasso_delete_nonce'); ?>
                                <input type="hidden" name="action" value="helloasso_delete_schedule">
                                <input type="hidden" name="schedule_index" value="<?php echo $index; ?>">
                                <button type="submit" class="button button-small button-link-delete" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet envoi programmé ?');">
                                    🗑️ Supprimer
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        
        <p style="margin-top: 10px; color: #666;">
            <a href="<?php echo add_query_arg('show_details', '1'); ?>">Afficher les détails</a> | 
            <a href="<?php echo add_query_arg('debug_schedules', '1'); ?>">Mode debug</a>
        </p>
    <?php endif; ?>
    
</div>

<script>
jQuery(document).ready(function($) {
    // Gestion des onglets
    $('.schedule-tab').on('click', function() {
        var tab = $(this).data('tab');
        
        // Mettre à jour l'apparence des onglets
        $('.schedule-tab').removeClass('active').css({
            'background': '#f0f0f0',
            'color': '#333'
        });
        $(this).addClass('active').css({
            'background': '#2196F3',
            'color': 'white'
        });
        
        // Afficher/masquer les contenus
        $('.schedule-tab-content').hide();
        $('#tab-' + tab).show();
    });
    
    // Gestion des destinataires pour l'envoi manuel
    $('.recipients-mode').on('change', function() {
        if ($('input[name="recipients_mode"]:checked').val() === 'custom') {
            $('#custom-recipients-field').show();
            $('#schedule-recipients').prop('required', true);
        } else {
            $('#custom-recipients-field').hide();
            $('#schedule-recipients').prop('required', false);
        }
    });
    
    // Gestion des destinataires pour l'envoi automatique
    $('.auto-recipients-mode').on('change', function() {
        if ($('input[name="auto_recipients_mode"]:checked').val() === 'custom') {
            $('#auto-custom-recipients-field').show();
            $('#auto-recipients').prop('required', true);
        } else {
            $('#auto-custom-recipients-field').hide();
            $('#auto-recipients').prop('required', false);
        }
    });
    
    // Bouton rafraîchir les événements
    $('#refresh-events').on('click', function(e) {
        e.preventDefault();
        var btn = $(this);
        var originalText = btn.html();
        
        btn.prop('disabled', true).html('🔄 Rafraîchissement...');
        
        // Vider le cache et recharger
        $.post(ajaxurl, {
            action: 'helloasso_refresh_events',
            nonce: '<?php echo wp_create_nonce('helloasso_refresh_events'); ?>'
        }, function(response) {
            if (response.success) {
                window.location.href = window.location.href.split('?')[0] + '?page=helloasso-email-reports&message=cache_cleared';
            } else {
                alert('Erreur lors du rafraîchissement');
                btn.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // Filtrer les événements selon le statut - déclencher au chargement
    $('input[name="show_disabled"]').on('change', function() {
        var showAll = $('#show-all').is(':checked');
        
        if (showAll) {
            $('.event-item').show();
        } else {
            $('.event-item').each(function() {
                if ($(this).data('state') === 'disabled') {
                    $(this).hide();
                } else {
                    $(this).show();
                }
            });
        }
    });
    
    // Déclencher le filtre au chargement de la page
    $('#show-active').trigger('change');
    
    // Tout sélectionner
    $('#select-all').on('click', function(e) {
        e.preventDefault();
        $('.event-checkbox:visible').prop('checked', true);
    });
    
    // Tout désélectionner
    $('#select-none').on('click', function(e) {
        e.preventDefault();
        $('.event-checkbox').prop('checked', false);
    });
    
    // Validation côté client
    $('#schedule-form').on('submit', function(e) {
        var checkedBoxes = $('.event-checkbox:checked').length;
        var useDefault = $('input[name="recipients_mode"]:checked').val() === 'default';
        var recipients = $('#schedule-recipients').val().trim();
        
        if (checkedBoxes === 0) {
            e.preventDefault();
            alert('⚠️ Veuillez sélectionner au moins un événement avant de programmer l\'envoi.');
            return false;
        }
        
        if (!useDefault && recipients === '') {
            e.preventDefault();
            alert('⚠️ Veuillez saisir au moins un destinataire ou choisir les destinataires par défaut.');
            return false;
        }
        
        // Si on utilise les destinataires par défaut, on vide le champ personnalisé
        if (useDefault) {
            $('#schedule-recipients').val('');
        }
        
        // Validation basique des emails si mode custom
        if (!useDefault && recipients !== '') {
            var emails = recipients.split(',').map(function(email) {
                return email.trim();
            });
            
            var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            var invalidEmails = emails.filter(function(email) {
                return !emailRegex.test(email);
            });
            
            if (invalidEmails.length > 0) {
                e.preventDefault();
                alert('⚠️ Adresse(s) email invalide(s) : ' + invalidEmails.join(', '));
                return false;
            }
        }
        
        return true;
    });
    
    // Validation pour l'envoi automatique
    $('#auto-schedule-form').on('submit', function(e) {
        var useDefault = $('input[name="auto_recipients_mode"]:checked').val() === 'default';
        var recipients = $('#auto-recipients').val().trim();
        
        if (!useDefault && recipients === '') {
            e.preventDefault();
            alert('⚠️ Veuillez saisir au moins un destinataire ou choisir les destinataires par défaut.');
            return false;
        }
        
        // Si on utilise les destinataires par défaut, on vide le champ personnalisé
        if (useDefault) {
            $('#auto-recipients').val('');
        }
        
        // Validation basique des emails si mode custom
        if (!useDefault && recipients !== '') {
            var emails = recipients.split(',').map(function(email) {
                return email.trim();
            });
            
            var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            var invalidEmails = emails.filter(function(email) {
                return !emailRegex.test(email);
            });
            
            if (invalidEmails.length > 0) {
                e.preventDefault();
                alert('⚠️ Adresse(s) email invalide(s) : ' + invalidEmails.join(', '));
                return false;
            }
        }
        
        return true;
    });
});
</script>

<style>
.form-table th {
    padding: 20px 10px 20px 0;
    vertical-align: middle;
}

.form-table td {
    padding: 15px 10px;
}

.wp-list-table td {
    vertical-align: middle;
}

.schedule-sent {
    background-color: #f0fdf4 !important;
}

.schedule-expired {
    background-color: #fef2f2 !important;
}

.schedule-pending {
    background-color: #eff6ff !important;
}

.event-item {
    transition: background-color 0.2s;
}

.event-item:hover {
    background-color: #f0f0f0 !important;
}
</style>