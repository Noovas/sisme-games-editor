<?php
/**
 * File: user-actions-api.php
 * Module: API publique User Actions
 * 
 * Fournit des fonctions publiques pour intégrer les actions utilisateur
 * dans d'autres modules ou templates.
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/user/user-actions/user-actions-data-manager.php';

class Sisme_User_Actions_API {
    
    /**
     * Générer le bouton d'action pour un jeu
     * 
     * @param int $game_id ID du jeu (term_id)
     * @param string $action_type Type d'action (favorite, owned)
     * @param array $options Options du bouton
     *    - classes: Classes CSS supplémentaires
     *    - size: Taille du bouton (small, medium, large)
     *    - show_count: Afficher le compteur
     *    - update: Forcer la mise à jour du cache
     * @return string HTML du bouton
     */
    public static function render_action_button($game_id, $action_type, $options = []) {
        // Si le jeu n'existe pas, ne rien afficher
        $term = get_term($game_id, 'post_tag');
        if (!$term || is_wp_error($term)) {
            return '';
        }
        
        // Options par défaut
        $defaults = [
            'classes' => '',
            'size' => 'medium',
            'show_count' => true,
            'update' => false,
            'show_text' => false,
            'text_active' => '',
            'text_inactive' => ''
        ];
        
        $options = wp_parse_args($options, $defaults);
        
        // Valider le type d'action
        $valid_actions = [
            Sisme_User_Actions_Data_Manager::COLLECTION_FAVORITE,
            Sisme_User_Actions_Data_Manager::COLLECTION_OWNED
        ];
        
        if (!in_array($action_type, $valid_actions)) {
            return '';
        }
        
        // Vérifier si l'utilisateur est connecté
        $user_id = get_current_user_id();
        $is_logged_in = ($user_id > 0);

        // BETA
        if (!$is_logged_in) {
            return '';
        }
        
        // Déterminer l'état actuel et les classes CSS
        $is_active = false;
        if ($is_logged_in) {
            $is_active = Sisme_User_Actions_Data_Manager::is_game_in_user_collection($user_id, $game_id, $action_type);
        }
        
        // Construire les classes CSS
        $btn_classes = [
            'sisme-action-btn',
            'sisme-action-' . $action_type,
            'sisme-action-size-' . $options['size'],
            $is_active ? 'sisme-action-active' : 'sisme-action-inactive'
        ];
        
        if (!empty($options['classes'])) {
            $btn_classes[] = $options['classes'];
        }
        
        // Définir les emojis et textes selon le type d'action
        $icon_active = '';
        $icon_inactive = '';
        $title_active = '';
        $title_inactive = '';
        
        switch ($action_type) {
            case Sisme_User_Actions_Data_Manager::COLLECTION_FAVORITE:
                $icon_active = '❤️';
                $icon_inactive = '🤍';
                $title_active = __('Retirer des favoris', 'sisme-games-editor');
                $title_inactive = __('Ajouter aux favoris', 'sisme-games-editor');
                
                if (empty($options['text_active'])) {
                    $options['text_active'] = __('Retirer des favoris', 'sisme-games-editor');
                }
                if (empty($options['text_inactive'])) {
                    $options['text_inactive'] = __('Ajouter aux favoris', 'sisme-games-editor');
                }
                break;
                
            case Sisme_User_Actions_Data_Manager::COLLECTION_OWNED:
                $icon_active = '🎮';
                $icon_inactive = '🎮';
                $title_active = __('Retirer de ma collection', 'sisme-games-editor');
                $title_inactive = __('Ajouter à ma collection', 'sisme-games-editor');
                
                if (empty($options['text_active'])) {
                    $options['text_active'] = __('Retirer de ma collection', 'sisme-games-editor');
                }
                if (empty($options['text_inactive'])) {
                    $options['text_inactive'] = __('Ajouter à ma collection', 'sisme-games-editor');
                }
                break;
        }
        
        // Obtenir le nombre d'utilisateurs
        $count = 0;
        if ($options['show_count']) {
            $count = Sisme_User_Actions_Data_Manager::get_game_collection_stats($game_id, $action_type);
        }
        
        // ID unique pour le bouton
        $btn_id = 'sisme-action-' . $action_type . '-' . $game_id;
        
        // Générer l'HTML du bouton
        ob_start();
        ?>
        <button id="<?php echo esc_attr($btn_id); ?>" 
                class="<?php echo esc_attr(implode(' ', $btn_classes)); ?>"
                data-game-id="<?php echo esc_attr($game_id); ?>"
                data-action-type="<?php echo esc_attr($action_type); ?>"
                data-is-active="<?php echo $is_active ? 'true' : 'false'; ?>"
                title="<?php echo esc_attr($is_active ? $title_active : $title_inactive); ?>">
            
            <span class="sisme-action-icon">
                <span class="sisme-action-icon-active"><?php echo $icon_active; ?></span>
                <span class="sisme-action-icon-inactive"><?php echo $icon_inactive; ?></span>
            </span>
            
            <?php if ($options['show_text']): ?>
                <span class="sisme-action-text">
                    <span class="sisme-action-text-active"><?php echo esc_html($options['text_active']); ?></span>
                    <span class="sisme-action-text-inactive"><?php echo esc_html($options['text_inactive']); ?></span>
                </span>
            <?php endif; ?>
            
            <?php if ($options['show_count'] && $count > 0): ?>
                <span class="sisme-action-count"><?php echo esc_html($count); ?></span>
            <?php endif; ?>
        </button>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Intégrer le bouton d'action dans la Hero Section
     * 
     * @param string $html HTML actuel
     * @param array $game_data Données du jeu
     * @return string HTML modifié
     */
    public static function integrate_action_buttons_in_hero($html, $game_data) {
        // Si pas de game_data ou d'ID, retourner l'HTML tel quel
        if (empty($game_data['id'])) {
            return $html;
        }
        
        $game_id = $game_data['id'];
        
        // Créer les boutons
        $favorite_btn = self::render_action_button($game_id, Sisme_User_Actions_Data_Manager::COLLECTION_FAVORITE, [
            'size' => 'large',
            'show_text' => true
        ]);
        
        $owned_btn = self::render_action_button($game_id, Sisme_User_Actions_Data_Manager::COLLECTION_OWNED, [
            'size' => 'large',
            'show_text' => true
        ]);
        
        // Créer le conteneur pour les boutons
        $buttons_html = '<div class="sisme-hero-actions">' . $favorite_btn . $owned_btn . '</div>';
        
        // Chercher où insérer les boutons
        $insertion_point = '</div><!-- .sisme-game-info -->';
        
        // Insérer les boutons juste avant la fermeture de .sisme-game-info
        $html = str_replace($insertion_point, $buttons_html . $insertion_point, $html);
        
        return $html;
    }
    
    /**
     * Intégrer le bouton d'action dans une carte de jeu
     * 
     * @param string $html HTML actuel de la carte
     * @param int $game_id ID du jeu
     * @param array $options Options de la carte
     * @return string HTML modifié
     */
    public static function integrate_action_button_in_card($html, $game_id, $options = []) {
        // Créer le bouton favoris avec des options adaptées aux cartes
        $favorite_btn = self::render_action_button($game_id, Sisme_User_Actions_Data_Manager::COLLECTION_FAVORITE, [
            'size' => 'small',
            'show_count' => false
        ]);
        
        // Chercher où insérer le bouton
        $insertion_point = '</div><!-- .sisme-card-title -->';
        
        // Insérer le bouton juste après le titre
        $html = str_replace($insertion_point, $insertion_point . $favorite_btn, $html);
        
        return $html;
    }
    
    /**
     * Rendu d'un shortcode pour afficher les jeux d'une collection utilisateur
     * 
     * @param array $atts Attributs du shortcode
     * @return string HTML
     */
    public static function render_user_collection_shortcode($atts) {
        $atts = shortcode_atts([
            'user_id' => get_current_user_id(),
            'collection' => 'favorite',
            'limit' => 10,
            Sisme_Utils_Games::KEY_TITLE => '',
            'view' => 'grid',
            'columns' => 4,
            'empty_text' => __('Aucun jeu dans cette collection', 'sisme-games-editor')
        ], $atts, 'sisme_user_collection');
        
        // Vérifier que l'utilisateur existe
        $user = get_user_by('ID', $atts['user_id']);
        if (!$user) {
            return '';
        }
        
        // Vérifier que le type de collection est valide
        if ($atts['collection'] !== Sisme_User_Actions_Data_Manager::COLLECTION_FAVORITE && 
            $atts['collection'] !== Sisme_User_Actions_Data_Manager::COLLECTION_OWNED) {
            return '';
        }
        
        // Récupérer les jeux de la collection
        $game_ids = Sisme_User_Actions_Data_Manager::get_user_collection(
            $atts['user_id'],
            $atts['collection'],
            $atts['limit']
        );
        
        // Si pas de jeux, afficher le message "vide"
        if (empty($game_ids)) {
            return '<div class="sisme-user-collection-empty">' . esc_html($atts['empty_text']) . '</div>';
        }
        
        // Générer le HTML selon la vue
        $output = '';
        
        // Ajouter le titre si présent
        if (!empty($atts['title'])) {
            $output .= '<h3 class="sisme-collection-title">' . esc_html($atts['title']) . '</h3>';
        }
        
        // Si le module Cards est disponible, l'utiliser pour le rendu
        if (class_exists('Sisme_Cards_API')) {
            $grid_options = [
                'columns' => $atts['columns'],
                'show_description' => false
            ];
            
            $output .= Sisme_Cards_API::render_cards_grid($game_ids, $grid_options);
        } else {
            // Fallback si Cards n'est pas disponible
            $output .= '<div class="sisme-user-collection">';
            $output .= '<ul>';
            
            foreach ($game_ids as $game_id) {
                $term = get_term($game_id, 'post_tag');
                if ($term && !is_wp_error($term)) {
                    $output .= '<li><a href="' . get_term_link($term) . '">' . esc_html($term->name) . '</a></li>';
                }
            }
            
            $output .= '</ul>';
            $output .= '</div>';
        }
        
        return $output;
    }
    
    /**
     * Initialiser les shortcodes
     */
    public static function init_shortcodes() {
        add_shortcode('sisme_user_collection', [self::class, 'render_user_collection_shortcode']);
    }
}

// Initialiser les shortcodes
add_action('init', ['Sisme_User_Actions_API', 'init_shortcodes']);

// Intégrer les boutons d'action dans la Hero Section
add_filter('sisme_hero_section_html', ['Sisme_User_Actions_API', 'integrate_action_buttons_in_hero'], 10, 2);

// Intégrer le bouton favoris dans les cartes de jeu
add_filter('sisme_card_html', ['Sisme_User_Actions_API', 'integrate_action_button_in_card'], 10, 3);