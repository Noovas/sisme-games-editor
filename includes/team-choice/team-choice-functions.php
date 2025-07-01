<?php
/**
 * File: /sisme-games-editor/includes/team-choice/team-choice-functions.php
 * Fonctions métier pour le système de choix de l'équipe
 * 
 * RESPONSABILITÉ:
 * - Wrappers vers utils-games.php pour les fonctions de base
 * - Gestion des actions en lot administratives
 * - Intégration tableau Game Data (colonnes et rendu)
 * - Logique d'affichage spécifique au module
 * 
 * DÉPENDANCES:
 * - utils-games.php (fonctions migrées)
 * - WordPress Admin (hooks et redirections)
 * - Module Game Data Table (filtres)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Team_Choice_Functions {      
    /**
     * 📊 Ajouter colonne dans le tableau Game Data
     * 
     * @param array $columns Colonnes existantes du tableau
     * @return array Colonnes avec "Choix équipe" ajoutée
     */
    public static function add_table_column($columns) {
        $new_columns = array();
        foreach ($columns as $key => $label) {
            $new_columns[$key] = $label;
            if ($key === 'vedette' || $key === 'featured') {
                $new_columns['team_choice'] = 'Choix équipe';
            }
        }
        if (!isset($new_columns['team_choice'])) {
            $new_columns['team_choice'] = 'Choix équipe';
        }
        return $new_columns;
    }
    
    /**
     * 🎨 Rendre le contenu de la colonne team choice
     * 
     * @param string $content Contenu existant de la colonne
     * @param string $column_key Clé de la colonne
     * @param array $game_data Données du jeu
     * @return string HTML de la colonne ou contenu original
     */
    public static function render_table_column($content, $column_key, $game_data) {
        if ($column_key !== 'team_choice') {
            return $content;
        }
        $term_id = $game_data[Sisme_Utils_Games::KEY_TERM_ID] ?? $game_data['id'] ?? 0;
        if (!$term_id) {
            return '❌';
        }
        $is_team_choice = Sisme_Utils_Games::is_team_choice($term_id);
        $heart_class = $is_team_choice ? 'team-choice-active' : 'team-choice-inactive';
        $heart_icon = $is_team_choice ? '❤️' : '🤍';
        $title = $is_team_choice ? 'Retirer des choix équipe' : 'Ajouter aux choix équipe';
        return sprintf(
            '<button class="team-choice-btn %s" data-game-id="%d" data-team-choice="%s" title="%s">%s</button>',
            esc_attr($heart_class),
            $term_id,
            $is_team_choice ? '1' : '0',
            esc_attr($title),
            $heart_icon
        );
    }
}