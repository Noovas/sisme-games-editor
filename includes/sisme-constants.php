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

    const SISME_ROOT_URL = 'https://games.sisme.fr/';  // ← Ajout point-virgule
    
    // ===== AVATARS DISPONIBLES =====
    const SISME_AVATARS_URL = self::SISME_ROOT_URL . 'images/avatar/';  // ← Utiliser self::
    const SISME_AVATARS_USER = [
        'default' => 'https://games.sisme.fr/images/avatar/avatar-user-borne-arcade.png',
        'borne-arcade' => 'https://games.sisme.fr/images/avatar/avatar-user-borne-arcade.png',
        'cd-rom' => 'https://games.sisme.fr/images/avatar/avatar-user-cd-rom.png',
        'clavier' => 'https://games.sisme.fr/images/avatar/avatar-user-clavier.png',
        'flipper' => 'https://games.sisme.fr/images/avatar/avatar-user-flipper.png',
        'gameboy' => 'https://games.sisme.fr/images/avatar/avatar-user-gameboy.png',
        'joystick' => 'https://games.sisme.fr/images/avatar/avatar-user-joystick.png',
        'manette' => 'https://games.sisme.fr/images/avatar/avatar-user-manette.png',
        'tourne-disque' => 'https://games.sisme.fr/images/avatar/avatar-user-tourne-disque.png'
    ];
    
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