<?php
/**
 * File: /sisme-admin-games-editor/admin/components/admin-page-users.php
 * Description: Composant de la page des utilisateurs dans l'interface d'administration
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('SISME_GAMES_EDITOR_PLUGIN_FILE')) {
    define('SISME_GAMES_EDITOR_PLUGIN_FILE', __FILE__);
}

class Sisme_Admin_Page_Users {

    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_admin_menu'));
        add_action('admin_menu', array(__CLASS__, 'admin_include'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_admin_scripts'));
    }

    public static function admin_include() {
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'admin/assets/PHP-admin-page-wrapper.php';
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'admin/assets/PHP-admin-user-getter.php';
    }

    public static function enqueue_admin_scripts() {
        wp_enqueue_script(
            'sisme-admin-search-bar',
            SISME_GAMES_EDITOR_PLUGIN_URL . 'admin/assets/JS-admin-search-bar.js',
            array(),
            null,
            true
        );
    }

    public static function add_admin_menu() {
        add_submenu_page(
            null,
            'Utilisateurs',
            'users',
            'manage_options',
            'sisme-games-users-page',
            array(__CLASS__, 'render')
        );
    }

    public static function render() {
        $users_list = Sisme_Admin_User_Getter::sisme_admin_get_users();
        $users_stats = Sisme_Admin_User_Getter::get_users_global_stats();

        $page = new Sisme_Admin_Page_Wrapper(
            'Tous les Utilisateurs',
            'Gestion complète de tous les utilisateurs disponibles',
            'user',
            admin_url('admin.php?page=sisme-games-users'),
            'Retour au menu utilisateurs',
        );
        
        $page->render_start();
        self::render_stats($users_stats);
        self::render_user_list($users_list);
        $page->render_end();    
    }

    public static function render_stats($users_stats = array()) {

        Sisme_Admin_Page_Wrapper::render_card_start(
            'Statistiques utilisateurs',
            'stats',
            '',
            'sisme-admin-grid sisme-admin-grid-5',
            false,
        );
            ?>
            <div class="sisme-admin-stat-card sisme-admin-stat-card-info">
                <div class="sisme-admin-stat-number"><?php echo $users_stats['total_users']; ?></div>
                <div class="sisme-admin-stat-label"><?php echo Sisme_Admin_Page_Wrapper::get_predefined_icon('user', 0, 12) ?> Utilisateurs</div>
            </div>
            <div class="sisme-admin-stat-card sisme-admin-stat-card-special">
                <div class="sisme-admin-stat-number"><?php echo $users_stats['developers_approved']; ?></div>
                <div class="sisme-admin-stat-label"><?php echo Sisme_Admin_Page_Wrapper::get_predefined_icon('dev', 0, 12) ?> Développeurs</div>
            </div>
            <div class="sisme-admin-stat-card sisme-admin-stat-card-danger">
                <div class="sisme-admin-stat-number"><?php echo $users_stats['users_favoris_games']; ?></div>
                <div class="sisme-admin-stat-label"><?php echo Sisme_Admin_Page_Wrapper::get_predefined_icon('heart', 0, 12) ?> Jeux favoris</div>
            </div>
            <div class="sisme-admin-stat-card sisme-admin-stat-card-warning">
                <div class="sisme-admin-stat-number"><?php echo $users_stats['users_owned_games']; ?></div>
                <div class="sisme-admin-stat-label"><?php echo Sisme_Admin_Page_Wrapper::get_predefined_icon('joystick', 0, 12) ?> Jeux possédés</div>
            </div>
            <div class="sisme-admin-stat-card sisme-admin-stat-card-danger">
                <div class="sisme-admin-stat-number"><?php echo $users_stats['users_social_links']; ?></div>
                <div class="sisme-admin-stat-label"><?php echo Sisme_Admin_Page_Wrapper::get_predefined_icon('users', 0, 12) ?> Liens sociaux</div>
            </div>
            <?php
        Sisme_Admin_Page_Wrapper::render_card_end();
    }

    public static function render_user_list($users_list = array()) {
        Sisme_Admin_Page_Wrapper::render_card_start(
            'Liste des utilisateurs',
            'users',
            '',
            'sisme-admin-flex sisme-admin-flex-col sisme-admin-gap-6',
            false,
        );
            Sisme_Admin_Page_Wrapper::render_card_start(
                'Chercher un utilisateur',
                'search',
                '',
                '',
                false,
            );
            ?>
            <div>
                <input 
                    type="text" 
                    id="sisme-admin-search-input-user" 
                    placeholder="Rechercher un utilisateur...">
            </div>
            <?php
            Sisme_Admin_Page_Wrapper::render_card_end();

            Sisme_Admin_Page_Wrapper::render_card_start( 'Liste des utilisateurs', 'lib', '', '', false,);
                if (!empty($users_list['users'])) {
                    ?>
                    <div id="sisme-admin-users-list" class="sisme-admin-card-content sisme-admin-grid">
                        <?php foreach ($users_list['users'] as $user) :?>
                            <?php $user_data = Sisme_Admin_User_Getter::sisme_admin_get_user_data($user['ID']); ?>
                            <?php $user_preferences = $user_data['preferences']; ?>
                            <?php $user_gaming_stats = $user_data['gaming_stats']; ?>
                            <?php $user_social_stats = $user_data['social_stats']; ?>
                            <div class="sisme-admin-card sisme-admin-bg-fade-black-xs sisme-admin-search-results">
                                <div class="sisme-admin-card-header sisme-admin-flex sisme-admin-align-items-center">                                
                                    <img class="sisme-admin-image-avatar-3xl" src="<?php echo esc_html($user_preferences['avatar']); ?>">
                                    <h3 id="sisme-admin-user-<?php echo esc_html($user['ID']); ?>"><?php echo esc_html($user['display_name']); ?></h3>
                                    <small>
                                        <?php echo ' | ' . esc_html($user['ID']. ' | '); ?>
                                        <?php echo esc_html($user['user_nicename']. ' | '); ?>
                                        <?php echo esc_html($user['user_login']. ' | '); ?>
                                        <?php echo esc_html(date('d/m/Y', strtotime($user['user_registered'])). ' | '); ?>
                                        <?php echo esc_html(implode(', ', $user['roles'])). ' | '; ?>
                                    </small>
                                </div>
                                <div class="sisme-admin-grid sisme-admin-grid-2">
                                    <div class="sisme-admin-flex-col sisme-admin-gap-1">
                                        <h3 class="sisme-admin-card-header"><?php echo Sisme_Admin_Page_Wrapper::get_predefined_icon('games') ?>Stats jeux</h3>
                                        <div class="sisme-admin-grid sisme-admin-grid-4">
                                            <div class="sisme-admin-stat-card sisme-admin-stat-card-info">
                                                <div class="sisme-admin-stat-number"><?php echo esc_html($user_gaming_stats['total_unique_games']); ?></div>
                                                <div class="sisme-admin-stat-label"><?php echo Sisme_Admin_Page_Wrapper::get_predefined_icon('joystick', 0, 12) ?> Total</div>
                                            </div>
                                            <div class="sisme-admin-stat-card sisme-admin-stat-card-danger">
                                                <div class="sisme-admin-stat-number"><?php echo esc_html($user_gaming_stats['favorite_games']); ?></div>
                                                <div class="sisme-admin-stat-label"><?php echo Sisme_Admin_Page_Wrapper::get_predefined_icon('heart', 0, 12) ?> Favoris</div>
                                            </div>
                                            <div class="sisme-admin-stat-card sisme-admin-stat-card-special">
                                                <div class="sisme-admin-stat-number"><?php echo esc_html($user_gaming_stats['owned_games']); ?></div>
                                                <div class="sisme-admin-stat-label"><?php echo Sisme_Admin_Page_Wrapper::get_predefined_icon('game', 0, 12) ?> Possédés</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="sisme-admin-flex-col sisme-admin-gap-1">
                                        <h3 class="sisme-admin-card-header"><?php echo Sisme_Admin_Page_Wrapper::get_predefined_icon('users') ?>Stats sociales</h3>
                                        <div class="sisme-admin-grid sisme-admin-grid-4">
                                            <div class="sisme-admin-stat-card sisme-admin-stat-card-info">
                                                <div class="sisme-admin-stat-number"><?php echo esc_html($user_social_stats['friends_count']); ?></div>
                                                <div class="sisme-admin-stat-label"><?php echo Sisme_Admin_Page_Wrapper::get_predefined_icon('user', 0, 12) ?> Amis</div>
                                            </div>
                                            <div class="sisme-admin-stat-card sisme-admin-stat-card-warning">
                                                <div class="sisme-admin-stat-number"><?php echo esc_html($user_social_stats['pending_requests']); ?></div>
                                                <div class="sisme-admin-stat-label"><?php echo Sisme_Admin_Page_Wrapper::get_predefined_icon('pending', 0, 12) ?> En attente</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php
                    
                } else {
                    ?>
                    <span>Aucun utilisateur trouvé.</span>
                    <?php
                }
                
            Sisme_Admin_Page_Wrapper::render_card_end();
            
        Sisme_Admin_Page_Wrapper::render_card_end();
    }

    /**
     * Rendu d'une ligne d'utilisateur
     *
     * @param int $user_id ID de l'utilisateur
     */
    public static function render_user_row($user_id) {

        Sisme_Admin_Page_Wrapper::render_card_start(
            'Détails de l\'utilisateur',
            'details',
            '',
            '',
            false,
        );
        ?>

        <?php
        Sisme_Admin_Page_Wrapper::render_card_end();
    }
}