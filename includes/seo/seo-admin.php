<?php
/**
 * File: /sisme-games-editor/includes/seo/seo-admin-new.php
 * Page d'administration SEO - Version améliorée
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
        add_action('wp_ajax_sisme_seo_load_games', array(__CLASS__, 'ajax_load_games'));
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
            admin_url('admin.php?page=sisme-games-game-data'),
            'Retour au tableau de bord'
        );
        
        $page->render_start();
        
        self::render_seo_dashboard();
        
        $page->render_end();
    }
    
    private static function render_seo_dashboard() {
        $health = Sisme_SEO_Loader::get_health_status();
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
                            <span class="sisme-health-label">Cache actif</span>
                            <span class="sisme-health-status <?php echo $cache_stats['active'] ? 'healthy' : 'critical'; ?>">
                                <?php echo $cache_stats['active'] ? 'Oui' : 'Non'; ?>
                            </span>
                        </div>
                        
                        <div class="sisme-health-item">
                            <span class="sisme-health-label">Statut général</span>
                            <span class="sisme-health-status <?php echo $health['status']; ?>">
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
            </div>
            
            <!-- Modules chargés -->
            <div class="sisme-card">
                <div class="sisme-card__header">
                    <h3>⚙️ Modules</h3>
                </div>
                <div class="sisme-card__body">
                    <div class="sisme-modules-list">
                        <?php if (!empty($health['loaded_modules'])): ?>
                            <?php foreach ($health['loaded_modules'] as $module): ?>
                            <div class="sisme-module-item success">
                                <span class="sisme-module-icon">✅</span>
                                <span class="sisme-module-name"><?php echo esc_html($module); ?></span>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="sisme-module-item error">
                                <span class="sisme-module-icon">❌</span>
                                <span class="sisme-module-name">Aucun module chargé</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Stats Sitemap -->
            <div class="sisme-card">
                <div class="sisme-card__header">
                    <h3>🗺️ Sitemaps</h3>
                </div>
                <div class="sisme-card__body">
                    <div class="sisme-sitemap-stats">
                        <div class="sisme-stat-item">
                            <span class="sisme-stat-label">Pages de jeux</span>
                            <span class="sisme-stat-value success"><?php echo $sitemap_stats['game_pages']; ?></span>
                        </div>
                        <div class="sisme-stat-item">
                            <span class="sisme-stat-label">Pages normales</span>
                            <span class="sisme-stat-value"><?php echo $sitemap_stats['normal_pages']; ?></span>
                        </div>
                        <div class="sisme-stat-item">
                            <span class="sisme-stat-label">Total indexé</span>
                            <span class="sisme-stat-value"><?php echo $sitemap_stats['total_pages']; ?></span>
                        </div>
                        <?php if (isset($sitemap_stats['excluded_pages'])): ?>
                        <div class="sisme-stat-item">
                            <span class="sisme-stat-label">Pages exclues</span>
                            <span class="sisme-stat-value warning"><?php echo $sitemap_stats['excluded_pages']; ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="sisme-stat-item">
                            <span class="sisme-stat-label">Cache</span>
                            <span class="sisme-stat-value <?php echo $sitemap_stats['cache_status'] === 'active' ? 'success' : 'warning'; ?>">
                                <?php echo $sitemap_stats['cache_status'] === 'active' ? 'Actif' : 'Expiré'; ?>
                            </span>
                        </div>
                        <?php if (isset($sitemap_stats['seo_optimized']) && $sitemap_stats['seo_optimized']): ?>
                        <div class="sisme-stat-item">
                            <span class="sisme-stat-label">SEO optimisé</span>
                            <span class="sisme-stat-value success">✅ Oui</span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="sisme-sitemap-links">
                        <a href="<?php echo home_url('/sitemap.xml'); ?>" target="_blank">sitemap.xml</a>
                        <a href="<?php echo home_url('/sitemap-pages.xml'); ?>" target="_blank">sitemap-pages.xml</a>
                        <a href="<?php echo home_url('/sitemap-games.xml'); ?>" target="_blank">sitemap-games.xml</a>
                    </div>
                </div>
            </div>
            
            <!-- Actions rapides -->
            <div class="sisme-card">
                <div class="sisme-card__header">
                    <h3>⚡ Actions</h3>
                </div>
                <div class="sisme-card__body">
                    <div class="sisme-actions-grid">
                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=sisme-games-seo&action=clear_cache'), 'sisme_seo_action'); ?>" 
                           class="sisme-action-card">
                            <div class="sisme-action-icon">🧹</div>
                            <div class="sisme-action-label">Vider le cache</div>
                            <div class="sisme-action-desc">Nettoie tout le cache SEO</div>
                        </a>
                        
                        <a href="#games-section" 
                           class="sisme-action-card"
                           onclick="document.getElementById('games-section').scrollIntoView({behavior: 'smooth'});">
                            <div class="sisme-action-icon">🎮</div>
                            <div class="sisme-action-label">Voir les jeux</div>
                            <div class="sisme-action-desc">Accéder à l'analyse des jeux</div>
                        </a>
                        
                        <a href="<?php echo admin_url('admin.php?page=sisme-games-game-data'); ?>" 
                           class="sisme-action-card">
                            <div class="sisme-action-icon">📊</div>
                            <div class="sisme-action-label">Tableau de bord</div>
                            <div class="sisme-action-desc">Retour au menu principal</div>
                        </a>
                        
                        <a href="<?php echo home_url('/sitemap.xml'); ?>" 
                           target="_blank"
                           class="sisme-action-card">
                            <div class="sisme-action-icon">🗺️</div>
                            <div class="sisme-action-label">Sitemap XML</div>
                            <div class="sisme-action-desc">Consulter le sitemap</div>
                        </a>
                        
                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=sisme-games-seo&action=fix_sitemap_rules'), 'sisme_seo_action'); ?>" 
                           class="sisme-action-card sisme-action-repair">
                            <div class="sisme-action-icon">🔧</div>
                            <div class="sisme-action-label">Réparer Sitemap</div>
                            <div class="sisme-action-desc">Réactiver les URLs .xml</div>
                        </a>
                    </div>
                </div>
            </div>
            
        </div>
        
        <!-- Section Pages de Jeux (pleine largeur) -->
        <div id="games-section" class="sisme-games-section">
            <div class="sisme-card">
                <div class="sisme-card__header">
                    <h3>🎮 Pages de Jeux - Analyse SEO</h3>
                    <div class="sisme-games-search-container">
                        <input type="text" 
                               id="games-search" 
                               placeholder="Rechercher un jeu par titre..." 
                               class="sisme-search-input">
                        <button type="button" 
                                id="refresh-games" 
                                class="sisme-refresh-button" 
                                onclick="sismeRefreshGames()">
                            🔄 Actualiser
                        </button>
                    </div>
                </div>
                <div class="sisme-card__body">
                    <div id="games-loading" class="sisme-loading" style="display: none;">
                        <p>🔄 Chargement des jeux...</p>
                    </div>
                    <div id="games-list" class="sisme-games-list">
                        <!-- Le contenu sera chargé dynamiquement -->
                    </div>
                </div>
            </div>
        </div>
        
        <script>
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
                    gamesListDiv.innerHTML = '<p class="no-games">Aucun jeu trouvé.</p>';
                }
            }).fail(function() {
                loadingDiv.style.display = 'none';
                gamesListDiv.innerHTML = '<p class="error">❌ Erreur de chargement</p>';
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
                gamesListDiv.innerHTML = '<p class="no-games">Aucun jeu trouvé.</p>';
                return;
            }
            
            let html = '';
            games.forEach(function(game) {
                html += '<div class="sisme-game-item" data-post-id="' + game.post_id + '" data-title="' + game.title.toLowerCase() + '">';
                html += '  <div class="sisme-game-info">';
                html += '    <div class="sisme-game-title">' + game.title + '</div>';
                html += '    <div class="sisme-game-meta">';
                html += '      <span class="sisme-meta-item">ID: ' + game.post_id + '</span>';
                if (game.game_data) {
                    html += '      <span class="sisme-meta-item">Jeu: ' + game.game_data.name + '</span>';
                }
                html += '    </div>';
                html += '  </div>';
                html += '  <div class="sisme-game-actions">';
                html += '    <a href="' + game.permalink + '" target="_blank" class="sisme-view-link">👁️ Voir</a>';
                html += '    <button type="button" class="sisme-test-button" onclick="sismeSeoTestPage(' + game.post_id + ')">🔍 Test SEO</button>';
                html += '  </div>';
                html += '  <div class="sisme-test-results" id="test-results-' + game.post_id + '" style="display:none;"></div>';
                html += '</div>';
            });
            
            gamesListDiv.innerHTML = html;
        }
        
        // Filtrer les jeux
        function sismeFilterGames(searchTerm) {
            const gameItems = document.querySelectorAll('.sisme-game-item');
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
                html += '<div class="sisme-test-section">';
                html += '  <h5>📝 Méta SEO</h5>';
                
                // Titre SEO
                html += '  <div class="sisme-test-item">';
                html += '    <span>Titre SEO:</span>';
                html += '  </div>';
                html += '  <div class="sisme-seo-preview ' + sismeGetSeoStatusClass(data.seo_meta.title_status) + '">';
                html += '    ' + data.seo_meta.title;
                html += '  </div>';
                html += '  <div class="sisme-seo-stats">';
                html += '    <small>Longueur: ' + data.seo_meta.title_length + ' caractères ';
                html += sismeGetSeoStatusText(data.seo_meta.title_status, 'title') + '</small>';
                html += '  </div>';
                
                // Description SEO
                html += '  <div class="sisme-test-item" style="margin-top: 15px;">';
                html += '    <span>Description SEO:</span>';
                html += '  </div>';
                html += '  <div class="sisme-seo-preview ' + sismeGetSeoStatusClass(data.seo_meta.description_status) + '">';
                html += '    ' + data.seo_meta.description;
                html += '  </div>';
                html += '  <div class="sisme-seo-stats">';
                html += '    <small>Longueur: ' + data.seo_meta.description_length + ' caractères ';
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
