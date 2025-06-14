<?php
/**
 * File: /sisme-games-editor/admin/pages/patch-news.php
 * Page Patch & News du plugin Sisme Games Editor
 */

// Sécurité : Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="sisme-games-container">
    <div class="sisme-games-header">
        <h1 class="sisme-games-title">
            <span class="dashicons dashicons-megaphone" style="margin-right: 12px; font-size: 28px; vertical-align: middle;"></span>
            Patch & News
        </h1>
        <p class="sisme-games-subtitle">Rédigez des articles sur les actualités et mises à jour gaming</p>
    </div>
    
    <div class="sisme-games-content">
        <div class="sisme-intro-section">
            <h2 class="sisme-intro-title">Gestion des Patch & News</h2>
            <p class="sisme-intro-text">
                Créez rapidement des articles pour couvrir les dernières actualités du gaming : patches, mises à jour, 
                annonces, événements spéciaux et toutes les news importantes de l'industrie.
            </p>
        </div>
        
        <div style="text-align: center; padding: 60px 20px;">
            <div class="sisme-card-icon" style="margin: 0 auto 20px;">
                <span class="dashicons dashicons-megaphone"></span>
            </div>
            <h3 style="color: var(--theme-palette-color-6); margin-bottom: 12px;">Création d'articles Patch & News</h3>
            <p style="color: var(--theme-palette-color-8); margin-bottom: 30px;">
                Cette fonctionnalité sera bientôt disponible ! Le template pour créer des articles de patch et news 
                sera intégré dans la prochaine étape de développement.
            </p>
            <a href="<?php echo admin_url('admin.php?page=sisme-games-editor'); ?>" class="sisme-btn-secondary">
                <span class="dashicons dashicons-arrow-left-alt"></span>
                Retour au tableau de bord
            </a>
        </div>
    </div>
</div>