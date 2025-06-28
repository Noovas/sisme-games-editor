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
    const SISME_AVATARS_URL = self::SISME_ROOT_URL . 'avatar/';  // ← Utiliser self::
    const SISME_AVATARS_USER = [
        'default' => self::SISME_AVATARS_URL . 'avatar user borne arcade.png',
        '1' => self::SISME_AVATARS_URL . 'avatar user borne arcade.png',
        '2' => self::SISME_AVATARS_URL . 'avatar user cd-rom.png',
        '3' => self::SISME_AVATARS_URL . 'avatar user clavier.png',
        '4' => self::SISME_AVATARS_URL . 'avatar user filpper.png',
        '5' => self::SISME_AVATARS_URL . 'avatar user gameboy.png',
        '6' => self::SISME_AVATARS_URL . 'avatar user joystick.png',
        '7' => self::SISME_AVATARS_URL . 'avatar user manette.png',
        '8' => self::SISME_AVATARS_URL . 'avatar user tourne disque.png'
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
        return array_key_exists($avatar_key, self::SISME_AVATARS_USER);  // ← Correction variable
    }
    
    /**
     * Obtenir l'URL d'un avatar
     */
    public static function get_avatar_url($avatar_key) {
        return self::SISME_AVATARS_USER[$avatar_key] ?? self::SISME_AVATARS_USER['default'];  // ← Correction variables + default
    }
}