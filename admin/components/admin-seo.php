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
 * - PHP-admin-user-getter.php
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
            array(__CLASS__, 'render')
        );
    }
    
    public static function enqueue_admin_scripts($hook) {
        if ($hook !== 'sisme-games_page_sisme-games-seo') {
            return;
        }
        wp_enqueue_script('jquery');
    }

    /**
     * Afficher les messages d'état
     */
    public static function render_message() {
        $action = $_GET['action'] ?? '';
        
        if ($action === 'clear_cache' && check_admin_referer('sisme_seo_action')) {
            Sisme_SEO_Loader::clear_all_cache();
            ?><div class="notice notice-success is-dismissible"><p>Cache SEO vidé avec succès.</p></div><?php
        }
        
        if ($action === 'fix_sitemap_rules' && check_admin_referer('sisme_seo_action')) {
            if (class_exists('Sisme_SEO_Sitemap')) {
                $sitemap = new Sisme_SEO_Sitemap();
                $sitemap->force_flush_rewrite_rules();
                ?><div class="notice notice-success is-dismissible"><p>Règles de réécriture sitemap réparées !</p></div><?php
            }
        }
    }

    public static function render_SEO_stats() {
        $total_games = wp_count_terms('post_tag', ['hide_empty' => false]);
        $total_posts = wp_count_posts()->publish;
        $cache_stats = self::get_cache_statistics();

        Sisme_Admin_Page_Wrapper::render_card_start(
            'Statistiques des jeux',
            'stats',
            '',
            'sisme-admin-grid sisme-admin-grid-3',
            false,
        );
        ?>
        <div class="sisme-admin-stat-card sisme-admin-stat-card-info">
            <div class="sisme-admin-stat-number"><?php echo number_format($total_games); ?></div>
            <div class="sisme-admin-stat-label">Jeux indexés</div>
        </div>
        <div class="sisme-admin-stat-card sisme-admin-stat-card-special">
            <div class="sisme-admin-stat-number"><?php echo number_format($total_posts); ?></div>
            <div class="sisme-admin-stat-label">Jeux publiés</div>
        </div>
        <div class="sisme-admin-stat-card sisme-admin-stat-card-warning">
            <div class="sisme-admin-stat-number"><?php echo $cache_stats['count']; ?></div>
            <div class="sisme-admin-stat-label">Entrées en cache</div>
        </div>
        <?php
        Sisme_Admin_Page_Wrapper::render_card_end();
    }

    public static function render_SEO_action() {
        Sisme_Admin_Page_Wrapper::render_card_start(
            'Actions Rapides',
            'tools',
            '',
            'sisme-admin-grid sisme-admin-grid-3',
            false,
        );
        ?>
        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=sisme-games-seo&action=clear_cache'), 'sisme_seo_action'); ?>" 
            class="sisme-admin-btn sisme-admin-btn-warning">
            <?php echo Sisme_Admin_Page_Wrapper::get_predefined_icon('delete', 0, 12) ?> Vider le cache SEO
        </a>
    
        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=sisme-games-seo&action=fix_sitemap_rules'), 'sisme_seo_action'); ?>" 
            class="sisme-admin-btn sisme-admin-btn-secondary">
            <?php echo Sisme_Admin_Page_Wrapper::get_predefined_icon('repair', 0, 12) ?> Réparer règles sitemap
        </a>

        <a href="<?php echo home_url('/sitemap-games.xml'); ?>" target="_blank" 
            class="sisme-admin-btn sisme-admin-btn-secondary">
            <?php echo Sisme_Admin_Page_Wrapper::get_predefined_icon('web', 0, 12) ?> Voir le site map
        </a>
        <?php
        Sisme_Admin_Page_Wrapper::render_card_end();
    }

    public static function render_SEO_games() {
        Sisme_Admin_Page_Wrapper::render_card_start(
            'SEO des jeux',
            'game',
            '',
            'sisme-admin-flex-col sisme-admin-gap-6',
            false,
        );
            Sisme_Admin_Page_Wrapper::render_card(
                'Recherche et Test SEO',
                'search',
                '',
                'sisme-admin-grid sisme-admin-grid-4',
                false,
                '<input type="text" id="sisme-game-search" placeholder="Tapez le nom du jeu..." class="sisme-admin-input">'
            );

            Sisme_Admin_Page_Wrapper::render_card(
                'Liste des jeux',
                'lib',
                '',
                'sisme-admin-flex-col sisme-admin-gap-6',
                false,
                '<div id="sisme-search-results" style="display: none;"></div>'
            );
        Sisme_Admin_Page_Wrapper::render_card_end();
    }

    
    public static function render() {
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'admin/assets/PHP-admin-page-wrapper.php';

        $page = new Sisme_Admin_Page_Wrapper(
            'SEO des Jeux',
            'Monitoring et diagnostic du référencement, analyse des performances SEO',
            'seo',
            admin_url('admin.php?page=sisme-games-outils'),
            'Retour aux outils'
        );

        $page->render_start();
        self::render_message();
        self::render_SEO_stats();
        self::render_SEO_action();
        self::render_SEO_games();
        ?>


        <script>
        const sismeSeo = {
            ajaxurl: '<?php echo admin_url('admin-ajax.php'); ?>',
            nonce: '<?php echo wp_create_nonce('sisme_seo_nonce'); ?>'
        };

        // Charger tous les jeux
        function sismeSeoLoadAllGames() {
            const container = document.getElementById('sisme-search-results');
            container.style.display = 'block';
            container.innerHTML = '<p class="sisme-admin-text-center">🔄 Chargement des jeux...</p>';
            
            jQuery.post(sismeSeo.ajaxurl, {
                action: 'sisme_seo_load_games',
                nonce: sismeSeo.nonce,
                limit: 50
            }, function(response) {
                if (response.success) {
                    sismeRenderGamesList(container, response.data, 'Tous les jeux');
                } else {
                    container.innerHTML = '<p class="sisme-admin-text-center">❌ Erreur de chargement</p>';
                }
            });
        }

        // Rechercher des jeux (filtrage local)
        function sismeSeoSearchGames() {
            const searchTerm = document.getElementById('sisme-game-search').value.trim().toLowerCase();
            const gameCards = document.querySelectorAll('#sisme-search-results .sisme-admin-grid .sisme-admin-card');
            
            if (!searchTerm) {
                // Afficher toutes les cartes si pas de recherche
                gameCards.forEach(card => card.style.display = 'block');
                return;
            }
            
            // Filtrer les cartes
            gameCards.forEach(card => {
                const gameNameElement = card.querySelector('[id^="game-name-"]'); // Trouve l'ID qui commence par "game-name-"
                if (gameNameElement) {
                    const gameName = gameNameElement.textContent.toLowerCase();
                    if (gameName.includes(searchTerm)) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                }
            });
        }

        // Afficher la liste des jeux
        function sismeRenderGamesList(container, games, title) {
            let html = '<h4 class="sisme-admin-heading">' + title + ' (' + games.length + ')</h4>';
            html += '<div class="sisme-admin-grid sisme-admin-grid-350">';
            
            games.forEach(function(game) {
                html += '<div class="sisme-admin-card sisme-admin-flex-col sisme-admin-gap-4">';
                html += '  <h2 id="game-name-' + game.id + '" class="sisme-admin-card-header sisme-admin-grid-whitespace">🎮 ' + game.name + '</h2>';
                html += '  <div class="sisme-admin-grid sisme-admin-grid-2">';
                html += '    <p class="sisme-admin-comment">ID: ' + game.id;
                html += '    <div class="sisme-admin-flex-between sisme-admin-mt-sm">';
                html += '      <a href="' + game.url + '" target="_blank" class="sisme-admin-btn sisme-admin-btn-primary sisme-admin-btn-sm sisme-admin-width-full">Voir</a>';
                html += '    </div>';
                html += '  </div>';
                html += '  <div class="sisme-admin-p-sm sisme-admin-height-full">';
                html += '    <div id="sisme-test-results-' + game.id + '">';
                html += '      <!-- Résultats du test SEO -->';
                html += '    </div>';
                html += '  </div>';
                html += '</div>';
            });
            
            html += '</div>';
            container.innerHTML = html;
            games.forEach(function(game) {
                sismeSeoTestGameAuto(game.id);
            });
        }

        // Test SEO automatique
        function sismeSeoTestGameAuto(gameId) {
            const resultsDiv = document.getElementById('sisme-test-results-' + gameId);
            resultsDiv.innerHTML = '<p>🔄 Analyse SEO en cours...</p>';
            
            jQuery.post(sismeSeo.ajaxurl, {
                action: 'sisme_seo_test_page',
                post_id: gameId,
                nonce: sismeSeo.nonce
            }, function(response) {
                if (response.success) {
                    sismeRenderSeoResults(resultsDiv, response.data);
                } else {
                    resultsDiv.innerHTML = '<div>❌ Erreur: ' + (response.data || 'Test échoué') + '</div>';
                }
            }).fail(function() {
                resultsDiv.innerHTML = '<div>❌ Erreur de connexion</div>';
            });
        }

        // Test SEO d'un jeu - version simplifiée qui utilise directement l'ID du term
        function sismeSeoTestGame(gameId) {
            const resultsDiv = document.getElementById('sisme-test-results-' + gameId);
            const button = event.target;
            
            button.disabled = true;
            button.textContent = '🔄 Test...';
            resultsDiv.style.display = 'block';
            resultsDiv.innerHTML = '<p>🔄 Analyse SEO en cours...</p>';
            
            // Utiliser directement l'ID du terme pour le test
            jQuery.post(sismeSeo.ajaxurl, {
                action: 'sisme_seo_test_page',
                post_id: gameId, // Utilise l'ID du terme directement
                nonce: sismeSeo.nonce
            }, function(response) {
                button.disabled = false;
                button.textContent = '🔍 Test SEO';
                
                if (response.success) {
                    sismeRenderSeoResults(resultsDiv, response.data);
                } else {
                    resultsDiv.innerHTML = '<div>❌ Erreur: ' + (response.data || 'Test échoué') + '</div>';
                }
            }).fail(function() {
                button.disabled = false;
                button.textContent = '🔍 Test SEO';
                resultsDiv.innerHTML = '<div>❌ Erreur de connexion</div>';
            });
        }

        // Rendu détaillé des résultats SEO - Version originale simplifiée
        function sismeRenderSeoResults(container, data) {
            let html = '';
            
            html += '<h3 class="sisme-admin-card-header">📝 Méta SEO</h3>';
            
            // Méta SEO (Titre et Description) - si disponible
            if (data.seo_meta) {
                
                // Titre SEO
                html += '  <div>';
                html += '    <h4>Titre SEO:</h4>';
                html += '  </div>';
                html += '  <div style="background: ' + getSeoStatusColor(data.seo_meta.title_status) + '; padding: 8px; border-radius: 3px;">';
                html += '    ' + data.seo_meta.title;
                html += '  </div>';
                html += '  <div>';
                html += '    <small>Longueur: ' + data.seo_meta.title_length + ' caractères ';
                html += getSeoStatusText(data.seo_meta.title_status, 'title') + '</small>';
                html += '  </div>';
                
                // Description SEO
                html += '  <div>';
                html += '    <h4>Description SEO:</h4>';
                html += '  </div>';
                html += '  <div style="background: ' + getSeoStatusColor(data.seo_meta.description_status) + '; padding: 8px; border-radius: 3px; margin-bottom: 5px;">';
                html += '    ' + data.seo_meta.description;
                html += '  </div>';
                html += '  <div>';
                html += '    <small>Longueur: ' + data.seo_meta.description_length + ' caractères ';
                html += getSeoStatusText(data.seo_meta.description_status, 'description') + '</small>';
                html += '  </div>';
            }
            
            // Détection du jeu
            if (data.page_detection) {
                html += '<div>';
                html += '  <h4>Détection de la page</h4>';
                html += '  <span style="color: ' + (data.page_detection.is_game_page ? 'green' : 'orange') + ';">';
                html += data.page_detection.is_game_page ? 'Page de jeu détectée' : 'Page normale';
                html += '</span>';
                html += '</div>';
            }
    
            container.innerHTML = html;
        }

        // Utilitaires pour les couleurs de statut SEO
        function getSeoStatusColor(status) {
            switch(status) {
                case 'good': return '#d4edda';
                case 'too_short': return '#fff3cd';
                case 'too_long': return '#f8d7da';
                default: return '#e2e3e5';
            }
        }

        function getSeoStatusText(status, type) {
            const limits = {
                title: { min: 30, max: 60 },
                description: { min: 120, max: 160 }
            };
            
            switch(status) {
                case 'good': return '✅ Optimal';
                case 'too_short': return '⚠️ Trop court (min: ' + limits[type].min + ')';
                case 'too_long': return '❌ Trop long (max: ' + limits[type].max + ')';
                default: return '';
            }
        }

        document.getElementById('sisme-game-search').addEventListener('input', function() {
            sismeSeoSearchGames();
        });

        document.addEventListener('DOMContentLoaded', function() {
            sismeSeoLoadAllGames();
        });
        </script>

        <?php
        $page->render_end();
    }

    /**
     * Obtenir les statistiques du cache
     */
    private static function get_cache_statistics() {
        global $wpdb;
        
        $cache_options = $wpdb->get_results(
            "SELECT option_name FROM {$wpdb->options} 
             WHERE option_name LIKE 'sisme_seo_%' 
             OR option_name LIKE '%transient%sisme_seo%'"
        );
        
        return [
            'count' => count($cache_options),
            'options' => $cache_options
        ];
    }

    /**
     * AJAX - Vider le cache SEO
     */
    public static function ajax_clear_cache() {
        check_ajax_referer('sisme_seo_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Permission insuffisante');
        }
        
        Sisme_SEO_Loader::clear_all_cache();
        
        wp_send_json_success('Cache SEO vidé avec succès');
    }
    
    /**
     * AJAX - Tester une page
     */
    public static function ajax_test_page() {
        check_ajax_referer('sisme_seo_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Permission insuffisante');
        }
        
        $post_id = intval($_POST['post_id']);
        if (!$post_id) {
            wp_send_json_error('ID de post invalide');
        }
        
        // Si c'est un term_id, trouver un post associé
        $term = get_term($post_id, 'post_tag');
        if ($term && !is_wp_error($term)) {
            // C'est un terme, trouve un post associé
            $posts = get_posts(array(
                'tag__in' => array($post_id),
                'post_status' => 'publish',
                'posts_per_page' => 1,
                'orderby' => 'date',
                'order' => 'DESC'
            ));
            
            if (empty($posts)) {
                wp_send_json_error('Aucun post trouvé pour ce jeu');
            }
            
            $post_id = $posts[0]->ID;
        }
        
        // Récupérer les données de base
        $debug_data = Sisme_SEO_Loader::debug_page($post_id);
        
        // Ajouter les méta SEO spécifiques comme dans l'original
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
            
            // Récupérer les métas titre et description EXACTEMENT comme dans le <head>
            $seo_title = '';
            $seo_description = '';
            
            // Utiliser les méthodes du module SEO
            if (class_exists('Sisme_SEO_Meta_Tags') && class_exists('Sisme_SEO_Title_Optimizer')) {
                $seo_title = Sisme_SEO_Title_Optimizer::get_generated_title($post_id);
                $seo_description = Sisme_SEO_Meta_Tags::get_generated_description($post_id);
            }
            
            // Fallback si méthodes pas dispo
            if (empty($seo_title) || empty($seo_description)) {
                if (empty($seo_title)) {
                    $seo_title = get_post_meta($post_id, '_sisme_seo_title', true);
                }
                if (empty($seo_description)) {
                    $seo_description = get_post_meta($post_id, '_sisme_seo_description', true);
                }
                
                // Fallback final
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
            
            // Ajouter aux données debug
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
            
            // Restaurer la query
            $wp_query = $original_query;
        }
        
        wp_send_json_success($debug_data);
    }
    
    /**
     * AJAX - Charger les jeux
     */
    public static function ajax_load_games() {
        check_ajax_referer('sisme_seo_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Permission insuffisante');
        }
        
        $search = sanitize_text_field($_POST['search'] ?? '');
        $limit = intval($_POST['limit'] ?? 20);
        
        $args = array(
            'taxonomy' => 'post_tag',
            'hide_empty' => false,
            'number' => $limit,
            'orderby' => 'count',
            'order' => 'DESC'
        );
        
        if (!empty($search)) {
            $args['name__like'] = $search;
        }
        
        $terms = get_terms($args);
        
        $games = array();
        foreach ($terms as $term) {
            $games[] = array(
                'id' => $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
                'count' => $term->count,
                'url' => home_url('/' . $term->slug . '/')
            );
        }
        
        wp_send_json_success($games);
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