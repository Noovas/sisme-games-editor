<?php
/**
 * File: /sisme-games-editor/includes/dev-editor-manager/dev-editor-manager.php
 * API CRUD pour la gestion des développeurs/éditeurs
 * 
 * RESPONSABILITÉ:
 * - CRUD complet pour les entités dev/éditeur
 * - Gestion des catégories enfants de "editeurs-developpeurs"
 * - Validation et nettoyage des données
 * - Vérification des usages avant suppression
 * 
 * DÉPENDANCES:
 * - WordPress Category API
 * - WordPress Meta API
 * - Sisme_Utils_Games constants
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Dev_Editor_Manager {
    
    public static function get_parent_category_id() {
        $parent = get_category_by_slug('editeurs-developpeurs');
        return $parent ? $parent->term_id : 0;
    }
    
    public static function get_all_entities() {
        $parent_id = self::get_parent_category_id();
        if (!$parent_id) {
            return [];
        }
        
        $entities = get_categories([
            'parent' => $parent_id,
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC'
        ]);
        
        if (is_wp_error($entities)) {
            return [];
        }
        
        $formatted_entities = [];
        foreach ($entities as $entity) {
            $formatted_entities[] = [
                'id' => $entity->term_id,
                'name' => $entity->name,
                'slug' => $entity->slug,
                'website' => get_term_meta($entity->term_id, Sisme_Utils_Games::META_ENTITY_WEBSITE, true),
                'games_count' => self::count_games_for_entity($entity->term_id)
            ];
        }
        
        return $formatted_entities;
    }
    
    public static function get_entity_by_id($entity_id) {
        $entity_id = intval($entity_id);
        if (!$entity_id) {
            return false;
        }
        
        $entity = get_category($entity_id);
        if (!$entity || is_wp_error($entity)) {
            return false;
        }
        
        $parent_id = self::get_parent_category_id();
        if ($entity->parent !== $parent_id) {
            return false;
        }
        
        return [
            'id' => $entity->term_id,
            'name' => $entity->name,
            'slug' => $entity->slug,
            'website' => get_term_meta($entity->term_id, Sisme_Utils_Games::META_ENTITY_WEBSITE, true),
            'games_count' => self::count_games_for_entity($entity->term_id)
        ];
    }
    
    public static function find_entity_by_name($name) {
        $name = sanitize_text_field($name);
        if (empty($name)) {
            return false;
        }
        
        $parent_id = self::get_parent_category_id();
        if (!$parent_id) {
            return false;
        }
        
        $entity = get_term_by('name', $name, 'category');
        if ($entity && $entity->parent === $parent_id) {
            return self::get_entity_by_id($entity->term_id);
        }
        
        return false;
    }
    
    public static function create_entity($name, $website = '') {
        $name = sanitize_text_field($name);
        if (empty($name)) {
            return new WP_Error('empty_name', 'Le nom de l\'entité est requis');
        }
        
        $website = esc_url_raw($website);
        $parent_id = self::get_parent_category_id();
        if (!$parent_id) {
            return new WP_Error('no_parent', 'Catégorie parent "editeurs-developpeurs" introuvable');
        }
        
        $existing = self::find_entity_by_name($name);
        if ($existing) {
            return new WP_Error('entity_exists', 'Une entité avec ce nom existe déjà');
        }
        
        $result = wp_insert_term($name, 'category', [
            'parent' => $parent_id
        ]);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        $entity_id = $result['term_id'];
        
        if (!empty($website)) {
            update_term_meta($entity_id, Sisme_Utils_Games::META_ENTITY_WEBSITE, $website);
        }
        
        return self::get_entity_by_id($entity_id);
    }
    
    public static function update_entity($entity_id, $name, $website = '') {
        $entity_id = intval($entity_id);
        $name = sanitize_text_field($name);
        
        if (!$entity_id || empty($name)) {
            return new WP_Error('invalid_params', 'ID et nom de l\'entité requis');
        }
        
        $entity = self::get_entity_by_id($entity_id);
        if (!$entity) {
            return new WP_Error('entity_not_found', 'Entité introuvable');
        }
        
        if ($entity['name'] !== $name) {
            $existing = self::find_entity_by_name($name);
            if ($existing && $existing['id'] !== $entity_id) {
                return new WP_Error('name_exists', 'Une autre entité avec ce nom existe déjà');
            }
        }
        
        $result = wp_update_term($entity_id, 'category', [
            'name' => $name
        ]);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        $website = esc_url_raw($website);
        if (!empty($website)) {
            update_term_meta($entity_id, Sisme_Utils_Games::META_ENTITY_WEBSITE, $website);
        } else {
            delete_term_meta($entity_id, Sisme_Utils_Games::META_ENTITY_WEBSITE);
        }
        
        return self::get_entity_by_id($entity_id);
    }
    
    public static function delete_entity($entity_id) {
        $entity_id = intval($entity_id);
        if (!$entity_id) {
            return new WP_Error('invalid_id', 'ID d\'entité invalide');
        }
        
        $entity = self::get_entity_by_id($entity_id);
        if (!$entity) {
            return new WP_Error('entity_not_found', 'Entité introuvable');
        }
        
        $games_count = self::count_games_for_entity($entity_id);
        if ($games_count > 0) {
            return new WP_Error('entity_in_use', "Impossible de supprimer : cette entité est utilisée dans {$games_count} jeu(x)");
        }
        
        $result = wp_delete_term($entity_id, 'category');
        if (is_wp_error($result)) {
            return $result;
        }
        
        return true;
    }
    
    public static function count_games_for_entity($entity_id) {
        $entity_id = intval($entity_id);
        if (!$entity_id) {
            return 0;
        }
        
        $all_tags = get_terms([
            'taxonomy' => 'post_tag',
            'hide_empty' => false,
            'fields' => 'ids'
        ]);
        
        if (is_wp_error($all_tags) || empty($all_tags)) {
            return 0;
        }
        
        $games_count = 0;
        foreach ($all_tags as $tag_id) {
            $developers = get_term_meta($tag_id, Sisme_Utils_Games::META_DEVELOPERS, true);
            if (is_array($developers) && in_array($entity_id, $developers)) {
                $games_count++;
                continue;
            }
            
            $publishers = get_term_meta($tag_id, Sisme_Utils_Games::META_PUBLISHERS, true);
            if (is_array($publishers) && in_array($entity_id, $publishers)) {
                $games_count++;
            }
        }
        
        return $games_count;
    }
    
    public static function get_entities_stats() {
        $entities = self::get_all_entities();
        $total_count = count($entities);
        $with_website = 0;
        $total_games = 0;
        
        foreach ($entities as $entity) {
            if (!empty($entity['website'])) {
                $with_website++;
            }
            $total_games += $entity['games_count'];
        }
        
        return [
            'total_entities' => $total_count,
            'with_website' => $with_website,
            'total_games_linked' => $total_games
        ];
    }
}