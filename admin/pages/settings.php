<?php
/**
 * File: /sisme-games-editor/admin/pages/settings.php
 * Page Réglages du plugin Sisme Games Editor
 */

// Sécurité : Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="sisme-games-container">
    <div class="sisme-games-header">
        <h1 class="sisme-games-title">
            <span class="dashicons dashicons-admin-settings" style="margin-right: 12px; font-size: 28px; vertical-align: middle;"></span>
            Réglages
        </h1>
        <p class="sisme-games-subtitle">Configurez les paramètres et options du plugin</p>
    </div>
    
    <div class="sisme-games-content">
        <div class="sisme-intro-section">
            <h2 class="sisme-intro-title">Configuration du plugin</h2>
            <p class="sisme-intro-text">
                Personnalisez le comportement du plugin selon vos besoins : templates par défaut, 
                paramètres d'affichage, intégration avec games.sisme.fr et autres options avancées.
            </p>
        </div>
        
        <div style="text-align: center; padding: 60px 20px;">
            <div class="sisme-card-icon" style="margin: 0 auto 20px;">
                <span class="dashicons dashicons-admin-settings"></span>
            </div>
            <h3 style="color: var(--theme-palette-color-6); margin-bottom: 12px;">Paramètres de configuration</h3>
            <p style="color: var(--theme-palette-color-8); margin-bottom: 30px;">
                Cette fonctionnalité sera bientôt disponible ! Les options de configuration 
                seront intégrées dans la prochaine étape de développement.
            </p>
            <a href="<?php echo admin_url('admin.php?page=sisme-games-editor'); ?>" class="sisme-btn-secondary">
                <span class="dashicons dashicons-arrow-left-alt"></span>
                Retour au tableau de bord
            </a>
        </div>
    </div>
</div>