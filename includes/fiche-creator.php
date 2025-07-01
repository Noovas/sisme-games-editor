<?php
/**
 * File: /sisme-games-editor/includes/fiche-creator.php
 * Gestionnaire de création d'articles pour les fiches de jeu
 * Version avec template intégré
 */

if (!defined('ABSPATH')) {
    exit;
}

// Inclure le template
require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/template-fiche.php';

class Sisme_Fiche_Creator {
    
    /**
     * Créer un article de fiche de jeu
     * 
     * @param int $tag_id ID du tag du jeu
     * @param array $sections Sections de la fiche (optionnel)
     * @return array ['success' => bool, 'post_id' => int|null, 'message' => string]
     */
    public static function create_fiche($tag_id, $sections = array()) {
        // Vérifier que le tag existe
        $tag_data = get_term($tag_id, 'post_tag');
        if (!$tag_data || is_wp_error($tag_data)) {
            return array(
                'success' => false,
                'post_id' => null,
                'message' => 'Jeu introuvable'
            );
        }
        
        // Récupérer les genres du jeu depuis Game Data
        $game_genres = get_term_meta($tag_id, Sisme_Utils_Games::META_GENRES, true) ?: array();
        
        // Titre de l'article
        $article_title = $tag_data->name;
        
        // Créer l'article
        $post_data = array(
            'post_title' => $article_title,
            'post_content' => '', // Sera généré par le template
            'post_status' => 'publish',
            'post_type' => 'post',
            'post_author' => get_current_user_id()
        );
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            return array(
                'success' => false,
                'post_id' => null,
                'message' => 'Erreur lors de la création : ' . $post_id->get_error_message()
            );
        }

        // Récupérer la cover principale du jeu depuis Game Data
        $cover_main_id = get_term_meta($tag_id, Sisme_Utils_Games::META_COVER_MAIN, true);

        // Définir l'image à la une automatiquement
        if (!empty($cover_main_id)) {set_post_thumbnail($post_id, $cover_main_id);}

        // Assigner le tag du jeu
        wp_set_post_terms($post_id, array($tag_id), 'post_tag');
        
        // Assigner les catégories genres
        if (!empty($game_genres)) {wp_set_post_categories($post_id, $game_genres);}
        
        // Sauvegarder les sections si fournies
        if (!empty($sections)) {update_post_meta($post_id, '_sisme_game_sections', $sections);}
        
        // Générer le contenu avec le template
        $generated_content = Sisme_Fiche_Template::generate_fiche_content($post_id);
        if (!empty($generated_content)) {
            wp_update_post(array(
                Sisme_Utils_Games::KEY_ID => $post_id,
                'post_content' => $generated_content
            ));
        }
        
        return array(
            'success' => true,
            'post_id' => $post_id,
            'message' => 'Fiche publiée avec succès !'
        );
    }
    
    /**
     * Mettre à jour une fiche existante
     * 
     * @param int $post_id ID de l'article
     * @param array $sections Sections de la fiche
     * @return array ['success' => bool, 'message' => string]
     */
    public static function update_fiche($post_id, $sections = array()) {
        // Vérifier que l'article existe
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'post') {
            return array(
                'success' => false,
                'message' => 'Article introuvable'
            );
        }
        
        // Sauvegarder les sections
        update_post_meta($post_id, '_sisme_game_sections', $sections);
        
        // Régénérer le contenu avec le template
        $generated_content = Sisme_Fiche_Template::generate_fiche_content($post_id);
        if (!empty($generated_content)) {
            wp_update_post(array(
                Sisme_Utils_Games::KEY_ID => $post_id,
                'post_content' => $generated_content
            ));
        }
        
        return array(
            'success' => true,
            'message' => 'Fiche mise à jour avec succès !'
        );
    }
    
    /**
     * Vérifier si une fiche existe déjà pour un jeu
     * 
     * @param int $tag_id ID du tag du jeu
     * @return int|false ID de la fiche ou false si aucune
     */
    public static function find_existing_fiche($tag_id) {
        // Chercher par tag et métadonnée
        $posts = get_posts(array(
            'tag_id' => $tag_id,
            'post_type' => 'post',
            'post_status' => array('publish', 'draft', 'private'),
            'posts_per_page' => 1,
            'meta_query' => array(
                array(
                    'key' => '_sisme_game_sections',
                    'compare' => 'EXISTS'
                )
            )
        ));
        
        if (!empty($posts)) {
            return $posts[0]->ID;
        }
        
        return false;
    }
}