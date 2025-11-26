<?php
/**
 * Page de présentation (développée)
 */

if (!defined('ABSPATH')) {
    exit;
}

$plugin = HelloAsso_Events_Reports::get_instance();
$credentials = $plugin->api->get_credentials();
$email_settings = $plugin->email->get_settings();

// Tester la connexion API
$api_connected = false;
$api_error = '';
$events_count = 0;

try {
    $token = $plugin->api->get_access_token();
    if ($token) {
        $api_connected = true;
        $events_data = $plugin->api->get_events();
        $events_count = isset($events_data['data']) ? count($events_data['data']) : 0;
    }
} catch (Exception $e) {
    $api_error = $e->getMessage();
}
?>

<div class="wrap">
    <h1>🎫 HelloAsso Events Reports</h1>
    
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 10px; margin: 20px 0;">
        <h2 style="margin: 0 0 10px 0; color: white;">Bienvenue dans HelloAsso Events Reports</h2>
        <p style="margin: 0; font-size: 1.1em; opacity: 0.9;">
            Affichez vos événements HelloAsso sur votre site WordPress avec les statistiques de réservation en temps réel.
        </p>
    </div>
    
    <!-- Dashboard - État du système -->
    <h2>📊 État du Système</h2>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0;">
        
        <!-- Carte API -->
        <div style="background: white; border: 1px solid #ddd; border-radius: 8px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 15px 0; color: #2196F3; font-size: 1.1em;">🔌 Connexion API</h3>
            <?php if ($api_connected): ?>
                <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; border-left: 4px solid #28a745;">
                    <strong>✓ Connecté</strong>
                    <p style="margin: 10px 0 0 0; font-size: 0.9em;"><?php echo $events_count; ?> événement(s) disponible(s)</p>
                </div>
            <?php else: ?>
                <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; border-left: 4px solid #dc3545;">
                    <strong>✗ Non connecté</strong>
                    <p style="margin: 10px 0 0 0; font-size: 0.9em;"><?php echo esc_html($api_error); ?></p>
                </div>
            <?php endif; ?>
            <p style="margin: 15px 0 0 0;">
                <a href="<?php echo admin_url('admin.php?page=helloasso-configuration'); ?>" class="button button-secondary">
                    Tester la connexion
                </a>
            </p>
        </div>
        
        <!-- Carte Identifiants -->
        <div style="background: white; border: 1px solid #ddd; border-radius: 8px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 15px 0; color: #2196F3; font-size: 1.1em;">🔑 Identifiants API</h3>
            <table style="width: 100%; font-size: 0.9em;">
                <tr>
                    <td style="padding: 5px 0;"><strong>Client ID:</strong></td>
                    <td style="padding: 5px 0;">
                        <?php if (!empty($credentials['client_id'])): ?>
                            <span style="color: #28a745;">✓</span>
                        <?php else: ?>
                            <span style="color: #dc3545;">✗</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 5px 0;"><strong>Client Secret:</strong></td>
                    <td style="padding: 5px 0;">
                        <?php if (!empty($credentials['client_secret'])): ?>
                            <span style="color: #28a745;">✓</span>
                        <?php else: ?>
                            <span style="color: #dc3545;">✗</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 5px 0;"><strong>Organization:</strong></td>
                    <td style="padding: 5px 0;">
                        <?php if (!empty($credentials['organization_slug'])): ?>
                            <span style="color: #28a745;">✓</span> <?php echo esc_html($credentials['organization_slug']); ?>
                        <?php else: ?>
                            <span style="color: #dc3545;">✗</span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
            <p style="margin: 15px 0 0 0; font-size: 0.85em; color: #666;">
                Configurés dans <code>wp-config.php</code>
            </p>
        </div>
        
        <!-- Carte Rapports Email -->
        <div style="background: white; border: 1px solid #ddd; border-radius: 8px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 15px 0; color: #2196F3; font-size: 1.1em;">📧 Rapports Email</h3>
            <?php if (!empty($email_settings['enable_email'])): ?>
                <div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 10px;">
                    <strong>✓ Activés</strong>
                </div>
                <p style="margin: 0; font-size: 0.9em;">
                    <strong>Destinataires:</strong><br>
                    <?php echo !empty($email_settings['email_recipients']) ? esc_html($email_settings['email_recipients']) : 'Non définis'; ?>
                </p>
                <?php
                $schedules_count = isset($email_settings['schedules']) ? count($email_settings['schedules']) : 0;
                if ($schedules_count > 0):
                ?>
                <p style="margin: 10px 0 0 0; font-size: 0.9em; color: #666;">
                    <?php echo $schedules_count; ?> envoi(s) programmé(s)
                </p>
                <?php endif; ?>
            <?php else: ?>
                <div style="background: #fff3cd; color: #856404; padding: 10px; border-radius: 5px; margin-bottom: 10px;">
                    <strong>○ Désactivés</strong>
                </div>
                <p style="margin: 0; font-size: 0.9em; color: #666;">
                    Les rapports automatiques ne sont pas activés
                </p>
            <?php endif; ?>
            <p style="margin: 15px 0 0 0;">
                <a href="<?php echo admin_url('admin.php?page=helloasso-email-reports'); ?>" class="button button-secondary">
                    Configurer
                </a>
            </p>
        </div>
        
        <!-- Carte Système -->
        <div style="background: white; border: 1px solid #ddd; border-radius: 8px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 15px 0; color: #2196F3; font-size: 1.1em;">💻 Système</h3>
            <table style="width: 100%; font-size: 0.9em;">
                <tr>
                    <td style="padding: 5px 0;"><strong>PHP:</strong></td>
                    <td style="padding: 5px 0;"><?php echo PHP_VERSION; ?></td>
                </tr>
                <tr>
                    <td style="padding: 5px 0;"><strong>WordPress:</strong></td>
                    <td style="padding: 5px 0;"><?php echo get_bloginfo('version'); ?></td>
                </tr>
                <tr>
                    <td style="padding: 5px 0;"><strong>cURL:</strong></td>
                    <td style="padding: 5px 0;">
                        <?php if (function_exists('curl_init')): ?>
                            <span style="color: #28a745;">✓</span>
                        <?php else: ?>
                            <span style="color: #dc3545;">✗</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 5px 0;"><strong>mail():</strong></td>
                    <td style="padding: 5px 0;">
                        <?php if (function_exists('mail')): ?>
                            <span style="color: #28a745;">✓</span>
                        <?php else: ?>
                            <span style="color: #dc3545;">✗</span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>
        
    </div>
    
    <!-- Fonctionnalités principales -->
    <h2>🚀 Fonctionnalités Principales</h2>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin: 20px 0;">
        
        <div style="background: white; border: 1px solid #ddd; border-radius: 8px; padding: 25px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <div style="font-size: 2em; margin-bottom: 10px;">🎟️</div>
            <h3 style="margin: 0 0 10px 0; color: #333;">Affichage des Événements</h3>
            <p style="color: #666; line-height: 1.6;">
                Affichez vos événements HelloAsso sur votre site avec un simple shortcode. 
                Informations en temps réel : places vendues, catégories, statistiques complètes.
            </p>
            <p style="margin-top: 15px;">
                <a href="<?php echo admin_url('admin.php?page=helloasso-shortcodes'); ?>" class="button button-primary">
                    Voir les shortcodes →
                </a>
            </p>
        </div>
        
        <div style="background: white; border: 1px solid #ddd; border-radius: 8px; padding: 25px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <div style="font-size: 2em; margin-bottom: 10px;">📊</div>
            <h3 style="margin: 0 0 10px 0; color: #333;">Rapports Automatiques</h3>
            <p style="color: #666; line-height: 1.6;">
                Recevez des rapports par email avec les statistiques de vos événements. 
                Programmez des envois automatiques ou à la demande.
            </p>
            <p style="margin-top: 15px;">
                <a href="<?php echo admin_url('admin.php?page=helloasso-email-reports'); ?>" class="button button-primary">
                    Configurer les rapports →
                </a>
            </p>
        </div>
        
        <div style="background: white; border: 1px solid #ddd; border-radius: 8px; padding: 25px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <div style="font-size: 2em; margin-bottom: 10px;">⚙️</div>
            <h3 style="margin: 0 0 10px 0; color: #333;">Configuration Simple</h3>
            <p style="color: #666; line-height: 1.6;">
                Configuration via <code>wp-config.php</code> pour une sécurité maximale. 
                Tests intégrés pour vérifier votre installation.
            </p>
            <p style="margin-top: 15px;">
                <a href="<?php echo admin_url('admin.php?page=helloasso-configuration'); ?>" class="button button-primary">
                    Accéder à la configuration →
                </a>
            </p>
        </div>
        
    </div>
    
    <!-- Guide de démarrage rapide -->
    <h2>🎯 Démarrage Rapide</h2>
    
    <div style="background: white; border: 1px solid #ddd; border-radius: 8px; padding: 25px; margin: 20px 0;">
        <ol style="line-height: 2; font-size: 1.05em;">
            <li>
                <strong>Configurer les identifiants API</strong><br>
                <span style="color: #666; font-size: 0.9em;">
                    Ajoutez vos identifiants HelloAsso dans <code>wp-config.php</code>
                    <?php if (empty($credentials['client_id']) || empty($credentials['client_secret'])): ?>
                        <span style="color: #dc3545;"> ⚠️ À faire</span>
                    <?php else: ?>
                        <span style="color: #28a745;"> ✓ Fait</span>
                    <?php endif; ?>
                </span>
            </li>
            <li>
                <strong>Tester la connexion</strong><br>
                <span style="color: #666; font-size: 0.9em;">
                    Vérifiez que la connexion à l'API fonctionne
                    <?php if (!$api_connected): ?>
                        <span style="color: #dc3545;"> ⚠️ À faire</span>
                    <?php else: ?>
                        <span style="color: #28a745;"> ✓ Fait</span>
                    <?php endif; ?>
                </span>
            </li>
            <li>
                <strong>Ajouter le shortcode sur votre site</strong><br>
                <span style="color: #666; font-size: 0.9em;">
                    Utilisez <code>[helloasso_events]</code> sur une page ou un article
                </span>
            </li>
            <li>
                <strong>(Optionnel) Configurer les rapports email</strong><br>
                <span style="color: #666; font-size: 0.9em;">
                    Recevez automatiquement les statistiques de vos événements
                    <?php if (!empty($email_settings['enable_email'])): ?>
                        <span style="color: #28a745;"> ✓ Activé</span>
                    <?php endif; ?>
                </span>
            </li>
        </ol>
    </div>
    
    <!-- Liens rapides -->
    <h2>🔗 Liens Utiles</h2>
    
    <div style="background: #f9f9f9; border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin: 20px 0;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
            <div>
                <h4 style="margin: 0 0 10px 0;">📚 Documentation</h4>
                <ul style="margin: 0; padding-left: 20px;">
                    <li><a href="<?php echo admin_url('admin.php?page=helloasso-shortcodes'); ?>">Guide des shortcodes</a></li>
                    <li><a href="<?php echo admin_url('admin.php?page=helloasso-configuration'); ?>">Configuration & Tests</a></li>
                    <li><a href="https://api.helloasso.com/v5/swagger/ui/index" target="_blank">API HelloAsso ↗</a></li>
                </ul>
            </div>
            <div>
                <h4 style="margin: 0 0 10px 0;">⚡ Actions Rapides</h4>
                <ul style="margin: 0; padding-left: 20px;">
                    <li><a href="<?php echo admin_url('admin.php?page=helloasso-configuration'); ?>">Tester l'API</a></li>
                    <li><a href="<?php echo admin_url('admin.php?page=helloasso-configuration'); ?>">Vider les caches</a></li>
                    <li><a href="<?php echo admin_url('admin.php?page=helloasso-configuration'); ?>">Tester l'envoi email</a></li>
                </ul>
            </div>
            <div>
                <h4 style="margin: 0 0 10px 0;">🆘 Aide</h4>
                <ul style="margin: 0; padding-left: 20px;">
                    <li><a href="<?php echo admin_url('admin.php?page=helloasso-configuration#troubleshooting'); ?>">Dépannage</a></li>
                    <li><a href="<?php echo admin_url('admin.php?page=helloasso-configuration#logs'); ?>">Voir les logs</a></li>
                    <li><a href="<?php echo admin_url('admin.php?page=helloasso-configuration#cron'); ?>">Configuration CRON</a></li>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Statistiques -->
    <?php if ($api_connected && $events_count > 0): ?>
    <h2>📈 Statistiques Rapides</h2>
    
    <div style="background: white; border: 1px solid #ddd; border-radius: 8px; padding: 25px; margin: 20px 0;">
        <?php
        try {
            $events_data = $plugin->api->get_events();
            $events = isset($events_data['data']) ? $events_data['data'] : array();
            
            // Compter les événements par statut
            $stats = array(
                'Public' => 0,
                'Private' => 0,
                'Draft' => 0,
                'Disabled' => 0
            );
            
            foreach ($events as $event) {
                $state = isset($event['state']) ? $event['state'] : 'Unknown';
                if (isset($stats[$state])) {
                    $stats[$state]++;
                }
            }
            ?>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 20px;">
                <div style="text-align: center; padding: 20px; background: #e3f2fd; border-radius: 8px;">
                    <div style="font-size: 2.5em; font-weight: bold; color: #2196F3;"><?php echo $events_count; ?></div>
                    <div style="color: #666; margin-top: 5px;">Événements Total</div>
                </div>
                
                <?php if ($stats['Public'] > 0): ?>
                <div style="text-align: center; padding: 20px; background: #d4edda; border-radius: 8px;">
                    <div style="font-size: 2.5em; font-weight: bold; color: #28a745;"><?php echo $stats['Public']; ?></div>
                    <div style="color: #666; margin-top: 5px;">Publics</div>
                </div>
                <?php endif; ?>
                
                <?php if ($stats['Private'] > 0): ?>
                <div style="text-align: center; padding: 20px; background: #fff3cd; border-radius: 8px;">
                    <div style="font-size: 2.5em; font-weight: bold; color: #856404;"><?php echo $stats['Private']; ?></div>
                    <div style="color: #666; margin-top: 5px;">Privés</div>
                </div>
                <?php endif; ?>
                
                <?php if ($stats['Draft'] > 0): ?>
                <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                    <div style="font-size: 2.5em; font-weight: bold; color: #6c757d;"><?php echo $stats['Draft']; ?></div>
                    <div style="color: #666; margin-top: 5px;">Brouillons</div>
                </div>
                <?php endif; ?>
            </div>
            
            <p style="text-align: center; margin-top: 20px;">
                <a href="<?php echo admin_url('admin.php?page=helloasso-shortcodes'); ?>" class="button button-primary">
                    Afficher ces événements sur votre site →
                </a>
            </p>
            
        <?php
        } catch (Exception $e) {
            echo '<p style="color: #dc3545;">Erreur lors du chargement des statistiques : ' . esc_html($e->getMessage()) . '</p>';
        }
        ?>
    </div>
    <?php endif; ?>
    
</div>