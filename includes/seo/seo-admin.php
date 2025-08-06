<?php
/**
 * File: /sisme-games-editor/includes/seo/seo-admin.php
 * Page d'administration SEO - Monitoring et debug
 * 
 * RESPONSABILITÉ:
 * - Interface admin pour surveiller le SEO des pages de jeux
 * - Debug et diagnostic des modules SEO
 * - Statistiques de performance et cache
 * - Validation des sitemaps et URLs
 * - Gestion du cache SEO
 * 
 * DÉPENDANCES:
 * - module-admin-page-wrapper.php
 * - seo-loader.php
 * - seo-game-detector.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_SEO_Admin {
    
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_admin_menu'));
        add_action('wp_ajax_sisme_seo_clear_cache', array(__CLASS__, 'ajax_clear_cache'));
        add_action('wp_ajax_sisme_seo_test_page', array(__CLASS__, 'ajax_test_page'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_admin_scripts'));
    }
    
    public static function add_admin_menu() {
        add_submenu_page(
            'sisme-games-game-data',
            'SEO des Jeux',
            '🔍 SEO',
            'manage_options',
            'sisme-games-seo',
            array(__CLASS__, 'admin_page')
        );
    }
    
    public static function enqueue_admin_scripts($hook) {
        if ($hook !== 'sisme-games_page_sisme-games-seo') {
            return;
        }
        
        wp_enqueue_script('jquery');
        wp_localize_script('jquery', 'sismeSeo', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sisme_seo_nonce')
        ));
    }
    
    public static function admin_page() {
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/module-admin-page-wrapper.php';
        
        $action = $_GET['action'] ?? '';
        
        if ($action === 'clear_cache' && check_admin_referer('sisme_seo_action')) {
            Sisme_SEO_Loader::clear_all_cache();
            echo '<div class="notice notice-success is-dismissible"><p>Cache SEO vidé avec succès.</p></div>';
        }
        
        $page = new Sisme_Admin_Page_Wrapper(
            'SEO des Jeux',
            'Monitoring et diagnostic du référencement',
            'search',
            admin_url('admin.php?page=sisme-games-game-data'),
            'Retour au tableau de bord'
        );
        
        $page->render_start();
        
        self::render_seo_dashboard();
        
        $page->render_end();
    }
    
    private static function render_seo_dashboard() {
        $health = Sisme_SEO_Loader::get_health_status();
        $game_posts = self::get_game_posts_sample();
        $sitemap_stats = self::get_sitemap_stats();
        $cache_stats = Sisme_SEO_Game_Detector::get_cache_stats();
        ?>
        
        <div class="sisme-seo-dashboard">
            
            <!-- Statut santé SEO -->
            <div class="sisme-card">
                <div class="sisme-card__header">
                    <h3>📊 État du SEO</h3>
                </div>
                <div class="sisme-card__body">
                    <div class="sisme-seo-health">
                        <div class="sisme-health-item">
                            <span class="sisme-health-label">Modules chargés</span>
                            <span class="sisme-health-value <?php echo $health['status'] === 'healthy' ? 'success' : 'warning'; ?>">
                                <?php echo $health['loaded_count']; ?>/<?php echo $health['total_modules']; ?>
                                (<?php echo $health['health_percentage']; ?>%)
                            </span>
                        </div>
                        
                        <div class="sisme-health-item">
                            <span class="sisme-health-label">Status</span>
                            <span class="sisme-health-status <?php echo $health['status']; ?>">
                                <?php 
                                switch($health['status']) {
                                    case 'healthy': echo '✅ Optimal'; break;
                                    case 'partial': echo '⚠️ Partiel'; break;
                                    default: echo '❌ Critique';
                                }
                                ?>
                            </span>
                        </div>
                        
                        <div class="sisme-health-item">
                            <span class="sisme-health-label">Cache</span>
                            <span class="sisme-health-value">
                                <?php echo $cache_stats['total_cached']; ?> éléments
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Actions rapides -->
            <div class="sisme-card">
                <div class="sisme-card__header">
                    <h3>⚡ Actions rapides</h3>
                </div>
                <div class="sisme-card__body">
                    <div class="sisme-seo-actions">
                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=sisme-games-seo&action=clear_cache'), 'sisme_seo_action'); ?>" 
                           class="sisme-action-button warning">
                            🗑️ Vider le cache SEO
                        </a>
                        
                        <a href="<?php echo home_url('/sitemap.xml'); ?>" 
                           target="_blank" 
                           class="sisme-action-button">
                            🗺️ Voir le sitemap
                        </a>
                        
                        <button type="button" 
                                class="sisme-action-button" 
                                onclick="sismeSeoTestRandomPage()">
                            🧪 Tester une page
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Modules SEO -->
            <div class="sisme-card">
                <div class="sisme-card__header">
                    <h3>🔧 Modules SEO</h3>
                </div>
                <div class="sisme-card__body">
                    <div class="sisme-modules-list">
                        <?php foreach ($health['loaded_modules'] as $module): ?>
                        <div class="sisme-module-item success">
                            <span class="sisme-module-icon">✅</span>
                            <span class="sisme-module-name"><?php echo esc_html($module); ?></span>
                        </div>
                        <?php endforeach; ?>
                        
                        <?php if (!empty($health['missing_modules'])): ?>
                            <?php foreach ($health['missing_modules'] as $module): ?>
                            <div class="sisme-module-item error">
                                <span class="sisme-module-icon">❌</span>
                                <span class="sisme-module-name"><?php echo esc_html($module); ?></span>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Statistiques Sitemap -->
            <div class="sisme-card">
                <div class="sisme-card__header">
                    <h3>🗺️ Sitemap</h3>
                </div>
                <div class="sisme-card__body">
                    <div class="sisme-sitemap-stats">
                        <div class="sisme-stat-item">
                            <span class="sisme-stat-label">Pages de jeux</span>
                            <span class="sisme-stat-value"><?php echo $sitemap_stats['game_pages']; ?></span>
                        </div>
                        
                        <div class="sisme-stat-item">
                            <span class="sisme-stat-label">Pages normales</span>
                            <span class="sisme-stat-value"><?php echo $sitemap_stats['normal_pages']; ?></span>
                        </div>
                        
                        <div class="sisme-stat-item">
                            <span class="sisme-stat-label">Total</span>
                            <span class="sisme-stat-value"><?php echo $sitemap_stats['total_pages']; ?></span>
                        </div>
                        
                        <div class="sisme-stat-item">
                            <span class="sisme-stat-label">Cache</span>
                            <span class="sisme-stat-value <?php echo $sitemap_stats['cache_status'] === 'active' ? 'success' : 'warning'; ?>">
                                <?php echo $sitemap_stats['cache_status'] === 'active' ? 'Actif' : 'Expiré'; ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="sisme-sitemap-links">
                        <a href="<?php echo home_url('/sitemap.xml'); ?>" target="_blank">sitemap.xml</a>
                        <a href="<?php echo home_url('/sitemap-pages.xml'); ?>" target="_blank">sitemap-pages.xml</a>
                        <a href="<?php echo home_url('/sitemap-games.xml'); ?>" target="_blank">sitemap-games.xml</a>
                    </div>
                </div>
            </div>
            
            <!-- Test pages de jeux -->
            <div class="sisme-card">
                <div class="sisme-card__header">
                    <h3>🧪 Pages de jeux (échantillon)</h3>
                </div>
                <div class="sisme-card__body">
                    <div class="sisme-games-sample">
                        <?php if (!empty($game_posts)): ?>
                            <?php foreach ($game_posts as $post): ?>
                            <div class="sisme-game-item" data-post-id="<?php echo $post->ID; ?>">
                                <div class="sisme-game-info">
                                    <strong><?php echo esc_html($post->post_title); ?></strong>
                                    <span class="sisme-game-meta">ID: <?php echo $post->ID; ?></span>
                                </div>
                                
                                <div class="sisme-game-actions">
                                    <a href="<?php echo get_permalink($post->ID); ?>" target="_blank" class="sisme-view-link">Voir</a>
                                    <button type="button" 
                                            class="sisme-test-button" 
                                            onclick="sismeSeoTestPage(<?php echo $post->ID; ?>)">
                                        Test SEO
                                    </button>
                                </div>
                                
                                <div class="sisme-test-results" id="test-results-<?php echo $post->ID; ?>" style="display:none;"></div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>Aucune page de jeu trouvée.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
        </div>
        
        <script>
        function sismeSeoTestPage(postId) {
            const resultsDiv = document.getElementById('test-results-' + postId);
            const button = event.target;
            
            button.disabled = true;
            button.textContent = 'Test...';
            resultsDiv.style.display = 'block';
            resultsDiv.innerHTML = '<p>🔄 Test en cours...</p>';
            
            jQuery.post(sismeSeo.ajaxurl, {
                action: 'sisme_seo_test_page',
                post_id: postId,
                nonce: sismeSeo.nonce
            }, function(response) {
                if (response.success) {
                    let html = '<div class="sisme-test-results-content">';
                    const data = response.data;
                    
                    html += '<h4>🔍 Résultats du test</h4>';
                    html += '<div class="sisme-test-item">';
                    html += '<span>Détection jeu:</span> ';
                    html += data.page_detection.is_game_page ? '<span class="success">✅ Oui</span>' : '<span class="error">❌ Non</span>';
                    html += '</div>';
                    
                    if (data.page_detection.game_name) {
                        html += '<div class="sisme-test-item">';
                        html += '<span>Nom du jeu:</span> <strong>' + data.page_detection.game_name + '</strong>';
                        html += '</div>';
                    }
                    
                    html += '<div class="sisme-test-item">';
                    html += '<span>Santé SEO:</span> <span class="' + data.seo_health.status + '">';
                    html += data.seo_health.loaded_count + '/' + data.seo_health.total_modules + ' modules';
                    html += '</span></div>';
                    
                    html += '</div>';
                    resultsDiv.innerHTML = html;
                } else {
                    resultsDiv.innerHTML = '<p class="error">❌ Erreur: ' + (response.data || 'Test échoué') + '</p>';
                }
                
                button.disabled = false;
                button.textContent = 'Test SEO';
            });
        }
        
        function sismeSeoTestRandomPage() {
            const gameItems = document.querySelectorAll('.sisme-game-item');
            if (gameItems.length > 0) {
                const randomItem = gameItems[Math.floor(Math.random() * gameItems.length)];
                const postId = randomItem.getAttribute('data-post-id');
                sismeSeoTestPage(postId);
                randomItem.scrollIntoView({ behavior: 'smooth' });
            }
        }
        </script>
        
        <?php
    }
    
    private static function get_game_posts_sample() {
        $posts = get_posts(array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'numberposts' => 10,
            'orderby' => 'rand'
        ));
        
        $game_posts = array();
        foreach ($posts as $post) {
            if (Sisme_SEO_Game_Detector::is_game_page($post->ID)) {
                $game_posts[] = $post;
                if (count($game_posts) >= 5) break;
            }
        }
        
        return $game_posts;
    }
    
    private static function get_sitemap_stats() {
        if (class_exists('Sisme_SEO_Sitemap')) {
            $sitemap = new Sisme_SEO_Sitemap();
            return $sitemap->get_sitemap_stats();
        }
        
        return array(
            'game_pages' => 0,
            'normal_pages' => 0,
            'total_pages' => 0,
            'cache_status' => 'unknown'
        );
    }
    
    public static function ajax_clear_cache() {
        check_ajax_referer('sisme_seo_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Permission insuffisante');
        }
        
        Sisme_SEO_Loader::clear_all_cache();
        
        wp_send_json_success('Cache SEO vidé avec succès');
    }
    
    public static function ajax_test_page() {
        check_ajax_referer('sisme_seo_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Permission insuffisante');
        }
        
        $post_id = intval($_POST['post_id']);
        if (!$post_id) {
            wp_send_json_error('ID de post invalide');
        }
        
        $debug_data = Sisme_SEO_Loader::debug_page($post_id);
        
        wp_send_json_success($debug_data);
    }
}