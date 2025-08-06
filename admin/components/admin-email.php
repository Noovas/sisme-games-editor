<?php
/**
 * File: /sisme-games-editor/admin/components/admin-email.php
 * Page admin pour la gestion des emails
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Admin_Email {

    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_hidden_page'));
    }

    /**
     * Ajouter comme page cachée
     */
    public static function add_hidden_page() {
        add_submenu_page(
            null,
            'Gestion Email',
            'Gestion Email',
            'manage_options',
            'sisme-games-email',
            array(__CLASS__, 'render')
        );
    }
    
    public static function render() {
        ?>
        <div class="sisme-admin-container">
            <h2 class="sisme-admin-title">📧 Gestion des Emails</h2>
            <p class="sisme-admin-comment">Gestion des emails, voir et modifier les templates</p>
            <div class="sisme-admin-flex-col">
            </div>
        </div>
        <?php        
    }
}

// Initialiser seulement si on est en admin
if (is_admin()) {
    Sisme_Admin_Email::init();
}
