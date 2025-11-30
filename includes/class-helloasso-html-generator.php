<?php
/**
 * Classe de génération d'emails HTML
 */

if (!defined('ABSPATH')) {
    exit;
}

class HelloAsso_HTML_Generator {
    
    private $api;
    
    public function __construct($api) {
        $this->api = $api;
    }
    
    /**
     * Construire le HTML de l'email
     * 
     * @param array $events Liste des événements
     * @return string HTML complet de l'email
     */
    public function generate($events) {
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
