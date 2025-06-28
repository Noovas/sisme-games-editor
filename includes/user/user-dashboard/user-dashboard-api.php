<?php
/**
 * File: /sisme-games-editor/includes/user/user-dashboard/user-dashboard-api.php
 * API et shortcode pour le dashboard utilisateur avec système d'onglets
 * 
 * RESPONSABILITÉ:
 * - Shortcode [sisme_user_dashboard]
 * - Rendu HTML complet du dashboard
 * - Système de navigation par onglets dynamique
 * - Gestion des sections : overview, favorites, library, activity
 * - Vérifications de sécurité et permissions
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_User_Dashboard_API {
    
    /**
     * Shortcode principal [sisme_user_dashboard]
     */
    public static function render_dashboard($atts = []) {
        // Vérifier si l'utilisateur est connecté
        if (!is_user_logged_in()) {
            return self::render_login_required();
        }
        
        $defaults = [
            'container_class' => 'sisme-user-dashboard',
            'user_id' => '',
            'title' => 'Mon Dashboard'
        ];
        
        $atts = shortcode_atts($defaults, $atts, 'sisme_user_dashboard');
        
        // ID utilisateur (utilisateur courant par défaut)
        $user_id = !empty($atts['user_id']) ? intval($atts['user_id']) : get_current_user_id();
        
        // Vérification permissions
        if ($user_id !== get_current_user_id() && !current_user_can('manage_users')) {
            return self::render_access_denied();
        }
        
        // Forcer le chargement des assets
        if (class_exists('Sisme_User_Dashboard_Loader')) {
            $loader = Sisme_User_Dashboard_Loader::get_instance();
            if (method_exists($loader, 'force_load_assets')) {
                $loader->force_load_assets();
            }
        }
        
        // Récupérer les données via data-manager
        if (!class_exists('Sisme_User_Dashboard_Data_Manager')) {
            return self::render_error('Module de données non disponible');
        }
        
        $dashboard_data = Sisme_User_Dashboard_Data_Manager::get_dashboard_data($user_id);
        if (!$dashboard_data) {
            return self::render_error('Impossible de charger les données utilisateur');
        }
        
        // Mettre à jour la dernière visite
        Sisme_User_Dashboard_Data_Manager::update_last_dashboard_visit($user_id);
        
        // Rendu complet
        ob_start();
        ?>
        <div class="<?php echo esc_attr($atts['container_class']); ?>" data-user-id="<?php echo esc_attr($user_id); ?>">
            <?php echo self::render_dashboard_header($dashboard_data['user_info'], $dashboard_data['gaming_stats']); ?>
            <?php echo self::render_dashboard_grid($dashboard_data); ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Rendu du header du dashboard
     */
    private static function render_dashboard_header($user_info, $gaming_stats) {
        ob_start();
        ?>
        <header class="sisme-dashboard-header">
            <div class="sisme-profile-card">
                <div class="sisme-profile-avatar">
                    <img src="<?php echo esc_url($user_info['avatar_url']); ?>" alt="Avatar" class="sisme-avatar">
                    <div class="sisme-status-indicator online"></div>
                </div>
                
                <div class="sisme-profile-info">
                    <h1 class="sisme-profile-name">
                        👋 Salut, <?php echo esc_html($user_info['display_name']); ?>!
                    </h1>
                    <p class="sisme-profile-tagline">
                        Membre depuis le <?php echo esc_html($user_info['member_since']); ?>
                    </p>
                    
                    <div class="sisme-profile-stats">
                        <div class="sisme-stat-bubble">
                            <span class="sisme-stat-number"><?php echo esc_html($gaming_stats['total_games']); ?></span>
                            <span class="sisme-stat-label">Jeux</span>
                        </div>
                        <div class="sisme-stat-bubble">
                            <span class="sisme-stat-number"><?php echo esc_html($gaming_stats['favorite_games']); ?></span>
                            <span class="sisme-stat-label">Favoris</span>
                        </div>
                        <div class="sisme-stat-bubble">
                            <span class="sisme-stat-number"><?php echo esc_html($gaming_stats['level']); ?></span>
                            <span class="sisme-stat-label">Niveau</span>
                        </div>
                    </div>
                </div>
                
                <div class="sisme-profile-actions">
                    <a href="<?php echo wp_logout_url(); ?>" class="sisme-button sisme-btn--secondary">
                        <span class="sisme-icon">🚪</span>
                        <span class="sisme-label">Déconnexion</span>
                    </a>
                    <a href="#stats" class="sisme-button sisme-button-bleu">
                        <span class="sisme-icon">🏆</span>
                        <span class="sisme-label">Statistiques</span>
                    </a>
                </div>
            </div>
        </header>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Grille principale du dashboard
     */
    public static function render_dashboard_grid($dashboard_data) {
        ob_start();
        ?>
        <div class="sisme-dashboard-grid">
            <!-- Sidebar Navigation -->
            <aside class="sisme-dashboard-sidebar">
                <?php echo self::render_sidebar_navigation(); ?>
                <?php echo self::render_quick_stats($dashboard_data['gaming_stats']); ?>
            </aside>

            <!-- Main Content avec sections dynamiques -->
            <main class="sisme-dashboard-main">
                <!-- Section Vue d'ensemble -->
                <div class="sisme-dashboard-section" data-section="overview">
                    <div class="sisme-section-header">
                        <h2 class="sisme-section-title">
                            <span class="sisme-title-icon">📊</span>
                            Vue d'ensemble
                        </h2>
                    </div>
                    <?php echo self::render_activity_feed($dashboard_data['activity_feed']); ?>
                    <?php echo self::render_recent_games($dashboard_data['recent_games']); ?>
                </div>

                <!-- Section Favoris -->
                <div class="sisme-dashboard-section" data-section="favorites" style="display: none;">
                    <div class="sisme-section-header">
                        <h2 class="sisme-section-title">
                            <span class="sisme-title-icon">❤️</span>
                            Mes Favoris
                        </h2>
                    </div>
                    <?php echo self::render_favorites_section($dashboard_data['favorite_games']); ?>
                </div>

                <!-- Section Sismothèque -->
                <div class="sisme-dashboard-section" data-section="library" style="display: none;">
                    <div class="sisme-section-header">
                        <h2 class="sisme-section-title">
                            <span class="sisme-title-icon">📚</span>
                            La Sismothèque
                        </h2>
                    </div>
                    <?php echo self::render_library_section($dashboard_data['owned_games']); ?>
                </div>

                <!-- Section Activité -->
                <div class="sisme-dashboard-section" data-section="activity" style="display: none;">
                    <div class="sisme-section-header">
                        <h2 class="sisme-section-title">
                            <span class="sisme-title-icon">📈</span>
                            Mon Activité
                        </h2>
                    </div>
                    <?php echo self::render_activity_section($dashboard_data['activity_feed']); ?>
                </div>

                <!-- Section paramètres -->
                <div class="sisme-dashboard-section" data-section="settings" style="display: none;">
                    <div class="sisme-section-header">
                        <h2 class="sisme-section-title">
                            <span class="sisme-title-icon">⚙️</span>
                            Paramètres
                        </h2>
                    </div>
                    <?php echo self::render_settings_section($dashboard_data['user_info']['id']); ?>
                </div>
            </main>

            <!-- Widgets Sidebar -->
            <aside class="sisme-dashboard-widgets">
                <?php echo self::render_favorites_widget($dashboard_data['favorite_games']); ?>
                <?php echo self::render_stats_widget($dashboard_data['gaming_stats']); ?>
            </aside>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Navigation sidebar
     */
    private static function render_sidebar_navigation() {
        ob_start();
        ?>
        <nav class="sisme-dashboard-nav">
            <h3 class="sisme-nav-title">☰ Mon Sismenu</h3>
            <ul class="sisme-nav-list">
                <li><a href="#overview" class="sisme-nav-link active" data-section="overview">
                    <span class="sisme-nav-icon">📊</span>
                    <span class="sisme-nav-text">Vue d'ensemble</span>
                </a></li>
                <li><a href="#favorites" class="sisme-nav-link" data-section="favorites">
                    <span class="sisme-nav-icon">❤️</span>
                    <span class="sisme-nav-text">Favoris</span>
                </a></li>
                <li><a href="#library" class="sisme-nav-link" data-section="library">
                    <span class="sisme-nav-icon">📚</span>
                    <span class="sisme-nav-text">La Sismothèque</span>
                </a></li>
                <li><a href="#activity" class="sisme-nav-link" data-section="activity">
                    <span class="sisme-nav-icon">📈</span>
                    <span class="sisme-nav-text">Activité</span>
                </a></li>
                <li><a href="#settings" class="sisme-nav-link" data-section="settings">
                    <span class="sisme-nav-icon">⚙️</span>
                    <span class="sisme-nav-text">Paramètres</span>
                    <!--<span class="sisme-nav-badge">Bientôt</span>-->
                </a></li>
            </ul>
        </nav>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Stats rapides sidebar
     */
    private static function render_quick_stats($gaming_stats) {
        ob_start();
        ?>
        <div class="sisme-quick-stats">
            <h3 class="sisme-stats-title">📈 Mes Stats</h3>
            <div class="sisme-stat-item">
                <span class="sisme-stat-icon">🎮</span>
                <span class="sisme-stat-value"><?php echo esc_html($gaming_stats['owned_games'] ?? 0); ?></span>
                <span class="sisme-stat-label">Jeux possédés</span>
            </div>
            <div class="sisme-stat-item">
                <span class="sisme-stat-icon">❤️</span>
                <span class="sisme-stat-value"><?php echo esc_html($gaming_stats['favorite_games']); ?></span>
                <span class="sisme-stat-label">Favoris</span>
            </div>
            <div class="sisme-stat-item">
                <span class="sisme-stat-icon">🏆</span>
                <span class="sisme-stat-value"><?php echo esc_html($gaming_stats['level']); ?></span>
                <span class="sisme-stat-label">Niveau</span>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Section Favoris avec liens
     */
    private static function render_favorites_section($favorite_games) {
        ob_start();
        ?>
        <div class="sisme-favorites-section">
            <?php if (empty($favorite_games)): ?>
                <div class="sisme-empty-state">
                    <div class="sisme-empty-icon">❤️</div>
                    <h3>Aucun jeu favori</h3>
                    <p>Explorez des jeux et ajoutez-les à vos favoris pour les retrouver ici !</p>
                </div>
            <?php else: ?>
                <div class="sisme-games-grid sisme-favorites-grid">
                    <?php foreach ($favorite_games as $game): ?>
                        <a href="<?php echo esc_url(self::get_game_url($game['id'])); ?>" class="sisme-game-card">
                            <div class="sisme-game-cover" style="background-image: url('<?php echo esc_url($game['cover_url'] ?? ''); ?>');">
                                <div class="sisme-game-overlay">
                                    <span class="sisme-favorite-badge">❤️</span>
                                </div>
                            </div>
                            <div class="sisme-game-info">
                                <h4 class="sisme-game-title"><?php echo esc_html($game['name']); ?></h4>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Section La Sismothèque (jeux possédés) avec liens
     */
    private static function render_library_section($owned_games) {
        ob_start();
        ?>
        <div class="sisme-library-section">
            <?php if (empty($owned_games)): ?>
                <div class="sisme-empty-state">
                    <div class="sisme-empty-icon">📚</div>
                    <h3>Sismothèque vide</h3>
                    <p>Commencez à construire votre collection de jeux !</p>
                </div>
            <?php else: ?>
                <div class="sisme-games-grid sisme-library-grid">
                    <?php foreach ($owned_games as $game): ?>
                        <a href="<?php echo esc_url(self::get_game_url($game['id'])); ?>" class="sisme-game-card">
                            <div class="sisme-game-cover" style="background-image: url('<?php echo esc_url($game['cover_url'] ?? ''); ?>');">
                                <div class="sisme-game-overlay">
                                    <span class="sisme-owned-badge">📚</span>
                                </div>
                            </div>
                            <div class="sisme-game-info">
                                <h4 class="sisme-game-title"><?php echo esc_html($game['name']); ?></h4>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Section Activité détaillée
     */
    private static function render_activity_section($activity_feed) {
        ob_start();
        ?>
        <div class="sisme-activity-section">
            <?php if (empty($activity_feed)): ?>
                <div class="sisme-empty-state">
                    <div class="sisme-empty-icon">📈</div>
                    <h3>Aucune activité</h3>
                    <p>Votre historique d'activité apparaîtra ici.</p>
                </div>
            <?php else: ?>
                <div class="sisme-activity-timeline">
                    <?php foreach ($activity_feed as $activity): ?>
                        <div class="sisme-activity-item sisme-activity-item--detailed">
                            <div class="sisme-activity-icon"><?php echo esc_html($activity['icon']); ?></div>
                            <div class="sisme-activity-content">
                                <p class="sisme-activity-text"><?php echo esc_html($activity['message']); ?></p>
                                <time class="sisme-activity-time" datetime="<?php echo esc_attr($activity['date']); ?>">
                                    <?php echo esc_html(self::format_time_ago($activity['date'])); ?>
                                </time>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Rendu de la section paramètres avec préférences utilisateur
     * 
     * Utilise exclusivement le module user-preferences moderne
     * 
     * @param int $user_id ID de l'utilisateur
     * @return string HTML de la section paramètres
     */
    private static function render_settings_section($user_id) {
        // Vérifier les prérequis pour user-preferences
        if (!class_exists('Sisme_User_Preferences_Loader')) {
            return self::render_preferences_unavailable();
        }
        
        if (!class_exists('Sisme_User_Preferences_API')) {
            return self::render_preferences_unavailable();
        }
        
        if (!class_exists('Sisme_User_Preferences_Data_Manager')) {
            return self::render_preferences_unavailable();
        }
        
        // Initialiser le loader user-preferences
        $preferences_loader = Sisme_User_Preferences_Loader::get_instance();
        
        // S'assurer que le module est prêt pour l'intégration dashboard
        if (!$preferences_loader->integrate_with_dashboard()) {
            return self::render_preferences_error();
        }
        
        // Forcer le chargement des assets user-preferences
        $preferences_loader->force_load_assets();
        
        // Vérifier que l'utilisateur existe
        if (!get_userdata($user_id)) {
            return self::render_preferences_error('Utilisateur introuvable');
        }
        
        // Utiliser directement l'API user-preferences plutôt que le shortcode
        // pour éviter tout conflit avec user-profile
        ob_start();
        ?>
        <div class="sisme-dashboard-preferences">
            <?php 
            // Rendu direct via l'API user-preferences
            $preferences_html = Sisme_User_Preferences_API::render_preferences_shortcode([
                'sections' => 'gaming,notifications,privacy',
                'title' => '',
                'user_id' => $user_id,
                'container_class' => 'sisme-user-preferences sisme-dashboard-integration'
            ]);
            
            echo $preferences_html;
            ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Message si le module préférences n'est pas disponible
     */
    private static function render_preferences_unavailable() {
        ob_start();
        ?>
        <div class="sisme-preferences-unavailable">
            <div class="sisme-empty-state">
                <div class="sisme-empty-icon">⚙️</div>
                <h3>Module Préférences non disponible</h3>
                <p>Le module de gestion des préférences n'est pas encore chargé. Veuillez contacter l'administrateur.</p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Message d'erreur d'intégration des préférences
     */
    private static function render_preferences_error() {
        ob_start();
        ?>
        <div class="sisme-preferences-error">
            <div class="sisme-empty-state">
                <div class="sisme-empty-icon">❌</div>
                <h3>Erreur de chargement</h3>
                <p>Impossible d'initialiser le module de préférences. Veuillez recharger la page.</p>
                <button onclick="location.reload()" class="sisme-btn sisme-btn--primary" style="margin-top: 1rem;">
                    🔄 Recharger la page
                </button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Feed d'activité (version originale pour Vue d'ensemble)
     */
    private static function render_activity_feed($activity_feed) {
        ob_start();
        ?>
        <div class="sisme-activity-feed">
            <h3 class="sisme-widget-title">🔥 Activité récente</h3>
            
            <?php if (empty($activity_feed)): ?>
                <p class="sisme-activity-empty">Aucune activité récente. Commencez à explorer des jeux !</p>
            <?php else: ?>
                <div class="sisme-activity-list">
                    <?php foreach (array_slice($activity_feed, 0, 3) as $activity): ?>
                        <div class="sisme-activity-item">
                            <div class="sisme-activity-icon"><?php echo esc_html($activity['icon']); ?></div>
                            <div class="sisme-activity-content">
                                <p class="sisme-activity-text"><?php echo esc_html($activity['message']); ?></p>
                                <time class="sisme-activity-time"><?php echo esc_html(self::format_time_ago($activity['date'])); ?></time>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Grille des derniers jeux mis en ligne avec description et date de sortie
     * 
     * @param array $recent_games_data Données des jeux récents (non utilisé, on récupère direct)
     * @return string HTML de la grille
     */
    private static function render_recent_games($recent_games_data) {
        ob_start();
        ?>
        <div class="sisme-recent-games">
            <h3 class="sisme-widget-title">⚡ Les derniers ajouts</h3>
            
            <?php
            // Récupérer les derniers jeux triés par ID décroissant (plus récents en premier)
            $recent_terms = get_terms([
                'taxonomy' => 'post_tag',
                'hide_empty' => false,
                'number' => 12, // Limiter à 12 jeux récents
                'meta_query' => [
                    [
                        'key' => 'game_description',
                        'compare' => 'EXISTS'
                    ]
                ],
                'orderby' => 'term_id',
                'order' => 'DESC' // Du plus récent (ID le plus élevé) au plus ancien
            ]);
            
            if (!is_wp_error($recent_terms) && !empty($recent_terms)): ?>
                <div class="sisme-games-grid sisme-recent-grid">
                    <?php foreach ($recent_terms as $term): 
                        // Récupérer les métadonnées du jeu
                        $game_description = get_term_meta($term->term_id, 'game_description', true);
                        $release_date = get_term_meta($term->term_id, 'release_date', true);
                        $cover_main = get_term_meta($term->term_id, 'cover_main', true);
                        $game_genres = get_term_meta($term->term_id, 'game_genres', true) ?: [];
                        
                        // URL de l'image de couverture
                        $cover_url = '';
                        if ($cover_main) {
                            $cover_url = wp_get_attachment_image_url($cover_main, 'medium');
                        }
                        
                        // Formater la date de sortie
                        $formatted_date = '';
                        if ($release_date) {
                            $formatted_date = date_i18n('j M Y', strtotime($release_date));
                        }
                        
                        // Récupérer les genres pour affichage
                        $genres_display = [];
                        if (!empty($game_genres)) {
                            $genres_terms = get_terms([
                                'taxonomy' => 'game_genre',
                                'include' => array_slice($game_genres, 0, 2), // Max 2 genres pour l'affichage
                                'hide_empty' => false
                            ]);
                            
                            if (!is_wp_error($genres_terms)) {
                                foreach ($genres_terms as $genre) {
                                    $genres_display[] = $genre->name;
                                }
                            }
                        }
                        
                        // Utiliser la fonction helper pour l'URL du jeu
                        $game_url = self::get_game_url($term->term_id);
                        ?>
                        <div class="sisme-game-card sisme-recent-grid">
                            <a href="<?php echo esc_url($game_url); ?>" class="sisme-game-link sisme-recent-grid">
                                <div class="sisme-game-cover sisme-recent-grid" style="<?php echo $cover_url ? 'background-image: url(' . esc_url($cover_url) . ');' : ''; ?>">
                                    <div class="sisme-game-overlay sisme-recent-grid">
                                        <span class="sisme-owned-badge sisme-recent-grid">⚡</span>
                                    </div>
                                </div>
                                
                                <div class="sisme-game-info sisme-recent-grid">
                                    <h4 class="sisme-game-title sisme-recent-grid"><?php echo esc_html($term->name); ?></h4>
                                    
                                    <?php if (!empty($game_description)): ?>
                                        <p class="sisme-game-description sisme-recent-grid">
                                            <?php echo esc_html(wp_trim_words($game_description, 15, '...')); ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($genres_display)): ?>
                                        <div class="sisme-game-genres sisme-recent-grid">
                                            <?php foreach ($genres_display as $genre): ?>
                                                <span class="sisme-genre-tag sisme-recent-grid"><?php echo esc_html($genre); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($formatted_date): ?>
                                        <div class="sisme-game-release-date sisme-recent-grid">
                                            <span class="sisme-date-icon sisme-recent-grid">📅</span>
                                            <span class="sisme-date-text sisme-recent-grid">Date de sortie : <?php echo esc_html($formatted_date); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
                
            <?php else: ?>
                <div class="sisme-empty-state">
                    <div class="sisme-empty-icon">⚡</div>
                    <h3>Aucun jeu récent</h3>
                    <p>Les derniers jeux ajoutés à Sisme Games apparaîtront ici.</p>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Récupérer l'URL d'un jeu (fiche en priorité, sinon tag)
     * 
     * @param int $game_id ID du jeu (term_id)
     * @return string URL du jeu
     */
    private static function get_game_url($game_id) {
        // Vérifier que le term existe
        $term = get_term($game_id, 'post_tag');
        if (!$term || is_wp_error($term)) {
            return '#';
        }
        
        // Chercher l'article de fiche associé au jeu
        $fiche_post = get_posts([
            'tag_id' => $game_id,
            'post_type' => 'post',
            'post_status' => ['publish'],
            'posts_per_page' => 1,
            'meta_query' => [
                [
                    'key' => '_sisme_game_sections',
                    'compare' => 'EXISTS'
                ]
            ]
        ]);
        
        // URL finale : si fiche existe = URL de l'article, sinon = URL du tag
        if (!empty($fiche_post)) {
            // Lien vers la fiche (article)
            return get_permalink($fiche_post[0]->ID);
        } else {
            // Fallback : lien vers la page tag
            return get_term_link($term);
        }
    }

    /**
     * Widget favoris sidebar avec liens vers les fiches (version simplifiée)
     */
    private static function render_favorites_widget($favorite_games) {
        ob_start();
        ?>
        <div class="sisme-favorites-widget">
            <h3 class="sisme-widget-title">❤️ Favoris récents</h3>
            
            <?php 
            // Récupérer les IDs des jeux favoris depuis les user_meta
            $current_user_id = get_current_user_id();
            $favorite_game_ids = get_user_meta($current_user_id, 'sisme_user_favorite_games', true);
            
            if (empty($favorite_game_ids) || !is_array($favorite_game_ids)): ?>
                <p class="sisme-widget-empty">Aucun favori ajouté.</p>
            <?php else: ?>
                <div class="sisme-favorites-preview">
                    <?php 
                    // Prendre seulement les 3 premiers favoris
                    $limited_favorites = array_slice($favorite_game_ids, 0, 3);
                    
                    foreach ($limited_favorites as $game_id): 
                        // Récupérer le term du jeu
                        $term = get_term($game_id, 'post_tag');
                        if (!$term || is_wp_error($term)) continue;
                        
                        // Récupérer la cover
                        $cover_main = get_term_meta($term->term_id, 'cover_main', true);
                        $cover_url = '';
                        if ($cover_main) {
                            $cover_url = wp_get_attachment_image_url($cover_main, 'medium');
                        }
                        
                        // Utiliser la fonction helper pour l'URL
                        $game_url = self::get_game_url($game_id);
                    ?>
                        <a href="<?php echo esc_url($game_url); ?>" class="sisme-favorite-item">
                            <div class="sisme-favorite-cover" style="background-image: url('<?php echo esc_url($cover_url); ?>');"></div>
                            <span class="sisme-favorite-name"><?php echo esc_html($term->name); ?></span>
                        </a>
                    <?php endforeach; ?>
                    
                    <?php if (count($favorite_game_ids) > 3): ?>
                        <a href="#favorites" class="sisme-view-all" data-section="favorites">
                            Voir tous les favoris (<?php echo count($favorite_game_ids); ?>)
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Widget statistiques
     */
    private static function render_stats_widget($gaming_stats) {
        ob_start();
        ?>
        <div class="sisme-stats-widget">
            <h3 class="sisme-widget-title">🏆 Mes Statistiques</h3>
            <div class="sisme-stats-overview">
                <div class="sisme-stat-circle">
                    <div class="sisme-stat-number"><?php echo esc_html($gaming_stats['favorite_games']); ?></div>
                    <div class="sisme-stat-text">Favoris</div>
                </div>
                <div class="sisme-stat-circle">
                    <div class="sisme-stat-number"><?php echo esc_html($gaming_stats['owned_games'] ?? 0); ?></div>
                    <div class="sisme-stat-text">Possédés</div>
                </div>
            </div>
            <div class="sisme-level-badge">
                <span class="sisme-level-icon">🎯</span>
                <span class="sisme-level-text">Niveau <?php echo esc_html($gaming_stats['level']); ?></span>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Affichage si utilisateur non connecté
     */
    private static function render_login_required() {
        ob_start();
        ?>
        <div class="sisme-auth-card sisme-auth-card--login-required">
            <div class="sisme-auth-content">
                <div class="sisme-auth-message sisme-auth-message--warning">
                    <span class="sisme-message-icon">🔒</span>
                    <p>Vous devez être connecté pour accéder à votre dashboard.</p>
                </div>
                <div class="sisme-auth-actions">
                    <a href="https://games.sisme.fr/sisme-user-login/" class="sisme-button sisme-button-vert">
                        <span class="sisme-btn-icon">🔐</span>
                        Se connecter
                    </a>
                    <a href="https://games.sisme.fr/sisme-user-register/" class="sisme-button sisme-button-bleu">
                        <span class="sisme-btn-icon">📝</span>
                        S'inscrire
                    </a>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Affichage en cas d'accès refusé
     */
    private static function render_access_denied() {
        ob_start();
        ?>
        <div class="sisme-user-dashboard sisme-access-denied">
            <div class="sisme-error-card">
                <span class="sisme-error-icon">❌</span>
                <h2 class="sisme-error-title">Accès refusé</h2>
                <p class="sisme-error-message">Vous n'avez pas les permissions nécessaires pour voir ce dashboard.</p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Affichage en cas d'erreur
     */
    private static function render_error($message) {
        ob_start();
        ?>
        <div class="sisme-user-dashboard sisme-error">
            <div class="sisme-error-card">
                <span class="sisme-error-icon">⚠️</span>
                <h2 class="sisme-error-title">Erreur</h2>
                <p class="sisme-error-message"><?php echo esc_html($message); ?></p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Formatter le temps relatif
     */
    private static function format_time_ago($date) {
        if (empty($date)) {
            return 'Date inconnue';
        }
        
        $time = time() - strtotime($date);
        
        if ($time < 60) return 'Il y a quelques secondes';
        if ($time < 3600) return 'Il y a ' . floor($time/60) . ' minutes';
        if ($time < 86400) return 'Il y a ' . floor($time/3600) . ' heures';
        if ($time < 2592000) return 'Il y a ' . floor($time/86400) . ' jours';
        
        return date('j M Y', strtotime($date));
    }
}

// Initialiser l'API
add_shortcode('sisme_user_dashboard', ['Sisme_User_Dashboard_API', 'render_dashboard']);