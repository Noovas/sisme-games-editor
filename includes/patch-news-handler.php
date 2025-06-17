<?php
/**
 * File: /sisme-games-editor/includes/patch-news-handler.php
 * Handler dédié pour la gestion des articles Patch & News
 */

// Sécurité : Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Patch_News_Handler {
    
    /**
     * Gérer la création d'un nouvel article Patch & News
     */
    public function handle_creation() {
        $errors = $this->validate_form_data($_POST);
        
        if (!empty($errors)) {
            $this->show_errors($errors);
            return;
        }
        
        // Sanitiser les données
        $sanitized_data = $this->sanitize_form_data($_POST);
        
        // Créer l'article WordPress
        $post_id = $this->create_wordpress_post($sanitized_data);
        
        if (is_wp_error($post_id)) {
            $this->show_error('Erreur lors de la création : ' . $post_id->get_error_message());
            return;
        }
        
        // Sauvegarder les métadonnées spécifiques
        $this->save_metadata($post_id, $sanitized_data);
        
        // Assigner la catégorie appropriée
        $this->assign_category($post_id, $sanitized_data['article_type']);

        // Assigner l'étiquette du jeu
        if (!empty($sanitized_data['game_tag'])) {
            wp_set_post_tags($post_id, array($sanitized_data['game_tag']));
        }
        
        // Assigner l'image mise en avant
        if (!empty($sanitized_data['featured_image_id'])) {
            set_post_thumbnail($post_id, $sanitized_data['featured_image_id']);
        }
        
        // Message de succès et redirection
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success"><p>Article créé avec succès !</p></div>';
        });
        
        // Rediriger vers l'édition
        wp_redirect(admin_url('admin.php?page=sisme-games-edit-patch-news&post_id=' . $post_id . '&created=1'));
        exit;
    }
    
    /**
     * Gérer la mise à jour d'un article existant
     */
    public function handle_update() {
        $post_id = intval($_POST['post_id']);
        
        if (!$post_id || !get_post($post_id)) {
            $this->show_error('Article introuvable');
            return;
        }
        
        $errors = $this->validate_form_data($_POST);
        
        if (!empty($errors)) {
            $this->show_errors($errors);
            return;
        }
        
        // Sanitiser les données
        $sanitized_data = $this->sanitize_form_data($_POST);
        
        // Mettre à jour l'article WordPress
        $result = $this->update_wordpress_post($post_id, $sanitized_data);
        
        if (is_wp_error($result)) {
            $this->show_error('Erreur lors de la mise à jour : ' . $result->get_error_message());
            return;
        }
        
        // Mettre à jour les métadonnées
        $this->save_metadata($post_id, $sanitized_data);
        
        // Mettre à jour l'image mise en avant
        if (!empty($sanitized_data['featured_image_id'])) {
            set_post_thumbnail($post_id, $sanitized_data['featured_image_id']);
        } else {
            delete_post_thumbnail($post_id);
        }

        // Mettre à jour les images des blocs croisés
        update_post_meta($post_id, '_sisme_fiche_block_image_id', $sanitized_data['fiche_block_image_id']);
        update_post_meta($post_id, '_sisme_news_block_image_id', $sanitized_data['news_block_image_id']);

        // Mettre à jour l'étiquette du jeu
        if (!empty($sanitized_data['game_tag'])) {
            wp_set_post_tags($post_id, array($sanitized_data['game_tag']));
        }
        
        // Message de succès
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success"><p>Article mis à jour avec succès !</p></div>';
        });
        
        // Redirection pour éviter la double soumission
        wp_redirect(admin_url('admin.php?page=sisme-games-edit-patch-news&post_id=' . $post_id));
        exit;
    }
    
    /**
     * Valider les données du formulaire
     */
    private function validate_form_data($data) {
        $errors = array();
        
        $post_id = isset($data['post_id']) ? intval($data['post_id']) : 0;
        
        // Type d'article obligatoire
        if ($post_id === 0) {
            // Mode création : type obligatoire
            if (empty($data['article_type']) || !in_array($data['article_type'], array('patch', 'news'))) {
                $errors[] = 'Le type d\'article est requis (patch ou news)';
            }
        } else {
            // Mode édition : vérifier que l'article existe
            $post = get_post($post_id);
            if (!$post) {
                $errors[] = 'Article introuvable';
            }
            // Le type sera récupéré dans sanitize_form_data(), pas besoin de vérifier ici
        }
        
        // Titre obligatoire
        if (empty($data['article_title'])) {
            $errors[] = 'Le titre de l\'article est requis';
        }
        
        // Description obligatoire
        if (empty($data['article_description'])) {
            $errors[] = 'La description de l\'article est requise';
        }
        
        // Date valide
        if (!empty($data['custom_date']) && !$this->is_valid_date($data['custom_date'])) {
            $errors[] = 'La date de publication n\'est pas valide';
        }

        // Étiquette du jeu obligatoire
        if (empty($data['game_tag'])) {
            $errors[] = 'Veuillez sélectionner le jeu associé à cet article';
        }
        
        return $errors;
    }
    
    /**
     * Sanitiser les données du formulaire
     */
    private function sanitize_form_data($data) {
        // Gérer le type selon le mode
        $article_type = '';
        
        // Récupérer l'ID du post s'il existe
        $post_id = isset($data['post_id']) ? intval($data['post_id']) : 0;
        
        if ($post_id > 0) {
            // Mode édition : récupérer depuis les métadonnées
            $article_type = get_post_meta($post_id, '_sisme_article_type', true);
            
            // Si pas trouvé dans les métadonnées, essayer via les catégories
            if (empty($article_type)) {
                $categories = get_the_category($post_id);
                foreach ($categories as $category) {
                    if ($category->slug === 'patch') {
                        $article_type = 'patch';
                        break;
                    } elseif ($category->slug === 'news') {
                        $article_type = 'news';
                        break;
                    }
                }
            }
        } else {
            // Mode création : prendre depuis le formulaire
            $article_type = sanitize_text_field($data['article_type'] ?? '');
        }

        return array(
            'article_type' => $article_type,
            'game_tag' => intval($data['game_tag'] ?? 0),
            'title' => sanitize_text_field($data['article_title'] ?? ''),
            'description' => $this->sanitize_html_content($data['article_description'] ?? ''),
            'custom_date' => sanitize_text_field($data['custom_date'] ?? ''),
            'featured_image_id' => intval($data['featured_image_id'] ?? 0),
            'fiche_block_image_id' => !empty($data['fiche_block_image_id']) ? intval($data['fiche_block_image_id']) : 0,
            'news_block_image_id' => !empty($data['news_block_image_id']) ? intval($data['news_block_image_id']) : 0,
            'sections' => $this->sanitize_sections($data['sections'] ?? array())
        );
    }
    
    /**
     * Sanitiser les sections avec balises autorisées
     */
    private function sanitize_sections($sections) {
        if (empty($sections) || !is_array($sections)) {
            return array();
        }
        
        $clean_sections = array();
        
        foreach ($sections as $section) {
            if (is_array($section)) {
                $clean_section = array(
                    'title' => $this->sanitize_html_content($section['title'] ?? ''),
                    'content' => $this->sanitize_html_content($section['content'] ?? ''),
                    'image_id' => intval($section['image_id'] ?? 0)
                );
                
                // Garder TOUTES les sections, même vides (pour préserver les indices)
                $clean_sections[] = $clean_section;
            }
        }
        
        return $clean_sections;
    }
    
    /**
     * Sanitiser le contenu HTML avec balises autorisées
     */
    private function sanitize_html_content($content) {
        if (empty($content)) {
            return '';
        }
        
        // Balises autorisées : em, strong, ul, ol, li, p, br
        $allowed_tags = array(
            'em' => array(),
            'strong' => array(),
            'ul' => array(),
            'ol' => array(),
            'li' => array(),
            'p' => array(),
            'br' => array()
        );
        
        return wp_kses(trim($content), $allowed_tags);
    }
    
    /**
     * Créer l'article WordPress
     */
    private function create_wordpress_post($data) {
        // Construire le contenu à partir des sections
        $content = $this->build_content_from_sections($data['sections'], $data['description']);
        
        $post_data = array(
            'post_title' => $data['title'],
            'post_content' => $content,
            'post_excerpt' => $data['description'],
            'post_status' => 'draft', // Créer en brouillon par défaut
            'post_type' => 'post',
            'post_author' => get_current_user_id(),
            'post_date' => !empty($data['custom_date']) ? $data['custom_date'] . ' 12:00:00' : current_time('mysql')
        );
        
        return wp_insert_post($post_data);
    }
    
    /**
     * Mettre à jour l'article WordPress
     */
    private function update_wordpress_post($post_id, $data) {
        // Construire le contenu à partir des sections
        $content = $this->build_content_from_sections($data['sections'], $data['description']);
        
        $post_data = array(
            'ID' => $post_id,
            'post_title' => $data['title'],
            'post_content' => $content,
            'post_excerpt' => $data['description'],
            'post_date' => !empty($data['custom_date']) ? $data['custom_date'] . ' 12:00:00' : get_the_date('Y-m-d H:i:s', $post_id)
        );
        
        return wp_update_post($post_data);
    }
    
    /**
     * Construire le contenu HTML à partir des sections
     */
    private function build_content_from_sections($sections, $description) {
        $content = '';
        
        // Ajouter la description avec titre et structure cohérente
        if (!empty($description)) {
            $content .= '<div class="patch-news-description">' . "\n";
            $content .= '<h2>Description</h2>' . "\n";
            $content .= '<div class="description-content">' . "\n";
            $content .= wpautop($description) . "\n";
            $content .= '</div>' . "\n";
            $content .= '</div>' . "\n\n";
        }
        
        // Ajouter les sections dans un conteneur global
        if (!empty($sections)) {
            // Déterminer le type d'article pour l'icône
            $article_type = 'news'; // Par défaut
            if (isset($_POST['article_type'])) {
                $article_type = $_POST['article_type'];
            } elseif (isset($_POST['post_id'])) {
                $stored_type = get_post_meta($_POST['post_id'], '_sisme_article_type', true);
                if ($stored_type) {
                    $article_type = $stored_type;
                }
            }
            
            $content .= '<div class="patch-news-sections-container type-' . esc_attr($article_type) . '">' . "\n";
            
            // Titre selon le type
            if ($article_type === 'patch') {
                $content .= '<h2>Détails du patch</h2>' . "\n";
            } else {
                $content .= '<h2>Détails de l\'actualité</h2>' . "\n";
            }
            
            $content .= '<div class="sections-content">' . "\n";
            
            // Ajouter chaque section
            foreach ($sections as $section) {
                // Vérifier s'il y a vraiment du contenu à afficher
                $has_content = !empty($section['title']) || !empty($section['content']) || !empty($section['image_id']);
                
                if ($has_content) {
                    $content .= '<div class="patch-news-section">' . "\n";
                    
                    // Titre de section
                    if (!empty($section['title'])) {
                        $content .= '<h3>' . $section['title'] . '</h3>' . "\n";
                    }
                    
                    // Contenu de section
                    if (!empty($section['content'])) {
                        $content .= wpautop($section['content']) . "\n";
                    }
                    
                    // Image de section
                    if (!empty($section['image_id'])) {
                        $image = wp_get_attachment_image($section['image_id'], 'large', false, array('class' => 'patch-news-image'));
                        if ($image) {
                            $content .= '<div class="patch-news-image-wrapper">' . $image . '</div>' . "\n";
                        }
                    }
                    
                    $content .= '</div>' . "\n\n";
                }
            }
            
            $content .= '</div>' . "\n"; // Fin sections-content
            $content .= '</div>' . "\n\n"; // Fin patch-news-sections-container
        }
        
        return $content;
    }
    
    /**
     * Sauvegarder les métadonnées spécifiques
     */
    private function save_metadata($post_id, $data) {
        // DEBUG : voir ce qui arrive
        
        // Type d'article
        update_post_meta($post_id, '_sisme_article_type', $data['article_type']);
        
        // Date personnalisée
        if (!empty($data['custom_date'])) {
            update_post_meta($post_id, '_sisme_custom_date', $data['custom_date']);
        }
        
        // Étiquette du jeu associé
        if (!empty($data['game_tag'])) {
            update_post_meta($post_id, '_sisme_game_tag', $data['game_tag']);
        }
        
        // Sections personnalisées
        update_post_meta($post_id, '_sisme_article_sections', $data['sections']);
        
        // DEBUG : voir ce qui est sauvé
        $saved = get_post_meta($post_id, '_sisme_article_sections', true);
        
        // Marquer comme article Patch & News
        update_post_meta($post_id, '_sisme_is_patch_news', true);

        // Sauvegarder les images des blocs croisés
        update_post_meta($post_id, '_sisme_fiche_block_image_id', $data['fiche_block_image_id']);
        update_post_meta($post_id, '_sisme_news_block_image_id', $data['news_block_image_id']);
    }
    
    /**
     * Assigner la catégorie appropriée
     */
    private function assign_category($post_id, $article_type) {
        // Récupérer ou créer la catégorie
        $category = get_category_by_slug($article_type);
        
        if (!$category) {
            // Créer la catégorie si elle n'existe pas
            $category_name = ($article_type === 'patch') ? 'Patch' : 'News';
            $category_id = wp_insert_category(array(
                'cat_name' => $category_name,
                'category_nicename' => $article_type
            ));
            
            if (!is_wp_error($category_id)) {
                wp_set_post_categories($post_id, array($category_id));
            }
        } else {
            wp_set_post_categories($post_id, array($category->term_id));
        }
    }
    
    /**
     * Vérifier si une date est valide
     */
    private function is_valid_date($date) {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
    
    /**
     * Afficher une erreur
     */
    private function show_error($message) {
        add_action('admin_notices', function() use ($message) {
            echo '<div class="notice notice-error"><p>' . esc_html($message) . '</p></div>';
        });
    }
    
    /**
     * Afficher plusieurs erreurs
     */
    private function show_errors($errors) {
        add_action('admin_notices', function() use ($errors) {
            echo '<div class="notice notice-error"><ul>';
            foreach ($errors as $error) {
                echo '<li>' . esc_html($error) . '</li>';
            }
            echo '</ul></div>';
        });
    }
}