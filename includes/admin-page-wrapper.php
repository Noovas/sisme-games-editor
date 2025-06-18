<?php
/**
 * File: /sisme-games-editor/includes/admin-page-wrapper.php
 * Module: Wrapper Page Admin - Sisme Games Editor
 * 
 * Ce module fournit une structure réutilisable pour créer des pages d'administration
 * dans le plugin Sisme Games Editor avec un style cohérent.
 * 
 * Utilisation:
 * 1. Inclure ce fichier
 * 2. Appeler la classe avec les paramètres appropriés
 * 
 * Exemple:
 * $page = new Sisme_Admin_Page_Wrapper(
 *     'Titre de la page', 
 *     'Sous-titre explicatif', 
 *     'game'
 * );
 * 
 * $page->render_start();
 * // Votre contenu ici
 * $page->render_end();
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Admin_Page_Wrapper {
    
    private $title;
    private $subtitle;
    private $icon;
    private $is_html_icon;
    private $back_url;
    private $back_text;
    private static $css_loaded = false;
    
    /**
     * Constructeur
     * 
     * @param string $title Titre principal de la page
     * @param string $subtitle Sous-titre explicatif (optionnel)
     * @param string|mixed $icon Classe dashicon, HTML complet, ou identifiant d'icône prédéfinie (optionnel)
     * @param string $back_url URL du bouton retour (optionnel)
     * @param string $back_text Texte du bouton retour (optionnel)
     * @param bool $is_html_icon Si true, $icon est traité comme du HTML, sinon comme une classe dashicon ou identifiant d'icône
     */
    public function __construct($title, $subtitle = '', $icon = 'dashboard', $back_url = '', $back_text = 'Retour', $is_html_icon = false) {
        $this->title = $title;
        $this->subtitle = $subtitle;
        $this->icon = $icon;
        $this->is_html_icon = $is_html_icon;
        $this->back_url = $back_url;
        $this->back_text = $back_text;
        
        // Enregistrer le CSS uniquement une fois
        if (!self::$css_loaded) {
            add_action('admin_enqueue_scripts', array($this, 'enqueue_styles'));
            self::$css_loaded = true;
        }
    }
    
    /**
     * Enregistre et charge les styles CSS
     */
    public function enqueue_styles() {
        // Vérifier si nous sommes dans l'admin et sur une page du plugin
        if (!is_admin()) {
            return;
        }
        
        // Construire le chemin vers le fichier CSS
        $css_file_path = plugin_dir_path(__FILE__) . '../assets/css/admin/sisme-admin-wrapper.css';
        $css_file_url = plugin_dir_url(__FILE__) . '../assets/css/admin/sisme-admin-wrapper.css';
        
        // Si le fichier n'existe pas dans le dossier relatif, essayer avec SISME_GAMES_EDITOR_PLUGIN_URL
        if (!file_exists($css_file_path) && defined('SISME_GAMES_EDITOR_PLUGIN_URL')) {
            $css_file_url = SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/css/admin/sisme-admin-wrapper.css';
            $css_file_path = SISME_GAMES_EDITOR_PLUGIN_PATH . 'assets/css/admin/sisme-admin-wrapper.css';
        }
        
        // Vérifier si le fichier CSS existe
        if (file_exists($css_file_path)) {
            // Enregistrer et charger le CSS avec la version basée sur la modification du fichier
            wp_enqueue_style(
                'sisme-admin-wrapper',
                $css_file_url,
                array(),
                filemtime($css_file_path)
            );
        } else {
            // Si le fichier n'existe pas, utiliser les styles de base en inline
            add_action('admin_head', array($this, 'output_fallback_styles'));
        }
    }
    
    /**
     * Styles CSS de fallback si le fichier externe n'est pas trouvé
     */
    public function output_fallback_styles() {
        ?>
        <style>
        /* Styles de fallback pour Sisme Admin Wrapper */
        .sisme-admin-wrap { margin: 20px 0; }
        .sisme-admin-container {
            max-width: 1200px; margin: 20px auto; padding: 20px;
            background: rgba(236, 240, 241, 0.95); border-radius: 12px;
            border: 1px solid rgba(161, 183, 141, 0.2);
            box-shadow: 0 8px 32px rgba(44, 62, 80, 0.08), 0 2px 8px rgba(44, 62, 80, 0.04);
        }
        .sisme-admin-header { margin-bottom: 32px; padding-bottom: 20px; border-bottom: 2px solid #A1B78D; }
        .sisme-admin-title {
            font-size: 2rem !important; font-weight: 600 !important; color: #2C3E50 !important;
            margin: 0 0 16px !important; display: flex !important; align-items: center !important;
        }
        .sisme-admin-title .dashicons { margin-right: 12px; font-size: 32px; color: #A1B78D; }
        .sisme-admin-content {
            background: white; padding: 32px 40px; border-radius: 12px;
            border: 1px solid rgba(161, 183, 141, 0.15);
            box-shadow: 0 4px 16px rgba(44, 62, 80, 0.06), 0 2px 4px rgba(44, 62, 80, 0.02);
        }
        </style>
        <?php
    }
    
    /**
     * Récupère une icône prédéfinie (émoji)
     * 
     * @param string $icon_id Identifiant de l'icône
     * @return string HTML de l'émoji
     */
    private function get_predefined_icon($icon_id) {
        $icons = array(
            // Icônes principales du plugin
            'dashboard' => '<span style="margin-right: 12px; font-size: 28px;">📊</span>',
            'game' => '<span style="margin-right: 12px; font-size: 28px;">🎮</span>',
            'news' => '<span style="margin-right: 12px; font-size: 28px;">📰</span>',
            'test' => '<span style="margin-right: 12px; font-size: 28px;">🧪</span>',
            'patch' => '<span style="margin-right: 12px; font-size: 28px;">🔧</span>',
            'all-articles' => '<span style="margin-right: 12px; font-size: 28px;">📋</span>',
            
            // Icônes d'état
            'drafts' => '<span style="margin-right: 12px; font-size: 28px;">📝</span>',
            'published' => '<span style="margin-right: 12px; font-size: 28px;">✅</span>',
            'private' => '<span style="margin-right: 12px; font-size: 28px;">🔒</span>',
            
            // Icônes d'action
            'create' => '<span style="margin-right: 12px; font-size: 28px;">➕</span>',
            'edit' => '<span style="margin-right: 12px; font-size: 28px;">✏️</span>',
            'settings' => '<span style="margin-right: 12px; font-size: 28px;">⚙️</span>',
            'actions' => '<span style="margin-right: 12px; font-size: 28px;">⚡</span>',
            
            // Icônes utilitaires
            'link' => '<span style="margin-right: 12px; font-size: 28px;">🔗</span>',
            'star' => '<span style="margin-right: 12px; font-size: 28px;">⭐</span>',
            'calendar' => '<span style="margin-right: 12px; font-size: 28px;">📅</span>',
            'stats' => '<span style="margin-right: 12px; font-size: 28px;">📈</span>',
            
            // Icônes feedback
            'info' => '<span style="margin-right: 12px; font-size: 28px;">ℹ️</span>',
            'warning' => '<span style="margin-right: 12px; font-size: 28px;">⚠️</span>',
            'success' => '<span style="margin-right: 12px; font-size: 28px;">✅</span>',
            'error' => '<span style="margin-right: 12px; font-size: 28px;">❌</span>',
            'help' => '<span style="margin-right: 12px; font-size: 28px;">❓</span>',
            
            // Icônes spéciales
            'trophy' => '<span style="margin-right: 12px; font-size: 28px;">🏆</span>',
            'fire' => '<span style="margin-right: 12px; font-size: 28px;">🔥</span>',
            'rocket' => '<span style="margin-right: 12px; font-size: 28px;">🚀</span>',
            'heart' => '<span style="margin-right: 12px; font-size: 28px;">❤️</span>',
            
            // Icônes métier gaming
            'controller' => '<span style="margin-right: 12px; font-size: 28px;">🕹️</span>',
            'joystick' => '<span style="margin-right: 12px; font-size: 28px;">🎯</span>',
            'puzzle' => '<span style="margin-right: 12px; font-size: 28px;">🧩</span>',
            'dice' => '<span style="margin-right: 12px; font-size: 28px;">🎲</span>'
        );
        
        return isset($icons[$icon_id]) ? $icons[$icon_id] : $icons['dashboard'];
    }
    
    /**
     * Ajoute une notice dans le wrapper
     * 
     * @param string $message Message à afficher
     * @param string $type Type de notice (success, warning, error, info)
     */
    public function add_notice($message, $type = 'info') {
        echo '<div class="sisme-admin-notice notice-' . esc_attr($type) . '">';
        echo esc_html($message);
        echo '</div>';
    }
    
    /**
     * Rend la partie supérieure de la page
     */
    public function render_start() {
        ?>
        <div class="sisme-admin-wrap">
            <div class="sisme-admin-container">
                <div class="sisme-admin-header">
                    <h1 class="sisme-admin-title">
                        <?php if ($this->is_html_icon) : ?>
                            <?php echo $this->icon; ?>
                        <?php elseif (strpos($this->icon, 'dashicons-') === 0) : ?>
                            <span class="dashicons <?php echo esc_attr($this->icon); ?>"></span>
                        <?php else : ?>
                            <?php echo $this->get_predefined_icon($this->icon); ?>
                        <?php endif; ?>
                        <?php echo esc_html($this->title); ?>
                    </h1>
                    <?php if (!empty($this->subtitle)) : ?>
                        <p class="sisme-admin-subtitle"><?php echo esc_html($this->subtitle); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($this->back_url)) : ?>
                        <a href="<?php echo esc_url($this->back_url); ?>" class="sisme-admin-back">
                            <span class="dashicons dashicons-arrow-left-alt"></span>
                            <?php echo esc_html($this->back_text); ?>
                        </a>
                    <?php endif; ?>
                </div>
                <div class="sisme-admin-content">
        <?php
    }
    
    /**
     * Rend la partie inférieure de la page
     */
    public function render_end() {
        ?>
                </div><!-- .sisme-admin-content -->
            </div><!-- .sisme-admin-container -->
        </div><!-- .sisme-admin-wrap -->
        <?php
    }
    
    /**
     * Méthode statique pour un usage rapide
     * 
     * @param string $title
     * @param string $subtitle
     * @param string $icon
     * @param callable $content_callback Fonction qui génère le contenu
     * @param string $back_url
     * @param string $back_text
     */
    public static function quick_page($title, $subtitle = '', $icon = 'dashboard', $content_callback = null, $back_url = '', $back_text = 'Retour') {
        $page = new self($title, $subtitle, $icon, $back_url, $back_text);
        $page->render_start();
        
        if ($content_callback && is_callable($content_callback)) {
            call_user_func($content_callback);
        }
        
        $page->render_end();
    }
    
    /**
     * Rendu d'une page complète avec gestion d'erreur
     * 
     * @param string $title
     * @param string $subtitle
     * @param string $icon
     * @param callable $content_callback
     * @param string $back_url
     * @param string $back_text
     */
    public static function render_page_with_error_handling($title, $subtitle = '', $icon = 'dashboard', $content_callback = null, $back_url = '', $back_text = 'Retour') {
        $page = new self($title, $subtitle, $icon, $back_url, $back_text);
        $page->render_start();
        
        try {
            if ($content_callback && is_callable($content_callback)) {
                call_user_func($content_callback);
            }
        } catch (Exception $e) {
            $page->add_notice('Une erreur est survenue : ' . $e->getMessage(), 'error');
        }
        
        $page->render_end();
    }
}