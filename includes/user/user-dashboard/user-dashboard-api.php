<?php
/**
 * File: /sisme-games-editor/includes/user/user-dashboard/user-dashboard-api.php
 * API et shortcode pour le dashboard utilisateur
 * 
 * RESPONSABILITÉ:
 * - Shortcode unique [sisme_user_dashboard]
 * - Rendu HTML complet du dashboard
 * - Intégration avec data-manager
 * - Gestion authentification et erreurs
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_User_Dashboard_API {
    
    /**
     * Initialisation de l'API
     */
    public static function init() {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Sisme User Dashboard API] API dashboard utilisateur initialisée');
        }
    }
    
    /**
     * Shortcode [sisme_user_dashboard] - Dashboard complet
     * 
     * @param array $atts Attributs du shortcode
     * @return string HTML du dashboard
     */
    public static function render_dashboard($atts = []) {
        // Valeurs par défaut (version simplifiée)
        $defaults = [
            'container_class' => 'sisme-user-dashboard',
            'user_id' => '', // Vide = utilisateur courant
            'title' => 'Mon Dashboard Gaming'
        ];
        
        $atts = shortcode_atts($defaults, $atts, 'sisme_user_dashboard');
        
        // Vérifier si connecté
        if (!is_user_logged_in()) {
            return self::render_login_required();
        }
        
        // Déterminer l'utilisateur
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
     * 
     * @param array $user_info Infos utilisateur
     * @param array $gaming_stats Stats gaming
     * @return string HTML header
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
                    <p class="sisme-profile-stats">
                        <span class="sisme-stat">🎮 <?php echo esc_html($gaming_stats['total_games']); ?> jeux</span>
                        <span class="sisme-stat">⭐ <?php echo esc_html($gaming_stats['favorite_games']); ?> favoris</span>
                        <span class="sisme-stat">🏆 Niveau <?php echo esc_html($gaming_stats['level']); ?></span>
                    </p>
                </div>
                
                <div class="sisme-profile-actions">
                    <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="sisme-btn sisme-btn-secondary">
                        <span>🚪</span> Déconnexion
                    </a>
                </div>
            </div>
            
            <div class="sisme-quick-actions">
                <a href="#library" class="sisme-quick-btn" data-section="library">
                    <span class="sisme-icon">📚</span>
                    <span class="sisme-label">Ma Bibliothèque</span>
                </a>
                <a href="#favorites" class="sisme-quick-btn" data-section="favorites">
                    <span class="sisme-icon">❤️</span>
                    <span class="sisme-label">Favoris</span>
                </a>
                <a href="#activity" class="sisme-quick-btn" data-section="activity">
                    <span class="sisme-icon">📊</span>
                    <span class="sisme-label">Activité</span>
                </a>
                <a href="#stats" class="sisme-quick-btn" data-section="stats">
                    <span class="sisme-icon">🏆</span>
                    <span class="sisme-label">Statistiques</span>
                </a>
            </div>
        </header>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Rendu de la grille principale du dashboard
     * 
     * @param array $dashboard_data Toutes les données
     * @return string HTML grid
     */
    private static function render_dashboard_grid($dashboard_data) {
        ob_start();
        ?>
        <div class="sisme-dashboard-grid">
            <!-- Sidebar Navigation -->
            <aside class="sisme-dashboard-sidebar">
                <?php echo self::render_sidebar_navigation(); ?>
                <?php echo self::render_quick_stats($dashboard_data['gaming_stats']); ?>
            </aside>

            <!-- Main Content -->
            <main class="sisme-dashboard-main">
                <div class="sisme-section-header">
                    <h2 class="sisme-section-title">
                        <span class="sisme-title-icon">📊</span>
                        Vue d'ensemble
                    </h2>
                </div>
                
                <?php echo self::render_activity_feed($dashboard_data['activity_feed']); ?>
                <?php echo self::render_recent_games($dashboard_data['recent_games']); ?>
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
            <h3 class="sisme-nav-title">🎮 Mon Gaming</h3>
            <ul class="sisme-nav-list">
                <li><a href="#overview" class="sisme-nav-link active" data-section="overview">
                    <span class="sisme-nav-icon">📊</span>
                    <span class="sisme-nav-text">Vue d'ensemble</span>
                </a></li>
                <li><a href="#library" class="sisme-nav-link" data-section="library">
                    <span class="sisme-nav-icon">📚</span>
                    <span class="sisme-nav-text">Ma Bibliothèque</span>
                </a></li>
                <li><a href="#favorites" class="sisme-nav-link" data-section="favorites">
                    <span class="sisme-nav-icon">⭐</span>
                    <span class="sisme-nav-text">Favoris</span>
                </a></li>
                <li><a href="#activity" class="sisme-nav-link" data-section="activity">
                    <span class="sisme-nav-icon">📈</span>
                    <span class="sisme-nav-text">Activité</span>
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
                <span class="sisme-stat-value"><?php echo esc_html($gaming_stats['total_games']); ?></span>
                <span class="sisme-stat-label">Jeux possédés</span>
            </div>
            <div class="sisme-stat-item">
                <span class="sisme-stat-icon">⭐</span>
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
     * Feed d'activité
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
                    <?php foreach ($activity_feed as $activity): ?>
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
     * Jeux récents
     */
    private static function render_recent_games($recent_games) {
        ob_start();
        ?>
        <div class="sisme-recent-games">
            <div class="sisme-section-header">
                <h3 class="sisme-section-title">
                    <span class="sisme-title-icon">🆕</span>
                    Jeux Récemment Ajoutés
                </h3>
                <a href="<?php echo home_url('/#sismeSearchInterface'); ?>" class="sisme-section-link">
                    Voir tous les jeux →
                </a>
            </div>
            <h3 class="sisme-widget-title">🆕 Derniers ajouts</h3>
            
            <?php if (empty($recent_games)): ?>
                <p class="sisme-games-empty">Aucun jeu récent trouvé.</p>
            <?php else: ?>
                <div class="sisme-games-grid">
                    <?php foreach ($recent_games as $game): ?>
                        <div class="sisme-game-card">
                            <div class="sisme-game-cover" style="background-image: url('<?php echo esc_url($game['cover_url']); ?>')">
                                <?php if (empty($game['cover_url'])): ?>
                                    <span class="sisme-game-placeholder">🎮</span>
                                <?php endif; ?>
                            </div>
                            <div class="sisme-game-info">
                                <h4 class="sisme-game-name">
                                    <a href="<?php echo esc_url($game['game_url']); ?>">
                                        <?php echo esc_html($game['name']); ?>
                                    </a>
                                </h4>
                                <?php if (!empty($game['genres'])): ?>
                                    <p class="sisme-game-meta">
                                        <?php 
                                        $genre_names = array_column($game['genres'], 'name');
                                        echo esc_html(implode(', ', $genre_names)); 
                                        ?>
                                    </p>
                                <?php endif; ?>
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
     * Widget favoris
     */
    private static function render_favorites_widget($favorite_games) {
        ob_start();
        ?>
        <div class="sisme-widget sisme-favorites">
            <h3 class="sisme-widget-title">
                <span class="sisme-widget-icon">⭐</span>
                Mes Favoris
            </h3>
            
            <div class="sisme-widget-content">
                <?php if (empty($favorite_games)): ?>
                    <p class="sisme-favorites-empty">Aucun favori pour le moment. Découvrez des jeux à aimer !</p>
                <?php else: ?>
                    <?php foreach (array_slice($favorite_games, 0, 5) as $game): ?>
                        <div class="sisme-favorite-item">
                            <div class="sisme-favorite-cover">
                                <?php if (!empty($game['cover_url'])): ?>
                                    <img src="<?php echo esc_url($game['cover_url']); ?>" alt="<?php echo esc_attr($game['name']); ?>">
                                <?php else: ?>
                                    <span class="sisme-favorite-placeholder">🎮</span>
                                <?php endif; ?>
                            </div>
                            <div class="sisme-favorite-info">
                                <h4 class="sisme-favorite-name">
                                    <a href="<?php echo esc_url($game['game_url']); ?>">
                                        <?php echo esc_html($game['name']); ?>
                                    </a>
                                </h4>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if (count($favorite_games) > 5): ?>
                        <p class="sisme-favorites-more">
                            <a href="#favorites" data-section="favorites">
                                Voir tous les favoris (<?php echo count($favorite_games); ?>)
                            </a>
                        </p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
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
        <div class="sisme-widget sisme-stats">
            <h3 class="sisme-widget-title">
                <span class="sisme-widget-icon">📊</span>
                Statistiques
            </h3>
            
            <div class="sisme-widget-content">
                <div class="sisme-stat-circle">
                    <div class="sisme-stat-circle-inner">
                        <span class="sisme-stat-circle-value"><?php echo esc_html($gaming_stats['total_games']); ?></span>
                        <span class="sisme-stat-circle-label">Jeux total</span>
                    </div>
                </div>
                
                <div class="sisme-stats-list">
                    <div class="sisme-stat-line">
                        <span class="sisme-stat-line-label">Niveau actuel</span>
                        <span class="sisme-stat-line-value"><?php echo esc_html($gaming_stats['level']); ?></span>
                    </div>
                    <div class="sisme-stat-line">
                        <span class="sisme-stat-line-label">Favoris</span>
                        <span class="sisme-stat-line-value"><?php echo esc_html($gaming_stats['favorite_games']); ?></span>
                    </div>
                    <div class="sisme-stat-line">
                        <span class="sisme-stat-line-label">Articles créés</span>
                        <span class="sisme-stat-line-value"><?php echo esc_html($gaming_stats['user_posts']); ?></span>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Formater le temps écoulé
     */
    private static function format_time_ago($date) {
        $time = time() - strtotime($date);
        
        if ($time < 60) return 'Il y a moins d\'une minute';
        if ($time < 3600) return 'Il y a ' . floor($time/60) . ' minute' . (floor($time/60) > 1 ? 's' : '');
        if ($time < 86400) return 'Il y a ' . floor($time/3600) . ' heure' . (floor($time/3600) > 1 ? 's' : '');
        if ($time < 2592000) return 'Il y a ' . floor($time/86400) . ' jour' . (floor($time/86400) > 1 ? 's' : '');
        
        return date_i18n('j F Y', strtotime($date));
    }
    
    /**
     * Message si utilisateur non connecté
     */
    private static function render_login_required() {
        ob_start();
        ?>
        <div class="sisme-dashboard-login-required">
            <div class="sisme-login-card">
                <h2 class="sisme-login-title">
                    <span class="sisme-login-icon">🔐</span>
                    Connexion requise
                </h2>
                <p class="sisme-login-message">
                    Vous devez être connecté pour accéder à votre dashboard gaming.
                </p>
                <div class="sisme-login-actions">
                    <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="sisme-btn sisme-btn-primary">
                        Se connecter
                    </a>
                    <a href="<?php echo esc_url(wp_registration_url()); ?>" class="sisme-btn sisme-btn-secondary">
                        S'inscrire
                    </a>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Message d'accès refusé
     */
    private static function render_access_denied() {
        ob_start();
        ?>
        <div class="sisme-dashboard-access-denied">
            <div class="sisme-error-card">
                <h2 class="sisme-error-title">
                    <span class="sisme-error-icon">❌</span>
                    Accès refusé
                </h2>
                <p class="sisme-error-message">
                    Vous n'avez pas l'autorisation d'accéder à ce dashboard.
                </p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Message d'erreur générique
     */
    private static function render_error($message) {
        ob_start();
        ?>
        <div class="sisme-dashboard-error">
            <div class="sisme-error-card">
                <h2 class="sisme-error-title">
                    <span class="sisme-error-icon">⚠️</span>
                    Erreur
                </h2>
                <p class="sisme-error-message">
                    <?php echo esc_html($message); ?>
                </p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}