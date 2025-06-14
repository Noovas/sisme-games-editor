<?php
/**
 * File: /sisme-games-editor/includes/ajax-handler.php
 * Gestionnaire des requêtes AJAX pour le plugin Sisme Games Editor
 */

// Sécurité : Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe pour gérer les requêtes AJAX
 */
class Sisme_Games_Ajax_Handler {
    
    public function __construct() {
        add_action('wp_ajax_sisme_duplicate_post', array($this, 'duplicate_post'));
        add_action('wp_ajax_sisme_get_posts_stats', array($this, 'get_posts_stats'));
        add_action('wp_ajax_sisme_quick_edit_post', array($this, 'quick_edit_post'));
    }
    
    /**
     * Dupliquer un article
     */
    public function duplicate_post() {
        // Vérification de sécurité
        if (!current_user_can('edit_posts')) {
            wp_die('Permission insuffisante');
        }
        
        // Vérification du nonce (à implémenter dans la prochaine version)
        // if (!wp_verify_nonce($_POST['nonce'], 'sisme_games_nonce')) {
        //     wp_die('Nonce invalide');
        // }
        
        $post_id = intval($_POST['post_id']);
        $original_post = get_post($post_id);
        
        if (!$original_post) {
            wp_send_json_error('Article introuvable');
            return;
        }
        
        // Créer une copie de l'article
        $new_post_data = array(
            'post_title' => $original_post->post_title . ' (Copie)',
            'post_content' => $original_post->post_content,
            'post_excerpt' => $original_post->post_excerpt,
            'post_status' => 'draft',
            'post_type' => $original_post->post_type,
            'post_author' => get_current_user_id(),
            'post_parent' => $original_post->post_parent,
            'menu_order' => $original_post->menu_order
        );
        
        $new_post_id = wp_insert_post($new_post_data);
        
        if (is_wp_error($new_post_id)) {
            wp_send_json_error('Erreur lors de la duplication');
            return;
        }
        
        // Copier les métadonnées
        $post_meta = get_post_meta($post_id);
        foreach ($post_meta as $key => $values) {
            // Éviter de copier certaines métadonnées
            if (in_array($key, array('_edit_lock', '_edit_last'))) {
                continue;
            }
            
            foreach ($values as $value) {
                add_post_meta($new_post_id, $key, maybe_unserialize($value));
            }
        }
        
        // Copier les taxonomies (catégories, étiquettes, etc.)
        $taxonomies = get_object_taxonomies($original_post->post_type);
        foreach ($taxonomies as $taxonomy) {
            $terms = wp_get_post_terms($post_id, $taxonomy);
            $term_ids = array();
            foreach ($terms as $term) {
                $term_ids[] = $term->term_id;
            }
            wp_set_post_terms($new_post_id, $term_ids, $taxonomy);
        }
        
        wp_send_json_success(array(
            'message' => 'Article dupliqué avec succès',
            'new_post_id' => $new_post_id,
            'edit_link' => get_edit_post_link($new_post_id)
        ));
    }
    
    /**
     * Récupérer les statistiques des articles
     */
    public function get_posts_stats() {
        if (!current_user_can('edit_posts')) {
            wp_die('Permission insuffisante');
        }
        
        // Utiliser les fonctions utilitaires pour les statistiques
        $stats = sisme_get_category_stats();
        
        wp_send_json_success(array(
            'fiches' => $stats['fiches'],
            'news' => $stats['news'],
            'tests' => $stats['tests'],
            'total' => $stats['total']
        ));
    }
    
    /**
     * Édition rapide d'un article
     */
    public function quick_edit_post() {
        if (!current_user_can('edit_posts')) {
            wp_die('Permission insuffisante');
        }
        
        $post_id = intval($_POST['post_id']);
        $field = sanitize_text_field($_POST['field']);
        $value = sanitize_text_field($_POST['value']);
        
        $allowed_fields = array('post_title', 'post_status', 'post_excerpt');
        
        if (!in_array($field, $allowed_fields)) {
            wp_send_json_error('Champ non autorisé');
            return;
        }
        
        $update_data = array(
            'ID' => $post_id,
            $field => $value
        );
        
        $result = wp_update_post($update_data);
        
        if (is_wp_error($result)) {
            wp_send_json_error('Erreur lors de la mise à jour');
            return;
        }
        
        wp_send_json_success(array(
            'message' => 'Article mis à jour avec succès',
            'field' => $field,
            'value' => $value
        ));
    }
}

// Initialiser le gestionnaire AJAX
new Sisme_Games_Ajax_Handler();