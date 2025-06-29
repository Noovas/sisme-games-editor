<?php
/**
 * Plugin Name: Sisme Games Editor
 * Description: Plugin pour la création rapide d'articles gaming (Fiches de jeu, Patch/News, Tests)
 * Version: 1.0.0
 * Author: Sisme Games
 * Text Domain: sisme-games-editor
 * 
 * File: /sisme-games-editor/sisme-games-editor.php
 */
if (!defined('ABSPATH')) {
    exit;
}

// Définir TOUTES les constantes AVANT de charger les fichiers
define('SISME_GAMES_EDITOR_VERSION', '1.0.0');
define('SISME_GAMES_EDITOR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SISME_GAMES_EDITOR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SISME_GAMES_MODULES', array(
    "vedettes", 
    "cards",
    "search",
    "team-choice",
    "user"
));

// Charger les fichiers
require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/sisme-constants.php';
require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/assets-loader.php';

/**
 * Charger automatiquement tous les fichiers utilitaires
 */
function sisme_load_utils() {
    $utils_dir = SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/utils/';
    
    // Vérifier que le dossier existe
    if (!is_dir($utils_dir)) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Sisme Games Editor] Dossier utils non trouvé: ' . $utils_dir);
        }
        return;
    }
    
    // Scanner le dossier pour les fichiers PHP
    $files = glob($utils_dir . '*.php');
    
    if (empty($files)) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Sisme Games Editor] Aucun fichier PHP trouvé dans utils/');
        }
        return;
    }
    
    $loaded_count = 0;
    
    foreach ($files as $file) {
        $filename = basename($file);
        
        // Exclure les fichiers commençant par un point ou underscore (convention fichiers privés)
        if (strpos($filename, '.') === 0 || strpos($filename, '_') === 0) {
            continue;
        }
        
        require_once $file;
        $loaded_count++;
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[Sisme Games Editor] Utilitaire chargé: {$filename}");
        }
    }
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("[Sisme Games Editor] {$loaded_count} utilitaires chargés depuis utils/");
    }
}


class SismeGamesEditor {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_hooks();
    }
    
    private function init_hooks() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_sisme_create_tag', array($this, 'handle_ajax_create_tag'));
        add_action('wp_ajax_sisme_create_category', array($this, 'handle_ajax_create_category'));
        add_action('wp_ajax_sisme_create_entity', array($this, 'handle_ajax_create_entity'));
        add_action('wp_ajax_sisme_delete_game_data', array($this, 'ajax_delete_game_data'));
        add_action('wp_ajax_sisme_toggle_team_choice', array($this, 'handle_toggle_team_choice'));
        add_action('init', array($this, 'init_modules_system'));

        $this->include_files();
        sisme_load_utils();

        new Sisme_Assets_Loader();
        new Sisme_Content_Filter();
    }
    
    private function include_files() {
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/assets-loader.php';
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/content-filter.php';
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/module-admin-page-table-game-data.php';
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/vedettes/vedettes-loader.php';
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/cards/cards-loader.php';
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/tools/emoji-helper.php';
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/search/search-loader.php';
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/team-choice/team-choice-loader.php';
    }

    public function add_admin_menu() {
        // Menu principal - Game Data comme page d'accueil
        add_menu_page(
            'Sisme Games',
            'Sisme Games',
            'manage_options',
            'sisme-games-game-data',
            array($this, 'game_data_page'),
            'dashicons-games',
            30
        );
        
        // Sous-menu Game Data (page d'accueil)
        add_submenu_page(
            'sisme-games-game-data',
            'Tableau de Bord',
            '💻 Tableau de Bord',
            'manage_options',
            'sisme-games-game-data',
            array($this, 'game_data_page')
        );

        // Sous-menu Créer/Éditer Jeu
        add_submenu_page(
            'sisme-games-game-data',
            'Créer Jeu',
            '📝 Créer Jeu',
            'manage_options',
            'sisme-games-edit-game-data',
            array($this, 'edit_game_data_page')
        );

        // Sous-menu Vedettes
        add_submenu_page(
            'sisme-games-game-data',
            'Vedettes',
            '⭐ Vedettes',
            'manage_options',
            'sisme-games-vedettes',
            array($this, 'vedettes_page')
        );

        // Sous-menu Créer Fiche (masqué du menu, accessible via liens)
        add_submenu_page(
            null,
            'Créer Fiche de Jeu',
            'Créer Fiche', 
            'manage_options',
            'sisme-games-edit-fiche-jeu',
            array($this, 'edit_fiche_jeu_page')
        );
    }

    // ===================================
    // PAGES ADMIN ESSENTIELLES
    // ===================================

    public function game_data_page() {
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'admin/pages/game-data.php';
    }

    public function edit_game_data_page() {
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'admin/pages/edit-game-data.php';
    }

    public function edit_fiche_jeu_page() {
        include_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'admin/pages/edit-fiche-jeu.php';
    }

    public function vedettes_page() {
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'admin/pages/vedettes.php';
    }

    // ===================================
    // AJAX HANDLERS ESSENTIELS
    // ===================================

    public function handle_ajax_create_tag() {
        if (!wp_verify_nonce($_POST['nonce'], 'sisme_create_tag')) {
            wp_die('Sécurité : nonce invalide');
        }
        
        $tag_name = sanitize_text_field($_POST['tag_name']);
        if (empty($tag_name)) {
            wp_send_json_error('Nom du tag requis');
        }
        
        $existing_tag = get_term_by('name', $tag_name, 'post_tag');
        if ($existing_tag) {
            wp_send_json_success(array(
                'term_id' => $existing_tag->term_id,
                'name' => $existing_tag->name
            ));
        }
        
        $new_tag = wp_insert_term($tag_name, 'post_tag');
        if (is_wp_error($new_tag)) {
            wp_send_json_error('Erreur : ' . $new_tag->get_error_message());
        }
        
        $tag_info = get_term($new_tag['term_id'], 'post_tag');
        wp_send_json_success(array(
            'term_id' => $tag_info->term_id,
            'name' => $tag_info->name
        ));
    }

    public function handle_ajax_create_category() {
        if (!wp_verify_nonce($_POST['nonce'], 'sisme_create_category')) {
            wp_die('Sécurité : nonce invalide');
        }
        
        $category_name = sanitize_text_field($_POST['category_name']);
        $parent_id = intval($_POST['parent_id']);
        
        if (empty($category_name)) {
            wp_send_json_error('Nom de la catégorie requis');
        }
        
        $existing_category = get_term_by('name', $category_name, 'category');
        if ($existing_category) {
            wp_send_json_success(array(
                'term_id' => $existing_category->term_id,
                'name' => $existing_category->name
            ));
        }
        
        $new_category = wp_insert_term($category_name, 'category', array('parent' => $parent_id));
        if (is_wp_error($new_category)) {
            wp_send_json_error('Erreur : ' . $new_category->get_error_message());
        }
        
        $category_info = get_term($new_category['term_id'], 'category');
        wp_send_json_success(array(
            'term_id' => $category_info->term_id,
            'name' => $category_info->name
        ));
    }

    public function handle_ajax_create_entity() {
        if (!wp_verify_nonce($_POST['nonce'], 'sisme_create_entity')) {
            wp_die('Sécurité : nonce invalide');
        }
        
        $entity_name = sanitize_text_field($_POST['entity_name']);
        $entity_website = esc_url_raw($_POST['entity_website']);
        
        if (empty($entity_name)) {
            wp_send_json_error('Nom de l\'entité requis');
        }
        
        $parent_category = get_category_by_slug('editeurs-developpeurs');
        if (!$parent_category) {
            wp_send_json_error('Catégorie parent "editeurs-developpeurs" introuvable');
        }
        
        $existing_entity = get_term_by('name', $entity_name, 'category');
        if ($existing_entity && $existing_entity->parent == $parent_category->term_id) {
            wp_send_json_success(array(
                'term_id' => $existing_entity->term_id,
                'name' => $existing_entity->name,
                'website' => get_term_meta($existing_entity->term_id, 'website_url', true)
            ));
        }
        
        $new_entity = wp_insert_term($entity_name, 'category', array('parent' => $parent_category->term_id));
        if (is_wp_error($new_entity)) {
            wp_send_json_error('Erreur : ' . $new_entity->get_error_message());
        }
        
        if (!empty($entity_website)) {
            update_term_meta($new_entity['term_id'], 'website_url', $entity_website);
        }
        
        $entity_info = get_term($new_entity['term_id'], 'category');
        wp_send_json_success(array(
            'term_id' => $entity_info->term_id,
            'name' => $entity_info->name,
            'website' => $entity_website
        ));
    }

    public function ajax_delete_game_data() {
        if (!check_ajax_referer('sisme_delete_game_data', 'nonce', false)) {
            wp_send_json_error('Erreur de sécurité');
        }
        
        $game_id = intval($_POST['game_id']);
        if (!$game_id) {
            wp_send_json_error('ID du jeu requis');
        }
        
        $tag = get_term($game_id, 'post_tag');
        if (!$tag || is_wp_error($tag)) {
            wp_send_json_error('Jeu introuvable');
        }
        
        $meta_keys = [
            'game_description', 'game_genres', 'game_modes', 'game_developers', 
            'game_publishers', 'game_platforms', 'release_date', 'external_links',
            'trailer_link', 'cover_main', 'cover_news', 'cover_patch', 'cover_test',
            'screenshots', 'game_sections', 'last_update'
        ];
        
        foreach ($meta_keys as $meta_key) {
            delete_term_meta($game_id, $meta_key);
        }
        
        wp_send_json_success('Données du jeu supprimées avec succès');
    }

    public function init_modules_system() {
        foreach (SISME_GAMES_MODULES as $module_name) {
            $loader_file = SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/' . $module_name . '/' . $module_name . '-loader.php';
            if (file_exists($loader_file)) {
                require_once $loader_file;
                $class_name = 'Sisme_' . ucfirst(str_replace('-', '_', $module_name)) . '_Loader';
                if (class_exists($class_name) && method_exists($class_name, 'get_instance')) {
                    $class_name::get_instance();
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('[Sisme] Module initialisé : ' . $module_name . ' (' . str_replace('-', '_', $class_name) . ')');
                    }
                } else {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('[Sisme] Classe introuvable : ' . class_name);
                    }
                }
            } else {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('[Sisme] Module introuvable : ' . $module_name . ' (' . $loader_file . ')');
                }
            }
        }
    }

    public function handle_toggle_team_choice() {
        if (!current_user_can('manage_categories')) {
            wp_die('Permissions insuffisantes', 403);
        }
        
        if (!check_ajax_referer('sisme_team_choice_nonce', 'nonce', false)) {
            wp_die('Nonce invalide', 403);
        }
        
        $term_id = intval($_POST['term_id'] ?? 0);
        $current_value = sanitize_text_field($_POST['current_value'] ?? '0');
        
        if (!$term_id) {
            wp_send_json_error('ID de jeu invalide');
        }
        
        $new_value = ($current_value === '1') ? '0' : '1';
        $success = update_term_meta($term_id, 'is_team_choice', $new_value);
        
        if ($success !== false) {
            wp_send_json_success(array(
                'new_value' => $new_value,
                'icon' => ($new_value === '1') ? '💖' : '🤍',
                'title' => ($new_value === '1') ? 'Retirer des choix équipe' : 'Ajouter aux choix équipe',
                'class' => ($new_value === '1') ? 'team-choice-active' : 'team-choice-inactive'
            ));
        } else {
            wp_send_json_error('Erreur lors de la mise à jour');
        }
    }
}

SismeGamesEditor::get_instance();