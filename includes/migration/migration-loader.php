<?php
/**
 * File: /sisme-games-editor/includes/migration/migration-loader.php
 * Loader principal du système de migration
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Migration_Loader {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->load_files();
        $this->init_admin();
    }
    
    private function load_files() {
        $migration_dir = SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/migration/';
        
        require_once $migration_dir . 'game-data-migration.php';
        require_once $migration_dir . 'migration-admin-interface.php';
    }
    
    private function init_admin() {
        if (is_admin() && current_user_can('manage_options')) {
            Sisme_Migration_Admin::init();
        }
    }
}