<?php
/**
 * File: /sisme-games-editor/includes/game-page-creator/game-page-content-filter.php
 * Filtre de contenu pour le système Game Page Creator
 * 
 * RESPONSABILITÉ:
 * - Remplacer le contenu des fiches de jeu par le rendu dynamique
 * - Détection automatique des pages créées avec le nouveau système
 * - Chargement et formatage des données depuis term_meta
 * 
 * DÉPENDANCES:
 * - game-data-formatter.php
 * - game-page-renderer.php
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/game-page-creator/game-data-formatter.php';
require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/game-page-creator/game-page-renderer.php';
require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'admin/assets/PHP-admin-games-actions.php';

class Sisme_Game_Page_Content_Filter {
    
    /**
     * Initialiser le filtre de contenu
     */
    public static function init() {
        add_filter('the_content', array(__CLASS__, 'process_content'));
    }
    
    /**
     * Traiter le contenu des pages
     * 
     * @param string $content Contenu original
     * @return string Contenu modifié ou original
     */
    public static function process_content($content) {
        global $post;
        if (!$post || !is_single()) {
            return $content;
        }
        
        $post_id = $post->ID;
        $term_id = self::get_game_term_id($post_id);
        
        // Si ce n'est pas une page de jeu, retourner le contenu original
        if (!$term_id) {
            return $content;
        }
        
        // Vérifier si le jeu est dépublié
        if (Sisme_Admin_Games_Actions::is_game_unpublished($term_id)) {
            // Si l'utilisateur est admin, afficher un message d'avertissement
            if (current_user_can('manage_options')) {
                return self::render_unpublished_admin_notice($term_id) . $content;
            } else {
                // Pour les visiteurs, rediriger vers 404 ou afficher un message
                return self::render_unpublished_public_message();
            }
        }

        return self::render_game_page($post_id);
    }
    
    /**
     * Générer le rendu d'une page de jeu
     * 
     * @param int $post_id ID du post
     * @return string HTML généré
     */
    private static function render_game_page($post_id) {
        $term_id = self::get_game_term_id($post_id);
        if (!$term_id) {
            return '<p>Erreur : Impossible de charger les données du jeu.</p>';
        }
        $game_data = Sisme_Game_Data_Formatter::format_game_data($term_id);
        if (!$game_data) {
            return '<p>Erreur : Impossible de formater les données du jeu.</p>';
        }
        $html = Sisme_Game_Page_Renderer::render($game_data);        
        return $html;
    }
    
    /**
     * Récupérer le term_id du jeu depuis les tags du post
     * 
     * @param int $post_id ID du post
     * @return int|false Term ID ou false si non trouvé
     */
    private static function get_game_term_id($post_id) {
        $tags = wp_get_post_tags($post_id);
        if (empty($tags)) {
            return false;
        }
        return $tags[0]->term_id;
    }
    
    /**
     * Afficher un message d'avertissement pour les administrateurs
     * 
     * @param int $term_id ID du terme du jeu
     * @return string HTML du message d'avertissement
     */
    private static function render_unpublished_admin_notice($term_id) {
        $unpublished_at = get_term_meta($term_id, 'game_unpublished_at', true);
        $formatted_date = $unpublished_at ? date('d/m/Y à H:i', strtotime($unpublished_at)) : 'Date inconnue';
        
        ob_start();
        ?>
        <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 20px 0; border-radius: 5px;">
            <h3 style="color: #856404; margin: 0 0 10px 0;">🚫 Jeu Dépublié - Vue Administrateur</h3>
            <p style="margin: 0; color: #856404;">
                <strong>Ce jeu a été dépublié le <?php echo esc_html($formatted_date); ?>.</strong><br>
                Il n'est plus accessible au public mais vous pouvez le voir car vous êtes administrateur.<br>
                <a href="<?php echo admin_url('admin.php?page=sisme-games-all-games'); ?>" style="color: #007cba;">Gérer ce jeu dans l'administration</a>
            </p>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Afficher un message pour les visiteurs publics
     * 
     * @return string HTML du message public
     */
    private static function render_unpublished_public_message() {
        ob_start();
        ?>
        <div style="text-align: center; padding: 40px 20px;">
            <h2 style="color: #666; margin-bottom: 20px;">🚫 Contenu non disponible</h2>
            <p style="color: #888; font-size: 16px; margin-bottom: 30px;">
                Ce jeu n'est temporairement plus accessible.<br>
                Il pourrait être en cours de mise à jour ou de révision.
            </p>
            <p style="color: #999; font-size: 14px;">
                <a href="<?php echo home_url(); ?>" style="color: #007cba; text-decoration: none;">← Retour à l'accueil</a> | 
                <a href="<?php echo home_url('/jeux'); ?>" style="color: #007cba; text-decoration: none;">Voir tous les jeux</a>
            </p>
        </div>
        <?php
        return ob_get_clean();
    }
}
