<?php
/**
 * Page de maintenance Sisme Games - Design authentique
 * Couleurs et style cohérents avec les fiches de jeux
 */

// Détecter le type d'erreur et le mode
$error_code = $_GET['error'] ?? $_GET['code'] ?? $_SERVER['REDIRECT_STATUS'] ?? '503';
$is_emergency = isset($_GET['emergency']);
$show_debug = isset($_GET['debug']);
$is_500_error = ($error_code == '500') || $is_emergency;

// Headers appropriés
if ($is_500_error) {
    header('HTTP/1.1 500 Internal Server Error');
} else {
    header('HTTP/1.1 503 Service Temporarily Unavailable');
}
header('Retry-After: 1800');
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $is_emergency ? 'Erreur Technique - Sisme Games' : 'Maintenance - Sisme Games' ?></title>
    <meta name="description" content="Sisme Games - Découverte de jeux indépendants. Site temporairement indisponible pour maintenance.">
    
    <style>
        /* Variables couleurs Sisme Games authentiques */
        :root {
            --sisme-color-primary: #A1B78D;
            --sisme-color-primary-dark: #557A46;
            --sisme-color-secondary: #D4A373;
            --sisme-color-background: #ECF0F1;
            --sisme-color-surface: #ECF0F1;
            --sisme-color-muted: #9caca0;
            --sisme-color-text: white;
            --sisme-color-text-secondary: #aeb3b8;
            
            --sisme-color-error: #e53e3e;
            --sisme-color-warning: #f6ad55;
            --sisme-color-success: var(--sisme-color-primary);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #242627;
            color: white;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 20px;
            line-height: 1.6;
        }
        
        /* Container principal - MÊME STYLE que les fiches de jeu */
        .sisme-maintenance-container {
            margin: 48px auto;
            max-width: 800px;
            padding: 40px 48px;
            background-color: rgba(0, 0, 0, 0.3);
            border-radius: 12px;
            position: relative;
            overflow: hidden;
            
            /* Bordure subtile */
            border: 1px solid rgba(161, 183, 141, 0.2);
            
            /* Ombres douces et professionnelles */
            box-shadow: 
                0 8px 32px rgba(44, 62, 80, 0.08),
                0 2px 8px rgba(44, 62, 80, 0.04);
            
            /* Animation d'apparition */
            animation: sismeFadeInUp 0.6s ease-out;
            
            /* Transition smooth */
            transition: all 0.3s ease;
            text-align: center;
        }
        
        @keyframes sismeFadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Animation subtile de points en haut - IDENTIQUE aux fiches */
        .sisme-maintenance-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: <?= $is_emergency ? 'rgba(229, 62, 62, 0.3)' : 'var(--sisme-color-text-secondary)' ?>;
            border-radius: 2px;
        }
        
        /* Dégradé qui se déplace - IDENTIQUE aux fiches */
        .sisme-maintenance-container::after {
            content: '';
            position: absolute;
            top: 0;
            left: -150px;
            width: 150px;
            height: 3px;
            background: linear-gradient(90deg, 
                transparent 0%, 
                <?= $is_emergency ? 'rgba(229, 62, 62, 0.8)' : 'rgba(85, 122, 70, 0.95)' ?> 50%, 
                transparent 100%);
            border-radius: 2px;
            animation: sismeDotsFlow 4s ease-out infinite;
        }
        
        @keyframes sismeDotsFlow {
            0% { left: -150px; opacity: 0; }
            5% { opacity: 1; }
            95% { opacity: 1; }
            100% { left: calc(100% + 150px); opacity: 0; }
        }
        
        /* Header avec logo */
        .sisme-header {
            margin-bottom: 32px;
        }
        
        .sisme-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            margin-bottom: 16px;
        }
        
        .sisme-logo-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, var(--sisme-color-primary), var(--sisme-color-primary-dark));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            animation: logoFloat 3s ease-in-out infinite;
        }
        
        @keyframes logoFloat {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-4px); }
        }
        
        .sisme-logo-text {
            font-size: 2rem;
            font-weight: 700;
            color: var(--sisme-color-primary-dark);
            letter-spacing: -0.01em;
        }
        
        .sisme-tagline {
            font-size: 1rem;
            color: var(--sisme-color-text-secondary);
            font-weight: 500;
        }
        
        /* Titre principal */
        .sisme-main-title {
            font-size: 2.25rem;
            font-weight: 700;
            margin-bottom: 16px;
        }
        
        .sisme-subtitle {
            font-size: 1.1rem;
            color: var(--sisme-color-text-secondary);
            margin-bottom: 32px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        
        /* Section statut - Style cohérent */
        .sisme-status-section {
            background: <?= $is_emergency ? 'rgba(229, 62, 62, 0.08)' : 'rgba(0, 0, 0, 0.3)' ?>;
            border: 1px solid <?= $is_emergency ? 'rgba(229, 62, 62, 0.2)' : 'rgba(161, 183, 141, 0.2)' ?>;
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 32px;
            text-align: left;
            position: relative;
        }
        
        .sisme-status-section::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: <?= $is_emergency ? 'var(--sisme-color-error)' : 'var(--sisme-color-primary)' ?>;
            border-radius: 0 2px 2px 0;
        }
        
        .sisme-status-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }
        
        .sisme-status-icon {
            font-size: 1.5rem;
            animation: pulse 2s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.8; transform: scale(1.05); }
        }
        
        .sisme-status-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: <?= $is_emergency ? 'var(--sisme-color-error)' : 'var(--sisme-color-primary-dark)' ?>;
            margin: 0;
        }
        
        .sisme-status-message {
            color: var(--sisme-color-text-secondary);
            line-height: 1.6;
            margin: 0;
        }
        
        /* Fonctionnalités en cours */
        .sisme-features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }
        
        .sisme-feature-card {
            background-color: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(161, 183, 141, 0.15);
            border-radius: 8px;
            padding: 20px;
            text-align: left;
            transition: all 0.3s ease;
        }
        
        .sisme-feature-icon {
            font-size: 1.5rem;
            margin-bottom: 12px;
            display: block;
        }
        
        .sisme-feature-title {
            font-size: 1rem;
            font-weight: 600;
            color: var(--sisme-color-primary-dark);
            margin-bottom: 8px;
        }
        
        .sisme-feature-description {
            font-size: 0.9rem;
            color: var(--sisme-color-text-secondary);
            line-height: 1.5;
        }
        
        /* Barre de progression */
        .sisme-progress-section {
            margin-bottom: 32px;
        }
        
        .sisme-progress-label {
            font-size: 0.9rem;
            color: var(--sisme-color-text-secondary);
            margin-bottom: 8px;
            text-align: left;
        }
        
        .sisme-progress-bar {
            width: 100%;
            height: 8px;
            background: rgba(161, 183, 141, 0.2);
            border-radius: 4px;
            overflow: hidden;
            position: relative;
        }
        
        .sisme-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--sisme-color-primary), var(--sisme-color-primary-dark));
            border-radius: 4px;
            width: <?= $is_emergency ? '75%' : '60%' ?>;
            animation: progressPulse 2s ease-in-out infinite;
        }
        
        @keyframes progressPulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }
        
        /* Boutons d'action - Style Sisme */
        .sisme-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            justify-content: center;
            margin-bottom: 32px;
        }
        
        .sisme-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: var(--sisme-color-primary);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.95rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .sisme-btn:hover {
            transform: translateY(-1px) !important;
            background: var(--sisme-color-primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(161, 183, 141, 0.3);
        }
        
        .sisme-btn-secondary {
            background: transparent;
            border: 2px solid var(--sisme-color-primary);
            color: var(--sisme-color-primary-dark);
        }
        
        .sisme-btn-secondary:hover {
            background: var(--sisme-color-primary);
            color: white;
        }
        
        /* Section debug */
        .sisme-debug-section {
            background: var(--sisme-color-text);
            color: var(--sisme-color-background);
            border-radius: 8px;
            padding: 20px;
            margin-top: 32px;
            text-align: left;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            font-size: 0.85rem;
        }
        
        .sisme-debug-title {
            color: var(--sisme-color-secondary);
            font-weight: 600;
            margin-bottom: 12px;
            font-family: inherit;
        }
        
        .sisme-debug-line {
            margin: 6px 0;
            color: var(--sisme-color-muted);
        }
        
        .sisme-debug-line strong {
            color: var(--sisme-color-background);
        }
        
        /* Footer */
        .sisme-footer {
            text-align: center;
            color: var(--sisme-color-text-secondary);
            font-size: 0.9rem;
            line-height: 1.6;
        }
        
        .sisme-footer-highlight {
            color: var(--sisme-color-primary-dark);
            font-weight: 600;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sisme-maintenance-container {
                padding: 32px 24px;
                margin: 20px;
            }
            
            .sisme-logo-text {
                font-size: 1.75rem;
            }
            
            .sisme-main-title {
                font-size: 1.875rem;
            }
            
            .sisme-features {
                grid-template-columns: 1fr;
            }
            
            .sisme-actions {
                flex-direction: column;
                align-items: center;
            }
            
            .sisme-btn {
                width: 100%;
                max-width: 280px;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="sisme-maintenance-container">
        <!-- Header avec logo -->
        <div class="sisme-header">
            <div class="sisme-logo">
                <div class="sisme-logo-icon">🎮</div>
                <div class="sisme-logo-text">Sisme Games</div>
            </div>
            <div class="sisme-tagline">Découverte de Jeux Indépendants</div>
        </div>
        
        <!-- Titre principal -->
        <h1 class="sisme-main-title">
            <?= $is_emergency ? 'Erreur Technique' : 'Maintenance en cours' ?>
        </h1>
        
        <p class="sisme-subtitle">
            <?= $is_emergency ? 
                'Une erreur technique temporaire affecte notre plateforme de découverte de jeux indépendants.' : 
                'Nous améliorons votre expérience de découverte des meilleurs jeux indépendants avec de nouvelles fonctionnalités.' 
            ?>
        </p>
        
        <!-- Section statut -->
        <div class="sisme-status-section">
            <div class="sisme-status-header">
                <span class="sisme-status-icon"><?= $is_emergency ? '🛠️' : '🔧' ?></span>
                <h3 class="sisme-status-title">
                    <?= $is_emergency ? 'Résolution en cours' : 'Améliorations en cours' ?>
                </h3>
            </div>
            <p class="sisme-status-message">
                <?= $is_emergency ? 
                    'Nos développeurs ont été automatiquement alertés et travaillent à résoudre ce problème. Nous nous excusons pour la gêne occasionnée.' : 
                    'Notre équipe déploie de nouvelles fonctionnalités pour enrichir votre découverte de pépites indépendantes. Le site sera de retour très prochainement avec des améliorations passionnantes !' 
                ?>
            </p>
        </div>
        
        <?php if (!$is_emergency): ?>
        <!-- Fonctionnalités en cours -->
        <div class="sisme-features">
            <div class="sisme-feature-card">
                <span class="sisme-feature-icon">🎯</span>
                <h4 class="sisme-feature-title">Découverte Améliorée</h4>
                <p class="sisme-feature-description">Algorithmes optimisés pour vous recommander les meilleures pépites indépendantes</p>
            </div>
            <div class="sisme-feature-card">
                <span class="sisme-feature-icon">⚡</span>
                <h4 class="sisme-feature-title">Performance Optimisée</h4>
                <p class="sisme-feature-description">Navigation plus fluide et chargement accéléré des fiches de jeux</p>
            </div>
            <div class="sisme-feature-card">
                <span class="sisme-feature-icon">🎨</span>
                <h4 class="sisme-feature-title">Interface Modernisée</h4>
                <p class="sisme-feature-description">Design repensé pour une expérience utilisateur encore plus agréable</p>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Barre de progression -->
        <div class="sisme-progress-section">
            <div class="sisme-progress-label">
                <?= $is_emergency ? 'Progression de la résolution' : 'Avancement des améliorations' ?>
            </div>
            <div class="sisme-progress-bar">
                <div class="sisme-progress-fill"></div>
            </div>
        </div>
        
        <!-- Actions -->
        <div class="sisme-actions">
            <a href="/" class="sisme-btn">
                🔄 Vérifier le retour
            </a>
            <a href="mailto:contact@sisme-games.com" class="sisme-btn sisme-btn-secondary">
                ✉️ Nous contacter
            </a>
        </div>
        
        <?php if ($show_debug): ?>
        <!-- Section debug -->
        <div class="sisme-debug-section">
            <h3 class="sisme-debug-title">Informations de Debug</h3>
            <div class="sisme-debug-line"><strong>Mode:</strong> <?= $is_emergency ? 'Emergency' : 'Maintenance' ?></div>
            <div class="sisme-debug-line"><strong>Code erreur:</strong> <?= htmlspecialchars($error_code) ?></div>
            <div class="sisme-debug-line"><strong>Timestamp:</strong> <?= date('Y-m-d H:i:s') ?></div>
            <div class="sisme-debug-line"><strong>URL:</strong> <?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'N/A') ?></div>
            <div class="sisme-debug-line"><strong>Source:</strong> <?= htmlspecialchars($_GET['source'] ?? $_GET['from'] ?? 'Direct') ?></div>
            <div class="sisme-debug-line"><strong>User-Agent:</strong> <?= htmlspecialchars(substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 60)) ?>...</div>
        </div>
        <?php endif; ?>
        
        <!-- Footer -->
        <div class="sisme-footer">
            <p>
                <strong>Temps estimé :</strong> 
                <span class="sisme-footer-highlight">
                    <?= $is_emergency ? '15-45 minutes' : '1-3 heures maximum' ?>
                </span>
            </p>
            <p>Merci pour votre patience et votre passion pour les jeux indépendants ! 🎮</p>
        </div>
    </div>
    
    <script>
        // Configuration
        const isEmergency = <?= $is_emergency ? 'true' : 'false' ?>;
        const showDebug = <?= $show_debug ? 'true' : 'false' ?>;
        
        // Auto-refresh intelligent
        let refreshInterval = isEmergency ? 60000 : 120000; // 1min urgence, 2min maintenance
        let checkCount = 0;
        const maxChecks = 30; // Arrêter après ~1h
        
        function checkSiteStatus() {
            if (showDebug) return; // Pas d'auto-refresh en debug
            
            checkCount++;
            if (checkCount > maxChecks) {
                console.log('Sisme Games: Auto-refresh arrêté');
                return;
            }
            
            fetch('/', { 
                method: 'HEAD', 
                cache: 'no-cache',
                redirect: 'manual'
            })
            .then(response => {
                if (response.status >= 200 && response.status < 400) {
                    console.log('Sisme Games: Site de retour ! 🎮');
                    window.location.href = '/';
                }
            })
            .catch(() => {
                console.log(`Sisme Games: Vérification ${checkCount}/${maxChecks}`);
            });
        }
        
        // Démarrer les vérifications
        const statusChecker = setInterval(checkSiteStatus, refreshInterval);
        
        // Raccourcis développeur
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'd') {
                e.preventDefault();
                const url = new URL(window.location);
                url.searchParams.set('debug', '1');
                window.location.href = url.toString();
            }
        });
        
        // Console info
        console.log(`🎮 Sisme Games - Maintenance
Mode: ${isEmergency ? 'Emergency' : 'Maintenance'}
Découverte de jeux indépendants`);
    </script>
</body>
</html>