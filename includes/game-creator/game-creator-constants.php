<?php
/**
 * File: /sisme-games-editor/includes/game-creator/game-creator-constants.php
 * Constantes centralisées pour le module Game Creator
 * 
 * RESPONSABILITÉ:
 * - Définir toutes les constantes META_KEYS pour term_meta
 * - Définir les constantes API_KEYS pour interface publique
 * - Constantes de validation et limites
 * - Remplace les constantes Sisme_Utils_Games pour ce module
 * 
 * DÉPENDANCES:
 * - Aucune (fichier de constantes pur)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Game_Creator_Constants {
    
    /**
     * META KEYS - Stockage WordPress (term_meta)
     */
    const META_DESCRIPTION = 'game_description';
    const META_COVER_MAIN = 'cover_main';
    const META_COVER_NEWS = 'cover_news';
    const META_COVER_PATCH = 'cover_patch';
    const META_COVER_TEST = 'cover_test';
    const META_RELEASE_DATE = 'release_date';
    const META_LAST_UPDATE = 'last_update';
    const META_PLATFORMS = 'game_platforms';
    const META_GENRES = 'game_genres';
    const META_MODES = 'game_modes';
    const META_TEAM_CHOICE = 'is_team_choice';
    const META_EXTERNAL_LINKS = 'external_links';
    const META_TRAILER_LINK = 'trailer_link';
    const META_SCREENSHOTS = 'screenshots';
    const META_DEVELOPERS = 'game_developers';
    const META_PUBLISHERS = 'game_publishers';
    const META_ENTITY_WEBSITE = 'website_url';
    
    /**
     * API KEYS - Interface publique
     */
    const KEY_TERM_ID = 'term_id';
    const KEY_ID = 'id';
    const KEY_NAME = 'name';
    const KEY_TITLE = 'title';
    const KEY_SLUG = 'slug';
    const KEY_DESCRIPTION = 'description';
    const KEY_COVER_URL = 'cover_url';
    const KEY_COVER_ID = 'cover_id';
    const KEY_GAME_URL = 'game_url';
    const KEY_RELEASE_DATE = 'release_date';
    const KEY_LAST_UPDATE = 'last_update';
    const KEY_TIMESTAMP = 'timestamp';
    const KEY_GENRES = 'genres';
    const KEY_MODES = 'modes';
    const KEY_PLATFORMS = 'platforms';
    const KEY_IS_TEAM_CHOICE = 'is_team_choice';
    const KEY_EXTERNAL_LINKS = 'external_links';
    const KEY_TRAILER_LINK = 'trailer_link';
    const KEY_SCREENSHOTS = 'screenshots';
    const KEY_DEVELOPERS = 'developers';
    const KEY_PUBLISHERS = 'publishers';
    const KEY_COVERS = 'covers';
    const KEY_RELEASE_STATUS = 'release_status';
    
    /**
     * COVERS SUB-KEYS
     */
    const KEY_COVER_MAIN = 'main';
    const KEY_COVER_NEWS = 'news';
    const KEY_COVER_PATCH = 'patch';
    const KEY_COVER_TEST = 'test';
    
    /**
     * SECTIONS DE FICHE
     */
    const META_SECTIONS = 'game_sections';
    const KEY_SECTIONS = 'sections';
    const MAX_SECTIONS = 10;
    const MIN_SECTIONS = 1;
    const MAX_SECTION_TITLE_LENGTH = 100;
    const MAX_SECTION_CONTENT_LENGTH = 2000;
    const MIN_SECTION_CONTENT_LENGTH = 20;
    
    /**
     * LIMITES ET VALIDATION
     */
    const MAX_GAME_NAME_LENGTH = 200;
    const MAX_DESCRIPTION_LENGTH = 180;
    const MAX_SCREENSHOTS = 10;
    const MAX_EXTERNAL_LINKS = 8;
    const MAX_DEVELOPERS = 5;
    const MAX_PUBLISHERS = 3;
    
    /**
     * TAXONOMIES
     */
    const TAXONOMY_GAMES = 'post_tag';
    const TAXONOMY_ENTITIES = 'category';
    
    /**
     * STATUTS DE SORTIE
     */
    const RELEASE_STATUS_RELEASED = 'released';
    const RELEASE_STATUS_UPCOMING = 'upcoming';
    const RELEASE_STATUS_EARLY_ACCESS = 'early_access';
    const RELEASE_STATUS_UNKNOWN = 'unknown';
    
    /**
     * PLATEFORMES SUPPORTÉES
     */
    const PLATFORMS = array(
        'windows' => 'Windows',
        'macos' => 'macOS',
        'mac' => 'macOS',
        'linux' => 'Linux',
        'playstation' => 'PlayStation',
        'xbox' => 'Xbox',
        'switch' => 'Switch',
        'ios' => 'iOS',
        'android' => 'Android',
        'web' => 'Navigateur'
    );
    
    /**
     * LIENS EXTERNES SUPPORTÉS
     */
    const EXTERNAL_LINKS_PLATFORMS = array(
        'steam' => 'Steam',
        'epic' => 'Epic Games Store',
        'gog' => 'GOG'
    );
    
    /**
     * Obtenir toutes les clés META utilisées
     */
    public static function get_all_meta_keys() {
        return array(
            self::META_DESCRIPTION,
            self::META_COVER_MAIN,
            self::META_COVER_NEWS,
            self::META_COVER_PATCH,
            self::META_COVER_TEST,
            self::META_RELEASE_DATE,
            self::META_LAST_UPDATE,
            self::META_PLATFORMS,
            self::META_GENRES,
            self::META_MODES,
            self::META_TEAM_CHOICE,
            self::META_EXTERNAL_LINKS,
            self::META_TRAILER_LINK,
            self::META_SCREENSHOTS,
            self::META_DEVELOPERS,
            self::META_PUBLISHERS,
            self::META_SECTIONS
        );
    }
    
    /**
     * Vérifier si une plateforme est supportée
     */
    public static function is_valid_platform($platform) {
        return array_key_exists($platform, self::PLATFORMS);
    }
    
    /**
     * Vérifier si une plateforme de lien externe est supportée
     */
    public static function is_valid_external_platform($platform) {
        return array_key_exists($platform, self::EXTERNAL_LINKS_PLATFORMS);
    }
    
    /**
     * Obtenir le label d'une plateforme
     */
    public static function get_platform_label($platform) {
        return self::PLATFORMS[$platform] ?? $platform;
    }
    
    /**
     * Obtenir la structure par défaut d'une section
     */
    public static function get_default_section_structure() {
        return array(
            'title' => '',
            'content' => '',
            'image_id' => ''
        );
    }
    
    /**
     * Valider une section
     */
    public static function is_valid_section($section) {
        if (!is_array($section)) {
            return false;
        }
        
        $title = $section['title'] ?? '';
        $content = $section['content'] ?? '';
        
        return !empty($title) && 
               strlen($title) <= self::MAX_SECTION_TITLE_LENGTH &&
               !empty($content) && 
               strlen($content) >= self::MIN_SECTION_CONTENT_LENGTH &&
               strlen($content) <= self::MAX_SECTION_CONTENT_LENGTH;
    }
}