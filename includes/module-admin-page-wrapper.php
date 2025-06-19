<?php
/**
 * File: /sisme-games-editor/includes/module-admin-page-wrapper.php
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
        echo '<div class="sisme-notice sisme-notice--' . esc_attr($type) . '">';
        echo esc_html($message);
        echo '</div>';
    }
    
    /**
     * Rend la partie supérieure de la page
     */
    public function render_start() {
        ?>
        <div class="sisme-admin-page">
            <header class="sisme-admin-header">
                <h1 class="sisme-admin-title">
                    <?php if ($this->is_html_icon) : ?>
                        <span class="sisme-admin-title-icon"><?php echo $this->icon; ?></span>
                    <?php elseif (strpos($this->icon, 'dashicons-') === 0) : ?>
                        <span class="sisme-admin-title-icon dashicons <?php echo esc_attr($this->icon); ?>"></span>
                    <?php else : ?>
                        <span class="sisme-admin-title-icon"><?php echo $this->get_predefined_icon($this->icon); ?></span>
                    <?php endif; ?>
                    <?php echo esc_html($this->title); ?>
                </h1>
                <?php if (!empty($this->subtitle)) : ?>
                    <p class="sisme-admin-subtitle"><?php echo esc_html($this->subtitle); ?></p>
                <?php endif; ?>
                <?php if (!empty($this->back_url)) : ?>
                    <a href="<?php echo esc_url($this->back_url); ?>" class="sisme-btn sisme-btn--secondary">
                        <span class="dashicons dashicons-arrow-left-alt"></span>
                        <?php echo esc_html($this->back_text); ?>
                    </a>
                <?php endif; ?>
            </header>
            <div class="sisme-admin-layout">
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