<?php
/**
 * File: /sisme-games-editor/admin/pages/edit-fiche-jeu.php
 * Version Simple - Juste formulaire sections + données jeu + DEBUG
 */

if (!defined('ABSPATH')) {
    exit;
}

// Inclure les modules nécessaires
require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/module-admin-page-wrapper.php';
require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/fiche-creator.php';

// Récupérer l'ID du tag (jeu)
$tag_id = isset($_GET['tag_id']) ? intval($_GET['tag_id']) : 0;
$post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;

// Vérifier que le tag existe
if (!$tag_id) {
    wp_die('Erreur : Aucun jeu spécifié.');
}

$tag_data = get_term($tag_id, 'post_tag');
if (is_wp_error($tag_data) || !$tag_data) {
    wp_die('Erreur : Jeu non trouvé.');
}

// Récupérer les Game Data
$game_data = array(
    'title' => $tag_data->name,
    'description' => get_term_meta($tag_id, 'game_description', true),
    'genres' => get_term_meta($tag_id, 'game_genres', true) ?: array(),
    'modes' => get_term_meta($tag_id, 'game_modes', true) ?: array(),
    'platforms' => get_term_meta($tag_id, 'game_platforms', true) ?: array(),
    'release_date' => get_term_meta($tag_id, 'release_date', true),
);

// DEBUG: Récupérer aussi les sections par défaut du jeu
$default_game_sections = get_term_meta($tag_id, 'game_sections', true) ?: array();

// Mode création ou édition
$is_edit_mode = $post_id > 0;

// Si pas de post_id fourni, vérifier si une fiche existe déjà pour ce jeu
if (!$is_edit_mode) {
    $existing_fiche_id = Sisme_Fiche_Creator::find_existing_fiche($tag_id);
    
    // Si pas trouvé avec la fonction officielle, chercher manuellement
    if (!$existing_fiche_id) {
        $manual_search = get_posts(array(
            'tag_id' => $tag_id,
            'post_type' => 'post',
            'post_status' => array('publish', 'draft', 'private'),
            'posts_per_page' => 1
        ));
        
        if (!empty($manual_search)) {
            $existing_fiche_id = $manual_search[0]->ID;
        }
    }
    
    if ($existing_fiche_id) {
        $post_id = $existing_fiche_id;
        $is_edit_mode = true;
        
        // Mettre à jour l'URL pour inclure post_id ET préserver tag_id
        $new_url = add_query_arg(array(
            'post_id' => $post_id,
            'tag_id' => $tag_id
        ));
        echo '<script>window.history.replaceState({}, "", "' . esc_js($new_url) . '");</script>';
    }
}

$existing_sections = array();

if ($is_edit_mode) {
    $post_data = get_post($post_id);
    if ($post_data) {
        $existing_sections = get_post_meta($post_id, '_sisme_game_sections', true) ?: array();
    }
}

// Si pas de sections dans l'article (création ou article sans sections), 
// utiliser les sections par défaut du jeu
if (empty($existing_sections) && !empty($default_game_sections)) {
    $existing_sections = $default_game_sections;
}

// Messages
$success_message = '';
$error_message = '';

// Traitement du formulaire
if (isset($_POST['action']) && $_POST['action'] === 'create_fiche' && check_admin_referer('sisme_fiche_sections')) {
    
    // Récupérer et nettoyer les sections
    $sections = $_POST['sections'] ?? array();
    $clean_sections = array();
    
    if (is_array($sections)) {
        foreach ($sections as $section) {
            if (!empty($section['title']) || !empty($section['content']) || !empty($section['image_id'])) {
                $clean_sections[] = array(
                    'title' => sanitize_text_field($section['title'] ?? ''),
                    'content' => wp_kses_post($section['content'] ?? ''),
                    'image_id' => intval($section['image_id'] ?? 0)
                );
            }
        }
    }
    
    if ($is_edit_mode) {
        // Mode édition : mettre à jour
        $result = Sisme_Fiche_Creator::update_fiche($post_id, $clean_sections);
        
        if ($result['success']) {
            $success_message = $result['message'];
            // Recharger les sections
            $existing_sections = get_post_meta($post_id, '_sisme_game_sections', true) ?: array();
        } else {
            $error_message = $result['message'];
        }
        
    } else {
        // Mode création : créer nouvelle fiche
        $result = Sisme_Fiche_Creator::create_fiche($tag_id, $clean_sections);
        
        if ($result['success']) {
            $success_message = $result['message'];
            
            // Basculer en mode édition
            $is_edit_mode = true;
            $post_id = $result['post_id'];
            $existing_sections = get_post_meta($post_id, '_sisme_game_sections', true) ?: array();
            
            // Mettre à jour l'URL pour inclure post_id ET préserver tag_id
            $new_url = add_query_arg(array(
                'post_id' => $post_id,
                'tag_id' => $tag_id
            ), remove_query_arg('post_id'));
            echo '<script>window.history.replaceState({}, "", "' . esc_js($new_url) . '");</script>';
            
        } else {
            $error_message = $result['message'];
        }
    }
}

// Configuration de la page
$page = new Sisme_Admin_Page_Wrapper(
    $is_edit_mode ? 'Modifier la fiche : ' . $tag_data->name : 'Créer la fiche : ' . $tag_data->name,
    'Édition des sections de présentation',
    'edit-pages',
    admin_url('admin.php?page=sisme-games-game-data'),
    'Retour à Game Data'
);

$page->render_start();
?>

<!-- Messages -->
<?php if (!empty($success_message)): ?>
    <div class="sisme-notice sisme-notice--success">
        ✅ <?php echo esc_html($success_message); ?>
    </div>
<?php endif; ?>

<?php if (!empty($error_message)): ?>
    <div class="sisme-notice sisme-notice--error">
        ❌ <?php echo esc_html($error_message); ?>
    </div>
<?php endif; ?>

<!-- Formulaire des sections -->
<form method="post">
    <?php wp_nonce_field('sisme_fiche_sections'); ?>
    <input type="hidden" name="action" value="create_fiche">
    
    <h3>Sections de présentation</h3>
    
    <div id="sections-container">
        <?php if (!empty($existing_sections)): ?>
            <?php foreach ($existing_sections as $index => $section): ?>
                <div style="border: 1px solid #ccc; margin: 10px 0; padding: 15px;">
                    <h4>Section <?php echo ($index + 1); ?></h4>
                    
                    <p>
                        <label>Titre de la section:</label><br>
                        <input type="text" name="sections[<?php echo $index; ?>][title]" 
                               value="<?php echo esc_attr($section['title'] ?? ''); ?>" 
                               style="width: 100%;">
                    </p>
                    
                    <p>
                        <label>Contenu:</label><br>
                        <textarea name="sections[<?php echo $index; ?>][content]" 
                                  rows="6" 
                                  style="width: 100%;"><?php echo esc_textarea($section['content'] ?? ''); ?></textarea>
                    </p>
                    
                    <p>
                        <label>ID Image:</label><br>
                        <input type="number" name="sections[<?php echo $index; ?>][image_id]" 
                               value="<?php echo intval($section['image_id'] ?? 0); ?>">
                    </p>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <!-- Section vide par défaut -->
            <div style="border: 1px solid #ccc; margin: 10px 0; padding: 15px;">
                <h4>Section 1</h4>
                
                <p>
                    <label>Titre de la section:</label><br>
                    <input type="text" name="sections[0][title]" value="" style="width: 100%;">
                </p>
                
                <p>
                    <label>Contenu:</label><br>
                    <textarea name="sections[0][content]" rows="6" style="width: 100%;"></textarea>
                </p>
                
                <p>
                    <label>ID Image:</label><br>
                    <input type="number" name="sections[0][image_id]" value="0">
                </p>
            </div>
        <?php endif; ?>
    </div>
    
    <p>
        <input type="submit" value="<?php echo $is_edit_mode ? 'Mettre à jour la fiche' : 'Créer la fiche'; ?>" class="button-primary">
    </p>
</form>

<?php
$page->render_end();
?>