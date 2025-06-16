<?php
/**
 * File: /sisme-games-editor/includes/form-handler.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Form_Handler {
    
    public function handle_fiche_step1() {
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
            add_action('admin_notices', function() use ($errors) {
                echo '<div class="notice notice-error"><ul>';
                foreach ($errors as $error) {
                    echo '<li>' . esc_html($error) . '</li>';
                }
                echo '</ul></div>';
            });
            return;
        }
        
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
        
        wp_redirect(admin_url('admin.php?page=sisme-games-fiches&action=create&step=2'));
        exit;
    }
    
    public function handle_fiche_step2() {
        if (!session_id()) {
            session_start();
        }
        
        if (!isset($_SESSION['sisme_form_step1'])) {
            wp_redirect(admin_url('admin.php?page=sisme-games-fiches&action=create'));
            exit;
        }
        
        $step1_data = $_SESSION['sisme_form_step1'];
        $step2_data = $this->sanitize_step2_data($_POST);
        
        $result = $this->create_fiche_post($step1_data, $step2_data);
        
        if ($result['success']) {
            unset($_SESSION['sisme_form_step1']);
            
            add_action('admin_notices', function() use ($result) {
                echo '<div class="notice notice-success"><p>Fiche de jeu créée avec succès ! <a href="' . get_edit_post_link($result['post_id']) . '">Éditer</a> | <a href="' . get_permalink($result['post_id']) . '">Voir</a></p></div>';
            });
            
            wp_redirect(admin_url('admin.php?page=sisme-games-edit-fiche&post_id=' . $result['post_id']));
            exit;
        } else {
            add_action('admin_notices', function() use ($result) {
                echo '<div class="notice notice-error"><p>' . esc_html($result['message']) . '</p></div>';
            });
        }
    }
    
    public function handle_fiche_creation() {
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
        
        if (empty($_POST['platforms'])) {
            $errors[] = 'Veuillez sélectionner au moins une plateforme';
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
        
        $post_data = array(
            'post_title' => sanitize_text_field($_POST['game_title']),
            'post_content' => '',
            'post_excerpt' => sanitize_textarea_field($_POST['game_description']),
            'post_status' => 'draft',
            'post_type' => 'post',
            'post_author' => get_current_user_id()
        );
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            add_action('admin_notices', function() use ($post_id) {
                echo '<div class="notice notice-error"><p>Erreur lors de la création : ' . esc_html($post_id->get_error_message()) . '</p></div>';
            });
            return;
        }
        
        if (!empty($_POST['game_categories'])) {
            wp_set_post_categories($post_id, array_map('intval', $_POST['game_categories']));
        }
        
        if (!empty($_POST['main_tag'])) {
            wp_set_post_tags($post_id, array(intval($_POST['main_tag'])));
        }
        
        if (!empty($_POST['featured_image_id'])) {
            set_post_thumbnail($post_id, intval($_POST['featured_image_id']));
        }
        
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
            '_sisme_news_image_id' => intval($_POST['news_image_id'] ?? 0),
            '_sisme_sections' => isset($_POST['sections']) ? $_POST['sections'] : array()
        );
        
        foreach ($metadata as $key => $value) {
            update_post_meta($post_id, $key, $value);
        }
        
        // Hook News Manager
        $news_manager = new Sisme_News_Manager();
        $game_data = array(
            'game_title' => sanitize_text_field($_POST['game_title']),
            'main_tag' => intval($_POST['main_tag'])
        );
        $news_page_id = $news_manager->create_news_page($post_id, $game_data);
        
        if ($news_page_id) {
            add_action('admin_notices', function() use ($news_page_id) {
                $news_url = get_permalink($news_page_id);
                echo '<div class="notice notice-info"><p>Page news créée automatiquement : <a href="' . esc_url($news_url) . '" target="_blank">Voir la page news</a></p></div>';
            });
        }
        
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success"><p>Fiche créée avec succès !</p></div>';
        });
        
        wp_redirect(admin_url('admin.php?page=sisme-games-edit-fiche&post_id=' . $post_id));
        exit;
    }
    
    public function handle_fiche_update() {
        $post_id = intval($_POST['post_id']);
        
        if (!$post_id) {
            wp_die('ID d\'article manquant');
        }
        
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
        
        if (!empty($_POST['game_categories'])) {
            wp_set_post_categories($post_id, array_map('intval', $_POST['game_categories']));
        }
        
        if (!empty($_POST['main_tag'])) {
            wp_set_post_tags($post_id, array(intval($_POST['main_tag'])));
        }
        
        if (!empty($_POST['featured_image_id'])) {
            set_post_thumbnail($post_id, intval($_POST['featured_image_id']));
        }
        
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
            '_sisme_news_image_id' => intval($_POST['news_image_id'] ?? 0),
            '_sisme_sections' => isset($_POST['sections']) ? $_POST['sections'] : array()
        );
        
        foreach ($metadata as $key => $value) {
            update_post_meta($post_id, $key, $value);
        }
        
        // Hook News Manager - Mise à jour
        $news_manager = new Sisme_News_Manager();
        $updated_game_data = array(
            'game_title' => sanitize_text_field($_POST['game_title']),
            'main_tag' => intval($_POST['main_tag'])
        );
        $news_manager->update_news_page($post_id, $updated_game_data);
        
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success"><p>Fiche mise à jour avec succès !</p></div>';
        });
        
        wp_redirect(admin_url('admin.php?page=sisme-games-edit-fiche&post_id=' . $post_id));
        exit;
    }
    
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
    
    private function sanitize_step2_data($post_data) {
        $sections = array();
        
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
    
    private function create_fiche_post($step1_data, $step2_data) {
        $content = $this->build_fiche_content($step1_data, $step2_data);
        
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
        
        if (!empty($step1_data['game_categories'])) {
            wp_set_post_categories($post_id, $step1_data['game_categories']);
        }
        
        if (!empty($step1_data['main_tag'])) {
            wp_set_post_tags($post_id, array($step1_data['main_tag']));
        }
        
        if (!empty($step1_data['featured_image_id'])) {
            set_post_thumbnail($post_id, $step1_data['featured_image_id']);
        }
        
        $this->save_fiche_metadata($post_id, array_merge($step1_data, $step2_data));
        return array(
            'success' => true,
            'post_id' => $post_id,
            'message' => 'Fiche créée avec succès'
        );
    }
    
    private function build_fiche_content($step1_data, $step2_data) {
        $content = '';
        
        if (!empty($step2_data['sections'])) {
            foreach ($step2_data['sections'] as $section) {
                if (!empty($section['title']) || !empty($section['content'])) {
                    $content .= '<div class="game-section">';
                    
                    if (!empty($section['title'])) {
                        $content .= '<h3>' . esc_html($section['title']) . '</h3>';
                    }
                    
                    if (!empty($section['content'])) {
                        $content .= $section['content'];
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
            '_sisme_news_image_id' => $data['news_image_id'] ?? 0,
            '_sisme_sections' => $data['sections'] ?? array()
        );
        
        foreach ($metadata as $key => $value) {
            if (!empty($value) || in_array($key, ['_sisme_test_image_id', '_sisme_news_image_id', '_sisme_sections'])) {
                update_post_meta($post_id, $key, $value);
            }
        }
    }

    // Page news/patch
    
    public function create_complete_fiche($post_data) {
        return array('success' => true, 'post_id' => 0);
    }

    public function handle_patch_news_creation() {
        // Validation des données
        // Création de l'article
        // Attribution des catégories patch/news
        // Sauvegarde des métadonnées
    }

    public function handle_patch_news_update() {
        // Mise à jour de l'article existant
        // Gestion des sections
        // Actualisation des métadonnées
    }
}