<?php
/**
 * Inspecteur de données des jeux
 * 
 * Cet outil permet d'examiner la structure des données des jeux
 * pour faciliter le développement et le débogage.
 */

class Sisme_Admin_Data_Inspector {
    public static function render() {
        // Vérifier si l'utilisateur a sélectionné un jeu à inspecter
        $inspect_game_id = isset($_GET['inspect_game']) ? intval($_GET['inspect_game']) : 0;
        
        echo '<div class="wrap">';
        echo '<h1>Inspecteur de données des jeux</h1>';
        echo '<p>Cet outil vous permet d\'examiner la structure des données des jeux pour faciliter le développement et le débogage.</p>';
        
        // Liste des jeux pour sélection
        self::render_game_selector($inspect_game_id);
        
        // Si un jeu est sélectionné, afficher ses données
        if ($inspect_game_id > 0) {
            self::render_game_data($inspect_game_id);
        }
        
        echo '</div>';
    }
    
    private static function render_game_selector($selected_id) {
        // Récupérer tous les jeux (termes de taxonomie)
        $games = get_terms([
            'taxonomy' => 'post_tag',
            'hide_empty' => false,
            'number' => 100
        ]);
        
        echo '<div class="sisme-game-selector">';
        echo '<h2>Sélectionner un jeu à inspecter</h2>';
        echo '<form method="get">';
        echo '<input type="hidden" name="page" value="sisme-games-data-inspector">';
        echo '<select name="inspect_game">';
        echo '<option value="0">-- Sélectionner un jeu --</option>';
        
        foreach ($games as $game) {
            printf(
                '<option value="%d" %s>%s (ID: %d)</option>',
                $game->term_id,
                selected($selected_id, $game->term_id, false),
                esc_html($game->name),
                $game->term_id
            );
        }
        
        echo '</select>';
        echo '<button type="submit" class="button">Inspecter</button>';
        echo '</form>';
        echo '</div>';
    }
    
    private static function render_game_data($game_id) {
        // Récupérer toutes les métadonnées du jeu
        $meta_data = get_term_meta($game_id);
        
        echo '<div class="sisme-data-inspector">';
        echo '<h2>Données du jeu ID: ' . $game_id . '</h2>';
        
        // Afficher le terme lui-même
        $term = get_term($game_id, 'post_tag');
        echo '<div class="data-section">';
        echo '<h3>Terme de taxonomie</h3>';
        echo '<pre>' . print_r($term, true) . '</pre>';
        echo '</div>';
        
        // Parcourir et afficher toutes les métadonnées
        foreach ($meta_data as $key => $values) {
            echo '<div class="data-section">';
            echo '<h3>Métadonnée: ' . esc_html($key) . '</h3>';
            
            // Pour chaque valeur de cette métadonnée
            foreach ($values as $index => $value) {
                $unserialized = maybe_unserialize($value);
                
                echo '<div class="meta-value">';
                echo '<h4>Valeur #' . ($index + 1) . '</h4>';
                
                // Afficher le type de données
                echo '<p><strong>Type:</strong> ' . gettype($unserialized) . '</p>';
                
                // Afficher la structure de données
                echo '<pre class="data-structure">';
                if (is_array($unserialized) || is_object($unserialized)) {
                    print_r($unserialized);
                } else {
                    echo esc_html($value);
                }
                echo '</pre>';
                
                // Afficher la représentation sérialisée
                echo '<p><strong>Représentation sérialisée:</strong></p>';
                echo '<pre class="serialized-data">' . esc_html($value) . '</pre>';
                
                echo '</div>';
            }
            
            echo '</div>';
        }
        
        echo '</div>';
        
        // Ajouter des styles CSS pour une meilleure lisibilité
        echo '<style>
            .sisme-data-inspector {
                margin-top: 20px;
                padding: 15px;
                background-color: rgba(0, 0, 0, 0.3);
                border: 1px solid #ddd;
                border-radius: 10px;
            }
            .data-section {
                margin-bottom: 25px;
                padding-bottom: 15px;
                border-bottom: 1px solid #eee;
                border-radius: 10px;
            }
            .meta-value {
                padding: 10px;
                background-color: rgba(255, 255, 255, 0.1);
                border-radius: 10px;
                margin-bottom: 10px;
            }
            pre.data-structure {
                background-color: rgba(255, 255, 255, 0.1);
                padding: 10px;
                overflow: auto;
                max-height: 300px;
                border-radius: 10px;
            }
            pre.serialized-data {
                background-color: rgba(255, 255, 255, 0.1);
                padding: 10px;
                overflow: auto;
                max-height: 100px;
                font-size: 12px;
                border-radius: 10px;
            }
        </style>';
    }
}