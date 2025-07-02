<?php
/**
 * File: /sisme-games-editor/includes/user/user-dashboard/user-dashboard-renderer.php
 * Renderer partagé pour les composants dashboard et profil public
 * 
 * RESPONSABILITÉ:
 * - Rendu HTML pur des composants dashboard
 * - Réutilisation pour dashboard privé et profil public
 * - Gestion contexte public/privé via paramètre
 * - Maintien compatibilité exacte avec existant
 * - Dépendance Sisme_Utils_Games pour constantes
 * - Dépendance Sisme_User_Dashboard_Data_Manager pour données
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_User_Dashboard_Renderer {
    
    /**
     * Rendu du header du dashboard
     * @param array $user_info Informations utilisateur
     * @param array $gaming_stats Statistiques gaming
     * @param array $context Contexte de rendu (public/privé)
     * @return string HTML du header
     */
    public static function render_dashboard_header($user_info, $gaming_stats, $context = ['is_public' => true]) {
        ob_start();
        ?>
        <header class="sisme-dashboard-header">
            <div class="sisme-profile-card">
                <div class="norification-avatar-conteneur">
                    <div class="sisme-profile-avatar">
                        <img src="<?php echo esc_url($user_info['avatar_url']); ?>" alt="Avatar" class="sisme-avatar">
                    </div>
                    <?php if (class_exists('Sisme_User_Notifications_API')): ?>
                        <div class="sisme-notifications-badge-container">
                            <?php echo do_shortcode('[sisme_user_notifications_badge]'); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="sisme-profile-info">
                    <div class="sisme-profile-main">
                        <h1 class="sisme-profile-name"><?php echo esc_html($user_info['display_name']); ?></h1>
                        <?php if (!empty($user_info['bio'])): ?>
                            <p class="sisme-profile-bio"><?php echo esc_html($user_info['bio']); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="sisme-profile-stats">
                        <div class="sisme-stat">
                            <span class="sisme-stat-value"><?php echo esc_html($gaming_stats['owned_games'] ?? 0); ?></span>
                            <span class="sisme-stat-label">Possédés</span>
                        </div>
                        <div class="sisme-stat">
                            <span class="sisme-stat-value"><?php echo esc_html($gaming_stats['favorite_games'] ?? 0); ?></span>
                            <span class="sisme-stat-label">Favoris</span>
                        </div>
                        <div class="sisme-stat">
                            <span class="sisme-stat-value"><?php echo esc_html($gaming_stats['level'] ?? 'Débutant'); ?></span>
                            <span class="sisme-stat-label">Niveau</span>
                        </div>
                    </div>
                </div>
                <div class="sisme-profile-actions">
                    <a href="<?php echo esc_url(wp_logout_url()); ?>" class="sisme-button sisme-button-rouge">
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
     * @param array $dashboard_data Données complètes dashboard
     * @param array $context Contexte de rendu
     * @return string HTML de la grille
     */
    public static function render_dashboard_grid($dashboard_data, $context = ['is_public' => true]) {
        ob_start();
        ?>
        <div class="sisme-dashboard-grid">
            <aside class="sisme-dashboard-sidebar">
                <?php echo self::render_sidebar_navigation($context); ?>
                <?php echo self::render_quick_stats($dashboard_data['gaming_stats'], $context); ?>
            </aside>
            <main class="sisme-dashboard-main">
                <div class="sisme-dashboard-section" data-section="overview">
                    <div class="sisme-section-header">
                        <h2 class="sisme-section-title">
                            <span class="sisme-title-icon">📊</span>
                            Vue d'ensemble
                        </h2>
                    </div>
                    <?php echo self::render_activity_feed($dashboard_data['activity_feed'], $context); ?>
                    <?php echo self::render_recent_games($dashboard_data['recent_games'], $context); ?>
                </div>
                <div class="sisme-dashboard-section" data-section="favorites" style="display: none;">
                    <div class="sisme-section-header">
                        <h2 class="sisme-section-title">
                            <span class="sisme-title-icon">❤️</span>
                            Mes Favoris
                        </h2>
                    </div>
                    <?php echo self::render_favorites_section($dashboard_data['favorite_games'], $context); ?>
                </div>
                <div class="sisme-dashboard-section" data-section="library" style="display: none;">
                    <div class="sisme-section-header">
                        <h2 class="sisme-section-title">
                            <span class="sisme-title-icon">📚</span>
                            La Sismothèque
                        </h2>
                    </div>
                    <?php echo self::render_library_section($dashboard_data['owned_games'], $context); ?>
                </div>
                <div class="sisme-dashboard-section" data-section="activity" style="display: none;">
                    <div class="sisme-section-header">
                        <h2 class="sisme-section-title">
                            <span class="sisme-title-icon">📈</span>
                            Mon Activité
                        </h2>
                    </div>
                    <?php echo self::render_activity_section($dashboard_data['activity_feed'], $context); ?>
                </div>
                <div class="sisme-dashboard-section" data-section="settings" style="display: none;">
                    <div class="sisme-section-header">
                        <h2 class="sisme-section-title">
                            <span class="sisme-title-icon">⚙️</span>
                            Paramètres
                        </h2>
                    </div>
                    <?php echo self::render_settings_section($dashboard_data['user_info']['id'], $context); ?>
                </div>
            </main>
            <aside class="sisme-dashboard-widgets">
                <?php echo self::render_favorites_widget($dashboard_data['favorite_games'], $context); ?>
                <?php echo self::render_stats_widget($dashboard_data['gaming_stats'], $context); ?>
            </aside>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Navigation sidebar
     * @param array $context Contexte de rendu
     * @return string HTML navigation
     */
    public static function render_sidebar_navigation($context = ['is_public' => true]) {
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
                </a></li>
            </ul>
        </nav>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Stats rapides sidebar
     * @param array $gaming_stats Statistiques gaming
     * @param array $context Contexte de rendu
     * @return string HTML stats rapides
     */
    public static function render_quick_stats($gaming_stats, $context = ['is_public' => true]) {
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
     * Feed d'activité (version originale pour Vue d'ensemble)
     * @param array $activity_feed Données activité
     * @param array $context Contexte de rendu
     * @return string HTML feed activité
     */
    public static function render_activity_feed($activity_feed, $context = ['is_public' => true]) {
        ob_start();
        ?>
        <div class="sisme-activity-feed">
            <h3 class="sisme-widget-title">🔥 Activité récente</h3>
            <?php if (empty($activity_feed)): ?>
                <p class="sisme-activity-empty">Aucune activité récente. Commencez à explorer des jeux !</p>
            <?php else: ?>
                <div class="sisme-activity-list">
                    <?php foreach (array_slice($activity_feed, 0, 3) as $activity): ?>
                        <div class="sisme-activity-item sisme-activity-item--detailed animated">
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
     * @param array $recent_games_data Données des jeux récents
     * @param array $context Contexte de rendu
     * @return string HTML de la grille
     */
    public static function render_recent_games($recent_games_data, $context = ['is_public' => true]) {
       ob_start();
       ?>
       <div class="sisme-recent-games">
           <?php $has_personalized = Sisme_User_Dashboard_Data_Manager::user_has_personalized_preferences(get_current_user_id()); ?>
           <h3 class="sisme-widget-title">
               ⚡ Les derniers ajouts
               <?php if ($has_personalized): ?>
                   <span class="sisme-filter-badge">🎯 personnalisés</span>
               <?php endif; ?>
           </h3>           
           <?php if (!empty($recent_games_data)): ?>
               <div class="sisme-games-grid sisme-recent-grid">
                   <?php foreach ($recent_games_data as $game): 
                       $game_id = $game['id'];
                       $game_description = get_term_meta($game_id, Sisme_Utils_Games::META_DESCRIPTION, true);
                       $release_date = get_term_meta($game_id, Sisme_Utils_Games::META_RELEASE_DATE, true);
                       $cover_main = get_term_meta($game_id, Sisme_Utils_Games::META_COVER_MAIN, true);
                       $game_genres = get_term_meta($game_id, Sisme_Utils_Games::META_GENRES, true) ?: [];
                       $current_user_id = get_current_user_id();
                       $user_owned_games = get_user_meta($current_user_id, 'sisme_user_owned_games', true) ?: [];
                       $is_owned = in_array($game_id, $user_owned_games);
                       $cover_url = '';
                       if ($cover_main) {
                           $cover_url = wp_get_attachment_image_url($cover_main, 'medium');
                       }
                       $game_url = $game[Sisme_Utils_Games::KEY_GAME_URL] ?? home_url($game['slug'] . '/');
                       $formatted_date = '';
                       if (!empty($release_date)) {
                           $formatted_date = Sisme_Utils_Formatting::format_release_date($release_date);
                       }
                       $genres_display = [];
                       if (!empty($game_genres)) {
                           foreach (array_slice($game_genres, 0, 2) as $genre_id) {
                               $genre_term = get_term($genre_id);
                               if ($genre_term && !is_wp_error($genre_term)) {
                                   $genres_display[] = str_replace('jeux-', '', $genre_term->slug);
                               }
                           }
                       }
                       ?>
                       <div class="sisme-game-card sisme-recent-grid">
                           <a href="<?php echo esc_url($game_url); ?>" class="sisme-game-link">
                               <div class="sisme-game-cover sisme-recent-grid" <?php echo $cover_url ? 'style="background-image: url(' . esc_url($cover_url) . ');"' : ''; ?>>
                                   <?php if ($is_owned): ?>
                                       <div class="sisme-game-overlay sisme-recent-grid">
                                           <span class="sisme-owned-badge sisme-recent-grid">⚡</span>
                                       </div>
                                   <?php endif; ?>
                               </div>
                               <div class="sisme-game-info sisme-recent-grid">
                                   <h4 class="sisme-game-title sisme-recent-grid"><?php echo esc_html($game[Sisme_Utils_Games::KEY_NAME]); ?></h4>
                                   <?php if (!empty($game_description)): ?>
                                       <p class="sisme-game-description sisme-recent-grid">
                                           <?php echo esc_html(wp_trim_words($game_description, 15, '...')); ?>
                                       </p>
                                   <?php endif; ?>
                                   <?php if (!empty($genres_display)): ?>
                                       <div class="sisme-game-genres sisme-recent-grid">
                                           <?php foreach ($genres_display as $genre): ?>
                                               <span class="sisme-badge sisme-badge-genre"><?php echo esc_html($genre); ?></span>
                                           <?php endforeach; ?>
                                       </div>
                                   <?php endif; ?>
                                   <?php if ($formatted_date): ?>
                                       <div class="sisme-game-release-date sisme-recent-grid">
                                           <span class="sisme-date-icon sisme-recent-grid">📅</span>
                                           <span class="sisme-date-text sisme-recent-grid"><?php echo esc_html($formatted_date); ?></span>
                                       </div>
                                   <?php endif; ?>
                               </div>
                           </a>
                       </div>
                   <?php endforeach; ?>
               </div>
           <?php else: ?>
               <p class="sisme-widget-empty">Aucun jeu récent trouvé.</p>
           <?php endif; ?>
       </div>
       <?php
       return ob_get_clean();
    }
    
    /**
     * Section Favoris avec liens
     * @param array $favorite_games Données jeux favoris
     * @param array $context Contexte de rendu
     * @return string HTML section favoris
     */
    public static function render_favorites_section($favorite_games, $context = ['is_public' => true]) {
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
                            <div class="sisme-game-cover" style="background-image: url('<?php echo esc_url($game[Sisme_Utils_Games::KEY_COVER_URL] ?? ''); ?>');"></div>
                            <div class="sisme-game-info">
                                <h4 class="sisme-game-title"><?php echo esc_html($game[Sisme_Utils_Games::KEY_NAME] ?? 'Jeu sans nom'); ?></h4>
                                <?php if (!empty($game[Sisme_Utils_Games::KEY_DESCRIPTION])): ?>
                                    <p class="sisme-game-description"><?php echo esc_html(wp_trim_words($game[Sisme_Utils_Games::KEY_DESCRIPTION], 15, '...')); ?></p>
                                <?php endif; ?>
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
     * Section Sismothèque avec liens
     * @param array $owned_games Données jeux possédés
     * @param array $context Contexte de rendu
     * @return string HTML section bibliothèque
     */
    public static function render_library_section($owned_games, $context = ['is_public' => true]) {
        ob_start();
        ?>
        <div class="sisme-library-section">
            <?php if (empty($owned_games)): ?>
                <div class="sisme-empty-state">
                    <div class="sisme-empty-icon">📚</div>
                    <h3>Aucun jeu possédé</h3>
                    <p>Marquez les jeux que vous possédez pour créer votre collection personnelle !</p>
                </div>
            <?php else: ?>
                <div class="sisme-games-grid sisme-library-grid">
                    <?php foreach ($owned_games as $game): ?>
                        <a href="<?php echo esc_url(self::get_game_url($game['id'])); ?>" class="sisme-game-card">
                            <div class="sisme-game-cover" style="background-image: url('<?php echo esc_url($game[Sisme_Utils_Games::KEY_COVER_URL] ?? ''); ?>');"></div>
                            <div class="sisme-game-info">
                                <h4 class="sisme-game-title"><?php echo esc_html($game[Sisme_Utils_Games::KEY_NAME] ?? 'Jeu sans nom'); ?></h4>
                                <?php if (!empty($game[Sisme_Utils_Games::KEY_DESCRIPTION])): ?>
                                    <p class="sisme-game-description"><?php echo esc_html(wp_trim_words($game[Sisme_Utils_Games::KEY_DESCRIPTION], 15, '...')); ?></p>
                                <?php endif; ?>
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
     * Section Activité complète
     * @param array $activity_feed Données activité
     * @param array $context Contexte de rendu
     * @return string HTML section activité
     */
    public static function render_activity_section($activity_feed, $context = ['is_public' => true]) {
        ob_start();
        ?>
        <div class="sisme-activity-section">
            <?php if (empty($activity_feed)): ?>
                <div class="sisme-empty-state">
                    <div class="sisme-empty-icon">📈</div>
                    <h3>Aucune activité</h3>
                    <p>Votre activité apparaîtra ici au fur et à mesure de vos interactions !</p>
                </div>
            <?php else: ?>
                <div class="sisme-activity-timeline">
                    <?php foreach ($activity_feed as $activity): ?>
                        <div class="sisme-activity-item sisme-activity-item--detailed animated">
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
     * Section Paramètres avec intégration user-preferences
     * @param int $user_id ID utilisateur
     * @param array $context Contexte de rendu
     * @return string HTML section paramètres
     */
    public static function render_settings_section($user_id, $context = ['is_public' => true]) {
        if (!class_exists('Sisme_User_Preferences_Loader')) {
            return self::render_preferences_unavailable();
        }
        $preferences_loader = Sisme_User_Preferences_Loader::get_instance();
        if (!$preferences_loader || !method_exists($preferences_loader, 'force_load_assets')) {
           return self::render_preferences_error();
        }
        $preferences_loader->force_load_assets();
        if (!get_userdata($user_id)) {
            return self::render_preferences_error('Utilisateur introuvable');
        }
        ob_start();
        ?>
        <div class="sisme-dashboard-preferences">
            <?php 
            $preferences_html = Sisme_User_Preferences_API::render_preferences_shortcode([
                'sections' => 'profile,gaming,notifications,privacy',
                Sisme_Utils_Games::KEY_TITLE => '',
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
     * Widget favoris sidebar avec liens vers les fiches
     * @param array $favorite_games Données jeux favoris
     * @param array $context Contexte de rendu
     * @return string HTML widget favoris
     */
    public static function render_favorites_widget($favorite_games, $context = ['is_public' => true]) {
        ob_start();
        ?>
        <div class="sisme-favorites-widget">
            <h3 class="sisme-widget-title">❤️ Favoris récents</h3>
            <?php 
            $current_user_id = get_current_user_id();
            
            // Utiliser le nouveau système user-actions
            if (class_exists('Sisme_User_Actions_Data_Manager')) {
                $favorite_game_ids = Sisme_User_Actions_Data_Manager::get_user_collection($current_user_id, 'favorite', 3);
            } else {
                // Fallback ancien système
                $favorite_game_ids = get_user_meta($current_user_id, 'sisme_user_favorite_games', true);
                if (is_array($favorite_game_ids)) {
                    $favorite_game_ids = array_slice($favorite_game_ids, 0, 3);
                }
            }
            
            if (empty($favorite_game_ids) || !is_array($favorite_game_ids)): ?>
                <p class="sisme-widget-empty">Aucun favori ajouté.</p>
            <?php else: ?>
                <div class="sisme-favorites-preview">
                    <?php foreach ($favorite_game_ids as $game_id): 
                        $term = get_term($game_id, 'post_tag');
                        if (!$term || is_wp_error($term)) continue;
                        
                        $cover_main = get_term_meta($game_id, Sisme_Utils_Games::META_COVER_MAIN, true);
                        $cover_url = '';
                        if ($cover_main) {
                            $cover_url = wp_get_attachment_image_url($cover_main, 'thumbnail');
                        }
                        ?>
                        <a href="<?php echo esc_url(self::get_game_url($game_id)); ?>" class="sisme-favorite-item">
                            <div class="sisme-favorite-cover" style="background-image: url('<?php echo esc_url($cover_url); ?>');"></div>
                            <span class="sisme-favorite-name"><?php echo esc_html($term->name); ?></span>
                        </a>
                    <?php endforeach; ?>
                    
                    <?php 
                    // Compter le total pour l'affichage "Voir tous"
                    $total_favorites = 0;
                    if (class_exists('Sisme_User_Actions_Data_Manager')) {
                        $all_favorites = Sisme_User_Actions_Data_Manager::get_user_collection($current_user_id, 'favorite');
                        $total_favorites = count($all_favorites);
                    } else {
                        $all_favorites = get_user_meta($current_user_id, 'sisme_user_favorite_games', true);
                        $total_favorites = is_array($all_favorites) ? count($all_favorites) : 0;
                    }
                    
                    if ($total_favorites > 3): ?>
                        <a href="#favorites" class="sisme-view-all" data-section="favorites">
                            Voir tous les favoris (<?php echo $total_favorites; ?>)
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Widget statistiques détaillées
     * @param array $gaming_stats Statistiques gaming
     * @param array $context Contexte de rendu
     * @return string HTML widget stats
     */
    public static function render_stats_widget($gaming_stats, $context = ['is_public' => true]) {
        ob_start();
        ?>
        <div class="sisme-stats-widget">
            <h3 class="sisme-widget-title">🏆 Statistiques</h3>
            <div class="sisme-stats-detailed">
                <div class="sisme-stat-row">
                    <span class="sisme-stat-label">Jeux possédés</span>
                    <span class="sisme-stat-value"><?php echo esc_html($gaming_stats['owned_games'] ?? 0); ?></span>
                </div>
                <div class="sisme-stat-row">
                    <span class="sisme-stat-label">Favoris</span>
                    <span class="sisme-stat-value"><?php echo esc_html($gaming_stats['favorite_games'] ?? 0); ?></span>
                </div>
                <div class="sisme-stat-row">
                    <span class="sisme-stat-label">Niveau</span>
                    <span class="sisme-stat-value"><?php echo esc_html($gaming_stats['level'] ?? 'Débutant'); ?></span>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Génération URL fiche jeu avec priorité article puis tag
     * @param int $game_id ID du jeu
     * @return string URL de la fiche
     */
    public static function get_game_url($game_id) {
        $fiche_post = get_posts([
            'post_type' => 'post',
            'meta_query' => [
                [
                    'key' => 'associated_game_id',
                    'value' => $game_id,
                    'compare' => '='
                ]
            ],
            'posts_per_page' => 1,
            'post_status' => 'publish'
        ]);
        $term = get_term($game_id, 'post_tag');
        if (!empty($fiche_post)) {
            return get_permalink($fiche_post[0]->ID);
        } else {
            return home_url($term->slug . '/');
        }
    }
    
    /**
     * Formatage date relative (il y a X temps)
     * @param string $date Date à formater
     * @return string Date relative formatée
     */
    public static function format_time_ago($date) {
        if (empty($date)) {
            return 'Date inconnue';
        }
        if (is_numeric($date)) {
            $timestamp = intval($date);
        } else {
            $timestamp = strtotime($date);
            if ($timestamp !== false) {
                $wp_offset = get_option('gmt_offset') * HOUR_IN_SECONDS;
                $timestamp = $timestamp - $wp_offset;
            }
        }
        if ($timestamp === false || $timestamp <= 0) {
            return 'Date invalide';
        }
        $time = time() - $timestamp;
        if ($time < 0) return 'Dans le futur ?!';
        if ($time < 60) return 'à l\'instant';
        if ($time < 3600) return 'Il y a ' . floor($time/60) . ' min';
        if ($time < 86400) return 'Il y a ' . floor($time/3600) . ' h';
        if ($time < 2592000) return 'Il y a ' . floor($time/86400) . ' j';
        if ($time < 31536000) return 'Il y a ' . floor($time/2592000) . ' mois';
        return 'Il y a ' . floor($time/31536000) . ' an' . (floor($time/31536000) > 1 ? 's' : '');
    }
    
    /**
     * Message erreur accès refusé
     * @return string HTML erreur accès
     */
    public static function render_access_denied() {
        ob_start();
        ?>
        <div class="sisme-access-denied">
            <div class="sisme-error-content">
                <div class="sisme-error-icon">🚫</div>
                <h3>Accès refusé</h3>
                <p>Vous n'avez pas les permissions nécessaires pour accéder à cette page.</p>
                <a href="<?php echo esc_url(home_url()); ?>" class="sisme-button sisme-button-primary">
                    Retour à l'accueil
                </a>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Messages d'erreur génériques
     * @param string $message Message d'erreur
     * @return string HTML erreur
     */
    public static function render_error($message) {
        ob_start();
        ?>
        <div class="sisme-dashboard-error">
            <div class="sisme-error-content">
                <div class="sisme-error-icon">❌</div>
                <h3>Erreur</h3>
                <p><?php echo esc_html($message); ?></p>
                <button onclick="location.reload()" class="sisme-button sisme-button-primary">
                    🔄 Recharger la page
                </button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Message si le module préférences n'est pas disponible
     * @return string HTML erreur préférences
     */
    public static function render_preferences_unavailable() {
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
     * @param string $message Message d'erreur optionnel
     * @return string HTML erreur préférences
     */
    public static function render_preferences_error($message = '') {
        ob_start();
        ?>
        <div class="sisme-preferences-error">
            <div class="sisme-empty-state">
                <div class="sisme-empty-icon">❌</div>
                <h3>Erreur de chargement</h3>
                <p><?php echo !empty($message) ? esc_html($message) : 'Impossible d\'initialiser le module de préférences. Veuillez recharger la page.'; ?></p>
                <button onclick="location.reload()" class="sisme-btn sisme-btn--primary" style="margin-top: 1rem;">
                    🔄 Recharger la page
                </button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}