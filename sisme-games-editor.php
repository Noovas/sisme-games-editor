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

define('SISME_GAMES_EDITOR_VERSION', '1.0.0');
define('SISME_GAMES_EDITOR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SISME_GAMES_EDITOR_PLUGIN_URL', plugin_dir_url(__FILE__));

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
        add_action('admin_init', array($this, 'handle_form_submission'));
        add_action('wp_ajax_sisme_create_fiche', array($this, 'ajax_create_fiche'));
        add_action('wp_ajax_sisme_add_editor', array($this, 'ajax_add_editor'));
        add_action('wp_ajax_sisme_load_more_articles', array($this, 'ajax_load_more_articles'));
        add_action('wp_ajax_sisme_create_tag', array($this, 'handle_ajax_create_tag'));

        $this->include_files();

        new Sisme_Assets_Loader();
        new Sisme_Content_Filter();
        new Sisme_SEO_Enhancements();
        new Sisme_News_Manager();
        //new Sisme_Game_Form_Module();

    }
    
    private function include_files() {
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/form-handler.php';
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/assets-loader.php';
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/content-filter.php';
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/seo-enhancements.php';
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/news-manager.php';
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/patch-news-handler.php';
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/module-admin-page-table-game-data.php';

    }

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
    
    public function add_admin_menu() {
        add_menu_page(
            'Sisme Games Editor',
            'Games Editor',
            'manage_options',
            'sisme-games-editor',
            array($this, 'dashboard_page'),
            'dashicons-games',
            30
        );
        
        add_submenu_page(
            'sisme-games-editor',
            'Tableau de bord',
            'Tableau de bord',
            'manage_options',
            'sisme-games-editor',
            array($this, 'dashboard_page')
        );

        add_submenu_page(
            'sisme-games-editor',
            'Tous les articles',
            'Tous les articles',
            'manage_options',
            'sisme-games-all-articles',
            array($this, 'all_articles_page')
        );
        
        add_submenu_page(
            'sisme-games-editor',
            'Fiches de jeu',
            'Fiches de jeu',
            'manage_options',
            'sisme-games-fiches',
            array($this, 'fiches_page')
        );
        
        add_submenu_page(
            'sisme-games-editor',
            'Patch & News',
            'Patch & News',
            'manage_options',
            'sisme-games-patch-news',
            array($this, 'patch_news_page')
        );
        
        add_submenu_page(
            'sisme-games-editor',
            'Tests',
            'Tests',
            'manage_options',
            'sisme-games-tests',
            array($this, 'tests_page')
        );
        
        add_submenu_page(
            'sisme-games-editor',
            'Réglages',
            'Réglages',
            'manage_options',
            'sisme-games-settings',
            array($this, 'settings_page')
        );

        add_submenu_page(
            'sisme-games-editor',
            'Game Data',
            'Game Data',
            'manage_options',
            'sisme-games-game-data',
            array($this, 'game_data_page')
        );

        add_submenu_page(
            null, 
            'Éditer une fiche',
            'Éditer une fiche',
            'manage_options',
            'sisme-games-edit-fiche',
            array($this, 'edit_fiche_page')
        );

        add_submenu_page(
            null,
            'Éditeur interne Sisme Games',
            'Éditeur interne',
            'manage_options',
            'sisme-games-internal-editor',
            array($this, 'internal_editor_page')
        );

        add_submenu_page(
            null, 
            'Éditer un article Patch & News',
            'Éditer Patch & News',
            'manage_options',
            'sisme-games-edit-patch-news',
            array($this, 'edit_patch_news_page')
        );

        add_submenu_page(
            null,
            'Édition de Test',
            'Édition de Test',
            'manage_options',
            'sisme-games-edit-test',
            array($this, 'edit_test_page')
        );

        add_submenu_page(
            null,
            'Éditer Game Data',
            'Éditer Game Data',
            'manage_options',
            'sisme-games-edit-game-data',
            array($this, 'edit_game_data_page')
        );


    }
    
    public function handle_form_submission() {
        if (!isset($_POST['sisme_form_action']) && !isset($_POST['sisme_edit_action']) && !isset($_POST['sisme_patch_news_action'])) {
            return;
        }
        
        if (isset($_POST['sisme_form_action'])) {
            if (!wp_verify_nonce($_POST['sisme_nonce'], 'sisme_form')) {
                wp_die('Erreur de sécurité');
            }
        } elseif (isset($_POST['sisme_edit_action'])) {
            if (!wp_verify_nonce($_POST['sisme_edit_nonce'], 'sisme_edit_form')) {
                wp_die('Erreur de sécurité');
            }
        } elseif (isset($_POST['sisme_patch_news_action'])) {
            if (!wp_verify_nonce($_POST['sisme_edit_patch_news_nonce'], 'sisme_edit_patch_news_form')) {
                wp_die('Erreur de sécurité');
            }
        }
        
        $form_handler = new Sisme_Form_Handler();
        
        if (isset($_POST['sisme_form_action'])) {
            switch ($_POST['sisme_form_action']) {
                case 'create_fiche_step1':
                    $form_handler->handle_fiche_step1();
                    break;
                case 'create_fiche_step2':
                    $form_handler->handle_fiche_step2();
                    break;
            }
        } elseif (isset($_POST['sisme_edit_action'])) {
            switch ($_POST['sisme_edit_action']) {
                case 'create_fiche':
                    $form_handler->handle_fiche_creation();
                    break;
                case 'update_fiche':
                    $form_handler->handle_fiche_update();
                    break;
            }
        } elseif (isset($_POST['sisme_patch_news_action'])) {
            $patch_news_handler = new Sisme_Patch_News_Handler();
            
            switch ($_POST['sisme_patch_news_action']) {
                case 'create_article':
                    $patch_news_handler->handle_creation();
                    break;
                case 'update_article':
                    $patch_news_handler->handle_update();
                    break;
            }
        }
    }
    
    public function ajax_create_fiche() {
        check_ajax_referer('sisme_form', 'nonce');
        
        $form_handler = new Sisme_Form_Handler();
        $result = $form_handler->create_complete_fiche($_POST);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }

    public function ajax_add_editor() {
        check_ajax_referer('sisme_editor', 'nonce');
        
        $editor_id = sanitize_text_field($_POST['editor_id']);
        $section_number = intval($_POST['section_number']);
        
        ob_start();
        wp_editor('', $editor_id, array(
            'textarea_name' => 'sections[' . $section_number . '][content]',
            'textarea_rows' => 8,
            'media_buttons' => true,
            'teeny' => false,
            'tinymce' => true
        ));
        $editor_html = ob_get_clean();
        
        wp_send_json_success($editor_html);
    }
    
    public function dashboard_page() {
        include_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'admin/pages/dashboard.php';
    }
    
    public function fiches_page() {
        include_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'admin/pages/fiches.php';
    }
    
    public function patch_news_page() {
        include_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'admin/pages/patch-news.php';
    }
    
    public function tests_page() {
        include_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'admin/pages/tests.php';
    }
    
    public function settings_page() {
        include_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'admin/pages/settings.php';
    }

    public function edit_fiche_page() {
        include_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'admin/pages/edit-fiche.php';
    }

    public function internal_editor_page() {
        include_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'admin/pages/internal-editor.php';
    }

    public function edit_patch_news_page() {
        include_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'admin/pages/edit-patch-news.php';
    }

    public function all_articles_page() {
        include_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'admin/pages/all-articles.php';
    }

    public function edit_test_page() {
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'admin/pages/edit-test.php';
    }

    public function game_data_page() {
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'admin/pages/game-data.php';
    }

    public function edit_game_data_page() {
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'admin/pages/edit-game-data.php';
    }

    public function ajax_load_more_articles() {
        // Vérification de sécurité
        if (!wp_verify_nonce($_POST['nonce'], 'sisme_load_more')) {
            wp_die('Erreur de sécurité');
        }
        
        $page_num = intval($_POST['page_num']);
        $search = sanitize_text_field($_POST['search']);
        $filter_type = sanitize_text_field($_POST['filter_type']);
        $filter_status = sanitize_text_field($_POST['filter_status']);
        
        // Fonction utilitaire pour récupérer les catégories Sisme
        $sisme_categories = $this->get_sisme_categories_for_ajax();
        
        // Configuration de la requête
        $args = array(
            'post_type' => 'post',
            'post_status' => array('publish', 'draft', 'private'),
            'posts_per_page' => 10,
            'paged' => $page_num,
            'category__in' => $sisme_categories,
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        // Appliquer les filtres
        if (!empty($search)) {
            $args['s'] = $search;
        }
        
        if (!empty($filter_type)) {
            switch ($filter_type) {
                case 'fiches':
                    $fiches_categories = array();
                    foreach (get_categories(array('hide_empty' => false)) as $category) {
                        if (strpos($category->slug, 'jeux-') === 0) {
                            $fiches_categories[] = $category->term_id;
                        }
                    }
                    if (!empty($fiches_categories)) {
                        $args['category__in'] = $fiches_categories;
                    }
                    break;
                case 'news':
                    $news_cat = get_category_by_slug('news');
                    if ($news_cat) {
                        $args['cat'] = $news_cat->term_id;
                    }
                    break;
                case 'patch':
                    $patch_cat = get_category_by_slug('patch');
                    if ($patch_cat) {
                        $args['cat'] = $patch_cat->term_id;
                    }
                    break;
                case 'tests':
                    $tests_cat = get_category_by_slug('tests');
                    if ($tests_cat) {
                        $args['cat'] = $tests_cat->term_id;
                    }
                    break;
            }
        }
        
        if (!empty($filter_status)) {
            $args['post_status'] = array($filter_status);
        }
        
        $articles_query = new WP_Query($args);
        
        if (!$articles_query->have_posts()) {
            wp_send_json_error('Aucun article trouvé');
        }
        
        // Générer le HTML des articles
        ob_start();
        
        while ($articles_query->have_posts()) : 
            $articles_query->the_post(); 
            $post_id = get_the_ID();
            $status = get_post_status();
            $article_info = $this->get_article_type_info_for_ajax($post_id);
            
            $status_labels = array(
                'publish' => 'Publié',
                'draft' => 'Brouillon',
                'private' => 'Privé'
            );
            ?>
            <!-- Article avec image à gauche -->
            <div class="article-item">
                <!-- Image à gauche -->
                <div class="article-image">
                    <?php if (has_post_thumbnail()) : ?>
                        <?php echo get_the_post_thumbnail($post_id, 'medium', array('class' => 'article-thumb')); ?>
                    <?php else : ?>
                        <div class="no-image"><?php echo $article_info['icon']; ?></div>
                    <?php endif; ?>
                </div>
                
                <!-- Contenu à droite -->
                <div class="article-content">
                    <!-- Ligne de données -->
                    <div class="article-data">
                        <div class="data-col title-col">
                            <h4 class="article-title">
                                <a href="<?php echo $article_info['edit_url']; ?>">
                                    <?php the_title(); ?>
                                </a>
                            </h4>
                        </div>
                        
                        <div class="data-col type-col">
                            <span class="type-badge <?php echo $article_info['type']; ?>-badge">
                                <?php echo $article_info['icon']; ?> <?php echo $article_info['label']; ?>
                            </span>
                        </div>
                        
                        <div class="data-col status-col">
                            <span class="status-badge status-<?php echo $status; ?>">
                                <?php echo isset($status_labels[$status]) ? $status_labels[$status] : ucfirst($status); ?>
                            </span>
                        </div>
                        
                        <div class="data-col date-col">
                            <div class="date-info">
                                <span class="date"><?php echo get_the_date('j M Y'); ?></span>
                                <span class="time"><?php echo get_the_date('H:i'); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Ligne d'actions -->
                    <div class="article-actions">
                        <a href="<?php echo $article_info['edit_url']; ?>" 
                           class="action-btn edit-btn">✏️ Modifier</a>
                        
                        <?php if ($status === 'publish') : ?>
                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=sisme-games-all-articles&action=unpublish&post_id=' . $post_id), 'unpublish_post_' . $post_id); ?>" 
                               class="action-btn draft-btn">📝 Brouillon</a>
                        <?php else : ?>
                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=sisme-games-all-articles&action=publish&post_id=' . $post_id), 'publish_post_' . $post_id); ?>" 
                               class="action-btn publish-btn">✅ Publier</a>
                        <?php endif; ?>
                        
                        <a href="<?php echo get_permalink($post_id); ?>" 
                           target="_blank" 
                           class="action-btn view-btn">👁️ Voir</a>
                        
                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=sisme-games-all-articles&action=delete&post_id=' . $post_id), 'delete_post_' . $post_id); ?>" 
                           onclick="return confirm('Supprimer cet article ?');"
                           class="action-btn delete-btn">🗑️ Supprimer</a>
                    </div>
                </div>
            </div>
            <?php
        endwhile;
        
        $html = ob_get_clean();
        wp_reset_postdata();
        
        wp_send_json_success(array('html' => $html));
    }
    
    // Méthodes utilitaires pour l'AJAX
    private function get_sisme_categories_for_ajax() {
        $all_categories = get_categories(array('hide_empty' => false));
        $sisme_categories = array();
        
        foreach ($all_categories as $category) {
            if (strpos($category->slug, 'jeux-') === 0 || 
                in_array($category->slug, array('news', 'patch', 'tests'))) {
                $sisme_categories[] = $category->term_id;
            }
        }
        
        return $sisme_categories;
    }
    
    private function get_article_type_info_for_ajax($post_id) {
        $categories = get_the_category($post_id);
        
        foreach ($categories as $category) {
            if (strpos($category->slug, 'jeux-') === 0) {
                return array(
                    'type' => 'fiche',
                    'icon' => '🎮',
                    'label' => 'Fiche',
                    'edit_url' => admin_url('admin.php?page=sisme-games-edit-fiche&post_id=' . $post_id)
                );
            } elseif ($category->slug === 'news') {
                return array(
                    'type' => 'news',
                    'icon' => '📰',
                    'label' => 'News',
                    'edit_url' => admin_url('admin.php?page=sisme-games-edit-patch-news&post_id=' . $post_id)
                );
            } elseif ($category->slug === 'patch') {
                return array(
                    'type' => 'patch',
                    'icon' => '🔧',
                    'label' => 'Patch',
                    'edit_url' => admin_url('admin.php?page=sisme-games-edit-patch-news&post_id=' . $post_id)
                );
            } elseif ($category->slug === 'tests') {
                return array(
                    'type' => 'tests',
                    'icon' => '⭐',
                    'label' => 'Test',
                    'edit_url' => admin_url('admin.php?page=sisme-games-tests&post_id=' . $post_id)
                );
            }
        }
        
        return array(
            'type' => 'other',
            'icon' => '📄',
            'label' => 'Article',
            'edit_url' => get_edit_post_link($post_id)
        );
    }
}

function sisme_games_editor_init() {
    SismeGamesEditor::get_instance();
}
add_action('init', 'sisme_games_editor_init');