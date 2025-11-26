<?php
/**
 * Page de configuration CRON et dépannage
 */

if (!defined('ABSPATH')) {
    exit;
}

$plugin = HelloAsso_Events_Manager::get_instance();

?>

<div class="wrap">
    <h1>⚙️ Configuration & Dépannage</h1>
    
    <div class="notice notice-info">
        <p><strong>Configuration technique et outils de diagnostic</strong></p>
        <p>Cette page vous aide à configurer le CRON système et à résoudre les problèmes courants.</p>
    </div>

    <!-- SECTION 1 : Configuration CRON -->
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
        
        <h4>Comment l'ajouter ?</h4>
        <ul>
            <li><strong>Via SSH :</strong> Connectez-vous en SSH et tapez <code>crontab -e</code>, puis ajoutez la ligne ci-dessus</li>
            <li><strong>Via cPanel :</strong> Allez dans "Tâches CRON" et ajoutez une nouvelle tâche avec la commande ci-dessus</li>
            <li><strong>Via Plesk :</strong> Allez dans "Tâches planifiées" et créez une nouvelle tâche</li>
            <li><strong>Via votre hébergeur :</strong> Consultez la documentation de votre hébergeur (OVH, O2Switch, etc.)</li>
        </ul>
    </div>

    <div style="background: #f9f9f9; padding: 20px; border-radius: 5px; margin: 20px 0;">
        <h3>Option 2 : Service externe (Alternative)</h3>
        <p>Si vous n'avez pas accès au CRON système, utilisez un service gratuit :</p>
        
        <ul>
            <li><strong><a href="https://cron-job.org" target="_blank">cron-job.org</a></strong> - Gratuit, jusqu'à 3 tâches</li>
            <li><strong><a href="https://www.easycron.com" target="_blank">EasyCron</a></strong> - Gratuit pour 1 tâche</li>
            <li><strong><a href="https://console.cron-job.org" target="_blank">Console Cron-Job</a></strong> - Interface web simple</li>
        </ul>
        
        <h4>URL à appeler :</h4>
        <div style="background: #fff; padding: 10px; border: 1px solid #ddd; border-radius: 3px; margin: 10px 0;">
            <input type="text" value="<?php echo admin_url('admin-ajax.php?action=helloasso_cron'); ?>" readonly style="width: 100%; padding: 8px; font-family: monospace;" onclick="this.select();">
        </div>
        
        <h4>Configuration recommandée :</h4>
        <ul>
            <li><strong>Fréquence :</strong> Toutes les minutes (ou toutes les 5 minutes minimum)</li>
            <li><strong>Méthode :</strong> GET</li>
            <li><strong>Timeout :</strong> 30 secondes</li>
        </ul>
    </div>

    <div style="background: #e7f3ff; border: 1px solid #2196F3; padding: 20px; border-radius: 5px; margin: 20px 0;">
        <h3>🧪 Test du endpoint CRON</h3>
        <p>Cliquez sur ce bouton pour vérifier que le endpoint CRON répond correctement :</p>
        <p>
            <a href="<?php echo admin_url('admin-ajax.php?action=helloasso_cron'); ?>" target="_blank" class="button button-secondary">
                🔍 Tester le endpoint CRON
            </a>
        </p>
        <p style="font-size: 0.9em; color: #666; margin-top: 10px;">
            <strong>Résultat attendu :</strong> Une page blanche avec le texte "OK - Checked" ou "OK - No schedules"<br>
            Si vous voyez une erreur, consultez la section dépannage ci-dessous.
        </p>
    </div>

    <hr style="margin: 40px 0;">

    <!-- SECTION 2 : Dépannage -->
    <h2>🔧 Dépannage</h2>

    <details style="background: #f9f9f9; padding: 15px; border-radius: 5px; margin: 20px 0; border: 1px solid #ddd;">
        <summary style="cursor: pointer; font-weight: bold; padding: 10px; background: #fff; margin: -15px; border-radius: 5px;">
            ❌ Les emails ne sont pas envoyés
        </summary>
        <div style="padding: 20px 0;">
            <h4>Vérifications à effectuer :</h4>
            <ol style="line-height: 1.8;">
                <li>
                    <strong>Vérifier que les rapports sont activés</strong><br>
                    → Allez dans "HelloAsso > Rapports email" et cochez "Envoyer des rapports par email"
                </li>
                <li>
                    <strong>Vérifier les destinataires</strong><br>
                    → Au moins une adresse email valide doit être configurée
                </li>
                <li>
                    <strong>Tester l'envoi immédiat</strong><br>
                    → Allez dans "HelloAsso > Tests" et cliquez sur "Envoyer un email de test"
                </li>
                <li>
                    <strong>Vérifier le CRON</strong><br>
                    → S'assurer qu'il est bien configuré et s'exécute (voir section ci-dessus)
                </li>
                <li>
                    <strong>Vérifier les logs PHP</strong><br>
                    → Regarder les erreurs dans <code>wp-content/debug.log</code> (voir ci-dessous)
                </li>
                <li>
                    <strong>Installer un plugin SMTP</strong><br>
                    → <a href="<?php echo admin_url('plugin-install.php?s=wp+mail+smtp&tab=search'); ?>">WP Mail SMTP</a> ou <a href="<?php echo admin_url('plugin-install.php?s=post+smtp&tab=search'); ?>">Post SMTP</a> pour plus de fiabilité
                </li>
            </ol>
        </div>
    </details>

    <details style="background: #f9f9f9; padding: 15px; border-radius: 5px; margin: 20px 0; border: 1px solid #ddd;">
        <summary style="cursor: pointer; font-weight: bold; padding: 10px; background: #fff; margin: -15px; border-radius: 5px;">
            ❌ Les schedules ne s'enregistrent pas
        </summary>
        <div style="padding: 20px 0;">
            <h4>Solutions à essayer :</h4>
            <ol style="line-height: 1.8;">
                <li>
                    <strong>Activer le mode debug</strong><br>
                    → Ajoutez <code>?debug_schedules=1</code> à l'URL dans "HelloAsso > Rapports email"<br>
                    → Cela affichera le contenu brut de la base de données
                </li>
                <li>
                    <strong>Vérifier les permissions de la base de données</strong><br>
                    → L'utilisateur MySQL doit avoir les droits UPDATE sur la table wp_options
                </li>
                <li>
                    <strong>Vérifier l'espace disque</strong><br>
                    → S'assurer qu'il reste suffisamment d'espace sur le serveur
                </li>
                <li>
                    <strong>Désactiver temporairement les plugins de cache</strong><br>
                    → WP Rocket, W3 Total Cache, etc. peuvent interférer
                </li>
                <li>
                    <strong>Consulter les logs PHP</strong><br>
                    → Voir la section "Logs" ci-dessous
                </li>
            </ol>
        </div>
    </details>

    <details style="background: #f9f9f9; padding: 15px; border-radius: 5px; margin: 20px 0; border: 1px solid #ddd;">
        <summary style="cursor: pointer; font-weight: bold; padding: 10px; background: #fff; margin: -15px; border-radius: 5px;">
            ❌ Les événements ne s'affichent pas dans le formulaire
        </summary>
        <div style="padding: 20px 0;">
            <h4>Solutions :</h4>
            <ol style="line-height: 1.8;">
                <li>
                    <strong>Tester la connexion API</strong><br>
                    → Allez dans "HelloAsso > Tests" et cliquez sur "Tester la connexion à l'API"
                </li>
                <li>
                    <strong>Vérifier les identifiants</strong><br>
                    → Les constantes dans wp-config.php doivent être correctes
                </li>
                <li>
                    <strong>Vérifier que vous avez des événements</strong><br>
                    → Connectez-vous sur HelloAsso et vérifiez que votre organisation a des événements actifs
                </li>
                <li>
                    <strong>Vider le cache</strong><br>
                    → Allez dans "HelloAsso > Tests" et cliquez sur "Vider tous les caches"
                </li>
            </ol>
        </div>
    </details>

    <details style="background: #f9f9f9; padding: 15px; border-radius: 5px; margin: 20px 0; border: 1px solid #ddd;">
        <summary style="cursor: pointer; font-weight: bold; padding: 10px; background: #fff; margin: -15px; border-radius: 5px;">
            ❌ Le endpoint CRON renvoie une erreur
        </summary>
        <div style="padding: 20px 0;">
            <h4>Erreurs possibles :</h4>
            <ul style="line-height: 1.8;">
                <li>
                    <strong>"403 Forbidden" ou "Access Denied"</strong><br>
                    → Votre hébergeur bloque les requêtes AJAX non authentifiées<br>
                    → Solution : Contactez votre hébergeur ou utilisez WP-Cron (moins fiable)
                </li>
                <li>
                    <strong>"500 Internal Server Error"</strong><br>
                    → Erreur PHP dans le code<br>
                    → Solution : Activez les logs PHP et consultez-les (voir ci-dessous)
                </li>
                <li>
                    <strong>Page blanche sans texte</strong><br>
                    → PHP Fatal Error<br>
                    → Solution : Consultez les logs d'erreur PHP du serveur
                </li>
            </ul>
        </div>
    </details>

    <hr style="margin: 40px 0;">

    <!-- SECTION 3 : Logs et Debug -->
    <h2>📄 Logs et Informations de Debug</h2>

    <?php if (defined('WP_DEBUG') && WP_DEBUG): ?>
        <div class="notice notice-success inline">
            <p>✓ <strong>WP_DEBUG est activé</strong> - Les erreurs sont enregistrées</p>
        </div>
    <?php else: ?>
        <div class="notice notice-warning inline">
            <p>⚠️ <strong>WP_DEBUG est désactivé</strong> - Les erreurs ne sont pas enregistrées</p>
            <p>Pour activer les logs, ajoutez ces lignes dans votre fichier <code>wp-config.php</code> :</p>
            <pre style="background: #2c3338; color: #fff; padding: 10px; border-radius: 3px;">define('WP_DEBUG', true);
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

    <!-- SECTION 4 : Informations système -->
    <h2>💻 Informations Système</h2>

    <table class="widefat" style="max-width: 800px;">
        <thead>
            <tr>
                <th style="width: 40%; padding: 10px;">Paramètre</th>
                <th style="width: 60%; padding: 10px;">Valeur</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="padding: 10px;"><strong>Version PHP</strong></td>
                <td style="padding: 10px;"><?php echo PHP_VERSION; ?></td>
            </tr>
            <tr>
                <td style="padding: 10px;"><strong>Version WordPress</strong></td>
                <td style="padding: 10px;"><?php echo get_bloginfo('version'); ?></td>
            </tr>
            <tr>
                <td style="padding: 10px;"><strong>Extension cURL</strong></td>
                <td style="padding: 10px;">
                    <?php if (function_exists('curl_init')): ?>
                        <span style="color: #46b450;">✓ Disponible</span>
                    <?php else: ?>
                        <span style="color: #d63638;">✗ Non disponible</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td style="padding: 10px;"><strong>Fonction mail()</strong></td>
                <td style="padding: 10px;">
                    <?php if (function_exists('mail')): ?>
                        <span style="color: #46b450;">✓ Disponible</span>
                    <?php else: ?>
                        <span style="color: #d63638;">✗ Non disponible</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td style="padding: 10px;"><strong>WP_DEBUG</strong></td>
                <td style="padding: 10px;">
                    <?php if (defined('WP_DEBUG') && WP_DEBUG): ?>
                        <span style="color: #46b450;">✓ Activé</span>
                    <?php else: ?>
                        <span style="color: #999;">○ Désactivé</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td style="padding: 10px;"><strong>WP_DEBUG_LOG</strong></td>
                <td style="padding: 10px;">
                    <?php if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG): ?>
                        <span style="color: #46b450;">✓ Activé</span>
                    <?php else: ?>
                        <span style="color: #999;">○ Désactivé</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td style="padding: 10px;"><strong>Fuseau horaire WordPress</strong></td>
                <td style="padding: 10px;"><?php echo wp_timezone_string(); ?></td>
            </tr>
            <tr>
                <td style="padding: 10px;"><strong>Heure serveur actuelle</strong></td>
                <td style="padding: 10px;"><?php echo current_time('d/m/Y H:i:s'); ?></td>
            </tr>
        </tbody>
    </table>

</div>

<style>
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
</style>