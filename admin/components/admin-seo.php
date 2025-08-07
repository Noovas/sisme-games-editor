<?php
/**
 * File: /sisme-games-editor/admin/components/admin-seo.php
 * Page d'administration SEO - Interface admin unifiée
 * 
 * RESPONSABILITÉ:
 * - Interface admin pour surveiller le SEO des pages de jeux
 * - Debug et diagnostic des modules SEO
 * - Statistiques de performance et cache
 * - Validation des sitemaps et URLs
 * - Gestion du cache SEO
 * - Recherche et analyse de tous les jeux
 * 
 * DÉPENDANCES:
 * - module-admin-page-wrapper.php
 * - seo-game-detector.php
 * - CSS-admin-shared.css (système de style unifié)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_SEO_Admin {
    
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_hidden_page'));
        add_action('wp_ajax_sisme_seo_clear_cache', array(__CLASS__, 'ajax_clear_cache'));
        add_action('wp_ajax_sisme_seo_test_page', array(__CLASS__, 'ajax_test_page'));
        add_action('wp_ajax_sisme_seo_load_games', array(__CLASS__, 'ajax_load_games'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_admin_scripts'));
    }
    
    public static function add_admin_menu() {
        add_submenu_page(
            'sisme-games-tableau-de-bord',
            'SEO des Jeux',
            '🔍 SEO',
            'manage_options',
            'sisme-games-seo',
            array(__CLASS__, 'admin_page')
        );
    }
    
    /**
     * Ajouter comme page cachée (accessible par URL mais pas dans le menu)
     */
    public static function add_hidden_page() {
        add_submenu_page(
            null, // null = page cachée
            'SEO des Jeux',
            'SEO des Jeux',
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
    }
    
    public static function admin_page() {
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/module-admin-page-wrapper.php';
        
        $action = $_GET['action'] ?? '';
        
        if ($action === 'clear_cache' && check_admin_referer('sisme_seo_action')) {
            Sisme_SEO_Loader::clear_all_cache();
            echo '<div class="notice notice-success is-dismissible"><p>Cache SEO vidé avec succès.</p></div>';
        }
        
        if ($action === 'fix_sitemap_rules' && check_admin_referer('sisme_seo_action')) {
            if (class_exists('Sisme_SEO_Sitemap')) {
                $sitemap = new Sisme_SEO_Sitemap();
                $sitemap->force_flush_rewrite_rules();
                echo '<div class="notice notice-success is-dismissible"><p>Règles de réécriture sitemap réparées ! Les URLs .xml devraient maintenant fonctionner.</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>Erreur : Classe Sisme_SEO_Sitemap non trouvée.</p></div>';
            }
        }
        
        $page = new Sisme_Admin_Page_Wrapper(
            'SEO des Jeux',
            'Monitoring et diagnostic du référencement',
            'search',
            admin_url('admin.php?page=sisme-games-outils'),
            'Retour aux outils'
        );
        
        self::render_seo_dashboard();
    }
    
    private static function render_seo_dashboard() {
        $health = Sisme_SEO_Loader::get_health_status();
        $sitemap_stats = self::get_sitemap_stats();
        $cache_stats = Sisme_SEO_Game_Detector::get_cache_stats();
        ?>
        <div class="sisme-admin-container">
            <h2 class="sisme-admin-title">🔍SEO et Sitemap du site</h2>
            <p class="sisme-admin-comment">Analyse des balises SEO des pages de jeux et outils de <strong>référencement</strong></p>
            <div class="sisme-admin-grid sisme-admin-grid-2 sisme-admin-mb-md">               
                <!-- Statut santé SEO -->
                <div class="sisme-admin-card">
                    <div class="sisme-admin-card-header">
                        <h3 class="sisme-admin-heading">📊 État du SEO</h3>
                    </div>
                    <div class="sisme-admin-p-md">
                        <div class="sisme-admin-flex sisme-admin-flex-center sisme-admin-mb-md">
                            <div class="sisme-admin-stat-card sisme-admin-stat-card-<?php echo $health['status'] === 'healthy' ? 'success' : ($health['status'] === 'partial' ? 'warning' : 'danger'); ?>">
                                <span class="sisme-admin-stat-number"><?php echo $health['loaded_count']; ?>/<?php echo $health['total_modules']; ?></span>
                                <span class="sisme-admin-stat-label">Modules chargés</span>
                            </div>
                        </div>
                        
                        <div class="sisme-admin-flex sisme-admin-flex-between sisme-admin-mb-sm">
                            <span class="sisme-admin-small">Cache actif</span>
                            <span class="sisme-admin-badge sisme-admin-badge-<?php echo $cache_stats['active'] ? 'success' : 'danger'; ?>">
                                <?php echo $cache_stats['active'] ? '✅ Oui' : '❌ Non'; ?>
                            </span>
                        </div>
                        
                        <div class="sisme-admin-flex sisme-admin-flex-between">
                            <span class="sisme-admin-small">Statut général</span>
                            <span class="sisme-admin-badge sisme-admin-badge-<?php echo $health['status'] === 'healthy' ? 'success' : ($health['status'] === 'partial' ? 'warning' : 'danger'); ?>">
                                <?php 
                                switch($health['status']) {
                                    case 'healthy': echo '✅ Bon'; break;
                                    case 'partial': echo '⚠️ Partiel'; break;
                                    default: echo '❌ Critique'; 
                                } 
                                ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Modules chargés -->
                <div class="sisme-admin-card">
                    <div class="sisme-admin-card-header">
                        <h3 class="sisme-admin-heading">⚙️ Modules</h3>
                    </div>
                    <div class="sisme-admin-p-md">
                        <?php if (!empty($health['loaded_modules'])): ?>
                            <?php foreach ($health['loaded_modules'] as $module): ?>
                            <div class="sisme-admin-flex sisme-admin-flex-center sisme-admin-mb-sm">
                                <span class="sisme-admin-badge sisme-admin-badge-success">✅</span>
                                <span class="sisme-admin-small sisme-admin-ml-sm"><?php echo esc_html($module); ?></span>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="sisme-admin-flex sisme-admin-flex-center">
                                <span class="sisme-admin-badge sisme-admin-badge-danger">❌</span>
                                <span class="sisme-admin-small sisme-admin-ml-sm">Aucun module chargé</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Stats Sitemap -->
                <div class="sisme-admin-card">
                    <div class="sisme-admin-card-header">
                        <h3 class="sisme-admin-heading">🗺️ Sitemaps</h3>
                    </div>
                    <div class="sisme-admin-p-md">
                        <div class="sisme-admin-grid sisme-admin-grid-2 sisme-admin-mb-md">
                            <div class="sisme-admin-stat-card sisme-admin-stat-card-success">
                                <span class="sisme-admin-stat-number"><?php echo $sitemap_stats['game_pages']; ?></span>
                                <span class="sisme-admin-stat-label">Pages de jeux</span>
                            </div>
                            <div class="sisme-admin-stat-card">
                                <span class="sisme-admin-stat-number"><?php echo $sitemap_stats['normal_pages']; ?></span>
                                <span class="sisme-admin-stat-label">Pages normales</span>
                            </div>
                            <div class="sisme-admin-stat-card">
                                <span class="sisme-admin-stat-number"><?php echo $sitemap_stats['total_pages']; ?></span>
                                <span class="sisme-admin-stat-label">Total indexé</span>
                            </div>
                            <?php if (isset($sitemap_stats['excluded_pages'])): ?>
                            <div class="sisme-admin-stat-card sisme-admin-stat-card-warning">
                                <span class="sisme-admin-stat-number"><?php echo $sitemap_stats['excluded_pages']; ?></span>
                                <span class="sisme-admin-stat-label">Pages exclues</span>
                            </div>
                            <?php endif; ?>
                            <div class="sisme-admin-stat-card sisme-admin-stat-card-<?php echo $sitemap_stats['cache_status'] === 'active' ? 'success' : 'warning'; ?>">
                                <span class="sisme-admin-stat-label">Cache <?php echo $sitemap_stats['cache_status'] === 'active' ? 'Actif' : 'Expiré'; ?></span>
                            </div>
                            <?php if (isset($sitemap_stats['seo_optimized']) && $sitemap_stats['seo_optimized']): ?>
                            <div class="sisme-admin-stat-card sisme-admin-stat-card-success">
                                <span class="sisme-admin-stat-label">✅ SEO optimisé</span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="sisme-admin-flex sisme-admin-flex-center">
                            <a href="<?php echo home_url('/sitemap.xml'); ?>" target="_blank" class="sisme-admin-btn sisme-admin-btn-primary sisme-admin-btn-sm">sitemap.xml</a>
                            <a href="<?php echo home_url('/sitemap-pages.xml'); ?>" target="_blank" class="sisme-admin-btn sisme-admin-btn-secondary sisme-admin-btn-sm">pages.xml</a>
                            <a href="<?php echo home_url('/sitemap-games.xml'); ?>" target="_blank" class="sisme-admin-btn sisme-admin-btn-secondary sisme-admin-btn-sm">games.xml</a>
                        </div>
                    </div>
                </div>
                
                <!-- Actions rapides -->
                <div class="sisme-admin-card">
                    <div class="sisme-admin-card-header">
                        <h3 class="sisme-admin-heading">⚡ Actions</h3>
                    </div>
                    <div class="sisme-admin-p-md">
                        <div class="sisme-admin-grid sisme-admin-grid-1">
                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=sisme-games-seo&action=clear_cache'), 'sisme_seo_action'); ?>" 
                            class="sisme-admin-btn sisme-admin-btn-warning">
                                🧹 Vider le cache
                            </a>
                            
                            <a href="#games-section" 
                            class="sisme-admin-btn sisme-admin-btn-primary"
                            onclick="document.getElementById('games-section').scrollIntoView({behavior: 'smooth'});">
                                🎮 Voir les jeux
                            </a>
                            
                            <a href="<?php echo home_url('/sitemap.xml'); ?>" 
                            target="_blank"
                            class="sisme-admin-btn sisme-admin-btn-secondary">
                                🗺️ Sitemap XML
                            </a>
                            
                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=sisme-games-seo&action=fix_sitemap_rules'), 'sisme_seo_action'); ?>" 
                            class="sisme-admin-btn sisme-admin-btn-danger">
                                🔧 Réparer Sitemap
                            </a>
                        </div>
                    </div>
                </div>
                
            </div>
            
            <!-- Section Pages de Jeux (pleine largeur) -->
            <div id="games-section" class="sisme-admin-section">
                <div class="sisme-admin-card">
                    <div class="sisme-admin-card-header">
                        <h3 class="sisme-admin-heading">🎮 Pages de Jeux - Analyse SEO</h3>
                        <div class="sisme-admin-flex sisme-admin-mt-md">
                            <input type="text" 
                                id="games-search" 
                                placeholder="Rechercher un jeu par titre..." 
                                class="sisme-admin-flex-1 sisme-admin-p-sm sisme-admin-border sisme-admin-rounded"
                                style="margin-right: var(--sisme-admin-spacing-sm);">
                            <button type="button" 
                                    id="refresh-games" 
                                    class="sisme-admin-btn sisme-admin-btn-secondary sisme-admin-btn-sm" 
                                    onclick="sismeRefreshGames()">
                                🔄 Actualiser
                            </button>
                        </div>
                    </div>
                    <div class="sisme-admin-p-md">
                        <div id="games-loading" class="sisme-admin-text-center sisme-admin-p-xl sisme-admin-text-muted" style="display: none;">
                            <p>🔄 Chargement des jeux...</p>
                        </div>
                        <div id="games-list">
                            <!-- Le contenu sera chargé dynamiquement -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script>
        // Variables AJAX pour JavaScript
        const sismeSeo = {
            ajaxurl: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
            nonce: '<?php echo wp_create_nonce('sisme_seo_nonce'); ?>'
        };
        
        // Initialisation au chargement de la page
        document.addEventListener('DOMContentLoaded', function() {
            sismeLoadGames();
            
            // Recherche en temps réel
            const searchInput = document.getElementById('games-search');
            searchInput.addEventListener('input', function() {
                sismeFilterGames(this.value);
            });
        });
        
        // Charger tous les jeux
        function sismeLoadGames() {
            const loadingDiv = document.getElementById('games-loading');
            const gamesListDiv = document.getElementById('games-list');
            
            loadingDiv.style.display = 'block';
            gamesListDiv.innerHTML = '';
            
            jQuery.post(sismeSeo.ajaxurl, {
                action: 'sisme_seo_load_games',
                nonce: sismeSeo.nonce
            }, function(response) {
                loadingDiv.style.display = 'none';
                
                if (response.success && response.data.games) {
                    sismeRenderGames(response.data.games);
                } else {
                    gamesListDiv.innerHTML = '<p class="sisme-admin-text-center sisme-admin-p-xl sisme-admin-text-muted">Aucun jeu trouvé.</p>';
                }
            }).fail(function() {
                loadingDiv.style.display = 'none';
                gamesListDiv.innerHTML = '<p class="sisme-admin-text-center sisme-admin-p-xl sisme-admin-text-danger">❌ Erreur de chargement</p>';
            });
        }
        
        // Actualiser la liste
        function sismeRefreshGames() {
            sismeLoadGames();
        }
        
        // Rendu des jeux
        function sismeRenderGames(games) {
            const gamesListDiv = document.getElementById('games-list');
            
            if (games.length === 0) {
                gamesListDiv.innerHTML = '<p class="sisme-admin-text-center sisme-admin-p-xl sisme-admin-text-muted">Aucun jeu trouvé.</p>';
                return;
            }
            
            let html = '';
            games.forEach(function(game) {
                html += '<div class="sisme-admin-card sisme-admin-mb-sm" data-post-id="' + game.post_id + '" data-title="' + game.title.toLowerCase() + '">';
                html += '  <div class="sisme-admin-flex sisme-admin-flex-between">';
                html += '    <div class="sisme-admin-flex-1">';
                html += '      <div class="sisme-admin-heading sisme-admin-mb-xs">' + game.title + '</div>';
                html += '      <div class="sisme-admin-flex">';
                html += '        <span class="sisme-admin-badge sisme-admin-badge-secondary sisme-admin-mr-sm">ID: ' + game.post_id + '</span>';
                if (game.game_data) {
                    html += '        <span class="sisme-admin-badge sisme-admin-badge-info">Jeu: ' + game.game_data.name + '</span>';
                }
                html += '      </div>';
                html += '    </div>';
                html += '    <div class="sisme-admin-flex">';
                html += '      <a href="' + game.permalink + '" target="_blank" class="sisme-admin-btn sisme-admin-btn-primary sisme-admin-btn-sm sisme-admin-mr-sm">👁️ Voir</a>';
                html += '      <button type="button" class="sisme-admin-btn sisme-admin-btn-success sisme-admin-btn-sm" onclick="sismeSeoTestPage(' + game.post_id + ')">🔍 Test SEO</button>';
                html += '    </div>';
                html += '  </div>';
                html += '  <div class="sisme-admin-alert sisme-admin-alert-info sisme-admin-mt-sm" id="test-results-' + game.post_id + '" style="display:none;"></div>';
                html += '</div>';
            });
            
            gamesListDiv.innerHTML = html;
        }
        
        // Filtrer les jeux
        function sismeFilterGames(searchTerm) {
            const gameItems = document.querySelectorAll('.sisme-admin-card[data-post-id]');
            const lowerSearchTerm = searchTerm.toLowerCase();
            
            gameItems.forEach(function(item) {
                const title = item.getAttribute('data-title');
                const isVisible = title.includes(lowerSearchTerm);
                item.style.display = isVisible ? 'block' : 'none';
            });
        }
        
        // Test SEO détaillé
        function sismeSeoTestPage(postId) {
            const resultsDiv = document.getElementById('test-results-' + postId);
            const button = event.target;
            
            button.disabled = true;
            button.textContent = '🔄 Test...';
            resultsDiv.style.display = 'block';
            resultsDiv.innerHTML = '<p>🔄 Analyse SEO en cours...</p>';
            
            jQuery.post(sismeSeo.ajaxurl, {
                action: 'sisme_seo_test_page',
                post_id: postId,
                nonce: sismeSeo.nonce
            }, function(response) {
                button.disabled = false;
                button.textContent = '🔍 Test SEO';
                
                if (response.success) {
                    sismeRenderSeoResults(resultsDiv, response.data);
                } else {
                    resultsDiv.innerHTML = '<div class="sisme-test-error">❌ Erreur: ' + (response.data || 'Test échoué') + '</div>';
                }
            }).fail(function() {
                button.disabled = false;
                button.textContent = '🔍 Test SEO';
                resultsDiv.innerHTML = '<div class="sisme-test-error">❌ Erreur de connexion</div>';
            });
        }
        
        // Rendu détaillé des résultats SEO
        function sismeRenderSeoResults(container, data) {
            let html = '<div class="sisme-test-results-content">';
            
            html += '<h4>🔍 Analyse SEO Détaillée</h4>';
            
            // Méta SEO (Titre et Description)
            if (data.seo_meta) {
                html += '<div class="sisme-admin-mb-md">';
                html += '  <h5 class="sisme-admin-heading">📝 Méta SEO</h5>';
                
                // Titre SEO
                html += '  <div class="sisme-admin-mb-sm">';
                html += '    <span class="sisme-admin-small">Titre SEO:</span>';
                html += '  </div>';
                html += '  <div class="sisme-admin-alert sisme-admin-alert-' + sismeGetSeoStatusClass(data.seo_meta.title_status) + ' sisme-admin-mb-xs">';
                html += '    ' + data.seo_meta.title;
                html += '  </div>';
                html += '  <div class="sisme-admin-mb-sm">';
                html += '    <small class="sisme-admin-small sisme-admin-text-muted">Longueur: ' + data.seo_meta.title_length + ' caractères ';
                html += sismeGetSeoStatusText(data.seo_meta.title_status, 'title') + '</small>';
                html += '  </div>';
                
                // Description SEO
                html += '  <div class="sisme-admin-mb-sm sisme-admin-mt-md">';
                html += '    <span class="sisme-admin-small">Description SEO:</span>';
                html += '  </div>';
                html += '  <div class="sisme-admin-alert sisme-admin-alert-' + sismeGetSeoStatusClass(data.seo_meta.description_status) + ' sisme-admin-mb-xs">';
                html += '    ' + data.seo_meta.description;
                html += '  </div>';
                html += '  <div class="sisme-admin-mb-sm">';
                html += '    <small class="sisme-admin-small sisme-admin-text-muted">Longueur: ' + data.seo_meta.description_length + ' caractères ';
                html += sismeGetSeoStatusText(data.seo_meta.description_status, 'description') + '</small>';
                html += '  </div>';
                
                html += '</div>';
            }
            
            // Détection du jeu
            html += '<div class="sisme-test-section">';
            html += '  <h5>🎮 Détection du Jeu</h5>';
            html += '  <div class="sisme-test-item">';
            html += '    <span>Type de page:</span> ';
            html += data.page_detection.is_game_page ? '<span class="success">✅ Page de jeu</span>' : '<span class="error">❌ Page normale</span>';
            html += '  </div>';
            
            if (data.page_detection.game_name) {
                html += '  <div class="sisme-test-item">';
                html += '    <span>Nom du jeu:</span> <strong>' + data.page_detection.game_name + '</strong>';
                html += '  </div>';
            }
            html += '</div>';
            
            // État des modules
            html += '<div class="sisme-test-section">';
            html += '  <h5>⚙️ État des Modules</h5>';
            html += '  <div class="sisme-test-item">';
            html += '    <span>Modules actifs:</span> <span class="' + data.seo_health.status + '">';
            html += data.seo_health.loaded_count + '/' + data.seo_health.total_modules + ' (' + Math.round(data.seo_health.health_percentage) + '%)';
            html += '    </span>';
            html += '  </div>';
            html += '</div>';
            
            html += '</div>';
            
            container.innerHTML = html;
        }
        
        // Fonction helper pour les classes de statut SEO
        function sismeGetSeoStatusClass(status) {
            switch(status) {
                case 'good': return 'sisme-seo-good';
                case 'too_short': return 'sisme-seo-warning';
                case 'too_long': return 'sisme-seo-error';
                default: return '';
            }
        }
        
        // Fonction helper pour les textes de statut SEO
        function sismeGetSeoStatusText(status, type) {
            if (type === 'title') {
                switch(status) {
                    case 'good': return '✅ Optimal';
                    case 'too_short': return '⚠️ Trop court (30-60 caractères recommandés)';
                    case 'too_long': return '❌ Trop long (30-60 caractères recommandés)';
                    default: return '';
                }
            } else {
                switch(status) {
                    case 'good': return '✅ Optimal';
                    case 'too_short': return '⚠️ Trop court (120-160 caractères recommandés)';
                    case 'too_long': return '❌ Trop long (120-160 caractères recommandés)';
                    default: return '';
                }
            }
        }
        </script>
        
        <?php
    }
    
    /**
     * Obtenir tous les posts de jeu avec leurs données
     */
    private static function get_all_game_posts() {
        $posts = get_posts(array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        
        $game_posts = array();
        foreach ($posts as $post) {
            if (Sisme_SEO_Game_Detector::is_game_page($post->ID)) {
                $game_data = Sisme_SEO_Game_Detector::get_game_data($post->ID);
                
                $game_posts[] = array(
                    'post_id' => $post->ID,
                    'title' => $post->post_title,
                    'permalink' => get_permalink($post->ID),
                    'game_data' => $game_data
                );
            }
        }
        
        return $game_posts;
    }
    
    /**
     * Méthode AJAX pour charger tous les jeux
     */
    public static function ajax_load_games() {
        check_ajax_referer('sisme_seo_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission insuffisante');
        }
        
        $games = self::get_all_game_posts();
        wp_send_json_success(array('games' => $games));
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
        
        // Récupérer les données de base
        $debug_data = Sisme_SEO_Loader::debug_page($post_id);
        
        // Ajouter les méta SEO spécifiques
        $post = get_post($post_id);
        if ($post) {
            // Simuler l'environnement de la page pour récupérer les métas
            global $wp_query;
            $original_query = $wp_query;
            $wp_query = new WP_Query(array('p' => $post_id, 'post_type' => 'any'));
            $wp_query->is_single = true;
            $wp_query->is_page = ($post->post_type === 'page');
            $wp_query->queried_object = $post;
            $wp_query->queried_object_id = $post_id;
            
            // Récupérer les métas titre et description
            // EXACTEMENT comme elles sont générées par le module SEO
            $seo_title = '';
            $seo_description = '';
            
            // Utiliser les méthodes du module SEO pour avoir les mêmes données que dans le <head>
            if (class_exists('Sisme_SEO_Meta_Tags') && class_exists('Sisme_SEO_Title_Optimizer')) {
                // Utiliser les nouvelles méthodes publiques pour récupérer les meta générées
                $seo_title = Sisme_SEO_Title_Optimizer::get_generated_title($post_id);
                $seo_description = Sisme_SEO_Meta_Tags::get_generated_description($post_id);
            }
            
            // Si les méthodes du module n'existent pas, utiliser la logique de fallback
            if (empty($seo_title) || empty($seo_description)) {
                // Essayer avec nos propres méta fields SEO
                if (empty($seo_title)) {
                    $seo_title = get_post_meta($post_id, '_sisme_seo_title', true);
                }
                if (empty($seo_description)) {
                    $seo_description = get_post_meta($post_id, '_sisme_seo_description', true);
                }
                
                // Fallback vers le titre et extrait du post
                if (empty($seo_title)) {
                    $seo_title = get_the_title($post_id);
                }
                
                if (empty($seo_description)) {
                    if (has_excerpt($post_id)) {
                        $seo_description = get_the_excerpt($post_id);
                    } else {
                        $post_content = get_post_field('post_content', $post_id);
                        $seo_description = wp_trim_words(wp_strip_all_tags($post_content), 25, '...');
                    }
                }
            }
            
            // Ajouter les informations SEO aux données de debug
            $debug_data['seo_meta'] = array(
                'title' => $seo_title,
                'description' => $seo_description,
                'title_length' => strlen($seo_title),
                'description_length' => strlen($seo_description),
                'title_status' => self::get_title_seo_status(strlen($seo_title)),
                'description_status' => self::get_description_seo_status(strlen($seo_description)),
                'post_title' => $post->post_title,
                'post_url' => get_permalink($post_id)
            );
            
            // Restaurer la query originale
            $wp_query = $original_query;
        }
        
        wp_send_json_success($debug_data);
    }
    
    /**
     * Évaluer le statut SEO du titre
     */
    private static function get_title_seo_status($length) {
        if ($length < 30) return 'too_short';
        if ($length > 60) return 'too_long';
        return 'good';
    }
    
    /**
     * Évaluer le statut SEO de la description
     */
    private static function get_description_seo_status($length) {
        if ($length < 120) return 'too_short';
        if ($length > 160) return 'too_long';
        return 'good';
    }
}
