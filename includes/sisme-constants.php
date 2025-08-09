<?php
/**
 * File: /sisme-games-editor/includes/sisme-constants.php
 * Constantes globales pour tout le site
 * 
 * RESPONSABILITÉ:
 * - Centraliser toutes les constantes du site
 * - Éviter la duplication de valeurs hardcodées
 */
if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Constants {

    const SISME_ROOT_URL = 'https://games.sisme.fr/preprod/';  
    
    // ===== AVATARS DISPONIBLES =====
    const SISME_AVATARS_URL = self::SISME_ROOT_URL . 'images/avatar/avatar-user-';
    const SISME_AVATARS_USER = [
        'default' => self::SISME_AVATARS_URL . 'borne-arcade.png',
        'borne-arcade' => self::SISME_AVATARS_URL . 'borne-arcade.png',
        'cd-rom' => self::SISME_AVATARS_URL . 'cd-rom.png',
        'clavier' => self::SISME_AVATARS_URL . 'clavier.png',
        'flipper' => self::SISME_AVATARS_URL . 'flipper.png',
        'gameboy' => self::SISME_AVATARS_URL . 'gameboy.png',
        'joystick' => self::SISME_AVATARS_URL . 'joystick.png',
        'manette' => self::SISME_AVATARS_URL . 'manette.png',
        'tourne-disque' => self::SISME_AVATARS_URL . 'tourne-disque.png'
    ];

    // ===== UPLOAD ET MÉDIAS =====
    const MAX_SECTION_IMAGE_SIZE = 10 * 1024 * 1024; 
    const SISME_IMAGE_ALLOWED_TYPES = ['image/jpeg', 'image/jpg', 'image/png'];
    const SISME_SECTION_IMAGE_ALLOWED_TYPES = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];

    /**
     * Obtenir tous les avatars
     */
    public static function get_avatars() {
        return self::SISME_AVATARS_USER;
    }
    
    /**
     * Vérifier si un avatar existe
     */
    public static function is_valid_avatar($avatar_key) {
        return array_key_exists($avatar_key, self::SISME_AVATARS_USER);
    }
    
    /**
     * Obtenir l'URL d'un avatar
     */
    public static function get_avatar_url($avatar_key) {
        $url = self::SISME_AVATARS_USER[$avatar_key] ?? self::SISME_AVATARS_USER['default'];
        return str_replace(' ', '%20', $url);
    }
}