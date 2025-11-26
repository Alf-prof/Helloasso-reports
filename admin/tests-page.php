<?php
/**
 * Page de Configuration & Tests (fusionnée)
 */

if (!defined('ABSPATH')) {
    exit;
}

$plugin = HelloAsso_Events_Reports::get_instance();

// Traiter le test de connexion
if (isset($_POST['test_connection']) && check_admin_referer('helloasso_test_connection', 'helloasso_nonce')) {
    delete_transient('helloasso_access_token');
    delete_transient('helloasso_events_cache');
    
    echo '<div style="background: #fff; border: 1px solid #ddd; padding: 20px; margin: 20px 0;">';
    echo '<h3>Résultat du test de connexion</h3>';
    
    try {
        echo '<p>🔄 Tentative de connexion à l\'API HelloAsso...</p>';
        
        $token = $plugin->api->get_access_token(true);
        
        if ($token) {
            echo '<div class="notice notice-success inline"><p><strong>✓ Connexion réussie !</strong></p></div>';
            echo '<p>Token obtenu : ' . esc_html(substr($token, 0, 20)) . '...</p>';
            
            echo '<hr><p>🔄 Récupération des événements...</p>';
            
            $events = $plugin->api->get_events(true);
            
            if ($events && isset($events['data'])) {
                echo '<div class="notice notice-success inline"><p><strong>✓ ' . count($events['data']) . ' événement(s) trouvé(s)</strong></p></div>';
                
                if (count($events['data']) > 0) {
                    echo '<h4>Premiers événements :</h4><ul>';
                    foreach (array_slice($events['data'], 0, 3) as $event) {
                        echo '<li>' . esc_html($event['title']) . ' (Slug: ' . esc_html($event['formSlug']) . ')</li>';
                    }
                    echo '</ul>';
                }
            }
        }
    } catch (Exception $e) {
        echo '<div class="notice notice-error inline"><p><strong>✗ Erreur : ' . esc_html($e->getMessage()) . '</strong></p></div>';
    }
    
    echo '</div>';
}

// Traiter le test d'email
if (isset($_POST['send_test_email']) && check_admin_referer('helloasso_test_email', 'helloasso_email_nonce')) {
    try {
        $result = $plugin->email->send_report(true);
        if ($result) {
            echo '<div class="notice notice-success"><p>✓ Email de test envoyé avec succès !</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>✗ Erreur lors de l\'envoi. Vérifiez que wp_mail() fonctionne sur votre serveur.</p></div>';
        }
    } catch (Exception $e) {
        echo '<div class="notice notice-error"><p>✗ Erreur : ' . esc_html($e->getMessage()) . '</p></div>';
    }
}

// Traiter le vidage de cache
if (isset($_POST['clear_cache']) && check_admin_referer('helloasso_clear_cache', 'helloasso_cache_nonce')) {
    delete_transient('helloasso_access_token');
    delete_transient('helloasso_events_cache');
    
    global $wpdb;
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_helloasso_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_helloasso_%'");
    
    echo '<div class="notice notice-success"><p>✓ Tous les caches ont été vidés !</p></div>';
}

?>

<div class="wrap">
    <h1>⚙️ Configuration & Tests</h1>
    
    <div class="notice notice-info">
        <p><strong>Configuration technique, tests de connexion et outils de diagnostic</strong></p>
    </div>

    <!-- Onglets -->
    <h2 class="nav-tab-wrapper">
        <a href="#tab-config" class="nav-tab nav-tab-active" data-tab="config">🔐 Configuration API</a>
        <a href="#tab-tests" class="nav-tab" data-tab="tests">🧪 Tests</a>
        <a href="#tab-cron" class="nav-tab" data-tab="cron">🔔 Configuration CRON</a>
        <a href="#tab-troubleshooting" class="nav-tab" data-tab="troubleshooting">🔧 Dépannage</a>
        <a href="#tab-system" class="nav-tab" data-tab="system">💻 Informations Système</a>
    </h2>

    <!-- TAB 1 : Configuration API -->
    <div id="tab-config" class="tab-content">
        <h2>🔐 Configuration de l'API HelloAsso</h2>
        
        <?php
        $credentials = $plugin->api->get_credentials();
        ?>
        
        <div style="background: #fff3cd; border: 1px solid #ffc107; padding: 20px; border-radius: 5px; margin: 20px 0;">
            <h3 style="margin-top: 0;">📋 Comment configurer ?</h3>
            <p>Les identifiants API HelloAsso doivent être ajoutés dans le fichier <code>wp-config.php</code> de votre installation WordPress.</p>
            <p><strong>Emplacement :</strong> À la racine de votre site, généralement <code>/public_html/wp-config.php</code></p>
        </div>

        <div style="background: #f9f9f9; padding: 20px; border-radius: 5px; margin: 20px 0;">
            <h3>1️⃣ Obtenir vos identifiants API</h3>
            <ol style="line-height: 2;">
                <li>Connectez-vous à votre compte HelloAsso</li>
                <li>Allez dans <strong>Paramètres > API & Intégrations</strong></li>
                <li>Créez une nouvelle application si nécessaire</li>
                <li>Notez votre <strong>Client ID</strong> et <strong>Client Secret</strong></li>
                <li>Notez également votre <strong>Organization Slug</strong> (nom de votre organisation dans l'URL)</li>
            </ol>
            
            <p style="background: #e3f2fd; padding: 15px; border-radius: 5px; margin-top: 20px;">
                💡 <strong>Trouver votre Organization Slug :</strong><br>
                Si l'URL de votre organisation est <code>https://www.helloasso.com/associations/mon-asso</code>,<br>
                alors votre slug est : <code>mon-asso</code>
            </p>
        </div>

        <div style="background: #f9f9f9; padding: 20px; border-radius: 5px; margin: 20px 0;">
            <h3>2️⃣ Ajouter les identifiants dans wp-config.php</h3>
            <p>Éditez le fichier <code>wp-config.php</code> et ajoutez ces lignes <strong>AVANT</strong> la ligne <code>/* C'est tout, ne touchez pas à ce qui suit ! */</code> :</p>
            
            <pre style="background: #2c3338; color: #fff; padding: 20px; border-radius: 5px; overflow-x: auto; user-select: all;">// Configuration HelloAsso API
define('HELLOASSO_CLIENT_ID', 'votre_client_id_ici');
define('HELLOASSO_CLIENT_SECRET', 'votre_client_secret_ici');
define('HELLOASSO_ORGANIZATION_SLUG', 'votre_organization_slug_ici');</pre>
            
            <p style="background: #fff3cd; padding: 15px; border-radius: 5px; margin-top: 20px;">
                ⚠️ <strong>Important :</strong> Remplacez les valeurs entre guillemets par vos véritables identifiants
            </p>
        </div>

        <h3>✅ État de la Configuration</h3>
        <table class="widefat" style="max-width: 600px; margin: 20px 0;">
            <thead>
                <tr>
                    <th style="padding: 12px; width: 50%;">Paramètre</th>
                    <th style="padding: 12px; width: 50%;">Statut</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="padding: 12px;"><strong>HELLOASSO_CLIENT_ID</strong></td>
                    <td style="padding: 12px;">
                        <?php if (!empty($credentials['client_id'])): ?>
                            <span style="color: #28a745;">✓ Configuré</span>
                            <br><small style="color: #666;"><?php echo esc_html(substr($credentials['client_id'], 0, 15)); ?>...</small>
                        <?php else: ?>
                            <span style="color: #dc3545;">✗ Non configuré</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr style="background: #f9f9f9;">
                    <td style="padding: 12px;"><strong>HELLOASSO_CLIENT_SECRET</strong></td>
                    <td style="padding: 12px;">
                        <?php if (!empty($credentials['client_secret'])): ?>
                            <span style="color: #28a745;">✓ Configuré</span>
                        <?php else: ?>
                            <span style="color: #dc3545;">✗ Non configuré</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 12px;"><strong>HELLOASSO_ORGANIZATION_SLUG</strong></td>
                    <td style="padding: 12px;">
                        <?php if (!empty($credentials['organization_slug'])): ?>
                            <span style="color: #28a745;">✓ Configuré</span>
                            <br><small style="color: #666;"><?php echo esc_html($credentials['organization_slug']); ?></small>
                        <?php else: ?>
                            <span style="color: #dc3545;">✗ Non configuré</span>
                        <?php endif; ?>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <?php if (!empty($credentials['client_id']) && !empty($credentials['client_secret']) && !empty($credentials['organization_slug'])): ?>
            <div style="background: #d4edda; border: 1px solid #28a745; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;">
                <strong>✓ Configuration complète !</strong> Vous pouvez maintenant tester la connexion dans l'onglet "Tests".
            </div>
        <?php else: ?>
            <div style="background: #f8d7da; border: 1px solid #dc3545; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;">
                <strong>⚠️ Configuration incomplète</strong> Veuillez ajouter tous les identifiants dans wp-config.php
            </div>
        <?php endif; ?>
    </div>

    <!-- TAB 2 : Tests -->
    <div id="tab-tests" class="tab-content" style="display: none;">
        <h2>🧪 Tests de Connexion</h2>
        
        <div style="background: #f9f9f9; padding: 20px; border-radius: 5px; margin: 20px 0;">
            <h3>Test de connexion à l'API</h3>
            <p>Ce test vérifie que :</p>
            <ul>
                <li>Les identifiants dans wp-config.php sont corrects</li>
                <li>La connexion à l'API HelloAsso fonctionne</li>
                <li>Votre organisation a des événements</li>
            </ul>
            
            <form method="post" action="">
                <?php wp_nonce_field('helloasso_test_connection', 'helloasso_nonce'); ?>
                <input type="hidden" name="test_connection" value="1">
                <?php submit_button('🔍 Tester la connexion à l\'API', 'primary', 'submit', false); ?>
            </form>
        </div>
        
        <hr>
        
        <h3>Test d'envoi d'email</h3>
        
        <div style="background: #f9f9f9; padding: 20px; border-radius: 5px; margin: 20px 0;">
            <p>Ce test envoie un email avec les 3 prochains événements aux destinataires configurés.</p>
            
            <?php
            $email_settings = $plugin->email->get_settings();
            $recipients = $email_settings['email_recipients'] ?? get_option('admin_email');
            ?>
            
            <p><strong>Destinataires actuels :</strong> <?php echo esc_html($recipients); ?></p>
            <p><small>Pour modifier les destinataires, allez dans "Rapports email"</small></p>
            
            <form method="post" action="">
                <?php wp_nonce_field('helloasso_test_email', 'helloasso_email_nonce'); ?>
                <input type="submit" name="send_test_email" class="button button-secondary" value="📨 Envoyer un email de test">
            </form>
            
            <div class="notice notice-warning inline" style="margin-top: 20px;">
                <p><strong>⚠️ Dépannage :</strong> Si l'envoi de test échoue :</p>
                <ul>
                    <li>Vérifiez que PHP peut envoyer des emails (fonction <code>mail()</code>)</li>
                    <li>Installez un plugin SMTP comme "WP Mail SMTP" ou "Post SMTP"</li>
                    <li>Vérifiez vos logs d'erreurs PHP</li>
                    <li>Vérifiez le dossier spam de votre boîte mail</li>
                </ul>
            </div>
        </div>
        
        <hr>
        
        <h3>Gestion du Cache</h3>
        
        <div style="background: #f9f9f9; padding: 20px; border-radius: 5px; margin: 20px 0;">
            <p>Si vous rencontrez des problèmes ou que les données semblent obsolètes, videz les caches.</p>
            
            <form method="post" action="">
                <?php wp_nonce_field('helloasso_clear_cache', 'helloasso_cache_nonce'); ?>
                <input type="submit" name="clear_cache" class="button" value="🗑️ Vider tous les caches" onclick="return confirm('Êtes-vous sûr de vouloir vider tous les caches ?');">
            </form>
            
            <p style="margin-top: 15px; color: #666; font-size: 0.9em;">
                Le cache est automatiquement rafraîchi toutes les 5 minutes pour les événements et 30 minutes pour le token d'authentification.
            </p>
        </div>
    </div>

    <!-- TAB 3 : Configuration CRON -->
    <div id="tab-cron" class="tab-content" style="display: none;">
        <h2>🔔 Configuration du CRON Système</h2>
        
        <div style="background: #fff3cd; border: 1px solid #ffc107; padding: 20px; border-radius: 5px; margin: 20px 0;">
            <h3 style="margin-top: 0;">Pourquoi configurer un CRON ?</h3>
            <p>Pour que les envois programmés fonctionnent de manière fiable et précise, vous devez configurer un <strong>vrai CRON système</strong>.</p>
            <p>Sans CRON, les rapports programmés ne seront jamais envoyés automatiquement.</p>
        </div>

        <div style="background: #f9f9f9; padding: 20px; border-radius: 5px; margin: 20px 0;">
            <h3>Option 1 : CRON système (Recommandé) 🏆</h3>
            <p>Ajoutez cette ligne dans votre crontab via SSH ou votre panneau d'hébergement :</p>
            <pre style="background: #2c3338; color: #fff; padding: 15px; border-radius: 3px; overflow-x: auto; user-select: all;">* * * * * curl -s <?php echo admin_url('admin-ajax.php?action=helloasso_cron'); ?> >/dev/null 2>&1</pre>
            <p style="font-size: 0.9em; color: #666; margin-top: 10px;">
                <strong>Cette commande vérifie toutes les minutes</strong> s'il y a des envois à effectuer.<br>
                Elle n'envoie les emails que si la date/heure programmée est atteinte.
            </p>
            
            <h4 style="margin-top: 25px;">Comment l'ajouter ?</h4>
            <ul style="line-height: 2;">
                <li><strong>Via SSH :</strong> Connectez-vous en SSH et tapez <code>crontab -e</code>, puis ajoutez la ligne ci-dessus</li>
                <li><strong>Via cPanel :</strong> Allez dans "Tâches CRON" et ajoutez une nouvelle tâche avec la commande ci-dessus</li>
                <li><strong>Via Plesk :</strong> Allez dans "Tâches planifiées" et créez une nouvelle tâche</li>
                <li><strong>Via votre hébergeur :</strong> Consultez la documentation de votre hébergeur (OVH, O2Switch, etc.)</li>
            </ul>
        </div>

        <div style="background: #f9f9f9; padding: 20px; border-radius: 5px; margin: 20px 0;">
            <h3>Option 2 : Service externe (Alternative)</h3>
            <p>Si vous n'avez pas accès au CRON système, utilisez un service gratuit :</p>
            
            <ul style="line-height: 2;">
                <li><strong><a href="https://cron-job.org" target="_blank">cron-job.org</a></strong> - Gratuit, jusqu'à 3 tâches</li>
                <li><strong><a href="https://www.easycron.com" target="_blank">EasyCron</a></strong> - Gratuit pour 1 tâche</li>
                <li><strong><a href="https://console.cron-job.org" target="_blank">Console Cron-Job</a></strong> - Interface web simple</li>
            </ul>
            
            <h4 style="margin-top: 20px;">URL à appeler :</h4>
            <div style="background: #fff; padding: 10px; border: 1px solid #ddd; border-radius: 3px; margin: 10px 0;">
                <input type="text" value="<?php echo admin_url('admin-ajax.php?action=helloasso_cron'); ?>" readonly style="width: 100%; padding: 8px; font-family: monospace;" onclick="this.select();">
            </div>
            
            <h4 style="margin-top: 20px;">Configuration recommandée :</h4>
            <ul style="line-height: 2;">
                <li><strong>Fréquence :</strong> Toutes les minutes (ou toutes les 5 minutes minimum)</li>
                <li><strong>Méthode :</strong> GET</li>
                <li><strong>Timeout :</strong> 30 secondes</li>
            </ul>
        </div>

        <div style="background: #e7f3ff; border: 1px solid #2196F3; padding: 20px; border-radius: 5px; margin: 20px 0;">
            <h3 style="margin-top: 0;">🧪 Test du endpoint CRON</h3>
            <p>Cliquez sur ce bouton pour vérifier que le endpoint CRON répond correctement :</p>
            <p>
                <a href="<?php echo admin_url('admin-ajax.php?action=helloasso_cron'); ?>" target="_blank" class="button button-secondary">
                    🔍 Tester le endpoint CRON
                </a>
            </p>
            <p style="font-size: 0.9em; color: #666; margin-top: 10px;">
                <strong>Résultat attendu :</strong> Une page blanche avec le texte "OK - Checked", "OK - No schedules" ou "Rapports désactivés"<br>
                Si vous voyez une erreur, consultez la section dépannage.
            </p>
        </div>
    </div>

    <!-- TAB 4 : Dépannage -->
    <div id="tab-troubleshooting" class="tab-content" style="display: none;">
        <h2>🔧 Dépannage</h2>

        <details style="background: #f9f9f9; padding: 15px; border-radius: 5px; margin: 20px 0; border: 1px solid #ddd;">
            <summary style="cursor: pointer; font-weight: bold; padding: 10px; background: #fff; margin: -15px; border-radius: 5px;">
                ❌ Les événements ne s'affichent pas
            </summary>
            <div style="padding: 20px 0;">
                <h4>Vérifications à effectuer :</h4>
                <ol style="line-height: 2;">
                    <li>Vérifiez que les identifiants API sont corrects dans wp-config.php</li>
                    <li>Testez la connexion dans l'onglet "Tests"</li>
                    <li>Vérifiez que votre organisation a des événements sur HelloAsso</li>
                    <li>Videz le cache et réessayez</li>
                    <li>Vérifiez que le shortcode est bien écrit : <code>[helloasso_events]</code></li>
                </ol>
            </div>
        </details>

        <details style="background: #f9f9f9; padding: 15px; border-radius: 5px; margin: 20px 0; border: 1px solid #ddd;">
            <summary style="cursor: pointer; font-weight: bold; padding: 10px; background: #fff; margin: -15px; border-radius: 5px;">
                ❌ Les emails ne sont pas envoyés
            </summary>
            <div style="padding: 20px 0;">
                <h4>Vérifications :</h4>
                <ol style="line-height: 2;">
                    <li>Vérifier que les rapports sont activés dans "HelloAsso > Rapports email"</li>
                    <li>Vérifier qu'au moins un destinataire est configuré</li>
                    <li>Tester l'envoi immédiat dans l'onglet "Tests"</li>
                    <li>Vérifier que le CRON est bien configuré et s'exécute</li>
                    <li>Consulter les logs PHP (voir onglet "Informations Système")</li>
                    <li>Installer un plugin SMTP fiable (WP Mail SMTP, Post SMTP)</li>
                </ol>
            </div>
        </details>

        <details style="background: #f9f9f9; padding: 15px; border-radius: 5px; margin: 20px 0; border: 1px solid #ddd;">
            <summary style="cursor: pointer; font-weight: bold; padding: 10px; background: #fff; margin: -15px; border-radius: 5px;">
                ❌ Les schedules ne s'enregistrent pas
            </summary>
            <div style="padding: 20px 0;">
                <h4>Solutions :</h4>
                <ol style="line-height: 2;">
                    <li>Activer le mode debug en ajoutant <code>?debug_schedules=1</code> à l'URL</li>
                    <li>Vérifier les permissions de la base de données</li>
                    <li>Vérifier l'espace disque disponible</li>
                    <li>Désactiver temporairement les plugins de cache</li>
                    <li>Consulter les logs d'erreur PHP</li>
                </ol>
            </div>
        </details>

        <details style="background: #f9f9f9; padding: 15px; border-radius: 5px; margin: 20px 0; border: 1px solid #ddd;">
            <summary style="cursor: pointer; font-weight: bold; padding: 10px; background: #fff; margin: -15px; border-radius: 5px;">
                ❌ Le endpoint CRON renvoie une erreur
            </summary>
            <div style="padding: 20px 0;">
                <h4>Erreurs courantes :</h4>
                <ul style="line-height: 2;">
                    <li>
                        <strong>"403 Forbidden" ou "Access Denied"</strong><br>
                        → Votre hébergeur bloque les requêtes AJAX non authentifiées<br>
                        → Solution : Contactez votre hébergeur
                    </li>
                    <li>
                        <strong>"500 Internal Server Error"</strong><br>
                        → Erreur PHP dans le code<br>
                        → Solution : Activez les logs PHP et consultez-les
                    </li>
                    <li>
                        <strong>Page blanche sans texte</strong><br>
                        → PHP Fatal Error<br>
                        → Solution : Consultez les logs d'erreur PHP du serveur
                    </li>
                </ul>
            </div>
        </details>

        <details style="background: #f9f9f9; padding: 15px; border-radius: 5px; margin: 20px 0; border: 1px solid #ddd;">
            <summary style="cursor: pointer; font-weight: bold; padding: 10px; background: #fff; margin: -15px; border-radius: 5px;">
                ❌ Les chiffres de réservations sont incorrects
            </summary>
            <div style="padding: 20px 0;">
                <h4>Solutions :</h4>
                <ol style="line-height: 2;">
                    <li>Videz le cache dans l'onglet "Tests"</li>
                    <li>Attendez 5 minutes (durée du cache)</li>
                    <li>Vérifiez directement sur HelloAsso</li>
                    <li>Utilisez le mode debug : ajoutez <code>?debug_ha</code> à l'URL de votre page</li>
                </ol>
            </div>
        </details>
    </div>

    <!-- TAB 5 : Informations Système -->
    <div id="tab-system" class="tab-content" style="display: none;">
        <h2>💻 Informations Système</h2>

        <table class="widefat" style="max-width: 800px; margin: 20px 0;">
            <thead>
                <tr>
                    <th style="padding: 12px; width: 40%;">Paramètre</th>
                    <th style="padding: 12px; width: 60%;">Valeur</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="padding: 12px;"><strong>Version PHP</strong></td>
                    <td style="padding: 12px;"><?php echo PHP_VERSION; ?></td>
                </tr>
                <tr style="background: #f9f9f9;">
                    <td style="padding: 12px;"><strong>Version WordPress</strong></td>
                    <td style="padding: 12px;"><?php echo get_bloginfo('version'); ?></td>
                </tr>
                <tr>
                    <td style="padding: 12px;"><strong>Version du Plugin</strong></td>
                    <td style="padding: 12px;"><?php echo HELLOASSO_VERSION; ?></td>
                </tr>
                <tr style="background: #f9f9f9;">
                    <td style="padding: 12px;"><strong>Extension cURL</strong></td>
                    <td style="padding: 12px;">
                        <?php if (function_exists('curl_init')): ?>
                            <span style="color: #28a745;">✓ Disponible</span>
                        <?php else: ?>
                            <span style="color: #dc3545;">✗ Non disponible</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 12px;"><strong>Fonction mail()</strong></td>
                    <td style="padding: 12px;">
                        <?php if (function_exists('mail')): ?>
                            <span style="color: #28a745;">✓ Disponible</span>
                        <?php else: ?>
                            <span style="color: #dc3545;">✗ Non disponible</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr style="background: #f9f9f9;">
                    <td style="padding: 12px;"><strong>WP_DEBUG</strong></td>
                    <td style="padding: 12px;">
                        <?php if (defined('WP_DEBUG') && WP_DEBUG): ?>
                            <span style="color: #28a745;">✓ Activé</span>
                        <?php else: ?>
                            <span style="color: #999;">○ Désactivé</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 12px;"><strong>WP_DEBUG_LOG</strong></td>
                    <td style="padding: 12px;">
                        <?php if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG): ?>
                            <span style="color: #28a745;">✓ Activé</span>
                        <?php else: ?>
                            <span style="color: #999;">○ Désactivé</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr style="background: #f9f9f9;">
                    <td style="padding: 12px;"><strong>Fuseau horaire WordPress</strong></td>
                    <td style="padding: 12px;"><?php echo wp_timezone_string(); ?></td>
                </tr>
                <tr>
                    <td style="padding: 12px;"><strong>Heure serveur actuelle</strong></td>
                    <td style="padding: 12px;"><?php echo current_time('d/m/Y H:i:s'); ?></td>
                </tr>
                <tr style="background: #f9f9f9;">
                    <td style="padding: 12px;"><strong>Limite mémoire PHP</strong></td>
                    <td style="padding: 12px;"><?php echo ini_get('memory_limit'); ?></td>
                </tr>
                <tr>
                    <td style="padding: 12px;"><strong>Temps d'exécution max</strong></td>
                    <td style="padding: 12px;"><?php echo ini_get('max_execution_time'); ?> secondes</td>
                </tr>
            </tbody>
        </table>

        <hr style="margin: 40px 0;">

        <h3>📄 Logs de Debug</h3>

        <?php if (defined('WP_DEBUG') && WP_DEBUG): ?>
            <div class="notice notice-success inline">
                <p>✓ <strong>WP_DEBUG est activé</strong> - Les erreurs sont enregistrées</p>
            </div>
        <?php else: ?>
            <div class="notice notice-warning inline">
                <p>⚠️ <strong>WP_DEBUG est désactivé</strong> - Les erreurs ne sont pas enregistrées</p>
                <p>Pour activer les logs, ajoutez ces lignes dans votre fichier <code>wp-config.php</code> :</p>
                <pre style="background: #2c3338; color: #fff; padding: 10px; border-radius: 3px; user-select: all;">define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);</pre>
            </div>
        <?php endif; ?>

        <?php if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG): ?>
            <details style="background: #f9f9f9; padding: 15px; border-radius: 5px; margin: 20px 0; border: 1px solid #ddd;">
                <summary style="cursor: pointer; font-weight: bold; padding: 10px; background: #fff; margin: -15px; border-radius: 5px;">
                    📄 Voir les logs HelloAsso récents
                </summary>
                <div style="padding: 20px 0;">
                    <?php
                    $log_file = WP_CONTENT_DIR . '/debug.log';
                    if (file_exists($log_file)) {
                        $logs = file($log_file);
                        $helloasso_logs = array_filter($logs, function($line) {
                            return stripos($line, 'helloasso') !== false;
                        });
                        
                        if (empty($helloasso_logs)) {
                            echo '<p style="color: #666;">Aucun log HelloAsso trouvé.</p>';
                        } else {
                            echo '<p><strong>Derniers logs (max 30 lignes) :</strong></p>';
                            echo '<pre style="background: #2c3338; color: #0f0; padding: 15px; max-height: 400px; overflow: auto; border-radius: 3px; font-size: 12px; line-height: 1.4;">';
                            echo esc_html(implode('', array_slice($helloasso_logs, -30)));
                            echo '</pre>';
                            
                            echo '<p style="margin-top: 15px;">';
                            echo '<a href="' . content_url('debug.log') . '" target="_blank" class="button">📥 Télécharger le fichier complet</a>';
                            echo '</p>';
                        }
                    } else {
                        echo '<p style="color: #d63638;">❌ Fichier de log introuvable : ' . esc_html($log_file) . '</p>';
                        echo '<p>Le fichier sera créé automatiquement dès qu\'une erreur sera enregistrée.</p>';
                    }
                    ?>
                </div>
            </details>
        <?php endif; ?>

        <hr style="margin: 40px 0;">

        <h3>🔍 Informations de Cache</h3>

        <table class="widefat" style="max-width: 800px; margin: 20px 0;">
            <thead>
                <tr>
                    <th style="padding: 12px; width: 50%;">Cache</th>
                    <th style="padding: 12px; width: 50%;">État</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="padding: 12px;"><strong>Token d'accès API</strong></td>
                    <td style="padding: 12px;">
                        <?php
                        $token_cache = get_transient('helloasso_access_token');
                        if ($token_cache):
                        ?>
                            <span style="color: #28a745;">✓ En cache</span>
                            <br><small style="color: #666;">Expire dans <?php echo get_option('_transient_timeout_helloasso_access_token') - time(); ?>s</small>
                        <?php else: ?>
                            <span style="color: #999;">○ Vide</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr style="background: #f9f9f9;">
                    <td style="padding: 12px;"><strong>Liste des événements</strong></td>
                    <td style="padding: 12px;">
                        <?php
                        $events_cache = get_transient('helloasso_events_cache');
                        if ($events_cache):
                        ?>
                            <span style="color: #28a745;">✓ En cache</span>
                            <br><small style="color: #666;">
                                <?php echo count($events_cache['data'] ?? []); ?> événement(s)
                                - Expire dans <?php echo get_option('_transient_timeout_helloasso_events_cache') - time(); ?>s
                            </small>
                        <?php else: ?>
                            <span style="color: #999;">○ Vide</span>
                        <?php endif; ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <p style="margin-top: 20px;">
            <a href="<?php echo add_query_arg('action', 'clear_cache'); ?>" class="button">
                🗑️ Vider tous les caches
            </a>
        </p>
    </div>

</div>

<script>
jQuery(document).ready(function($) {
    // Gestion des onglets
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        
        // Retirer la classe active de tous les onglets
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        // Cacher tous les contenus
        $('.tab-content').hide();
        
        // Afficher le contenu correspondant
        var tab = $(this).data('tab');
        $('#tab-' + tab).show();
        
        // Mettre à jour l'URL avec l'ancre
        if (history.pushState) {
            history.pushState(null, null, '#tab-' + tab);
        }
    });
    
    // Charger l'onglet depuis l'URL au chargement
    if (window.location.hash) {
        var hash = window.location.hash.substring(1); // Enlever le #
        var tab = hash.replace('tab-', '');
        
        if ($('.nav-tab[data-tab="' + tab + '"]').length) {
            $('.nav-tab[data-tab="' + tab + '"]').click();
        }
    }
    
    // Gestion des details/summary
    $('details summary').on('click', function() {
        var details = $(this).parent();
        setTimeout(function() {
            if (details.attr('open')) {
                details.find('summary').css('background', '#f0f0f0');
            } else {
                details.find('summary').css('background', '#fff');
            }
        }, 10);
    });
});
</script>

<style>
.nav-tab-wrapper {
    border-bottom: 1px solid #ccd0d4;
    margin-bottom: 0;
    padding-top: 0;
}

.nav-tab {
    cursor: pointer;
    transition: all 0.2s;
}

.nav-tab:hover {
    background: #f0f0f0;
}

.tab-content {
    background: white;
    padding: 20px;
    border: 1px solid #ccd0d4;
    border-top: none;
}

details summary {
    transition: background 0.2s;
}

details summary:hover {
    background: #f0f0f0 !important;
}

details[open] summary {
    margin-bottom: 0 !important;
    border-bottom: 2px solid #2196F3;
    border-radius: 5px 5px 0 0 !important;
}

details[open] > div {
    border-top: none;
}

.widefat th,
.widefat td {
    vertical-align: middle;
}

.notice.inline {
    padding: 12px;
    margin: 15px 0;
}
</style><?php