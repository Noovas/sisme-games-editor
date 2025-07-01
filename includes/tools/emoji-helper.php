<?php
/**
 * File: /sisme-games-editor/includes/tools/emoji-helper.php
 * Helper pour la gestion des émojis compatibles WordPress/HTML
 * 
 * UTILISATION:
 * - Listes organisées pour formulaires
 * - Validation d'émojis
 * - Génération de sélecteurs
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Emoji_Helper {
    
    /**
     * 🎮 Émojis Gaming & Jeux Vidéo
     */
    const GAMING_EMOJIS = array(
        '🎮' => 'Manette de jeu',
        '🕹️' => 'Joystick rétro',
        '🎯' => 'Cible/Objectif',
        '🏆' => 'Trophée',
        '🥇' => 'Médaille d\'or',
        '🥈' => 'Médaille d\'argent',
        '🥉' => 'Médaille de bronze',
        '👑' => 'Couronne',
        '🏅' => 'Médaille sportive'
    );
    
    /**
     * 🌟 Émojis de Mise en Avant
     */
    const FEATURED_EMOJIS = array(
        '⭐' => 'Étoile classique',
        '🌟' => 'Étoile brillante',
        '✨' => 'Étincelles',
        '💫' => 'Étoile filante',
        '⚡' => 'Éclair',
        '🔥' => 'Feu/Tendance',
        '💎' => 'Diamant/Premium',
        '💍' => 'Bijou/Précieux',
        '👑' => 'Royal/VIP',
        '🏆' => 'Champion'
    );
    
    /**
     * 🆕 Émojis Nouveautés & Actualités
     */
    const NEWS_EMOJIS = array(
        '🆕' => 'Nouveau',
        '🔥' => 'Hot/Tendance',
        '📢' => 'Annonce',
        '📣' => 'Mégaphone',
        '🚨' => 'Urgence/Alerte',
        '⚡' => 'Flash/Rapide',
        '💥' => 'Explosion/Impact',
        '🌪️' => 'Tourbillon/Dynamique'
    );
    
    /**
     * 🎨 Émojis par Genres de Jeux
     */
    const GENRE_EMOJIS = array(
        // Action & Aventure
        '⚔️' => 'Action & Combat',
        '🏹' => 'Archer/RPG',
        '🛡️' => 'Défense/Stratégie',
        '🗡️' => 'Action/Aventure',
        '🧙' => 'Fantasy/Magie',
        '🐲' => 'Fantasy/Aventure',
        
        // Sci-Fi & Space
        '🚀' => 'Space/Sci-Fi',
        '🛸' => 'Sci-Fi/Alien',
        '👽' => 'Extraterrestre',
        '🌌' => 'Galaxie',
        
        // Puzzle & Réflexion
        '🧩' => 'Puzzle/Réflexion',
        '🎲' => 'Jeux de hasard',
        '🧠' => 'Réflexion/Mental',
        '🔍' => 'Investigation',
        
        // Cartes & Casino
        '♠️' => 'Cartes Pique',
        '♥️' => 'Cartes Cœur',
        '♦️' => 'Cartes Carreau',
        '♣️' => 'Cartes Trèfle',
        
        // Simulation & Gestion
        '🏗️' => 'Construction',
        '🏭' => 'Industrie',
        '🚜' => 'Farming',
        '🌱' => 'Jardinage',
        '🏘️' => 'City Builder',
        
        // Transport & Course
        '🚗' => 'Simulation auto',
        '✈️' => 'Simulation vol',
        '🚢' => 'Simulation navale',
        '🚂' => 'Trains',
        '🏎️' => 'Course auto',
        '🏍️' => 'Moto',
        
        // Sports
        '⚽' => 'Football',
        '🏀' => 'Basketball',
        '🏈' => 'Football US',
        '🎾' => 'Tennis',
        
        // Horreur & Thriller
        '👻' => 'Fantôme',
        '🎃' => 'Halloween',
        '🧟' => 'Zombie',
        '🧛' => 'Vampire',
        '🕷️' => 'Araignée',
        '🦇' => 'Chauve-souris',
        '💀' => 'Crâne'
    );
    
    /**
     * 📱 Émojis d'Interface & Navigation
     */
    const INTERFACE_EMOJIS = array(
        '📂' => 'Catégorie',
        '📁' => 'Dossier ouvert',
        '📋' => 'Liste',
        '📊' => 'Statistiques',
        '📈' => 'Tendances',
        '🔖' => 'Marque-page',
        '🏷️' => 'Étiquette',
        '📌' => 'Épinglé',
        '📍' => 'Localisation',
        '👆' => 'Cliquer',
        '👀' => 'Voir/Regarder',
        '❤️' => 'J\'aime',
        '💖' => 'Coup de cœur',
        '🔗' => 'Lien',
        '💬' => 'Commentaire'
    );
    
    /**
     * 🎉 Émojis Événements & Célébrations
     */
    const EVENT_EMOJIS = array(
        '🎄' => 'Noël',
        '🎃' => 'Halloween',
        '🎆' => 'Célébration',
        '🎊' => 'Fête',
        '🎈' => 'Anniversaire',
        '🎂' => 'Gâteau d\'anniversaire',
        '🌅' => 'Nouveau début',
        '🌇' => 'Fin de journée'
    );
    
    /**
     * Obtenir tous les émojis par catégorie
     * 
     * @return array Tableau associatif [catégorie => émojis]
     */
    public static function get_all_categories() {
        return array(
            'gaming' => array(
                Sisme_Utils_Games::KEY_NAME => '🎮 Gaming & Jeux Vidéo',
                'emojis' => self::GAMING_EMOJIS
            ),
            'featured' => array(
                Sisme_Utils_Games::KEY_NAME => '🌟 Mise en Avant',
                'emojis' => self::FEATURED_EMOJIS
            ),
            'news' => array(
                Sisme_Utils_Games::KEY_NAME => '🆕 Nouveautés & Actualités',
                'emojis' => self::NEWS_EMOJIS
            ),
            Sisme_Utils_Games::KEY_GENRES => array(
                Sisme_Utils_Games::KEY_NAME => '🎨 Genres de Jeux',
                'emojis' => self::GENRE_EMOJIS
            ),
            'interface' => array(
                Sisme_Utils_Games::KEY_NAME => '📱 Interface & Navigation',
                'emojis' => self::INTERFACE_EMOJIS
            ),
            'events' => array(
                Sisme_Utils_Games::KEY_NAME => '🎉 Événements & Célébrations',
                'emojis' => self::EVENT_EMOJIS
            )
        );
    }
    
    /**
     * Obtenir tous les émojis (liste plate)
     * 
     * @return array [emoji => description]
     */
    public static function get_all_emojis() {
        return array_merge(
            self::GAMING_EMOJIS,
            self::FEATURED_EMOJIS,
            self::NEWS_EMOJIS,
            self::GENRE_EMOJIS,
            self::INTERFACE_EMOJIS,
            self::EVENT_EMOJIS
        );
    }
    
    /**
     * Obtenir les émojis d'une catégorie spécifique
     * 
     * @param string $category Nom de la catégorie
     * @return array|false Émojis de la catégorie ou false
     */
    public static function get_category_emojis($category) {
        $categories = self::get_all_categories();
        return isset($categories[$category]) ? $categories[$category]['emojis'] : false;
    }
    
    /**
     * Générer un sélecteur HTML pour formulaire
     * 
     * @param string $name Nom du champ
     * @param string $selected Valeur sélectionnée
     * @param array $options Options supplémentaires
     * @return string HTML du select
     */
    public static function render_emoji_selector($name, $selected = '', $options = array()) {
        $defaults = array(
            Sisme_Utils_Games::KEY_ID => $name,
            'class' => 'sisme-emoji-selector',
            'required' => false,
            'categories' => array(), // Vide = toutes les catégories
            'include_none' => true,
            'none_text' => 'Aucun émoji'
        );
        
        $options = array_merge($defaults, $options);
        
        // Déterminer les catégories à afficher
        if (empty($options['categories'])) {
            $categories = self::get_all_categories();
        } else {
            $all_categories = self::get_all_categories();
            $categories = array();
            foreach ($options['categories'] as $cat) {
                if (isset($all_categories[$cat])) {
                    $categories[$cat] = $all_categories[$cat];
                }
            }
        }
        
        $output = '<select name="' . esc_attr($name) . '" id="' . esc_attr($options['id']) . '" class="' . esc_attr($options['class']) . '"';
        if ($options['required']) {
            $output .= ' required';
        }
        $output .= '>';
        
        // Option "aucun" si demandée
        if ($options['include_none']) {
            $output .= '<option value=""' . selected('', $selected, false) . '>' . esc_html($options['none_text']) . '</option>';
        }
        
        // Groupes par catégorie
        foreach ($categories as $cat_key => $category) {
            $output .= '<optgroup label="' . esc_attr($category[Sisme_Utils_Games::KEY_NAME]) . '">';
            
            foreach ($category['emojis'] as $emoji => $description) {
                $output .= '<option value="' . esc_attr($emoji) . '"' . selected($emoji, $selected, false) . '>';
                $output .= $emoji . ' ' . esc_html($description);
                $output .= '</option>';
            }
            
            $output .= '</optgroup>';
        }
        
        $output .= '</select>';
        
        return $output;
    }
    
    /**
     * Générer un sélecteur avec prévisualisation
     * 
     * @param string $name Nom du champ
     * @param string $selected Valeur sélectionnée
     * @param array $options Options supplémentaires
     * @return string HTML complet avec préview
     */
    public static function render_emoji_selector_with_preview($name, $selected = '', $options = array()) {
        $output = '<div class="sisme-emoji-selector-wrapper">';
        
        // Prévisualisation
        $output .= '<div class="sisme-emoji-preview">';
        $output .= '<span class="sisme-emoji-preview-display" id="' . esc_attr($name) . '_preview">';
        $output .= !empty($selected) ? $selected : '❓';
        $output .= '</span>';
        $output .= '<label for="' . esc_attr($name) . '">Prévisualisation</label>';
        $output .= '</div>';
        
        // Sélecteur
        $output .= self::render_emoji_selector($name, $selected, $options);
        
        $output .= '</div>';
        
        // JavaScript pour la prévisualisation
        $output .= '<script>
        document.addEventListener("DOMContentLoaded", function() {
            var selector = document.getElementById("' . esc_js($options['id'] ?? $name) . '");
            var preview = document.getElementById("' . esc_js($name) . '_preview");
            
            if (selector && preview) {
                selector.addEventListener("change", function() {
                    var selectedOption = this.options[this.selectedIndex];
                    var emoji = this.value;
                    
                    if (emoji) {
                        preview.textContent = emoji;
                        preview.title = selectedOption.text;
                    } else {
                        preview.textContent = "❓";
                        preview.title = "Aucun émoji sélectionné";
                    }
                });
            }
        });
        </script>';
        
        return $output;
    }
    
    /**
     * Valider qu'un émoji est dans la liste autorisée
     * 
     * @param string $emoji Émoji à valider
     * @return bool True si valide
     */
    public static function is_valid_emoji($emoji) {
        $all_emojis = self::get_all_emojis();
        return array_key_exists($emoji, $all_emojis);
    }
    
    /**
     * Obtenir la description d'un émoji
     * 
     * @param string $emoji Émoji
     * @return string|false Description ou false
     */
    public static function get_emoji_description($emoji) {
        $all_emojis = self::get_all_emojis();
        return isset($all_emojis[$emoji]) ? $all_emojis[$emoji] : false;
    }
    
    /**
     * Suggestions d'émojis pour types de contenu gaming
     * 
     * @param string $content_type Type de contenu
     * @return array Émojis suggérés
     */
    public static function get_gaming_suggestions($content_type) {
        $suggestions = array(
            'featured' => array('💎', '🌟', '👑', '🏆'),
            'new' => array('🆕', '🔥', '⚡', '💥'),
            'action' => array('⚔️', '🗡️', '🏹', '🛡️'),
            'puzzle' => array('🧩', '🧠', '🔍', '🎲'),
            'simulation' => array('🏗️', '🚜', '🌱', '🏘️'),
            'sports' => array('⚽', '🏀', '🏎️', '🏍️'),
            'horror' => array('👻', '🎃', '💀', '🧟'),
            'scifi' => array('🚀', '🛸', '👽', '🌌')
        );
        
        return isset($suggestions[$content_type]) ? $suggestions[$content_type] : array();
    }
    
    /**
     * Générer les options CSS pour le style des émojis
     * 
     * @return string CSS pour les émojis
     */
    public static function get_emoji_css() {
        return '
        .sisme-emoji-selector-wrapper {
            display: flex;
            align-items: center;
            gap: 15px;
            margin: 10px 0;
        }
        
        .sisme-emoji-preview {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
        }
        
        .sisme-emoji-preview-display {
            font-size: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            border: 2px solid #ddd;
            border-radius: 8px;
            background: #f9f9f9;
            transition: all 0.3s ease;
        }
        
        .sisme-emoji-preview label {
            font-size: 0.8rem;
            color: #666;
            text-align: center;
        }
        
        .sisme-emoji-selector {
            min-width: 300px;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        
        .sisme-emoji-selector optgroup {
            font-weight: bold;
            color: #333;
        }
        
        .sisme-emoji-selector option {
            padding: 5px;
            font-weight: normal;
        }
        ';
    }
}

/**
 * 🎮 EXEMPLES D'USAGE DANS VOS FORMULAIRES
 */

/*
// Exemple 1: Sélecteur simple pour titre de carrousel
echo Sisme_Emoji_Helper::render_emoji_selector('carousel_title_emoji', '🎮');

// Exemple 2: Sélecteur avec prévisualisation
echo Sisme_Emoji_Helper::render_emoji_selector_with_preview(
    'section_emoji', 
    '💎', 
    array(
        'categories' => array('featured', 'gaming'),
        'class' => 'ma-classe-custom'
    )
);

// Exemple 3: Validation côté serveur
if (isset($_POST['emoji']) && Sisme_Emoji_Helper::is_valid_emoji($_POST['emoji'])) {
    $safe_emoji = $_POST['emoji'];
    $description = Sisme_Emoji_Helper::get_emoji_description($safe_emoji);
}

// Exemple 4: Suggestions par type
$action_emojis = Sisme_Emoji_Helper::get_gaming_suggestions('action');
// Retourne: array('⚔️', '🗡️', '🏹', '🛡️')

// Exemple 5: Liste pour un admin
$all_categories = Sisme_Emoji_Helper::get_all_categories();
foreach ($all_categories as $cat_key => $category) {
    echo '<h3>' . $category[Sisme_Utils_Games::KEY_NAME] . '</h3>';
    foreach ($category['emojis'] as $emoji => $desc) {
        echo '<span title="' . $desc . '">' . $emoji . '</span> ';
    }
}
*/

?>