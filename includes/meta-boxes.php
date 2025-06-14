<?php
/**
 * File: /sisme-games-editor/includes/meta-boxes.php
 * Gestion des champs personnalisés (meta boxes) pour les fiches de jeu
 */

// Sécurité : Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe pour gérer les meta boxes des fiches de jeu
 */
class Sisme_Games_Meta_Boxes {
    
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_boxes'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_meta_box_scripts'));
    }
    
    /**
     * Ajouter les meta boxes
     */
    public function add_meta_boxes() {
        // Vérifier si on est sur un article d'une catégorie "jeux-*"
        global $post;
        
        if (!$post) return;
        
        $post_categories = get_the_category($post->ID);
        $is_jeux_article = false;
        
        foreach ($post_categories as $category) {
            if (strpos($category->slug, 'jeux-') === 0) {
                $is_jeux_article = true;
                break;
            }
        }
        
        // Ajouter la meta box seulement pour les articles de jeux
        if ($is_jeux_article || (isset($_GET['page']) && strpos($_GET['page'], 'sisme-games') !== false)) {
            add_meta_box(
                'sisme-games-fiche-details',
                '🎮 Détails de la fiche de jeu',
                array($this, 'render_fiche_meta_box'),
                'post',
                'normal',
                'high'
            );
        }
    }
    
    /**
     * Afficher la meta box des détails de fiche
     */
    public function render_fiche_meta_box($post) {
        // Nonce pour la sécurité
        wp_nonce_field('sisme_games_meta_box', 'sisme_games_meta_nonce');
        
        // Récupérer les valeurs existantes
        $date_sortie = get_post_meta($post->ID, '_sisme_date_sortie', true);
        $developpeur = get_post_meta($post->ID, '_sisme_developpeur', true);
        $editeur = get_post_meta($post->ID, '_sisme_editeur', true);
        $plateformes = get_post_meta($post->ID, '_sisme_plateformes', true);
        $genre = get_post_meta($post->ID, '_sisme_genre', true);
        $note_globale = get_post_meta($post->ID, '_sisme_note_globale', true);
        $prix = get_post_meta($post->ID, '_sisme_prix', true);
        $site_officiel = get_post_meta($post->ID, '_sisme_site_officiel', true);
        
        ?>
        <div class="sisme-meta-box-container">
            <div class="sisme-meta-fields">
                
                <!-- Date de sortie -->
                <div class="sisme-field-group">
                    <label for="sisme_date_sortie" class="sisme-field-label">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        Date de sortie
                    </label>
                    <input type="date" 
                           id="sisme_date_sortie" 
                           name="sisme_date_sortie" 
                           value="<?php echo esc_attr($date_sortie); ?>"
                           class="sisme-field-input">
                    <p class="sisme-field-description">Date de sortie officielle du jeu</p>
                </div>
                
                <!-- Développeur -->
                <div class="sisme-field-group">
                    <label for="sisme_developpeur" class="sisme-field-label">
                        <span class="dashicons dashicons-admin-users"></span>
                        Développeur
                    </label>
                    <input type="text" 
                           id="sisme_developpeur" 
                           name="sisme_developpeur" 
                           value="<?php echo esc_attr($developpeur); ?>"
                           class="sisme-field-input"
                           placeholder="Ex: Studio XYZ">
                    <p class="sisme-field-description">Studio ou équipe de développement</p>
                </div>
                
                <!-- Éditeur -->
                <div class="sisme-field-group">
                    <label for="sisme_editeur" class="sisme-field-label">
                        <span class="dashicons dashicons-building"></span>
                        Éditeur
                    </label>
                    <input type="text" 
                           id="sisme_editeur" 
                           name="sisme_editeur" 
                           value="<?php echo esc_attr($editeur); ?>"
                           class="sisme-field-input"
                           placeholder="Ex: Publisher ABC">
                    <p class="sisme-field-description">Maison d'édition du jeu</p>
                </div>
                
                <!-- Plateformes -->
                <div class="sisme-field-group">
                    <label for="sisme_plateformes" class="sisme-field-label">
                        <span class="dashicons dashicons-desktop"></span>
                        Plateformes
                    </label>
                    <div class="sisme-checkbox-group">
                        <?php
                        $available_platforms = array(
                            'pc' => 'PC (Windows)',
                            'mac' => 'Mac',
                            'linux' => 'Linux',
                            'steam' => 'Steam',
                            'epic' => 'Epic Games Store',
                            'gog' => 'GOG',
                            'ps5' => 'PlayStation 5',
                            'ps4' => 'PlayStation 4',
                            'xbox-series' => 'Xbox Series X/S',
                            'xbox-one' => 'Xbox One',
                            'switch' => 'Nintendo Switch',
                            'mobile' => 'Mobile (iOS/Android)'
                        );
                        
                        $selected_platforms = is_array($plateformes) ? $plateformes : array();
                        
                        foreach ($available_platforms as $key => $label) {
                            $checked = in_array($key, $selected_platforms) ? 'checked' : '';
                            echo '<label class="sisme-checkbox-label">';
                            echo '<input type="checkbox" name="sisme_plateformes[]" value="' . esc_attr($key) . '" ' . $checked . '>';
                            echo '<span>' . esc_html($label) . '</span>';
                            echo '</label>';
                        }
                        ?>
                    </div>
                </div>
                
                <!-- Genre -->
                <div class="sisme-field-group">
                    <label for="sisme_genre" class="sisme-field-label">
                        <span class="dashicons dashicons-category"></span>
                        Genre principal
                    </label>
                    <select id="sisme_genre" name="sisme_genre" class="sisme-field-select">
                        <option value="">Sélectionner un genre...</option>
                        <?php
                        $genres = array(
                            'action' => 'Action',
                            'aventure' => 'Aventure',
                            'rpg' => 'RPG',
                            'simulation' => 'Simulation',
                            'strategie' => 'Stratégie',
                            'puzzle' => 'Puzzle',
                            'plateforme' => 'Plateforme',
                            'course' => 'Course',
                            'sport' => 'Sport',
                            'survie' => 'Survie',
                            'horreur' => 'Horreur',
                            'arcade' => 'Arcade',
                            'roguelike' => 'Roguelike',
                            'metroidvania' => 'Metroidvania',
                            'tower-defense' => 'Tower Defense',
                            'battle-royale' => 'Battle Royale'
                        );
                        
                        foreach ($genres as $key => $label) {
                            $selected = ($genre === $key) ? 'selected' : '';
                            echo '<option value="' . esc_attr($key) . '" ' . $selected . '>' . esc_html($label) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                
                <!-- Note globale -->
                <div class="sisme-field-group">
                    <label for="sisme_note_globale" class="sisme-field-label">
                        <span class="dashicons dashicons-star-filled"></span>
                        Note globale
                    </label>
                    <div class="sisme-rating-input">
                        <input type="range" 
                               id="sisme_note_globale" 
                               name="sisme_note_globale" 
                               min="0" 
                               max="100" 
                               value="<?php echo esc_attr($note_globale ?: 50); ?>"
                               class="sisme-range-input">
                        <div class="sisme-rating-display">
                            <span class="sisme-rating-value"><?php echo esc_html($note_globale ?: 50); ?></span>/100
                            <div class="sisme-rating-stars"></div>
                        </div>
                    </div>
                    <p class="sisme-field-description">Note sur 100 (sera convertie en étoiles)</p>
                </div>
                
                <!-- Prix -->
                <div class="sisme-field-group">
                    <label for="sisme_prix" class="sisme-field-label">
                        <span class="dashicons dashicons-money-alt"></span>
                        Prix
                    </label>
                    <div class="sisme-price-input">
                        <input type="number" 
                               id="sisme_prix" 
                               name="sisme_prix" 
                               value="<?php echo esc_attr($prix); ?>"
                               step="0.01"
                               min="0"
                               class="sisme-field-input"
                               placeholder="19.99">
                        <span class="sisme-currency">€</span>
                    </div>
                    <p class="sisme-field-description">Prix en euros (laisser vide si gratuit)</p>
                </div>
                
                <!-- Site officiel -->
                <div class="sisme-field-group">
                    <label for="sisme_site_officiel" class="sisme-field-label">
                        <span class="dashicons dashicons-admin-links"></span>
                        Site officiel
                    </label>
                    <input type="url" 
                           id="sisme_site_officiel" 
                           name="sisme_site_officiel" 
                           value="<?php echo esc_attr($site_officiel); ?>"
                           class="sisme-field-input"
                           placeholder="https://exemple.com">
                    <p class="sisme-field-description">URL du site officiel du jeu</p>
                </div>
                
            </div>
        </div>
        <?php
    }
    
    /**
     * Sauvegarder les meta boxes
     */
    public function save_meta_boxes($post_id) {
        // Vérifications de sécurité
        if (!isset($_POST['sisme_games_meta_nonce']) || 
            !wp_verify_nonce($_POST['sisme_games_meta_nonce'], 'sisme_games_meta_box')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Sauvegarder les champs
        $fields = array(
            '_sisme_date_sortie' => 'sisme_date_sortie',
            '_sisme_developpeur' => 'sisme_developpeur',
            '_sisme_editeur' => 'sisme_editeur',
            '_sisme_genre' => 'sisme_genre',
            '_sisme_note_globale' => 'sisme_note_globale',
            '_sisme_prix' => 'sisme_prix',
            '_sisme_site_officiel' => 'sisme_site_officiel'
        );
        
        foreach ($fields as $meta_key => $form_key) {
            if (isset($_POST[$form_key])) {
                $value = sanitize_text_field($_POST[$form_key]);
                update_post_meta($post_id, $meta_key, $value);
            }
        }
        
        // Sauvegarder les plateformes (array)
        if (isset($_POST['sisme_plateformes'])) {
            $plateformes = array_map('sanitize_text_field', $_POST['sisme_plateformes']);
            update_post_meta($post_id, '_sisme_plateformes', $plateformes);
        } else {
            delete_post_meta($post_id, '_sisme_plateformes');
        }
    }
    
    /**
     * Charger les scripts CSS/JS pour les meta boxes
     */
    public function enqueue_meta_box_scripts($hook) {
        if ($hook === 'post.php' || $hook === 'post-new.php') {
            wp_enqueue_style(
                'sisme-meta-boxes',
                SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/css/meta-boxes.css',
                array(),
                SISME_GAMES_EDITOR_VERSION
            );
            
            wp_enqueue_script(
                'sisme-meta-boxes',
                SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/js/meta-boxes.js',
                array('jquery'),
                SISME_GAMES_EDITOR_VERSION,
                true
            );
        }
    }
}

/**
 * Fonctions utilitaires pour récupérer les meta données
 */

/**
 * Récupérer la date de sortie d'un jeu
 */
function sisme_get_release_date($post_id) {
    return get_post_meta($post_id, '_sisme_date_sortie', true);
}

/**
 * Récupérer la note d'un jeu
 */
function sisme_get_game_rating($post_id) {
    return get_post_meta($post_id, '_sisme_note_globale', true);
}

/**
 * Récupérer les plateformes d'un jeu
 */
function sisme_get_game_platforms($post_id) {
    return get_post_meta($post_id, '_sisme_plateformes', true);
}

/**
 * Récupérer le prix d'un jeu
 */
function sisme_get_game_price($post_id) {
    return get_post_meta($post_id, '_sisme_prix', true);
}

/**
 * Convertir une note sur 100 en étoiles
 */
function sisme_rating_to_stars($rating) {
    $stars = round($rating / 20); // Convertir 100 en 5 étoiles
    $output = '';
    
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $stars) {
            $output .= '<span class="dashicons dashicons-star-filled sisme-star-filled"></span>';
        } else {
            $output .= '<span class="dashicons dashicons-star-empty sisme-star-empty"></span>';
        }
    }
    
    return $output;
}

// Initialiser la classe
new Sisme_Games_Meta_Boxes();