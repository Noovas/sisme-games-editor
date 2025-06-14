<?php
/**
 * File: /sisme-games-editor/admin/pages/dashboard.php
 * Page Tableau de bord du plugin Sisme Games Editor
 */

// Sécurité : Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="sisme-games-container">
    <div class="sisme-games-header">
        <h1 class="sisme-games-title">
            <span class="dashicons dashicons-games" style="margin-right: 12px; font-size: 28px; vertical-align: middle;"></span>
            Sisme Games Editor
        </h1>
        <p class="sisme-games-subtitle">Tableau de bord - Créez rapidement vos contenus gaming</p>
    </div>
    
    <div class="sisme-games-content">
        <div class="sisme-intro-section">
            <h2 class="sisme-intro-title">Bienvenue dans votre éditeur gaming !</h2>
            <p class="sisme-intro-text">
                Créez facilement et rapidement vos fiches de jeux, articles de patch & news, et tests détaillés pour games.sisme.fr. 
                Chaque type de contenu dispose de son propre template optimisé pour vous faire gagner du temps.
            </p>
        </div>
        
        <div class="sisme-dashboard-cards">
            <a href="<?php echo admin_url('admin.php?page=sisme-games-fiches'); ?>" class="sisme-dashboard-card">
                <div class="sisme-card-icon">
                    <span class="dashicons dashicons-media-document"></span>
                </div>
                <h3 class="sisme-card-title">Fiches de jeu</h3>
                <p class="sisme-card-description">
                    Créez des fiches détaillées pour présenter les jeux : informations principales, captures d'écran, 
                    caractéristiques techniques et description complète.
                </p>
            </a>
            
            <a href="<?php echo admin_url('admin.php?page=sisme-games-patch-news'); ?>" class="sisme-dashboard-card">
                <div class="sisme-card-icon">
                    <span class="dashicons dashicons-megaphone"></span>
                </div>
                <h3 class="sisme-card-title">Patch & News</h3>
                <p class="sisme-card-description">
                    Rédigez rapidement des articles sur les dernières mises à jour, patches et actualités 
                    du monde du gaming avec un template adapté.
                </p>
            </a>
            
            <a href="<?php echo admin_url('admin.php?page=sisme-games-tests'); ?>" class="sisme-dashboard-card">
                <div class="sisme-card-icon">
                    <span class="dashicons dashicons-star-filled"></span>
                </div>
                <h3 class="sisme-card-title">Tests</h3>
                <p class="sisme-card-description">
                    Créez des tests complets avec système de notation, points forts/faibles, 
                    et analyse détaillée pour guider vos lecteurs.
                </p>
            </a>
            
            <a href="<?php echo admin_url('admin.php?page=sisme-games-settings'); ?>" class="sisme-dashboard-card">
                <div class="sisme-card-icon">
                    <span class="dashicons dashicons-admin-settings"></span>
                </div>
                <h3 class="sisme-card-title">Réglages</h3>
                <p class="sisme-card-description">
                    Configurez les paramètres du plugin, personnalisez les templates 
                    et ajustez les options selon vos besoins.
                </p>
            </a>
        </div>
        
        <div style="margin-top: 40px; text-align: center;">
            <a href="<?php echo admin_url('admin.php?page=sisme-games-fiches'); ?>" class="sisme-btn">
                <span class="dashicons dashicons-plus-alt"></span>
                Créer votre premier contenu
            </a>
        </div>
    </div>
</div>