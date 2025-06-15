<?php
/**
 * File: /sisme-games-editor/admin/pages/patch-news.php
 * Page Patch & News - Version fonctionnelle simple
 */

// Sécurité : Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1>Patch & News</h1>
    <p>Rédigez des articles sur les actualités et mises à jour gaming</p>
    
    <div style="text-align: center; padding: 60px 20px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 5px;">
        <span class="dashicons dashicons-megaphone" style="font-size: 48px; color: #0073aa; margin-bottom: 20px;"></span>
        <h2>Création d'articles Patch & News</h2>
        <p style="color: #666; margin-bottom: 30px;">
            Cette fonctionnalité sera bientôt disponible ! Le template pour créer des articles de patch et news 
            sera intégré dans la prochaine étape de développement.
        </p>
        <a href="<?php echo admin_url('admin.php?page=sisme-games-editor'); ?>" class="button button-primary">
            Retour au tableau de bord
        </a>
    </div>
</div>

<?php
/**
 * File: /sisme-games-editor/admin/pages/tests.php
 * Page Tests - Version fonctionnelle simple
 */
?>

<div class="wrap">
    <h1>Tests</h1>
    <p>Créez des tests complets avec système de notation</p>
    
    <div style="text-align: center; padding: 60px 20px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 5px;">
        <span class="dashicons dashicons-star-filled" style="font-size: 48px; color: #0073aa; margin-bottom: 20px;"></span>
        <h2>Création de tests de jeux</h2>
        <p style="color: #666; margin-bottom: 30px;">
            Cette fonctionnalité sera bientôt disponible ! Le template pour créer des tests complets 
            sera intégré dans la prochaine étape de développement.
        </p>
        <a href="<?php echo admin_url('admin.php?page=sisme-games-editor'); ?>" class="button button-primary">
            Retour au tableau de bord
        </a>
    </div>
</div>

<?php
/**
 * File: /sisme-games-editor/admin/pages/settings.php
 * Page Réglages - Version fonctionnelle simple
 */
?>

<div class="wrap">
    <h1>Réglages</h1>
    <p>Configurez les paramètres et options du plugin</p>
    
    <form method="post" action="options.php">
        <?php
        settings_fields('sisme_games_settings');
        do_settings_sections('sisme_games_settings');
        ?>
        
        <table class="form-table">
            <tr>
                <th scope="row">Template par défaut</th>
                <td>
                    <select name="sisme_default_template">
                        <option value="basic">Template de base</option>
                        <option value="advanced">Template avancé</option>
                    </select>
                    <p class="description">Template utilisé par défaut pour les nouvelles fiches</p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">Auto-publication</th>
                <td>
                    <fieldset>
                        <label>
                            <input type="checkbox" name="sisme_auto_publish" value="1">
                            Publier automatiquement les nouvelles fiches
                        </label>
                        <p class="description">Si coché, les fiches seront publiées directement au lieu d'être en brouillon</p>
                    </fieldset>
                </td>
            </tr>
            
            <tr>
                <th scope="row">Catégories par défaut</th>
                <td>
                    <p class="description">
                        <strong>Information :</strong> Les catégories sont automatiquement détectées.<br>
                        • Fiches de jeu : toutes les catégories commençant par "jeux-"<br>
                        • News : catégorie "news"<br>
                        • Tests : catégorie "tests"
                    </p>
                    <p>
                        <a href="<?php echo admin_url('edit-tags.php?taxonomy=category'); ?>" class="button">
                            Gérer les catégories
                        </a>
                    </p>
                </td>
            </tr>
        </table>
        
        <h2>Statistiques</h2>
        <table class="form-table">
            <tr>
                <th scope="row">Version du plugin</th>
                <td><?php echo SISME_GAMES_EDITOR_VERSION; ?></td>
            </tr>
            
            <tr>
                <th scope="row">Statut</th>
                <td>
                    <span style="color: green;">✓ Actif et fonctionnel</span>
                </td>
            </tr>
        </table>
        
        <p class="submit">
            <input type="submit" class="button-primary" value="Enregistrer les modifications">
        </p>
    </form>
    
    <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 20px; border-radius: 5px; margin: 30px 0;">
        <h3 style="margin-top: 0;">🔧 Configuration recommandée</h3>
        <ol>
            <li>Créez les catégories nécessaires :
                <ul>
                    <li><code>jeux-action</code>, <code>jeux-rpg</code>, <code>jeux-simulation</code>, etc. pour les fiches</li>
                    <li><code>news</code> pour les actualités</li>
                    <li><code>tests</code> pour les tests de jeux</li>
                </ul>
            </li>
            <li>Configurez les permaliens pour de belles URLs</li>
            <li>Testez la création d'une première fiche</li>
        </ol>
    </div>
</div>