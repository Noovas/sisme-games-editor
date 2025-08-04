<?php
/**
 * File: /sisme-games-editor/includes/game-data-creator/game-data-creator.php
 * API principale du module Game Creator
 * 
 * RESPONSABILITÉ:
 * - Interface publique unifiée pour création/modification/suppression de jeux
 * - Orchestration validation + data-manager
 * - Gestion des permissions et sécurité
 * - Fonctions helper pour conversion de données
 * 
 * DÉPENDANCES:
 * - game-data-creator-constants.php
 * - game-data-creator-validator.php
 * - game-data-creator-data-manager.php
 * - Sisme_Game_data_creator_Loader (permissions)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Game_Creator {
    
    /**
     * Créer un nouveau jeu
     * @param array $game_data Données du jeu (non validées)
     * @param int $user_id ID utilisateur (optionnel, par défaut utilisateur courant)
     * @return int|WP_Error ID du jeu créé ou erreur
     */
    public static function create_game($game_data, $user_id = null) {
        // Vérification des permissions
        if (!self::can_create_games($user_id)) {
            return new WP_Error('permission_denied', 'Permissions insuffisantes pour créer un jeu');
        }
        
        // Validation et sanitisation des données
        $validation_result = Sisme_Game_Creator_Validator::validate_game_data($game_data);
        if (!$validation_result['valid']) {
            return new WP_Error('validation_failed', implode(' | ', $validation_result['errors']));
        }
        
        // Création via le data manager
        return Sisme_Game_Creator_Data_Manager::create_game($validation_result['sanitized_data']);
    }
    
    /**
     * Mettre à jour un jeu existant
     * @param int $term_id ID du jeu à modifier
     * @param array $game_data Nouvelles données (non validées)
     * @param int $user_id ID utilisateur (optionnel)
     * @return bool|WP_Error Succès ou erreur
     */
    public static function update_game($term_id, $game_data, $user_id = null) {
        // Vérification des permissions
        if (!self::can_create_games($user_id)) {
            return new WP_Error('permission_denied', 'Permissions insuffisantes pour modifier un jeu');
        }
        
        // Vérifier que le jeu existe
        if (!Sisme_Game_Creator_Data_Manager::game_exists($term_id)) {
            return new WP_Error('game_not_found', 'Jeu introuvable');
        }
        
        // Validation des nouvelles données
        $validation_result = Sisme_Game_Creator_Validator::validate_game_data($game_data);
        if (!$validation_result['valid']) {
            return new WP_Error('validation_failed', implode(' | ', $validation_result['errors']));
        }
        
        // Mise à jour via le data manager
        return Sisme_Game_Creator_Data_Manager::update_game($term_id, $validation_result['sanitized_data']);
    }
    
    /**
     * Supprimer un jeu
     * @param int $term_id ID du jeu à supprimer
     * @param bool $cleanup_files Supprimer aussi les fichiers médias
     * @param int $user_id ID utilisateur (optionnel)
     * @return bool|WP_Error Succès ou erreur
     */
    public static function delete_game($term_id, $cleanup_files = false, $user_id = null) {
        // Vérification des permissions
        if (!self::can_create_games($user_id)) {
            return new WP_Error('permission_denied', 'Permissions insuffisantes pour supprimer un jeu');
        }
        
        // Vérifier que le jeu existe
        if (!Sisme_Game_Creator_Data_Manager::game_exists($term_id)) {
            return new WP_Error('game_not_found', 'Jeu introuvable');
        }
        
        // Suppression via le data manager
        return Sisme_Game_Creator_Data_Manager::delete_game($term_id, $cleanup_files);
    }
    
    /**
     * Récupérer les données complètes d'un jeu
     * @param int $term_id ID du jeu
     * @return array|false Données du jeu ou false si introuvable
     */
    public static function get_game($term_id) {
        return Sisme_Game_Creator_Data_Manager::get_game_data($term_id);
    }
    
    /**
     * Vérifier si un jeu existe
     * @param int $term_id ID du jeu
     * @return bool Existe ou non
     */
    public static function game_exists($term_id) {
        return Sisme_Game_Creator_Data_Manager::game_exists($term_id);
    }
    
    /**
     * Dupliquer un jeu existant
     * @param int $source_term_id ID du jeu source
     * @param string $new_name Nom du nouveau jeu
     * @param int $user_id ID utilisateur (optionnel)
     * @return int|WP_Error ID du nouveau jeu ou erreur
     */
    public static function duplicate_game($source_term_id, $new_name, $user_id = null) {
        // Vérification des permissions
        if (!self::can_create_games($user_id)) {
            return new WP_Error('permission_denied', 'Permissions insuffisantes pour dupliquer un jeu');
        }
        
        // Valider le nouveau nom
        if (empty($new_name)) {
            return new WP_Error('missing_name', 'Le nom du nouveau jeu est requis');
        }
        
        if (strlen($new_name) > Sisme_Game_Creator_Constants::MAX_GAME_NAME_LENGTH) {
            return new WP_Error('name_too_long', 'Le nom est trop long');
        }
        
        return Sisme_Game_Creator_Data_Manager::duplicate_game($source_term_id, $new_name);
    }
    
    /**
     * Créer un jeu depuis des données de soumission
     * @param array $submission_data Structure complète de soumission
     * @param int $user_id ID utilisateur (optionnel)
     * @return int|WP_Error ID du jeu créé ou erreur
     */
    public static function create_from_submission_data($submission_data, $user_id = null) {
        // Extraire les game_data depuis la structure de soumission
        $game_data = self::extract_game_data_from_submission($submission_data);
        if (is_wp_error($game_data)) {
            return $game_data;
        }
        
        // Créer le jeu avec les données extraites
        return self::create_game($game_data, $user_id);
    }
    
    /**
     * Obtenir la liste de tous les jeux
     * @param array $args Arguments pour filtrer/trier
     * @return array Liste des jeux
     */
    public static function get_all_games($args = array()) {
        return Sisme_Game_Creator_Data_Manager::get_all_games($args);
    }
    
    /**
     * Convertir les données game-submission vers format game-data-creator
     * @param array $submission_data Structure de soumission complète
     * @return array|WP_Error Données extraites ou erreur
     */
    public static function extract_game_data_from_submission($submission_data) {
        if (!is_array($submission_data)) {
            return new WP_Error('invalid_submission', 'Structure de soumission invalide');
        }
        
        // Vérifier que game_data existe
        if (!isset($submission_data['game_data']) || !is_array($submission_data['game_data'])) {
            return new WP_Error('missing_game_data', 'Données de jeu manquantes dans la soumission');
        }
        
        $submission_game_data = $submission_data['game_data'];
        $converted_data = array();
        
        // Mapping des champs game-submission vers game-data-creator
        $field_mapping = array(
            'game_name' => 'name',
            'game_description' => 'description',
            'game_release_date' => 'release_date',
            'game_trailer' => 'trailer_link',
            'game_genres' => 'genres',
            'game_platforms' => 'platforms',
            'game_modes' => 'modes',
            'game_external_links' => 'external_links',
            'covers' => 'covers',
            'screenshots' => 'screenshots',
            'sections' => 'sections'
        );
        
        // Convertir les champs standards
        foreach ($field_mapping as $submission_key => $creator_key) {
            if (isset($submission_game_data[$submission_key])) {
                $converted_data[$creator_key] = $submission_game_data[$submission_key];
            }
        }
        
        // Gestion spéciale des développeurs/éditeurs
        if (isset($submission_game_data['game_studio_name']) && !empty($submission_game_data['game_studio_name'])) {
            $converted_data['developers'] = self::resolve_developer_from_submission($submission_game_data);
        }
        
        if (isset($submission_game_data['game_publisher_name']) && !empty($submission_game_data['game_publisher_name'])) {
            $converted_data['publishers'] = self::resolve_publisher_from_submission($submission_game_data);
        }
        
        // Marquer comme choix équipe si c'est une soumission approuvée avec qualité
        $converted_data['is_team_choice'] = self::should_mark_as_team_choice($submission_data);
        
        return $converted_data;
    }
    
    /**
     * Résoudre le développeur depuis les données de soumission
     * @param array $submission_game_data Données de jeu de la soumission
     * @return array IDs de développeurs
     */
    private static function resolve_developer_from_submission($submission_game_data) {
        $studio_name = $submission_game_data['game_studio_name'];
        $studio_url = $submission_game_data['game_studio_url'] ?? '';
        
        // Utiliser le système existant dev-editor-manager si disponible
        if (class_exists('Sisme_Dev_Editor_Manager')) {
            $developer = Sisme_Dev_Editor_Manager::find_entity_by_name($studio_name);
            
            if (!$developer) {
                $developer = Sisme_Dev_Editor_Manager::create_entity($studio_name, $studio_url);
                if (is_wp_error($developer)) {
                    return array();
                }
            }
            
            return array($developer['id']);
        }
        
        return array();
    }
    
    /**
     * Résoudre l'éditeur depuis les données de soumission
     * @param array $submission_game_data Données de jeu de la soumission
     * @return array IDs d'éditeurs
     */
    private static function resolve_publisher_from_submission($submission_game_data) {
        $publisher_name = $submission_game_data['game_publisher_name'];
        $publisher_url = $submission_game_data['game_publisher_url'] ?? '';
        
        // Utiliser le système existant dev-editor-manager si disponible
        if (class_exists('Sisme_Dev_Editor_Manager')) {
            $publisher = Sisme_Dev_Editor_Manager::find_entity_by_name($publisher_name);
            
            if (!$publisher) {
                $publisher = Sisme_Dev_Editor_Manager::create_entity($publisher_name, $publisher_url);
                if (is_wp_error($publisher)) {
                    return array();
                }
            }
            
            return array($publisher['id']);
        }
        
        return array();
    }
    
    /**
     * Déterminer si un jeu doit être marqué comme choix équipe
     * @param array $submission_data Structure complète de soumission
     * @return bool Marquer comme choix équipe ou non
     */
    private static function should_mark_as_team_choice($submission_data) {
        // Par défaut, ne pas marquer automatiquement
        // Cette logique peut être personnalisée selon les critères métier
        
        // Exemple de critères possibles :
        // - Soumission avec note admin élevée
        // - Développeur avec historique de qualité
        // - Jeu avec beaucoup de sections détaillées
        
        $game_data = $submission_data['game_data'] ?? array();
        $admin_data = $submission_data['admin_data'] ?? array();
        
        // Critère simple : si admin a laissé une note positive
        if (isset($admin_data['admin_notes']) && 
            strpos(strtolower($admin_data['admin_notes']), 'excellent') !== false) {
            return true;
        }
        
        // Critère : jeu avec beaucoup de contenu détaillé
        if (isset($game_data['sections']) && 
            is_array($game_data['sections']) && 
            count($game_data['sections']) >= 5) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Vérifier les permissions de création/modification
     * @param int $user_id ID utilisateur (optionnel)
     * @return bool Peut créer/modifier ou non
     */
    private static function can_create_games($user_id = null) {
        return Sisme_Game_data_creator_Loader::can_create_games($user_id);
    }
    
    /**
     * Obtenir les statistiques du module
     * @return array Statistiques diverses
     */
    public static function get_stats() {
        $all_games = self::get_all_games(array('fields' => 'count'));
        $team_choice_games = self::get_all_games(array(
            'fields' => 'count',
            'meta_query' => array(
                array(
                    'key' => Sisme_Game_Creator_Constants::META_TEAM_CHOICE,
                    'value' => '1',
                    'compare' => '='
                )
            )
        ));
        
        return array(
            'total_games' => is_numeric($all_games) ? $all_games : count($all_games),
            'team_choice_games' => is_numeric($team_choice_games) ? $team_choice_games : count($team_choice_games),
            'constants_loaded' => class_exists('Sisme_Game_Creator_Constants'),
            'validator_loaded' => class_exists('Sisme_Game_Creator_Validator'),
            'data_manager_loaded' => class_exists('Sisme_Game_Creator_Data_Manager')
        );
    }
    
    /**
     * Valider des données sans les enregistrer
     * @param array $game_data Données à valider
     * @return array Résultat de validation ['valid' => bool, 'errors' => array, 'sanitized_data' => array]
     */
    public static function validate_only($game_data) {
        return Sisme_Game_Creator_Validator::validate_game_data($game_data);
    }
    
    /**
     * Obtenir la structure par défaut d'un jeu vide
     * @return array Structure avec valeurs par défaut
     */
    public static function get_default_game_structure() {
        return array(
            'name' => '',
            'description' => '',
            'release_date' => '',
            'trailer_link' => '',
            'platforms' => array(),
            'genres' => array(),
            'modes' => array(),
            'developers' => array(),
            'publishers' => array(),
            'external_links' => array(),
            'covers' => array(),
            'screenshots' => array(),
            'sections' => array(
                Sisme_Game_Creator_Validator::get_default_section_structure()
            ),
            'is_team_choice' => false
        );
    }
}