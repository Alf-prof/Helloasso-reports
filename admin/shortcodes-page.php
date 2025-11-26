<?php
/**
 * Page des shortcodes - Documentation complète
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1>🎨 Shortcodes HelloAsso</h1>
    
    <div class="notice notice-info">
        <p><strong>Utilisez les shortcodes pour afficher vos événements HelloAsso sur votre site WordPress.</strong></p>
        <p>Copiez-collez simplement le code dans n'importe quelle page, article ou widget de texte.</p>
    </div>
    
    <!-- Onglets -->
    <h2 class="nav-tab-wrapper">
        <a href="#tab-liste" class="nav-tab nav-tab-active" data-tab="liste">📋 Liste d'événements</a>
        <a href="#tab-unique" class="nav-tab" data-tab="unique">🎫 Événement unique</a>
        <a href="#tab-exemples" class="nav-tab" data-tab="exemples">💡 Exemples pratiques</a>
    </h2>

    <!-- TAB 1 : Liste d'événements -->
    <div id="tab-liste" class="tab-content">
        <h2>📝 Shortcode : Liste d'événements</h2>
        
        <div style="background: white; border: 1px solid #ddd; border-radius: 8px; padding: 25px; margin: 20px 0;">
            <h3>Affichage simple</h3>
            <div style="background: #f5f5f5; padding: 15px; border-radius: 5px; font-family: monospace; font-size: 1.1em; margin: 15px 0;">
                [helloasso_events]
            </div>
            <p>Ce shortcode affiche par défaut les 10 prochains événements avec toutes les informations disponibles.</p>
            
            <h4 style="margin-top: 30px;">Aperçu :</h4>
            <div style="background: #f9f9f9; padding: 20px; border-radius: 5px; border: 2px dashed #ddd;">
                <p style="margin: 0; color: #666; font-style: italic;">Les événements s'afficheront ici avec :</p>
                <ul style="color: #666;">
                    <li>Titre et date de l'événement</li>
                    <li>Nombre total de places vendues</li>
                    <li>Détail par catégorie de billets</li>
                    <li>Lien vers la page HelloAsso</li>
                    <li>État de l'événement (Public, Privé, etc.)</li>
                </ul>
            </div>
        </div>
        
        <h2>⚙️ Options Disponibles</h2>
        
        <div style="background: white; border: 1px solid #ddd; border-radius: 8px; padding: 25px; margin: 20px 0;">
            <table class="widefat" style="margin-top: 15px;">
                <thead>
                    <tr>
                        <th style="padding: 12px; width: 20%;">Option</th>
                        <th style="padding: 12px; width: 15%;">Valeurs</th>
                        <th style="padding: 12px; width: 15%;">Défaut</th>
                        <th style="padding: 12px; width: 50%;">Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="padding: 12px;"><code>limit</code></td>
                        <td style="padding: 12px;">Nombre entier</td>
                        <td style="padding: 12px;">10</td>
                        <td style="padding: 12px;">Nombre maximum d'événements à afficher</td>
                    </tr>
                    <tr style="background: #f9f9f9;">
                        <td style="padding: 12px;"><code>show_sold_out</code></td>
                        <td style="padding: 12px;">yes / no</td>
                        <td style="padding: 12px;">yes</td>
                        <td style="padding: 12px;">Afficher ou masquer les événements complets</td>
                    </tr>
                    <tr>
                        <td style="padding: 12px;"><code>future_only</code></td>
                        <td style="padding: 12px;">yes / no</td>
                        <td style="padding: 12px;">no</td>
                        <td style="padding: 12px;">Afficher uniquement les événements à venir</td>
                    </tr>
                    <tr style="background: #f9f9f9;">
                        <td style="padding: 12px;"><code>has_bookings_only</code></td>
                        <td style="padding: 12px;">yes / no</td>
                        <td style="padding: 12px;">no</td>
                        <td style="padding: 12px;">Afficher uniquement les événements avec réservations</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <h2>💡 Exemples d'Utilisation</h2>
        
        <div style="display: grid; gap: 20px; margin: 20px 0;">
            
            <div style="background: white; border: 1px solid #ddd; border-radius: 8px; padding: 25px;">
                <h3 style="margin: 0 0 15px 0; color: #2196F3;">📅 Afficher 5 événements</h3>
                <div style="background: #f5f5f5; padding: 15px; border-radius: 5px; font-family: monospace; margin: 10px 0;">
                    [helloasso_events limit="5"]
                </div>
                <p style="margin: 10px 0 0 0; color: #666; font-size: 0.95em;">
                    <strong>Utilisation :</strong> Page d'accueil pour mettre en avant quelques événements
                </p>
            </div>
            
            <div style="background: white; border: 1px solid #ddd; border-radius: 8px; padding: 25px;">
                <h3 style="margin: 0 0 15px 0; color: #2196F3;">🎯 Événements futurs uniquement</h3>
                <div style="background: #f5f5f5; padding: 15px; border-radius: 5px; font-family: monospace; margin: 10px 0;">
                    [helloasso_events future_only="yes"]
                </div>
                <p style="margin: 10px 0 0 0; color: #666; font-size: 0.95em;">
                    <strong>Utilisation :</strong> Ne pas afficher les événements passés
                </p>
            </div>
            
            <div style="background: white; border: 1px solid #ddd; border-radius: 8px; padding: 25px;">
                <h3 style="margin: 0 0 15px 0; color: #2196F3;">🎟️ Avec réservations</h3>
                <div style="background: #f5f5f5; padding: 15px; border-radius: 5px; font-family: monospace; margin: 10px 0;">
                    [helloasso_events has_bookings_only="yes"]
                </div>
                <p style="margin: 10px 0 0 0; color: #666; font-size: 0.95em;">
                    <strong>Utilisation :</strong> Événements avec inscriptions uniquement
                </p>
            </div>
            
            <div style="background: white; border: 1px solid #ddd; border-radius: 8px; padding: 25px;">
                <h3 style="margin: 0 0 15px 0; color: #2196F3;">⭐ Configuration complète</h3>
                <div style="background: #f5f5f5; padding: 15px; border-radius: 5px; font-family: monospace; margin: 10px 0;">
                    [helloasso_events limit="8" future_only="yes" show_sold_out="no"]
                </div>
                <p style="margin: 10px 0 0 0; color: #666; font-size: 0.95em;">
                    <strong>Utilisation :</strong> 8 événements futurs avec places disponibles
                </p>
            </div>
            
        </div>
    </div>

    <!-- TAB 2 : Événement unique -->
    <div id="tab-unique" class="tab-content" style="display: none;">
        <h2>🎫 Shortcode : Événement unique</h2>
        
        <div style="background: #e3f2fd; border: 2px solid #2196F3; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <h3 style="margin-top: 0;">🆕 Nouveau shortcode !</h3>
            <p style="font-size: 1.05em; margin: 0;">
                Ce shortcode permet d'afficher <strong>UN SEUL événement</strong> avec ses statistiques détaillées, 
                idéal pour des pages dédiées ou des widgets.
            </p>
        </div>
        
        <div style="background: white; border: 1px solid #ddd; border-radius: 8px; padding: 25px; margin: 20px 0;">
            <h3>Utilisation de base</h3>
            <div style="background: #f5f5f5; padding: 15px; border-radius: 5px; font-family: monospace; font-size: 1.1em; margin: 15px 0;">
                [helloasso_event slug="votre-slug-evenement"]
            </div>
            
            <div style="background: #fff3cd; padding: 15px; border-radius: 5px; margin: 15px 0;">
                <strong>⚠️ Important :</strong> L'attribut <code>slug</code> est <strong>OBLIGATOIRE</strong>
            </div>
            
            <h4 style="margin-top: 25px;">📋 Comment trouver le slug d'un événement ?</h4>
            <p>Le slug est le dernier segment de l'URL HelloAsso de votre événement :</p>
            <div style="background: #f9f9f9; padding: 15px; border-radius: 5px; margin: 10px 0;">
                <code style="font-size: 0.9em;">https://www.helloasso.com/associations/mon-asso/evenements/<strong style="color: #2196F3;">creche-vivante-23-decembre-2024</strong></code>
                <p style="margin: 10px 0 0 0; font-size: 0.9em;">
                    → Le slug est : <code style="background: #2196F3; color: white; padding: 3px 8px; border-radius: 3px;">creche-vivante-23-decembre-2024</code>
                </p>
            </div>
        </div>
        
        <h2>⚙️ Attributs disponibles</h2>
        
        <div style="background: white; border: 1px solid #ddd; border-radius: 8px; padding: 25px; margin: 20px 0;">
            <table class="widefat" style="margin-top: 15px;">
                <thead>
                    <tr>
                        <th style="padding: 12px; width: 20%;">Attribut</th>
                        <th style="padding: 12px; width: 25%;">Valeurs possibles</th>
                        <th style="padding: 12px; width: 15%;">Défaut</th>
                        <th style="padding: 12px; width: 40%;">Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style="background: #fff3cd;">
                        <td style="padding: 12px;"><code><strong>slug</strong></code></td>
                        <td style="padding: 12px;">Texte</td>
                        <td style="padding: 12px;"><strong>OBLIGATOIRE</strong></td>
                        <td style="padding: 12px;">Le slug de l'événement à afficher</td>
                    </tr>
                    <tr>
                        <td style="padding: 12px;"><code>display</code></td>
                        <td style="padding: 12px;">full / count / details</td>
                        <td style="padding: 12px;">full</td>
                        <td style="padding: 12px;">Mode d'affichage (voir ci-dessous)</td>
                    </tr>
                    <tr style="background: #f9f9f9;">
                        <td style="padding: 12px;"><code>total_places</code></td>
                        <td style="padding: 12px;">Nombre entier</td>
                        <td style="padding: 12px;">0</td>
                        <td style="padding: 12px;">Nombre total de places (affiche les places restantes)</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <h2>🎨 Modes d'affichage</h2>
        
        <div style="display: grid; gap: 20px; margin: 20px 0;">
            
            <!-- Mode FULL -->
            <div style="background: white; border: 1px solid #ddd; border-radius: 8px; padding: 25px;">
                <h3 style="margin: 0 0 15px 0; color: #2196F3;">1️⃣ Mode FULL (complet)</h3>
                
                <h4>Sans total_places :</h4>
                <div style="background: #f5f5f5; padding: 15px; border-radius: 5px; font-family: monospace; margin: 10px 0;">
                    [helloasso_event slug="mon-evenement"]
                </div>
                <p style="color: #666; font-size: 0.95em;">
                    ✅ Titre + Places vendues + Détail par catégorie + Lien
                </p>
                
                <h4 style="margin-top: 20px;">Avec total_places :</h4>
                <div style="background: #f5f5f5; padding: 15px; border-radius: 5px; font-family: monospace; margin: 10px 0;">
                    [helloasso_event slug="mon-evenement" total_places="100"]
                </div>
                <p style="color: #666; font-size: 0.95em;">
                    ✅ Titre + Statistiques 3 colonnes + Barre de progression + Badge "COMPLET"<br>
                    ❌ Pas de détail par catégorie
                </p>
            </div>
            
            <!-- Mode COUNT -->
            <div style="background: white; border: 1px solid #ddd; border-radius: 8px; padding: 25px;">
                <h3 style="margin: 0 0 15px 0; color: #2196F3;">2️⃣ Mode COUNT (nombre uniquement)</h3>
                
                <h4>Sans total :</h4>
                <div style="background: #f5f5f5; padding: 15px; border-radius: 5px; font-family: monospace; margin: 10px 0;">
                    [helloasso_event slug="mon-evenement" display="count"]
                </div>
                <p style="color: #666; font-size: 0.95em;">
                    Affiche : <code style="background: #e3f2fd; padding: 3px 8px; border-radius: 3px;">54</code>
                </p>
                
                <h4 style="margin-top: 20px;">Avec total :</h4>
                <div style="background: #f5f5f5; padding: 15px; border-radius: 5px; font-family: monospace; margin: 10px 0;">
                    [helloasso_event slug="mon-evenement" display="count" total_places="100"]
                </div>
                <p style="color: #666; font-size: 0.95em;">
                    Affiche : <code style="background: #e3f2fd; padding: 3px 8px; border-radius: 3px;">54 / 100</code>
                </p>
                
                <p style="background: #e3f2fd; padding: 10px; border-radius: 5px; margin-top: 15px; font-size: 0.95em;">
                    💡 <strong>Idéal pour</strong> : Intégrer dans un texte ou un widget compact
                </p>
            </div>
            
            <!-- Mode DETAILS -->
            <div style="background: white; border: 1px solid #ddd; border-radius: 8px; padding: 25px;">
                <h3 style="margin: 0 0 15px 0; color: #2196F3;">3️⃣ Mode DETAILS (catégories uniquement)</h3>
                
                <div style="background: #f5f5f5; padding: 15px; border-radius: 5px; font-family: monospace; margin: 10px 0;">
                    [helloasso_event slug="mon-evenement" display="details"]
                </div>
                <p style="color: #666; font-size: 0.95em;">
                    ✅ Liste des catégories avec nombre de places par tarif<br>
                    ❌ Pas de titre ni de lien
                </p>
                
                <div style="background: #f9f9f9; padding: 15px; border-radius: 5px; margin-top: 15px;">
                    <strong>Exemple de rendu :</strong>
                    <ul style="margin: 10px 0; padding-left: 20px;">
                        <li>Normal → 45 places</li>
                        <li>Réduit → 32 places</li>
                        <li>Enfant → 18 places</li>
                    </ul>
                </div>
                
                <p style="background: #e3f2fd; padding: 10px; border-radius: 5px; margin-top: 15px; font-size: 0.95em;">
                    💡 <strong>Idéal pour</strong> : Sidebar ou section détaillée sans répéter le titre
                </p>
            </div>
            
        </div>
    </div>

    <!-- TAB 3 : Exemples pratiques -->
    <div id="tab-exemples" class="tab-content" style="display: none;">
        <h2>💡 Exemples Pratiques</h2>
        
        <!-- Exemple 1 -->
        <div style="background: white; border: 1px solid #ddd; border-radius: 8px; padding: 25px; margin: 20px 0;">
            <h3 style="margin: 0 0 15px 0; color: #2196F3;">📊 Afficher les places restantes avec barre de progression</h3>
            <div style="background: #f5f5f5; padding: 15px; border-radius: 5px; font-family: monospace; margin: 10px 0;">
                [helloasso_event slug="gala-2025" total_places="150"]
            </div>
            <div style="background: #f9f9f9; padding: 20px; border-radius: 5px; margin-top: 15px;">
                <strong>Rendu :</strong>
                <ul style="margin: 10px 0; padding-left: 20px;">
                    <li>✅ Titre de l'événement</li>
                    <li>✅ 3 colonnes : Places vendues / Restantes / Total</li>
                    <li>✅ Barre de progression visuelle</li>
                    <li>✅ Pourcentage vendu</li>
                    <li>✅ Badge "COMPLET" si applicable</li>
                </ul>
            </div>
        </div>
        
        <!-- Exemple 2 -->
        <div style="background: white; border: 1px solid #ddd; border-radius: 8px; padding: 25px; margin: 20px 0;">
            <h3 style="margin: 0 0 15px 0; color: #2196F3;">📝 Intégrer dans un texte</h3>
            <div style="background: #f5f5f5; padding: 15px; border-radius: 5px; font-family: monospace; margin: 10px 0; white-space: pre-wrap;">Déjà &lt;strong&gt;[helloasso_event slug="mon-evenement" display="count"]&lt;/strong&gt; personnes inscrites !</div>
            <div style="background: #f9f9f9; padding: 15px; border-radius: 5px; margin-top: 15px;">
                <strong>Rendu :</strong> Déjà <strong>54</strong> personnes inscrites !
            </div>
        </div>
        
        <!-- Exemple 3 -->
        <div style="background: white; border: 1px solid #ddd; border-radius: 8px; padding: 25px; margin: 20px 0;">
            <h3 style="margin: 0 0 15px 0; color: #2196F3;">🎯 Widget sidebar avec détails</h3>
            <div style="background: #f5f5f5; padding: 15px; border-radius: 5px; font-family: monospace; margin: 10px 0; white-space: pre-wrap;">&lt;div class="widget"&gt;
    &lt;h3&gt;Inscriptions par catégorie&lt;/h3&gt;
    [helloasso_event slug="mon-evenement" display="details"]
&lt;/div&gt;</div>
            <p style="color: #666; font-size: 0.95em; margin-top: 10px;">
                Parfait pour afficher la répartition des places sans répéter le titre de l'événement.
            </p>
        </div>
        
        <!-- Exemple 4 -->
        <div style="background: white; border: 1px solid #ddd; border-radius: 8px; padding: 25px; margin: 20px 0;">
            <h3 style="margin: 0 0 15px 0; color: #2196F3;">🎪 Page avec plusieurs événements</h3>
            <div style="background: #f5f5f5; padding: 15px; border-radius: 5px; font-family: monospace; margin: 10px 0; white-space: pre-wrap;">&lt;h2&gt;Concert de Noël&lt;/h2&gt;
[helloasso_event slug="concert-noel" total_places="200"]

&lt;h2&gt;Marché de Noël&lt;/h2&gt;
[helloasso_event slug="marche-noel" total_places="500"]

&lt;h2&gt;Crèche Vivante&lt;/h2&gt;
[helloasso_event slug="creche-vivante" total_places="300"]</div>
            <p style="color: #666; font-size: 0.95em; margin-top: 10px;">
                Créez facilement une page dédiée avec plusieurs événements mis en avant.
            </p>
        </div>
        
        <!-- Exemple 5 -->
        <div style="background: white; border: 1px solid #ddd; border-radius: 8px; padding: 25px; margin: 20px 0;">
            <h3 style="margin: 0 0 15px 0; color: #2196F3;">🎨 Combinaison liste + focus</h3>
            <div style="background: #f5f5f5; padding: 15px; border-radius: 5px; font-family: monospace; margin: 10px 0; white-space: pre-wrap;">&lt;!-- Événement mis en avant --&gt;
&lt;div class="highlight-event"&gt;
    [helloasso_event slug="evenement-special" total_places="100"]
&lt;/div&gt;

&lt;!-- Liste des autres événements --&gt;
&lt;h2&gt;Tous nos événements&lt;/h2&gt;
[helloasso_events limit="10" future_only="yes"]</div>
            <p style="color: #666; font-size: 0.95em; margin-top: 10px;">
                Combinez les deux shortcodes pour mettre un événement en avant tout en affichant la liste complète.
            </p>
        </div>
    </div>
    
    <!-- Section commune : Mode debug et test -->
    <hr style="margin: 40px 0;">
    
    <h2>🔍 Mode Debug</h2>
    
    <div style="background: white; border: 1px solid #ddd; border-radius: 8px; padding: 25px; margin: 20px 0;">
        <p>Pour afficher des informations de débogage, ajoutez <code>?debug_ha</code> à l'URL de votre page.</p>
        <div style="background: #fff3cd; padding: 15px; border-radius: 5px; margin: 15px 0;">
            <strong>Exemple :</strong> <code>https://votresite.com/evenements/?debug_ha</code>
        </div>
        <p style="color: #666; font-size: 0.95em; margin: 10px 0 0 0;">
            ⚠️ Ce mode affiche des informations techniques. À utiliser uniquement pour le dépannage.
        </p>
    </div>
    
    <h2>🧪 Tester en Direct</h2>
    
    <div style="background: white; border: 1px solid #ddd; border-radius: 8px; padding: 25px; margin: 20px 0;">
        <p>Créez une page de test pour voir le rendu :</p>
        <ol style="line-height: 2;">
            <li>Allez dans <strong>Pages > Ajouter</strong></li>
            <li>Donnez un titre (ex: "Test Shortcodes HelloAsso")</li>
            <li>Ajoutez le shortcode dans le contenu</li>
            <li>Sauvegardez comme <strong>Brouillon</strong></li>
            <li>Cliquez sur <strong>Aperçu</strong></li>
        </ol>
        
        <p style="background: #e3f2fd; padding: 15px; border-radius: 5px; margin-top: 20px;">
            💡 <strong>Astuce :</strong> Testez plusieurs shortcodes sur la même page pour comparer les rendus
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

.widefat th,
.widefat td {
    vertical-align: middle;
}
</style>