<?php
/**
 * File: /sisme-games-editor/includes/user/user-header-menu/user-header-menu-loader.php
 * Loader du module menu utilisateur header
 * 
 * RESPONSABILITÉ:
 * - Gestion affichage menu utilisateur dans header avec avatar
 * - Injection automatique dans bouton .sisme-header-user-menu
 * - Intégration système tooltip existant pour menu déroulant
 * - Chargement assets CSS/JS conditionnels
 * 
 * DÉPENDANCES:
 * - SismeTooltip (assets/js/tooltip.js)
 * - SISME_GAMES_EDITOR_PLUGIN_URL constante
 * - wp_get_current_user() et get_avatar_url()
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_User_Header_Menu_Loader {
    
    public static function init() {
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
        add_action('wp_footer', [__CLASS__, 'inject_header_menu']);
    }
    
    public static function enqueue_assets() {
        
        wp_enqueue_style(
            'sisme-user-header-menu',
            SISME_GAMES_EDITOR_PLUGIN_URL . 'includes/user/user-header-menu/assets/user-header-menu.css',
            [],
            SISME_GAMES_EDITOR_VERSION
        );
        
        wp_enqueue_script(
            'sisme-user-header-menu',
            SISME_GAMES_EDITOR_PLUGIN_URL . 'includes/user/user-header-menu/assets/user-header-menu.js',
            ['jquery'],
            SISME_GAMES_EDITOR_VERSION,
            true
        );
    }
    
    public static function inject_header_menu() {
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const headerButton = document.querySelector('.sisme-header-user-menu');
            if (!headerButton) return;
            
            headerButton.innerHTML = '👤';
        });
        </script>
        <?php
    }
}

// Auto-chargement
Sisme_User_Header_Menu_Loader::init();