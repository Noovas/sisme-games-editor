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
    private $is_menu;
    private $menu_title;
    private $menu_icon;
    
    /**
     * Constructeur
     * 
     * @param string $title Titre principal de la page
     * @param string $subtitle Sous-titre explicatif (optionnel)
     * @param string|mixed $icon Classe dashicon, HTML complet, ou identifiant d'icône prédéfinie (optionnel)
     * @param string $back_url URL du bouton retour (optionnel)
     * @param string $back_text Texte du bouton retour (optionnel)
     * @param bool $is_menu Indique si c'est un menu (optionnel, par défaut false)
     * @param string $menu_title Titre du menu (optionnel)
     * @param string $menu_icon Icône du menu (optionnel, par défaut 'dashboard')
     */

    public function __construct($title, $subtitle = '', $icon = 'dashboard', $back_url = '', $back_text = 'Retour', $is_menu = false, $menu_icon = '', $menu_title = '') {
        $this->title = $title;
        $this->subtitle = $subtitle;
        $this->icon = $icon;
        $this->back_url = $back_url;
        $this->back_text = $back_text;
        $this->is_menu = $is_menu;
        $this->menu_title = $menu_title;
        $this->menu_icon = $menu_icon;
    }
    
    /**
     * Récupère une icône prédéfinie (émoji)
     * 
     * @param string $icon_id Identifiant de l'icône
     * @return string HTML de l'émoji
     */
    public static function get_predefined_icon($icon_id) {
        $icons = array(
            // Icônes principales du plugin
            'dashboard' => '<span style="margin-right: 12px; font-size: 28px;">📊</span>',
            'stats' => '<span style="margin-right: 12px; font-size: 28px;">📊</span>',
            'stat' => '<span style="margin-right: 12px; font-size: 28px;">📊</span>',

            'game' => '<span style="margin-right: 12px; font-size: 28px;">🎮</span>',
            'jeu' => '<span style="margin-right: 12px; font-size: 28px;">🎮</span>',
            'games' => '<span style="margin-right: 12px; font-size: 28px;">🎮</span>',
            'jeux' => '<span style="margin-right: 12px; font-size: 28px;">🎮</span>',

            
            'outils' => '<span style="margin-right: 12px; font-size: 28px;">🧰</span>',
            'tools' => '<span style="margin-right: 12px; font-size: 28px;">🧰</span>',

            'patch' => '<span style="margin-right: 12px; font-size: 28px;">🔧</span>',
            'reparer' => '<span style="margin-right: 12px; font-size: 28px;">🔧</span>',
            'repair' => '<span style="margin-right: 12px; font-size: 28px;">🔧</span>',

            'com' => '<span style="margin-right: 12px; font-size: 28px;">📢</span>',
            'comm' => '<span style="margin-right: 12px; font-size: 28px;">📢</span>',
            'communication' => '<span style="margin-right: 12px; font-size: 28px;">📢</span>',
            'communications' => '<span style="margin-right: 12px; font-size: 28px;">📢</span>',
            'annonce' => '<span style="margin-right: 12px; font-size: 28px;">📢</span>',
            'annonces' => '<span style="margin-right: 12px; font-size: 28px;">📢</span>',

            'notifications' => '<span style="margin-right: 12px; font-size: 28px;">🔔</span>',
            'notification' => '<span style="margin-right: 12px; font-size: 28px;">🔔</span>',
            'notif' => '<span style="margin-right: 12px; font-size: 28px;">🔔</span>',
            'notifs' => '<span style="margin-right: 12px; font-size: 28px;">🔔</span>',

            'email' => '<span style="margin-right: 12px; font-size: 28px;">📧</span>',
            'emails' => '<span style="margin-right: 12px; font-size: 28px;">📧</span>',
            'mail' => '<span style="margin-right: 12px; font-size: 28px;">📧</span>',
            'emails' => '<span style="margin-right: 12px; font-size: 28px;">📧</span>',
            '@' => '<span style="margin-right: 12px; font-size: 28px;">📧</span>',
            
            'star' => '<span style="margin-right: 12px; font-size: 28px;">⭐</span>',
            'stars' => '<span style="margin-right: 12px; font-size: 28px;">⭐</span>',
            'vedette' => '<span style="margin-right: 12px; font-size: 28px;">⭐</span>',
            'vedettes' => '<span style="margin-right: 12px; font-size: 28px;">⭐</span>',
            'featured' => '<span style="margin-right: 12px; font-size: 28px;">⭐</span>',

            'no-featured' => '<span style="margin-right: 12px; font-size: 28px;">☆</span>',
            'no-star' => '<span style="margin-right: 12px; font-size: 28px;">☆</span>',
            'no-stars' => '<span style="margin-right: 12px; font-size: 28px;">☆</span>',
            'no-vedette' => '<span style="margin-right: 12px; font-size: 28px;">☆</span>',
            'no-vedettes' => '<span style="margin-right: 12px; font-size: 28px;">☆</span>',

            'save' => '<span style="margin-right: 12px; font-size: 28px;">💾</span>',
            'saves' => '<span style="margin-right: 12px; font-size: 28px;">💾</span>',
            'sauvegarde' => '<span style="margin-right: 12px; font-size: 28px;">💾</span>',
            'sauvegardes' => '<span style="margin-right: 12px; font-size: 28px;">💾</span>',
            'sauvegarder' => '<span style="margin-right: 12px; font-size: 28px;">💾</span>',
            'bdd' => '<span style="margin-right: 12px; font-size: 28px;">💾</span>',
            'data' => '<span style="margin-right: 12px; font-size: 28px;">💾</span>',
            'datas' => '<span style="margin-right: 12px; font-size: 28px;">💾</span>',

            'search' => '<span style="margin-right: 12px; font-size: 28px;">🔍</span>',
            'seo' => '<span style="margin-right: 12px; font-size: 28px;">🔍</span>',
            'recherche' => '<span style="margin-right: 12px; font-size: 28px;">🔍</span>',
            'rechercher' => '<span style="margin-right: 12px; font-size: 28px;">🔍</span>',

            'migration' => '<span style="margin-right: 12px; font-size: 28px;">📦</span>',
            'migrations' => '<span style="margin-right: 12px; font-size: 28px;">📦</span>',
            'pack' => '<span style="margin-right: 12px; font-size: 28px;">📦</span>',
            'packs' => '<span style="margin-right: 12px; font-size: 28px;">📦</span>',
            'package' => '<span style="margin-right: 12px; font-size: 28px;">📦</span>',
            'packages' => '<span style="margin-right: 12px; font-size: 28px;">📦</span>',

            'users' => '<span style="margin-right: 12px; font-size: 28px;">👥</span>',
            'user' => '<span style="margin-right: 12px; font-size: 28px;">👥</span>',
            'utilisateur' => '<span style="margin-right: 12px; font-size: 28px;">👥</span>',
            'utilisateurs' => '<span style="margin-right: 12px; font-size: 28px;">👥</span>',

            'developpeurs' => '<span style="margin-right: 12px; font-size: 28px;">💻</span>',
            'developpeur' => '<span style="margin-right: 12px; font-size: 28px;">💻</span>',
            'dev' => '<span style="margin-right: 12px; font-size: 28px;">💻</span>',
            'devs' => '<span style="margin-right: 12px; font-size: 28px;">💻</span>',
            'ordi' => '<span style="margin-right: 12px; font-size: 28px;">💻</span>',
            'ordinateur' => '<span style="margin-right: 12px; font-size: 28px;">💻</span>',
            'ordinateurs' => '<span style="margin-right: 12px; font-size: 28px;">💻</span>',
            'pc' => '<span style="margin-right: 12px; font-size: 28px;">💻</span>',

            'premium' => '<span style="margin-right: 12px; font-size: 28px;">💎</span>',
            'premiums' => '<span style="margin-right: 12px; font-size: 28px;">💎</span>',

            'library' => '<span style="margin-right: 12px; font-size: 28px;">📚</span>',
            'librairie' => '<span style="margin-right: 12px; font-size: 28px;">📚</span>',
            'lib' => '<span style="margin-right: 12px; font-size: 28px;">📚</span>',

            'wait' => '<span style="margin-right: 12px; font-size: 28px;">💤</span>',

            'trash' => '<span style="margin-right: 12px; font-size: 28px;">🗑️</span>',
            'delete' => '<span style="margin-right: 12px; font-size: 28px;">🗑️</span>',
            'supprimer' => '<span style="margin-right: 12px; font-size: 28px;">🗑️</span>',
            'suppr' => '<span style="margin-right: 12px; font-size: 28px;">🗑️</span>',
            'del' => '<span style="margin-right: 12px; font-size: 28px;">🗑️</span>',

            'carousel' => '<span style="margin-right: 12px; font-size: 28px;">🎠</span>',

            'puzzle' => '<span style="margin-right: 12px; font-size: 28px;">🧩</span>',

            'shortcode' => '<span style="margin-right: 12px; font-size: 28px;">📋</span>',
            'code' => '<span style="margin-right: 12px; font-size: 28px;">📋</span>',
            'notice' => '<span style="margin-right: 12px; font-size: 28px;">📋</span>',
            'all-articles' => '<span style="margin-right: 12px; font-size: 28px;">📋</span>',
            'article' => '<span style="margin-right: 12px; font-size: 28px;">📋</span>',
            'articles' => '<span style="margin-right: 12px; font-size: 28px;">📋</span>',

            'pending' => '<span style="margin-right: 12px; font-size: 28px;">⏳</span>',
            'attente' => '<span style="margin-right: 12px; font-size: 28px;">⏳</span>',
            'loading' => '<span style="margin-right: 12px; font-size: 28px;">⏳</span>',
            'load' => '<span style="margin-right: 12px; font-size: 28px;">⏳</span>',
            
            'drafts' => '<span style="margin-right: 12px; font-size: 28px;">📝</span>',
            'draft' => '<span style="margin-right: 12px; font-size: 28px;">📝</span>',
            'brouillon' => '<span style="margin-right: 12px; font-size: 28px;">📝</span>',
            'brouillons' => '<span style="margin-right: 12px; font-size: 28px;">📝</span>',

            'news' => '<span style="margin-right: 12px; font-size: 28px;">📰</span>',
            'submission' => '<span style="margin-right: 12px; font-size: 28px;">📰</span>',
            
            'test' => '<span style="margin-right: 12px; font-size: 28px;">🧪</span>',

            // Icônes d'état
            
            'published' => '<span style="main-right: 12px; font-size: 28px;">✅</span>',
            'private' => '<span style="margin-right: 12px; font-size: 28px;">🔒</span>',
            
            // Icônes d'action
            'create' => '<span style="margin-right: 12px; font-size: 28px;">➕</span>',
            'edit' => '<span style="margin-right: 12px; font-size: 28px;">✏️</span>',
            'settings' => '<span style="margin-right: 12px; font-size: 28px;">⚙️</span>',
            'parametres' => '<span style="margin-right: 12px; font-size: 28px;">⚙️</span>',
            'actions' => '<span style="margin-right: 12px; font-size: 28px;">⚡</span>',
            
            // Icônes utilitaires
            'link' => '<span style="margin-right: 12px; font-size: 28px;">🔗</span>',
            'calendar' => '<span style="margin-right: 12px; font-size: 28px;">📅</span>',
            
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
            'dice' => '<span style="margin-right: 12px; font-size: 28px;">🎲</span>',
            'screen' => '<span style="margin-right: 12px; font-size: 28px;">💻</span>'
        );
        
        return isset($icons[$icon_id]) ? $icons[$icon_id] : '';
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
        <div class="sisme-admin-card sisme-admin-gap-6 sisme-admin-flex-col sisme-admin-card-no-transformation sisme-admin-m-md">
            <header class="sisme-admin-card sisme-admin-card-no-transformation">
                <h1 class="sisme-admin-card-header">
                    <span class="sisme-admin-title-icon"><?php echo $this->get_predefined_icon($this->icon); ?></span>
                    <?php echo esc_html($this->title); ?>
                </h1>
                <?php if (!empty($this->subtitle)) : ?>
                    <p class="sisme-admin-subtitle"><?php echo esc_html($this->subtitle); ?></p>
                <?php endif; ?>
                <?php if (!empty($this->back_url)) : ?>
                    <a href="<?php echo esc_url($this->back_url); ?>" class="sisme-admin-btn sisme-admin-btn-success">
                        <span class="dashicons dashicons-arrow-left-alt"></span>
                        <?php echo esc_html($this->back_text); ?>
                    </a>
                <?php endif; ?>
            </header>

            <?php if ($this->is_menu) : ?>
                <div class="sisme-admin-card sisme-admin-card-no-transformation">
                    <h2 class="sisme-admin-title"><?php echo $this->get_predefined_icon($this->menu_icon); ?> <?php echo esc_html($this->menu_title); ?></h2>
                    <div class="sisme-admin-grid sisme-admin-grid-2">
            <?php endif; ?>
        <?php
    }

    /**
     * Rend la partie inférieure de la page
     */
    public function render_end() {
        ?>
                    </div> <!-- .sisme-admin-card -->
            <?php if ($this->is_menu) : ?>
                </div> <!-- .sisme-admin-grid -->
            </div> <!-- .sisme-admin-card -->
            <?php endif; ?>
        <?php
    }

    /**
     * Rend une carte avec un titre, une icône, un sous-titre et du contenu
     * 
     * @param string $card_title Titre de la carte
     * @param string $card_icon Icône de la carte (optionnel)
     * @param string $card_subtitle Sous-titre de la carte (optionnel)
     * @param string $card_added_content_classes Classes CSS supplémentaires pour le contenu de la carte (optionnel)
     * @param bool $card_transformation Indique si la carte doit avoir une transformation (optionnel, par défaut false)
     * @param string $card_content Contenu HTML à afficher dans la carte (optionnel)
     */
    public static function render_card($card_title = '', $card_icon = '', $card_subtitle = '', $card_added_content_classes = '', $card_transformation = false, $card_content = '') {
        self::render_card_start($card_title, $card_icon, $card_subtitle, $card_added_content_classes, $card_transformation, $card_content);
        self::render_card_end();
    }

    /**
     * Rend le début d'une carte
     * 
     * @param string $card_title Titre de la carte
     * @param string $card_icon Icône de la carte (optionnel)
     * @param string $card_subtitle Sous-titre de la carte (optionnel)
     */
    public static function render_card_start($card_title = '', $card_icon = '', $card_subtitle = '', $card_added_content_classes = '', $card_transformation = false, $card_content = '') {
        ?>
        <div class="sisme-admin-card<?php echo !$card_transformation ? ' sisme-admin-card-no-transformation' : ''; ?>">
            <div class="sisme-admin-card-header">
                <h3 class="sisme-admin-heading"><?php echo self::get_predefined_icon($card_icon); ?> <?php echo esc_html($card_title); ?></h3>
            </div>
            <?php if (!empty($card_subtitle)) : ?>
                <p class="sisme-admin-comment"><?php echo esc_html($card_subtitle); ?></p>
            <?php endif; ?>
            <div class="sisme-admin-card-content <?php echo esc_attr($card_added_content_classes); ?>" >
            <?php echo $card_content;
    }

    /**
     * Rend la fin d'une carte
     */
    public static function render_card_end() {
        ?>
                </div> <!-- .sisme-admin-card-content -->
            </div> <!-- .sisme-admin-card -->
        <?php
    }

    /**
     * Rend un menu de carte avec un titre, une icône, un sous-titre et un lien
     * 
     * @param string $menu_title Titre du menu
     * @param string $menu_icon Icône du menu
     * @param string $menu_subtitle Sous-titre du menu
     * @param string $menu_url URL du lien du menu
     */
    public static function render_menu_card($menu_title, $menu_icon, $menu_subtitle, $menu_url) {
        self::render_card_start($menu_title, $menu_icon, $menu_subtitle);
        ?>
        <div class="sisme-admin-flex-between sisme-admin-mt-md">
            <span class="sisme-admin-badge sisme-admin-badge-info"><?php echo esc_html($menu_title); ?></span>
            <a href="<?php echo esc_url($menu_url); ?>" class="sisme-admin-btn sisme-admin-btn-primary">
                <?php echo self::get_predefined_icon($menu_icon); ?> Accéder
            </a>
        </div>
        <?php
        self::render_card_end();
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