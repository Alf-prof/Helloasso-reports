<?php
/**
 * Classe de génération de rapports CSV
 */

if (!defined('ABSPATH')) {
    exit;
}

class HelloAsso_CSV_Generator {
    
    private $api;
    
    public function __construct($api) {
        $this->api = $api;
    }
    
    /**
     * Générer le contenu CSV
     */
    public function generate($events) {
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
     * Créer un fichier CSV temporaire
     * 
     * @param string $csv_content Contenu du CSV
     * @param bool $is_test Mode test avec logs détaillés
     * @return string Chemin du fichier temporaire créé
     * @throws Exception Si impossible de créer le fichier
     */
    public function create_temp_file($csv_content, $is_test = false) {
        $csv_filename = 'rapport-helloasso-' . date('Y-m-d-His') . '.csv';
        
        // Créer le dossier temp s'il n'existe pas
        $temp_dir = WP_CONTENT_DIR . '/temp';
        
        if ($is_test) {
            error_log('HelloAsso CSV: Dossier temp cible: ' . $temp_dir);
        }
        
        if (!file_exists($temp_dir)) {
            if ($is_test) {
                error_log('HelloAsso CSV: Le dossier temp n\'existe pas, création...');
            }
            
            $mkdir_result = wp_mkdir_p($temp_dir);
            
            if ($is_test) {
                error_log('HelloAsso CSV: Résultat de wp_mkdir_p: ' . ($mkdir_result ? 'SUCCESS' : 'FAILED'));
            }
            
            if (!$mkdir_result) {
                throw new Exception('Impossible de créer le dossier temp: ' . $temp_dir);
            }
            
            // Créer un fichier .htaccess pour protéger le dossier
            $htaccess_content = "# Protect temp directory\n<Files *.csv>\n  Order Allow,Deny\n  Deny from all\n</Files>";
            file_put_contents($temp_dir . '/.htaccess', $htaccess_content);
        }
        
        if ($is_test) {
            error_log('HelloAsso CSV: Dossier temp existe: ' . (file_exists($temp_dir) ? 'OUI' : 'NON'));
            error_log('HelloAsso CSV: Dossier temp accessible en écriture: ' . (is_writable($temp_dir) ? 'OUI' : 'NON'));
            if (file_exists($temp_dir)) {
                error_log('HelloAsso CSV: Permissions du dossier: ' . substr(sprintf('%o', fileperms($temp_dir)), -4));
            }
        }
        
        $temp_file = $temp_dir . '/' . $csv_filename;
        
        if ($is_test) {
            error_log('HelloAsso CSV: Chemin fichier: ' . $temp_file);
        }
        
        $write_result = file_put_contents($temp_file, $csv_content);
        
        if ($write_result === false) {
            if ($is_test) {
                error_log('HelloAsso CSV: ÉCHEC file_put_contents');
                $last_error = error_get_last();
                if ($last_error) {
                    error_log('HelloAsso CSV: Erreur PHP: ' . print_r($last_error, true));
                }
            }
            throw new Exception('Impossible de créer le fichier CSV dans: ' . $temp_dir);
        }
        
        if ($is_test) {
            error_log('HelloAsso CSV: Fichier créé, taille: ' . filesize($temp_file) . ' octets');
        }
        
        return $temp_file;
    }
    
    /**
     * Supprimer un fichier temporaire
     * 
     * @param string $temp_file Chemin du fichier à supprimer
     * @param bool $is_test Mode test avec logs
     * @return bool Résultat de la suppression
     */
    public function delete_temp_file($temp_file, $is_test = false) {
        if (file_exists($temp_file)) {
            $result = unlink($temp_file);
            if ($is_test) {
                error_log('HelloAsso CSV: Fichier temporaire supprimé: ' . ($result ? 'OUI' : 'NON'));
            }
            return $result;
        }
        return false;
    }
}
