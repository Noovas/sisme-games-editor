<?php
/**
 * File: /sisme-games-editor/includes/form-handler.php
 */

// Sécurité : Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Form_Handler {
    
    /**
     * Traiter l'étape 1 : Métadonnées de base
     */
    public function handle_fiche_step1() {
        // Validation des données requises
        $errors = array();
        
        if (empty($_POST['game_title'])) {
            $errors[] = 'Le titre du jeu est requis';
        }
        
        if (empty($_POST['game_description'])) {
            $errors[] = 'La description du jeu est requise';
        }
        
        if (empty($_POST['game_categories'])) {
            $errors[] = 'Veuillez sélectionner au moins une catégorie';
        }
        
        if (empty($_POST['game_modes'])) {
            $errors[] = 'Veuillez sélectionner au moins un mode de jeu';
        }
        
        if (empty($_POST['main_tag'])) {
            $errors[] = 'Veuillez sélectionner une étiquette principale';
        }

        if (empty($_POST['platforms'])) {
            $errors[] = 'Veuillez sélectionner au moins une plateforme';
        }
        
        if (!empty($errors)) {
            // Afficher les erreurs et revenir au formulaire
            add_action('admin_notices', function() use ($errors) {
                echo '<div class="notice notice-error"><ul>';
                foreach ($errors as $error) {
                    echo '<li>' . esc_html($error) . '</li>';
                }
                echo '</ul></div>';
            });
            return;
        }
        
        // Sauvegarder les données dans la session
        if (!session_id()) {
            session_start();
        }
        
        $_SESSION['sisme_form_step1'] = array(
            'game_title' => sanitize_text_field($_POST['game_title']),
            'game_description' => sanitize_textarea_field($_POST['game_description']),
            'game_categories' => array_map('intval', $_POST['game_categories']),
            'game_modes' => array_map('sanitize_text_field', $_POST['game_modes']),
            'platforms' => array_map('sanitize_text_field', $_POST['platforms']),
            'main_tag' => intval($_POST['main_tag']),
            'release_date' => sanitize_text_field($_POST['release_date']),
            'developers' => $this->sanitize_developers($_POST['developers'] ?? array()),
            'editors' => $this->sanitize_editors($_POST['editors'] ?? array()),
            'trailer_url' => esc_url_raw($_POST['trailer_url']),
            'steam_url' => esc_url_raw($_POST['steam_url']),
            'epic_url' => esc_url_raw($_POST['epic_url']),
            'gog_url' => esc_url_raw($_POST['gog_url']),
            'featured_image_id' => intval($_POST['featured_image_id']),
            'test_image_id' => intval($_POST['test_image_id']),
            'news_image_id' => intval($_POST['news_image_id'])
        );
        
        // Rediriger vers l'étape 2
        wp_redirect(admin_url('admin.php?page=sisme-games-fiches&action=create&step=2'));
        exit;
    }
    
    /**
     * Traiter l'étape 2 : Création du contenu
     */
    public function handle_fiche_step2() {
        if (!session_id()) {
            session_start();
        }
        
        if (!isset($_SESSION['sisme_form_step1'])) {
            wp_redirect(admin_url('admin.php?page=sisme-games-fiches&action=create'));
            exit;
        }
        
        // Récupérer les données des deux étapes
        $step1_data = $_SESSION['sisme_form_step1'];
        $step2_data = $this->sanitize_step2_data($_POST);
        
        // Créer l'article
        $result = $this->create_fiche_post($step1_data, $step2_data);
        
        if ($result['success']) {
            // Nettoyer la session
            unset($_SESSION['sisme_form_step1']);
            
            // Message de succès
            add_action('admin_notices', function() use ($result) {
                echo '<div class="notice notice-success"><p>Fiche de jeu créée avec succès ! <a href="' . get_edit_post_link($result['post_id']) . '">Éditer</a> | <a href="' . get_permalink($result['post_id']) . '">Voir</a></p></div>';
            });
            
            // Rediriger vers la visualisation
            wp_redirect(admin_url('admin.php?page=sisme-games-edit-fiche&post_id=' . $result['post_id']));
            exit;
        } else {
            add_action('admin_notices', function() use ($result) {
                echo '<div class="notice notice-error"><p>' . esc_html($result['message']) . '</p></div>';
            });
        }
    }
    
    /**
     * Traiter la mise à jour d'une fiche depuis l'éditeur interne
     */
    public function handle_fiche_update() {
        $post_id = intval($_POST['post_id']);
        
        if (!$post_id) {
            wp_die('ID d\'article manquant');
        }
        
        // Validation des données requises
        $errors = array();
        
        if (empty($_POST['game_title'])) {
            $errors[] = 'Le titre du jeu est requis';
        }
        
        if (empty($_POST['game_description'])) {
            $errors[] = 'La description du jeu est requise';
        }
        
        if (!empty($errors)) {
            add_action('admin_notices', function() use ($errors) {
                echo '<div class="notice notice-error"><ul>';
                foreach ($errors as $error) {
                    echo '<li>' . esc_html($error) . '</li>';
                }
                echo '</ul></div>';
            });
            return;
        }
        
        // Mettre à jour l'article
        $post_data = array(
            'ID' => $post_id,
            'post_title' => sanitize_text_field($_POST['game_title']),
            'post_excerpt' => sanitize_textarea_field($_POST['game_description'])
        );
        
        $result = wp_update_post($post_data);
        
        if (is_wp_error($result)) {
            add_action('admin_notices', function() use ($result) {
                echo '<div class="notice notice-error"><p>Erreur lors de la mise à jour : ' . esc_html($result->get_error_message()) . '</p></div>';
            });
            return;
        }
        
        // Mettre à jour les catégories
        if (!empty($_POST['game_categories'])) {
            wp_set_post_categories($post_id, array_map('intval', $_POST['game_categories']));
        }
        
        // Mettre à jour l'étiquette principale
        if (!empty($_POST['main_tag'])) {
            wp_set_post_tags($post_id, array(intval($_POST['main_tag'])));
        }
        
        // Mettre à jour l'image mise en avant
        if (!empty($_POST['featured_image_id'])) {
            set_post_thumbnail($post_id, intval($_POST['featured_image_id']));
        }
        
        // Mettre à jour les métadonnées
        $metadata = array(
            '_sisme_game_modes' => isset($_POST['game_modes']) ? array_map('sanitize_text_field', $_POST['game_modes']) : array(),
            '_sisme_platforms' => isset($_POST['platforms']) ? array_map('sanitize_text_field', $_POST['platforms']) : array(),
            '_sisme_release_date' => sanitize_text_field($_POST['release_date']),
            '_sisme_developers' => $this->sanitize_developers($_POST['developers'] ?? array()),
            '_sisme_editors' => $this->sanitize_editors($_POST['editors'] ?? array()),
            '_sisme_trailer_url' => esc_url_raw($_POST['trailer_url']),
            '_sisme_steam_url' => esc_url_raw($_POST['steam_url']),
            '_sisme_epic_url' => esc_url_raw($_POST['epic_url']),
            '_sisme_gog_url' => esc_url_raw($_POST['gog_url']),
            '_sisme_main_tag' => intval($_POST['main_tag']),
            '_sisme_test_image_id' => intval($_POST['test_image_id'] ?? 0),
            '_sisme_news_image_id' => intval($_POST['news_image_id'] ?? 0)
        );
        
        foreach ($metadata as $key => $value) {
            update_post_meta($post_id, $key, $value);
        }
        
        // Message de succès
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success"><p>Fiche mise à jour avec succès !</p></div>';
        });
        
        // Rediriger vers la visualisation
        wp_redirect(admin_url('admin.php?page=sisme-games-edit-fiche&post_id=' . $post_id));
        exit;
    }
    
    /**
     * Nettoyer les données des développeurs
     */
    private function sanitize_developers($developers) {
        if (empty($developers) || !is_array($developers)) {
            return array();
        }
        
        $clean_developers = array();
        foreach ($developers as $dev) {
            if (is_array($dev) && isset($dev['name']) && !empty(trim($dev['name']))) {
                $clean_developers[] = array(
                    'name' => sanitize_text_field($dev['name']),
                    'url' => !empty($dev['url']) ? esc_url_raw($dev['url']) : ''
                );
            }
        }
        
        return $clean_developers;
    }
    
    /**
     * Nettoyer les données des éditeurs
     */
    private function sanitize_editors($editors) {
        if (empty($editors) || !is_array($editors)) {
            return array();
        }
        
        $clean_editors = array();
        foreach ($editors as $editor) {
            if (is_array($editor) && isset($editor['name']) && !empty(trim($editor['name']))) {
                $clean_editors[] = array(
                    'name' => sanitize_text_field($editor['name']),
                    'url' => !empty($editor['url']) ? esc_url_raw($editor['url']) : ''
                );
            }
        }
        
        return $clean_editors;
    }
    
    /**
     * Nettoyer les données de l'étape 2
     */
    private function sanitize_step2_data($post_data) {
        $sections = array();
        
        // Traiter les sections de contenu
        if (isset($post_data['sections'])) {
            foreach ($post_data['sections'] as $section_data) {
                if (is_array($section_data)) {
                    $sections[] = array(
                        'title' => sanitize_text_field($section_data['title']),
                        'content' => wp_kses_post($section_data['content']),
                        'image_id' => intval($section_data['image_id'])
                    );
                }
            }
        }
        
        return array('sections' => $sections);
    }
    
    /**
     * Créer l'article de fiche de jeu
     */
    private function create_fiche_post($step1_data, $step2_data) {
        // Construire le contenu de l'article (UNIQUEMENT les sections étape 2)
        $content = $this->build_fiche_content($step1_data, $step2_data);
        
        // Créer l'article WordPress
        $post_data = array(
            'post_title' => $step1_data['game_title'],
            'post_content' => $content,
            'post_excerpt' => wp_trim_words($step1_data['game_description'], 55),
            'post_status' => 'draft',
            'post_type' => 'post',
            'post_author' => get_current_user_id()
        );
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            return array(
                'success' => false,
                'message' => 'Erreur lors de la création de l\'article : ' . $post_id->get_error_message()
            );
        }
        
        // Assigner les catégories
        if (!empty($step1_data['game_categories'])) {
            wp_set_post_categories($post_id, $step1_data['game_categories']);
        }
        
        // Assigner l'étiquette principale
        if (!empty($step1_data['main_tag'])) {
            wp_set_post_tags($post_id, array($step1_data['main_tag']));
        }
        
        // Assigner l'image mise en avant
        if (!empty($step1_data['featured_image_id'])) {
            set_post_thumbnail($post_id, $step1_data['featured_image_id']);
        }
        
        // Sauvegarder les métadonnées personnalisées
        $this->save_fiche_metadata($post_id, $step1_data);
        
        return array(
            'success' => true,
            'post_id' => $post_id,
            'message' => 'Fiche créée avec succès'
        );
    }
    
    /**
     * Construire le contenu HTML de la fiche (UNIQUEMENT les sections étape 2)
     */
    private function build_fiche_content($step1_data, $step2_data) {
        $content = '';
        
        // UNIQUEMENT les sections de contenu personnalisées (étape 2)
        if (!empty($step2_data['sections'])) {
            foreach ($step2_data['sections'] as $section) {
                if (!empty($section['title']) || !empty($section['content'])) {
                    $content .= '<div class="game-section">';
                    
                    if (!empty($section['title'])) {
                        $content .= '<h3>' . esc_html($section['title']) . '</h3>';
                    }
                    
                    if (!empty($section['content'])) {
                        $content .= $section['content']; // Déjà nettoyé avec wp_kses_post
                    }
                    
                    if (!empty($section['image_id'])) {
                        $image = wp_get_attachment_image($section['image_id'], 'large');
                        if ($image) {
                            $content .= '<div class="game-section-image">' . $image . '</div>';
                        }
                    }
                    
                    $content .= '</div>';
                }
            }
        }
        
        return $content;
    }
    
    /**
     * Formater les entreprises (développeurs/éditeurs) avec liens
     */
    private function format_companies($companies) {
        if (empty($companies)) {
            return '';
        }
        
        // Si c'est une chaîne simple (ancien format), la retourner telle quelle
        if (is_string($companies)) {
            return esc_html($companies);
        }
        
        // Si ce n'est pas un array, retourner vide
        if (!is_array($companies)) {
            return '';
        }
        
        $formatted = array();
        foreach ($companies as $company) {
            // Vérifier que c'est bien un array avec les bonnes clés
            if (is_array($company) && !empty($company['name'])) {
                if (!empty($company['url'])) {
                    $formatted[] = '<a href="' . esc_url($company['url']) . '" target="_blank">' . esc_html($company['name']) . '</a>';
                } else {
                    $formatted[] = esc_html($company['name']);
                }
            }
            // Si c'est juste une chaîne (cas de fallback)
            elseif (is_string($company) && !empty($company)) {
                $formatted[] = esc_html($company);
            }
        }
        
        return implode(', ', $formatted);
    }
    
    /**
     * Sauvegarder les métadonnées de la fiche
     */
    private function save_fiche_metadata($post_id, $data) {
        $metadata = array(
            '_sisme_game_modes' => $data['game_modes'],
            '_sisme_platforms' => $data['platforms'],
            '_sisme_main_tag' => $data['main_tag'],
            '_sisme_release_date' => $data['release_date'],
            '_sisme_developers' => $data['developers'],
            '_sisme_editors' => $data['editors'],
            '_sisme_trailer_url' => $data['trailer_url'],
            '_sisme_steam_url' => $data['steam_url'],
            '_sisme_epic_url' => $data['epic_url'],
            '_sisme_gog_url' => $data['gog_url'],
            '_sisme_test_image_id' => $data['test_image_id'] ?? 0,
            '_sisme_news_image_id' => $data['news_image_id'] ?? 0
        );
        
        foreach ($metadata as $key => $value) {
            if (!empty($value)) {
                update_post_meta($post_id, $key, $value);
            }
        }
    }
    
    /**
     * Créer une fiche complète (pour AJAX)
     */
    public function create_complete_fiche($post_data) {
        // À implémenter selon les besoins futurs
        return array('success' => true, 'post_id' => 0);
    }
}