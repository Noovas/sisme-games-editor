<?php
/**
 * File: /sisme-games-editor/includes/team-choice/team-choice-loader.php
 * Chargeur principal du système de choix de l'équipe (is_team_choice)
 * 
 * RESPONSABILITÉ:
 * - Inclure tous les modules du système
 * - Initialiser les hooks WordPress
 * - Enregistrer les shortcodes
 * - Charger les assets CSS/JS
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Team_Choice_Loader {
    private static $instance = null;
    private static $modules_loaded = false;

    private function __construct() {
        $this->load_modules();
        $this->init_hooks();
    }
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * 📦 Charger tous les modules du système
     */
    private function load_modules() {
        if (self::$modules_loaded) {
            return;
        }
        
        // Charger les fonctions principales
        require_once dirname(__FILE__) . '/team-choice-functions.php';
        
        self::$modules_loaded = true;
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Sisme Team Choice] Modules chargés avec succès');
        }
    }
    
    /**
     * 🎣 Initialiser les hooks
     */
    private function init_hooks() {
        // Charger les assets selon le contexte
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        
        // Hook pour les actions d'administration (si nécessaire)
        add_action('admin_post_sisme_team_choice_bulk', array($this, 'handle_bulk_actions'));
        
        // Ajouter une colonne dans le tableau Game Data (si le module existe)
        add_filter('sisme_game_data_table_columns', array($this, 'add_table_column'), 10, 1);
        add_filter('sisme_game_data_table_column_content', array($this, 'render_table_column'), 10, 3);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Sisme Team Choice] Hooks initialisés');
        }
    }
    
    /**
     * 🎨 Charger les assets admin
     */
    public function enqueue_admin_assets($hook_suffix) {
        // Charger uniquement sur les pages Sisme Games
        if (strpos($hook_suffix, 'sisme-games') === false) {
            return;
        }
        
        $this->load_team_choice_assets(true);
    }
    
    /**
     * 🌐 Charger les assets frontend
     */
    public function enqueue_frontend_assets() {
        // Charger uniquement si on a des shortcodes team-choice sur la page
        if ($this->page_has_team_choice_content()) {
            $this->load_team_choice_assets(false);
        }
    }
    
    /**
     * 📦 Charger les assets CSS/JS
     */
    private function load_team_choice_assets($is_admin = false) {
        $css_dependencies = array();
        
        // En admin, pas de dépendances spéciales
        // En frontend, dépendre des tokens CSS si disponibles
        if (!$is_admin && wp_style_is('sisme-frontend-tokens', 'registered')) {
            $css_dependencies[] = 'sisme-frontend-tokens';
        }
        
        wp_enqueue_style(
            'sisme-team-choice-css',
            SISME_GAMES_EDITOR_PLUGIN_URL . 'includes/team-choice/assets/team-choice.css',
            $css_dependencies,
            SISME_GAMES_EDITOR_VERSION
        );
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Sisme Team Choice] Assets chargés (' . ($is_admin ? 'admin' : 'frontend') . ')');
        }
    }
    
    /**
     * 🔍 Vérifier si la page contient du contenu team-choice
     */
    private function page_has_team_choice_content() {
        global $post;
        
        if (!$post) {
            return false;
        }
        
        // Chercher les shortcodes avec is_team_choice="true"
        $content = $post->post_content;
        
        // Patterns à chercher
        $patterns = array(
            '/\[game_cards_grid[^\]]*is_team_choice=["\']true["\'][^\]]*\]/',
            '/\[game_cards_carousel[^\]]*is_team_choice=["\']true["\'][^\]]*\]/',
        );
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * 🚀 Gérer les actions en lot
     */
    public function handle_bulk_actions() {
        // Vérifier les permissions
        if (!current_user_can('manage_categories')) {
            wp_die('Permissions insuffisantes');
        }
        
        // Vérifier le nonce
        if (!check_admin_referer('sisme_team_choice_bulk')) {
            wp_die('Action non autorisée');
        }
        
        $action = sanitize_text_field($_POST['bulk_action'] ?? '');
        $term_ids = array_map('intval', $_POST['term_ids'] ?? array());
        
        if (empty($term_ids)) {
            wp_redirect(add_query_arg('error', 'no_selection', wp_get_referer()));
            exit;
        }
        
        $results = array('success' => 0, 'errors' => 0);
        
        switch ($action) {
            case 'set_team_choice':
                $bulk_results = Sisme_Team_Choice_Functions::bulk_set_team_choice($term_ids, true);
                $results['success'] = count($bulk_results['success']);
                $results['errors'] = count($bulk_results['errors']);
                break;
                
            case 'unset_team_choice':
                $bulk_results = Sisme_Team_Choice_Functions::bulk_set_team_choice($term_ids, false);
                $results['success'] = count($bulk_results['success']);
                $results['errors'] = count($bulk_results['errors']);
                break;
                
            default:
                wp_redirect(add_query_arg('error', 'invalid_action', wp_get_referer()));
                exit;
        }
        
        // Redirection avec message de succès
        $redirect_args = array(
            'team_choice_updated' => $results['success']
        );
        
        if ($results['errors'] > 0) {
            $redirect_args['team_choice_errors'] = $results['errors'];
        }
        
        wp_redirect(add_query_arg($redirect_args, wp_get_referer()));
        exit;
    }
    
    /**
     * 📊 Ajouter colonne dans le tableau Game Data
     */
    public function add_table_column($columns) {
        // Ajouter la colonne "Choix équipe" après "Vedette"
        $new_columns = array();
        
        foreach ($columns as $key => $label) {
            $new_columns[$key] = $label;
            
            // Ajouter après la colonne vedette si elle existe
            if ($key === 'vedette' || $key === 'featured') {
                $new_columns['team_choice'] = 'Choix équipe';
            }
        }
        
        // Si pas de colonne vedette, ajouter à la fin
        if (!isset($new_columns['team_choice'])) {
            $new_columns['team_choice'] = 'Choix équipe';
        }
        
        return $new_columns;
    }
    
    /**
     * 🎨 Rendre le contenu de la colonne
     */
    public function render_table_column($content, $column_key, $game_data) {
        if ($column_key !== 'team_choice') {
            return $content;
        }
        
        $term_id = $game_data['term_id'] ?? 0;
        $is_team_choice = Sisme_Team_Choice_Functions::is_team_choice($term_id);
        
        if ($is_team_choice) {
            return '<span class="team-choice-badge team-choice-yes">💖</span>';
        } else {
            return '<span class="team-choice-badge team-choice-no">🤍</span>';
        }
    }
    
    /**
     * 🔧 Méthodes utilitaires statiques
     */
    /**
     * Vérifier si le système est chargé
     */
    public static function is_loaded() {
        return self::$modules_loaded;
    }
    
    /**
     * Obtenir les statistiques des choix de l'équipe
     */
    public static function get_stats() {
        if (!self::is_loaded()) {
            return array('error' => 'Module non chargé');
        }
        
        return array(
            'total_team_choices' => Sisme_Team_Choice_Functions::count_team_choice_games(),
            'total_games' => wp_count_terms('post_tag', array('hide_empty' => false)),
            'module_loaded' => true
        );
    }
}