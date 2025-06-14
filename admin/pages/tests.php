<?php
/**
 * File: /sisme-games-editor/admin/pages/tests.php
 * Page Tests du plugin Sisme Games Editor
 */

// Sécurité : Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="sisme-games-container">
    <div class="sisme-games-header">
        <h1 class="sisme-games-title">
            <span class="dashicons dashicons-star-filled" style="margin-right: 12px; font-size: 28px; vertical-align: middle;"></span>
            Tests
        </h1>
        <p class="sisme-games-subtitle">Créez des tests complets avec système de notation</p>
    </div>
    
    <div class="sisme-games-content">
        <div class="sisme-intro-section">
            <h2 class="sisme-intro-title">Gestion des Tests</h2>
            <p class="sisme-intro-text">
                Rédigez des tests détaillés et professionnels avec système de notation, analyse des points forts/faibles, 
                captures d'écran et verdict final pour guider vos lecteurs dans leurs choix.
            </p>
        </div>
        
        <div style="text-align: center; padding: 60px 20px;">
            <div class="sisme-card-icon" style="margin: 0 auto 20px;">
                <span class="dashicons dashicons-star-filled"></span>
            </div>
            <h3 style="color: var(--theme-palette-color-6); margin-bottom: 12px;">Création de tests de jeux</h3>
            <p style="color: var(--theme-palette-color-8); margin-bottom: 30px;">
                Cette fonctionnalité sera bientôt disponible ! Le template pour créer des tests complets 
                sera intégré dans la prochaine étape de développement.
            </p>
            <a href="<?php echo admin_url('admin.php?page=sisme-games-editor'); ?>" class="sisme-btn-secondary">
                <span class="dashicons dashicons-arrow-left-alt"></span>
                Retour au tableau de bord
            </a>
        </div>
    </div>
</div>