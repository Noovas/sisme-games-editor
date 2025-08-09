<?php
/**
 * File: /sisme-admin-games-editor/admin/components/admin-page-users.php
 * Description: Composant de la page des utilisateurs dans l'interface d'administration
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Admin_Page_Users {
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_admin_menu'));
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
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/module-admin-page-wrapper.php';

        $page = new Sisme_Admin_Page_Wrapper(
            'Tous les Utilisateurs',
            'Gestion complète de tous les utilisateurs disponibles',
            'user',
            admin_url('admin.php?page=sisme-games-users'),
            'Retour au menu utilisateurs',
        );
        
        $page->render_start();
        self::render_stats();
        self::render_user_list();
        $page->render_end();    
    }

    public static function render_stats() {
        Sisme_Admin_Page_Wrapper::render_card_start(
            'Statistiques utilisateurs',
            'stats',
            '',
            'sisme-admin-grid sisme-admin-grid-3',
            false,
        );
            ?>
            <div class="sisme-admin-stat-card sisme-admin-stat-warning">
                <div class="sisme-admin-stat-number"><?php echo 'NC'; ?></div>
                <div class="sisme-admin-stat-label">Label</div>
            </div>
            <div class="sisme-admin-stat-card sisme-admin-stat-primary">
                <div class="sisme-admin-stat-number"><?php echo 'NC'; ?></div>
                <div class="sisme-admin-stat-label">Label</div>
            </div>
            <div class="sisme-admin-stat-card sisme-admin-stat-danger">
                <div class="sisme-admin-stat-number"><?php echo 'NC'; ?></div>
                <div class="sisme-admin-stat-label">Label</div>
            </div>
            <?php
        Sisme_Admin_Page_Wrapper::render_card_end();
    }

    public static function render_user_list() {
        $users = get_users();

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

            Sisme_Admin_Page_Wrapper::render_card_end();

            Sisme_Admin_Page_Wrapper::render_card_start(
                'Liste des utilisateurs',
                'lib',
                '',
                '',
                false,
            );
            if (!empty($users)) {
                foreach ($users as $user) {
                    self::render_user_row($user->ID);
                }
            } else {
                echo '<p>Aucun utilisateur trouvé.</p>';
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
        $user = get_userdata($user_id);
        if ($user) {
            $user_name = $user->display_name;
            //$user_avatar = Avatar tiré du module user
            $user_nickname = $user->nickname;
            $user_email = $user->user_email;
            $user_roles = implode(', ', $user->roles);
        }

        Sisme_Admin_Page_Wrapper::render_card_start(
            'Détails de l\'utilisateur',
            'details',
            '',
            '',
            false,
        );
        ?>
        <div class="sisme-admin-user-details">
            <!-- Avatar tiré du module user -->

            <div class="sisme-admin-user-info">
                <div class="sisme-admin-user-name"><?php echo $user_name; ?></div>
                <div class="sisme-admin-user-nickname"><?php echo $user_nickname; ?></div>
                <div class="sisme-admin-user-email"><?php echo $user_email; ?></div>
                <div class="sisme-admin-user-roles"><?php echo $user_roles; ?></div>
            </div>
        </div>

        <?php
        Sisme_Admin_Page_Wrapper::render_card_end();
    }
}