<?php
/**
 * Classe de gestion de l'API HelloAsso
 * Version CORRIGÉE avec comptage simplifié des billets
 */

if (!defined('ABSPATH')) {
    exit;
}

class HelloAsso_API {
    
    /**
     * Récupérer les identifiants depuis wp-config.php
     */
    public function get_credentials() {
        return array(
            'client_id' => defined('HELLOASSO_CLIENT_ID') ? HELLOASSO_CLIENT_ID : '',
            'client_secret' => defined('HELLOASSO_CLIENT_SECRET') ? HELLOASSO_CLIENT_SECRET : '',
            'organization_slug' => defined('HELLOASSO_ORGANIZATION_SLUG') ? HELLOASSO_ORGANIZATION_SLUG : ''
        );
    }
    
    /**
     * Vérifier que les credentials sont configurés
     */
    public function are_credentials_configured() {
        $creds = $this->get_credentials();
        return !empty($creds['client_id']) && !empty($creds['client_secret']) && !empty($creds['organization_slug']);
    }
    
    /**
     * Obtenir le token d'accès (avec cache)
     */
    public function get_access_token($debug = false) {
        // Vérifier le cache
        $cached_token = get_transient('helloasso_access_token');
        if ($cached_token && !$debug) {
            return $cached_token;
        }
        
        $credentials = $this->get_credentials();
        
        if (empty($credentials['client_id']) || empty($credentials['client_secret'])) {
            throw new Exception('Identifiants manquants dans wp-config.php');
        }
        
        // Vérifier que cURL est disponible
        if (!function_exists('curl_init')) {
            throw new Exception('L\'extension PHP cURL n\'est pas installée sur votre serveur');
        }
        
        $url = 'https://api.helloasso.com/oauth2/token';
        
        $data = array(
            'client_id' => $credentials['client_id'],
            'client_secret' => $credentials['client_secret'],
            'grant_type' => 'client_credentials'
        );
        
        if ($debug) {
            echo '<p>URL : ' . esc_html($url) . '</p>';
            echo '<p>Client ID : ' . esc_html(substr($credentials['client_id'], 0, 10)) . '...</p>';
            echo '<p>Utilisation de cURL natif...</p>';
        }
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/x-www-form-urlencoded'
        ));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $result = curl_exec($ch);
        
        if ($result === false) {
            $curl_error = curl_error($ch);
            curl_close($ch);
            throw new Exception('Erreur cURL : ' . $curl_error);
        }
        
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($debug) {
            echo '<p>Code HTTP : ' . esc_html($http_code) . '</p>';
            echo '<p>Réponse complète : <pre>' . esc_html($result) . '</pre></p>';
        }
        
        if ($http_code !== 200) {
            $error_detail = $result ? " - Détails: " . $result : "";
            throw new Exception("Erreur HTTP {$http_code}. Vérifiez vos identifiants API{$error_detail}");
        }
        
        $response_data = json_decode($result, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Erreur lors du décodage JSON : ' . json_last_error_msg());
        }
        
        if (!isset($response_data['access_token'])) {
            throw new Exception('Token non reçu dans la réponse. Vérifiez que vos identifiants API sont corrects.');
        }
        
        if ($debug) {
            echo '<p>Scopes reçus : ' . esc_html($response_data['scope'] ?? 'N/A') . '</p>';
            echo '<p>Expire dans : ' . esc_html($response_data['expires_in'] ?? 'N/A') . ' secondes</p>';
        }
        
        // Mettre en cache le token (expires_in - 60 secondes de marge)
        $expires_in = isset($response_data['expires_in']) ? ($response_data['expires_in'] - 60) : 1800;
        set_transient('helloasso_access_token', $response_data['access_token'], $expires_in);
        
        return $response_data['access_token'];
    }
    
    /**
     * Récupérer les événements
     */
    public function get_events($debug = false) {
        // Vérifier le cache (24h)
        $cached_events = get_transient('helloasso_events_cache');
        if ($cached_events && !$debug) {
            return $cached_events;
        }
        
        $credentials = $this->get_credentials();
        $token = $this->get_access_token($debug);
        
        $url = "https://api.helloasso.com/v5/organizations/{$credentials['organization_slug']}/forms?formType=Event&pageSize=50";
        
        if ($debug) {
            echo '<p>URL événements : ' . esc_html($url) . '</p>';
            echo '<p>Organization slug : ' . esc_html($credentials['organization_slug']) . '</p>';
        }
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $token,
            'Accept: application/json'
        ));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $result = curl_exec($ch);
        
        if ($result === false) {
            $curl_error = curl_error($ch);
            curl_close($ch);
            throw new Exception('Erreur cURL événements : ' . $curl_error);
        }
        
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($debug) {
            echo '<p>Code HTTP événements : ' . esc_html($http_code) . '</p>';
            if ($http_code !== 200) {
                echo '<p>Réponse complète : <pre>' . esc_html($result) . '</pre></p>';
            }
        }
        
        if ($http_code !== 200) {
            throw new Exception("Erreur HTTP {$http_code} lors de la récupération des événements. Vérifiez votre organization_slug.");
        }
        
        $data = json_decode($result, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Erreur lors du décodage JSON des événements : ' . json_last_error_msg());
        }
        
        // Mettre en cache (24h)
        set_transient('helloasso_events_cache', $data, 86400);
        
        return $data;
    }
    
    /**
     * Récupérer les détails d'un événement (informations du formulaire uniquement)
     */
    public function get_event_details($event_slug) {
        $cache_key = 'helloasso_event_' . md5($event_slug);
        $cached_detail = get_transient($cache_key);
        if ($cached_detail) {
            return $cached_detail;
        }
        
        $credentials = $this->get_credentials();
        $token = $this->get_access_token();
        
        $url = "https://api.helloasso.com/v5/organizations/{$credentials['organization_slug']}/forms/Event/{$event_slug}";
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $token,
            'Accept: application/json'
        ));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $result = curl_exec($ch);
        
        if ($result === false) {
            curl_close($ch);
            return null;
        }
        
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code !== 200) {
            return null;
        }
        
        $data = json_decode($result, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }
        
        set_transient($cache_key, $data, 86400);
        return $data;
    }
    
    /**
     * Récupérer TOUTES les commandes d'un événement avec pagination automatique
     */
    public function get_event_orders($event_slug, $debug = false) {
        $credentials = $this->get_credentials();
        $token = $this->get_access_token();
        
        $all_orders = array();
        $page_index = 1;
        $max_pages = 100;
        
        if ($debug) {
            echo '<div style="background: #e3f2fd; padding: 15px; margin: 10px 0; border-radius: 5px;">';
            echo '<h4 style="margin-top: 0;">📦 Récupération des commandes avec pagination</h4>';
            echo '<p><strong>Événement :</strong> ' . esc_html($event_slug) . '</p>';
        }
        
        while ($page_index <= $max_pages) {
            $url = "https://api.helloasso.com/v5/organizations/{$credentials['organization_slug']}/forms/Event/{$event_slug}/orders?pageIndex={$page_index}";
            
            if ($debug) {
                echo '<p><strong>Page ' . $page_index . ' :</strong> ' . esc_html($url) . '</p>';
            }
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Authorization: Bearer ' . $token,
                'Accept: application/json'
            ));
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            $result = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($result === false) {
                if ($debug) {
                    echo '<p style="color: #d63638;">❌ <strong>Erreur cURL :</strong> ' . esc_html($error) . '</p></div>';
                }
                throw new Exception("Erreur cURL lors de la récupération des commandes : " . $error);
            }
            
            if ($http_code !== 200) {
                if ($debug) {
                    echo '<p style="color: #d63638;">❌ <strong>Erreur HTTP ' . $http_code . '</strong></p>';
                    echo '<pre style="background: #f5f5f5; padding: 10px; overflow: auto;">' . esc_html($result) . '</pre></div>';
                }
                
                if ($http_code === 403) {
                    throw new Exception("Accès refusé (403). Vérifiez les permissions de votre application API HelloAsso.");
                }
                
                throw new Exception("Erreur HTTP {$http_code} lors de la récupération des commandes : " . $result);
            }
            
            $response = json_decode($result, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                if ($debug) {
                    echo '<p style="color: #d63638;">❌ <strong>Erreur JSON :</strong> ' . esc_html(json_last_error_msg()) . '</p>';
                    echo '<pre style="background: #f5f5f5; padding: 10px; overflow: auto;">' . esc_html($result) . '</pre></div>';
                }
                throw new Exception("Erreur JSON : " . json_last_error_msg());
            }
            
            if (!isset($response['data']) || count($response['data']) === 0) {
                if ($debug) {
                    echo '<p style="color: #666;">✓ Page ' . $page_index . ' : Aucune donnée (fin de pagination)</p>';
                }
                break;
            }
            
            $orders_count = count($response['data']);
            $all_orders = array_merge($all_orders, $response['data']);
            
            if ($debug) {
                echo '<p style="color: #28a745;">✓ Page ' . $page_index . ' : <strong>' . $orders_count . '</strong> commande(s) récupérée(s)</p>';
            }
            
            if ($orders_count < 20) {
                if ($debug) {
                    echo '<p style="color: #666;">✓ Dernière page détectée (moins de 20 commandes)</p>';
                }
                break;
            }
            
            $page_index++;
        }
        
        if ($debug) {
            echo '<p style="background: #d4edda; padding: 10px; border-radius: 3px; margin-top: 15px;">';
            echo '<strong>📊 Total :</strong> ' . count($all_orders) . ' commande(s) récupérée(s) sur ' . ($page_index) . ' page(s)';
            echo '</p></div>';
        }
        
        if ($page_index > $max_pages) {
            error_log("HelloAsso: Nombre maximum de pages atteint ({$max_pages}) pour l'événement {$event_slug}");
        }
        
        return $all_orders;
    }
    
    /**
     * Récupérer le nombre de places vendues pour un événement
     * VERSION CORRIGÉE avec logique simplifiée
     */
    public function get_event_sold_count($event_slug, $debug = false) {
        $cache_key = 'helloasso_sold_' . md5($event_slug);
        $cached_count = get_transient($cache_key);
        if ($cached_count !== false && !$debug) {
            return $cached_count;
        }
        
        try {
            $orders = $this->get_event_orders($event_slug, $debug);
            
            if ($debug) {
                error_log('=== HelloAsso Debug START pour ' . $event_slug . ' ===');
                error_log('Nombre de commandes récupérées : ' . count($orders));
            }
            
            $tiers_count = array();
            $total_sold = 0;
            
            $debug_stats = array(
                'total_orders' => count($orders),
                'valid_orders' => 0,
                'contested_orders' => 0,
                'refunded_orders' => 0,
                'donations_ignored' => 0,
                'registrations_counted' => 0,
                'order_states' => array(),
                'items_detail' => array(),
                'raw_orders_sample' => array()
            );
            
            $order_index = 0;
            
            foreach ($orders as $order) {
                $order_index++;
                
                // Vérifier le statut du PAIEMENT (le plus fiable)
                $order_payment_state = 'Unknown';
                if (isset($order['payments']) && is_array($order['payments']) && count($order['payments']) > 0) {
                    $order_payment_state = isset($order['payments'][0]['state']) ? $order['payments'][0]['state'] : 'Unknown';
                }
                
                // Sauvegarder échantillon pour debug
                if ($debug && $order_index <= 3) {
                    $debug_stats['raw_orders_sample'][] = $order;
                }
                
                // Compter les états
                if (!isset($debug_stats['order_states'][$order_payment_state])) {
                    $debug_stats['order_states'][$order_payment_state] = 0;
                }
                $debug_stats['order_states'][$order_payment_state]++;
                
                // ⚠️ RÈGLE CRITIQUE : Ignorer les paiements Contested
                if ($order_payment_state === 'Contested') {
                    $debug_stats['contested_orders']++;
                    if ($debug) {
                        error_log('HelloAsso Debug Order ' . $order_index . ': IGNORÉE - payment.state = Contested');
                    }
                    continue;
                }
                
                // Ignorer aussi Refunded, Refunding, Canceled
                if (in_array($order_payment_state, array('Refunded', 'Refunding', 'Canceled'))) {
                    $debug_stats['refunded_orders']++;
                    if ($debug) {
                        error_log('HelloAsso Debug Order ' . $order_index . ': IGNORÉE - payment.state = ' . $order_payment_state);
                    }
                    continue;
                }
                
                // Commande valide
                $debug_stats['valid_orders']++;
                
                if ($debug) {
                    error_log('HelloAsso Debug Order ' . $order_index . ': VALIDE - payment.state = ' . $order_payment_state);
                }
                
                // Vérifier items
                if (!isset($order['items']) || !is_array($order['items'])) {
                    if ($debug) {
                        error_log('HelloAsso Debug Order ' . $order_index . ': Pas d\'items');
                    }
                    continue;
                }
                
                // ========================================
                // LOGIQUE SIMPLIFIÉE : 
                // 1 item "Registration" ou "Payment" = 1 BILLET
                // Items "Donation" = IGNORÉS
                // ========================================
                
                foreach ($order['items'] as $item_idx => $item) {
                    $item_type = isset($item['type']) ? $item['type'] : 'Unknown';
                    $item_name = isset($item['name']) ? $item['name'] : 'Sans nom';
                    $item_state = isset($item['state']) ? $item['state'] : 'Unknown';
                    
                    // Debug : enregistrer tous les items
                    if ($debug) {
                        $debug_stats['items_detail'][] = array(
                            'order_id' => isset($order['id']) ? substr(strval($order['id']), -8) : 'N/A',
                            'payment_state' => $order_payment_state,
                            'item_type' => $item_type,
                            'item_name' => $item_name,
                            'item_state' => $item_state,
                            'has_ticketUrl' => isset($item['ticketUrl']),
                            'item_amount' => isset($item['amount']) ? $item['amount'] : 'N/A',
                            'counted' => false,
                            'reason_not_counted' => ''
                        );
                        $last_index = count($debug_stats['items_detail']) - 1;
                    }
                    
                    // ❌ IGNORER les dons
                    if ($item_type === 'Donation') {
                        $debug_stats['donations_ignored']++;
                        if ($debug) {
                            $debug_stats['items_detail'][$last_index]['reason_not_counted'] = 'Type = Donation (ignoré)';
                        }
                        continue;
                    }
                    
                    // ❌ IGNORER les types autres que Registration/Payment
                    if (!in_array($item_type, array('Registration', 'Payment'))) {
                        if ($debug) {
                            $debug_stats['items_detail'][$last_index]['reason_not_counted'] = 'Type = ' . $item_type . ' (ignoré)';
                        }
                        continue;
                    }
                    
                    // ❌ IGNORER les items remboursés/annulés
                    if (in_array($item_state, array('Refunded', 'Refunding', 'Canceled'))) {
                        if ($debug) {
                            $debug_stats['items_detail'][$last_index]['reason_not_counted'] = 'Item state = ' . $item_state . ' (ignoré)';
                        }
                        continue;
                    }
                    
                    // ✅ COMPTER : 1 item Registration/Payment = 1 BILLET
                    $tier_label = $item_name;
                    if (!isset($tiers_count[$tier_label])) {
                        $tiers_count[$tier_label] = 0;
                    }
                    $tiers_count[$tier_label]++;
                    $total_sold++;
                    $debug_stats['registrations_counted']++;
                    
                    if ($debug) {
                        $debug_stats['items_detail'][$last_index]['counted'] = true;
                        $debug_stats['items_detail'][$last_index]['reason_not_counted'] = '✓ COMPTÉ (1 billet)';
                        error_log('HelloAsso Debug Order ' . $order_index . ' Item #' . $item_idx . ': ✓ COMPTÉ - "' . $tier_label . '"');
                    }
                }
            }
            
            $result_data = array(
                'sold' => $total_sold,
                'tiers' => $tiers_count,
                'orders_count' => $debug_stats['valid_orders'],
                'debug_info' => $debug_stats
            );
            
            // Afficher debug si demandé
            if ($debug) {
                echo '<div style="background: #fff3cd; padding: 20px; margin: 10px 0; border-radius: 5px; border: 2px solid #ffc107;">';
                echo '<h3 style="margin-top: 0; color: #856404;">🔍 DEBUG DÉTAILLÉ - ' . esc_html($event_slug) . '</h3>';
                
                // Résumé
                echo '<div style="background: white; padding: 15px; border-radius: 5px; margin-bottom: 15px;">';
                echo '<h4 style="margin-top: 0;">📊 Résumé</h4>';
                echo '<table style="width: 100%; font-size: 0.95em;">';
                echo '<tr><td style="padding: 5px;"><strong>Total commandes :</strong></td><td style="padding: 5px;">' . $debug_stats['total_orders'] . '</td></tr>';
                echo '<tr style="background: #f0f0f0;"><td style="padding: 5px;"><strong>Commandes valides :</strong></td><td style="padding: 5px; color: #28a745; font-weight: bold;">' . $debug_stats['valid_orders'] . '</td></tr>';
                echo '<tr><td style="padding: 5px;"><strong>Contestées :</strong></td><td style="padding: 5px; color: #d63638;">' . $debug_stats['contested_orders'] . '</td></tr>';
                echo '<tr style="background: #f0f0f0;"><td style="padding: 5px;"><strong>Remboursées :</strong></td><td style="padding: 5px; color: #d63638;">' . $debug_stats['refunded_orders'] . '</td></tr>';
                echo '<tr><td style="padding: 5px;"><strong>Dons ignorés :</strong></td><td style="padding: 5px; color: #856404;">' . $debug_stats['donations_ignored'] . '</td></tr>';
                echo '<tr style="background: #e3f2fd;"><td style="padding: 5px;"><strong>🎟️ BILLETS COMPTÉS :</strong></td><td style="padding: 5px; color: #2196F3; font-weight: bold; font-size: 1.3em;">' . $debug_stats['registrations_counted'] . '</td></tr>';
                echo '</table>';
                echo '</div>';
                
                // États des paiements
                if (!empty($debug_stats['order_states'])) {
                    echo '<div style="background: white; padding: 15px; border-radius: 5px; margin-bottom: 15px;">';
                    echo '<h4 style="margin-top: 0;">📋 États des paiements</h4>';
                    echo '<ul style="margin: 0; padding-left: 20px;">';
                    foreach ($debug_stats['order_states'] as $state => $count) {
                        $color = $state === 'Authorized' ? '#28a745' : ($state === 'Contested' ? '#d63638' : '#666');
                        echo '<li style="padding: 3px 0;"><span style="color: ' . $color . '; font-weight: bold;">' . esc_html($state) . '</span>: ' . $count . ' commande(s)</li>';
                    }
                    echo '</ul>';
                    echo '</div>';
                }
                
                // Détail par catégorie
                if (!empty($tiers_count)) {
                    echo '<div style="background: white; padding: 15px; border-radius: 5px; margin-bottom: 15px;">';
                    echo '<h4 style="margin-top: 0;">🎫 Détail par catégorie</h4>';
                    echo '<ul style="margin: 0; padding-left: 20px;">';
                    foreach ($tiers_count as $tier => $count) {
                        echo '<li style="padding: 3px 0;"><strong>' . esc_html($tier) . '</strong>: ' . $count . ' place(s)</li>';
                    }
                    echo '</ul>';
                    echo '</div>';
                }
                
                echo '</div>';
            }
            
            // Mettre en cache (24h)
            set_transient($cache_key, $result_data, 86400);
            
            return $result_data;
            
        } catch (Exception $e) {
            if ($debug) {
                echo '<p style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;">';
                echo '<strong>❌ Erreur :</strong> ' . esc_html($e->getMessage());
                echo '</p>';
            }
            
            error_log('HelloAsso: Erreur get_event_sold_count pour ' . $event_slug . ' : ' . $e->getMessage());
            
            return array(
                'sold' => 0,
                'tiers' => array(),
                'orders_count' => 0,
                'error' => $e->getMessage()
            );
        }
    }
}