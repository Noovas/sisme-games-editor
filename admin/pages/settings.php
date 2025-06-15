<?php
/**
 * File: /sisme-games-editor/admin/pages/settings.php
 * Page Réglages du plugin Sisme Games Editor
 */

// Sécurité : Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

// Traitement du formulaire
if (isset($_POST['submit']) && wp_verify_nonce($_POST['sisme_settings_nonce'], 'sisme_settings')) {
    update_option('sisme_override_seo_for_games', isset($_POST['sisme_override_seo_for_games']) ? 1 : 0);
    update_option('sisme_game_content_type', sanitize_text_field($_POST['sisme_game_content_type']));
    update_option('sisme_default_template', sanitize_text_field($_POST['sisme_default_template']));
    update_option('sisme_auto_publish', isset($_POST['sisme_auto_publish']) ? 1 : 0);
    
    echo '<div class="notice notice-success"><p>Réglages sauvegardés avec succès !</p></div>';
}
?>

<div class="wrap">
    <h1>
        <span class="dashicons dashicons-admin-settings" style="margin-right: 12px; font-size: 28px; vertical-align: middle;"></span>
        Réglages - Sisme Games Editor
    </h1>
    <p>Configurez les paramètres et options du plugin selon vos besoins</p>
    
    <form method="post" action="">
        <?php wp_nonce_field('sisme_settings', 'sisme_settings_nonce'); ?>
        
        <!-- Section SEO -->
        <div style="background: white; padding: 20px; margin: 20px 0; border: 1px solid #ddd; border-radius: 5px;">
            <h2 style="margin-top: 0; color: #2c3e50;">
                <span class="dashicons dashicons-search" style="margin-right: 8px;"></span>
                Réglages SEO
            </h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">Gestion SEO des fiches de jeu</th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="checkbox" 
                                       name="sisme_override_seo_for_games" 
                                       value="1" 
                                       <?php checked(get_option('sisme_override_seo_for_games', true)); ?>>
                                <strong>Override complet du SEO pour les fiches de jeu</strong>
                            </label>
                            <p class="description">
                                Si coché, le plugin gère complètement le SEO des fiches de jeu (métadonnées gaming, 
                                données structurées, Open Graph). Désactive les autres plugins SEO pour ces pages uniquement.
                            </p>
                            
                            <?php if (function_exists('aioseo')) : ?>
                                <div style="background: #fff3cd; padding: 12px; border-left: 4px solid #ffc107; margin-top: 10px; border-radius: 3px;">
                                    <strong>⚠️ All in One SEO détecté</strong><br>
                                    Avec cette option activée, All in One SEO sera désactivé uniquement pour les fiches de jeu 
                                    pour éviter les conflits. Vos autres pages continueront d'utiliser All in One SEO normalement.
                                </div>
                            <?php elseif (class_exists('WPSEO_Frontend')) : ?>
                                <div style="background: #fff3cd; padding: 12px; border-left: 4px solid #ffc107; margin-top: 10px; border-radius: 3px;">
                                    <strong>⚠️ Yoast SEO détecté</strong><br>
                                    Avec cette option activée, Yoast SEO sera désactivé uniquement pour les fiches de jeu.
                                </div>
                            <?php elseif (class_exists('RankMath\Frontend\Frontend')) : ?>
                                <div style="background: #fff3cd; padding: 12px; border-left: 4px solid #ffc107; margin-top: 10px; border-radius: 3px;">
                                    <strong>⚠️ RankMath détecté</strong><br>
                                    Avec cette option activée, RankMath sera désactivé uniquement pour les fiches de jeu.
                                </div>
                            <?php else : ?>
                                <div style="background: #d1ecf1; padding: 12px; border-left: 4px solid #17a2b8; margin-top: 10px; border-radius: 3px;">
                                    <strong>ℹ️ Aucun plugin SEO détecté</strong><br>
                                    Le plugin gérera complètement le SEO des fiches de jeu.
                                </div>
                            <?php endif; ?>
                        </fieldset>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">Type de contenu gaming</th>
                    <td>
                        <select name="sisme_game_content_type" class="regular-text">
                            <option value="IndieGame" <?php selected(get_option('sisme_game_content_type', 'IndieGame'), 'IndieGame'); ?>>
                                Jeu Indépendant (IndieGame) - Recommandé
                            </option>
                            <option value="VideoGame" <?php selected(get_option('sisme_game_content_type', 'IndieGame'), 'VideoGame'); ?>>
                                Jeu Vidéo (VideoGame)
                            </option>
                        </select>
                        <p class="description">
                            Type de contenu utilisé dans les données structurées Schema.org. 
                            "IndieGame" est optimisé pour le référencement des jeux indépendants.
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Section Templates -->
        <div style="background: white; padding: 20px; margin: 20px 0; border: 1px solid #ddd; border-radius: 5px;">
            <h2 style="margin-top: 0; color: #2c3e50;">
                <span class="dashicons dashicons-media-document" style="margin-right: 8px;"></span>
                Templates et Affichage
            </h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">Template par défaut</th>
                    <td>
                        <select name="sisme_default_template" class="regular-text">
                            <option value="standard" <?php selected(get_option('sisme_default_template', 'standard'), 'standard'); ?>>
                                Template standard
                            </option>
                            <option value="enhanced" <?php selected(get_option('sisme_default_template', 'standard'), 'enhanced'); ?>>
                                Template enrichi (bientôt disponible)
                            </option>
                        </select>
                        <p class="description">Template utilisé par défaut pour l'affichage des nouvelles fiches</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">Publication automatique</th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="checkbox" 
                                       name="sisme_auto_publish" 
                                       value="1" 
                                       <?php checked(get_option('sisme_auto_publish', false)); ?>>
                                Publier automatiquement les nouvelles fiches
                            </label>
                            <p class="description">
                                Si coché, les fiches créées seront publiées directement. 
                                Sinon, elles seront créées en brouillon pour relecture.
                            </p>
                        </fieldset>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Section Informations -->
        <div style="background: white; padding: 20px; margin: 20px 0; border: 1px solid #ddd; border-radius: 5px;">
            <h2 style="margin-top: 0; color: #2c3e50;">
                <span class="dashicons dashicons-info" style="margin-right: 8px;"></span>
                Informations du Plugin
            </h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">Version</th>
                    <td>
                        <code><?php echo SISME_GAMES_EDITOR_VERSION; ?></code>
                        <p class="description">Version actuelle du plugin Sisme Games Editor</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">Statut</th>
                    <td>
                        <span style="color: #28a745; font-weight: bold;">✓ Actif et fonctionnel</span>
                        <p class="description">Le plugin fonctionne correctement</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">Articles gaming créés</th>
                    <td>
                        <?php
                        $game_posts = new WP_Query(array(
                            'post_type' => 'post',
                            'post_status' => array('publish', 'draft', 'private'),
                            'meta_query' => array(
                                array(
                                    'key' => '_sisme_game_modes',
                                    'compare' => 'EXISTS'
                                )
                            ),
                            'posts_per_page' => -1,
                            'fields' => 'ids'
                        ));
                        $count = $game_posts->found_posts;
                        wp_reset_postdata();
                        ?>
                        <strong><?php echo $count; ?></strong> fiche<?php echo $count > 1 ? 's' : ''; ?> de jeu
                        <p class="description">Nombre total de fiches de jeu créées avec le plugin</p>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Section Configuration -->
        <div style="background: #f8f9fa; padding: 20px; margin: 20px 0; border: 1px solid #dee2e6; border-radius: 5px;">
            <h3 style="margin-top: 0; color: #495057;">
                <span class="dashicons dashicons-admin-tools" style="margin-right: 8px;"></span>
                Configuration recommandée
            </h3>
            <ol style="margin-left: 20px;">
                <li>
                    <strong>Catégories :</strong> Créez des catégories commençant par "jeux-" dans 
                    <a href="<?php echo admin_url('edit-tags.php?taxonomy=category'); ?>">Articles > Catégories</a>
                    <br><small>Exemples : "jeux-action", "jeux-rpg", "jeux-simulation"</small>
                </li>
                <li>
                    <strong>Étiquettes :</strong> Créez des étiquettes avec les noms des jeux pour faciliter le regroupement
                </li>
                <li>
                    <strong>SEO :</strong> Configurez les options SEO ci-dessus selon votre setup existant
                </li>
                <li>
                    <strong>Test :</strong> Créez votre première fiche de jeu pour valider la configuration
                </li>
            </ol>
        </div>
        
        <p class="submit">
            <input type="submit" name="submit" class="button-primary" value="Enregistrer les modifications">
            <a href="<?php echo admin_url('admin.php?page=sisme-games-editor'); ?>" class="button">
                Retour au tableau de bord
            </a>
        </p>
    </form>
    
    <!-- Section Aide -->
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; margin: 30px 0; border-radius: 8px;">
        <h3 style="margin-top: 0; color: white;">
            <span class="dashicons dashicons-sos" style="margin-right: 8px;"></span>
            Besoin d'aide ?
        </h3>
        <p style="margin-bottom: 0;">
            Consultez la documentation du plugin ou contactez le support Sisme Games pour toute question 
            sur la configuration et l'utilisation du plugin.
        </p>
    </div>
</div>