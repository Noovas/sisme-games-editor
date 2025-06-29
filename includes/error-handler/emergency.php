<?php
/**
 * SOLUTION D'URGENCE POUR ERREUR 500
 * Fichier: /emergency.php (à placer à la racine)
 * 
 * Cette page bypass complètement WordPress et affiche les infos de debug
 */

// Couper tous les outputs et erreurs
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Headers pour debug
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🚨 Emergency Debug - Sisme Games</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: monospace; 
            background: #000; 
            color: #0f0; 
            padding: 20px; 
            line-height: 1.4; 
        }
        .container { 
            max-width: 1200px; 
            margin: 0 auto; 
            background: #111; 
            padding: 20px; 
            border: 2px solid #0f0; 
            border-radius: 8px; 
        }
        h1 { color: #f00; margin-bottom: 20px; }
        h2 { color: #ff0; margin: 20px 0 10px 0; }
        .section { 
            background: #222; 
            padding: 15px; 
            margin: 10px 0; 
            border-left: 3px solid #0f0; 
        }
        .error { color: #f00; }
        .success { color: #0f0; }
        .warning { color: #ff0; }
        .code { 
            background: #000; 
            padding: 10px; 
            border: 1px solid #333; 
            overflow: auto; 
            white-space: pre; 
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #333;
            color: #0f0;
            text-decoration: none;
            border: 1px solid #0f0;
            margin: 5px;
        }
        .btn:hover { background: #555; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚨 EMERGENCY DEBUG MODE</h1>
        <p>Diagnostic système pour récupérer du site en erreur 500</p>
        
        <div class="section">
            <h2>📍 Informations de base</h2>
            <div class="code">
Timestamp: <?= date('Y-m-d H:i:s') ?>

URL courante: <?= $_SERVER['REQUEST_URI'] ?? 'Inconnue' ?>
Méthode: <?= $_SERVER['REQUEST_METHOD'] ?? 'Inconnue' ?>
IP Client: <?= $_SERVER['REMOTE_ADDR'] ?? 'Inconnue' ?>
User-Agent: <?= substr($_SERVER['HTTP_USER_AGENT'] ?? 'Inconnu', 0, 100) ?>

PHP Version: <?= PHP_VERSION ?>

Serveur: <?= $_SERVER['SERVER_SOFTWARE'] ?? 'Inconnu' ?>
            </div>
        </div>
        
        <div class="section">
            <h2>🔍 Diagnostic WordPress</h2>
            <div class="code">
<?php
// Test 1: Fichiers WordPress
$wp_files_check = [
    'wp-config.php' => file_exists(__DIR__ . '/wp-config.php'),
    'wp-load.php' => file_exists(__DIR__ . '/wp-load.php'),
    'index.php' => file_exists(__DIR__ . '/index.php'),
    '.htaccess' => file_exists(__DIR__ . '/.htaccess'),
    'wp-content/' => is_dir(__DIR__ . '/wp-content'),
    'maintenance.php' => file_exists(__DIR__ . '/maintenance.php')
];

echo "FICHIERS WORDPRESS:\n";
foreach ($wp_files_check as $file => $exists) {
    echo $file . ': ' . ($exists ? '✓ EXISTS' : '✗ MISSING') . "\n";
}

// Test 2: Permissions
echo "\nPERMISSIONS:\n";
$permission_checks = [
    '/' => is_writable(__DIR__),
    '/wp-content/' => is_writable(__DIR__ . '/wp-content'),
    '/wp-config.php' => (file_exists(__DIR__ . '/wp-config.php') ? is_readable(__DIR__ . '/wp-config.php') : false)
];

foreach ($permission_checks as $path => $writable) {
    echo $path . ': ' . ($writable ? '✓ OK' : '✗ PROBLEM') . "\n";
}

// Test 3: Configuration WordPress (lecture sécurisée)
echo "\nCONFIGURATION:\n";
if (file_exists(__DIR__ . '/wp-config.php')) {
    $wp_config_content = file_get_contents(__DIR__ . '/wp-config.php');
    
    // Extraire certaines infos sans exposer les mots de passe
    preg_match("/define\s*\(\s*['\"]WP_DEBUG['\"],\s*([^)]+)\)/", $wp_config_content, $debug_match);
    preg_match("/define\s*\(\s*['\"]DB_NAME['\"],\s*['\"]([^'\"]+)['\"]/", $wp_config_content, $db_match);
    
    echo "WP_DEBUG: " . (isset($debug_match[1]) ? trim($debug_match[1]) : 'Non défini') . "\n";
    echo "DB_NAME: " . (isset($db_match[1]) ? $db_match[1] : 'Non trouvé') . "\n";
    
} else {
    echo "wp-config.php: ✗ FICHIER MANQUANT\n";
}
?>
            </div>
        </div>
        
        <div class="section">
            <h2>🗂️ Contenu wp-content</h2>
            <div class="code">
<?php
if (is_dir(__DIR__ . '/wp-content')) {
    echo "PLUGINS:\n";
    $plugins_dir = __DIR__ . '/wp-content/plugins';
    if (is_dir($plugins_dir)) {
        $plugins = array_diff(scandir($plugins_dir), ['.', '..']);
        foreach (array_slice($plugins, 0, 10) as $plugin) {
            echo "- $plugin\n";
        }
        if (count($plugins) > 10) {
            echo "... et " . (count($plugins) - 10) . " autres\n";
        }
    }
    
    echo "\nTHEMES:\n";
    $themes_dir = __DIR__ . '/wp-content/themes';
    if (is_dir($themes_dir)) {
        $themes = array_diff(scandir($themes_dir), ['.', '..']);
        foreach ($themes as $theme) {
            echo "- $theme\n";
        }
    }
}
?>
            </div>
        </div>
        
        <div class="section">
            <h2>📋 Logs disponibles</h2>
            <div class="code">
<?php
// Rechercher les fichiers de log
$log_locations = [
    'debug.log' => __DIR__ . '/wp-content/debug.log',
    'error.log' => __DIR__ . '/error.log',
    'access.log' => __DIR__ . '/access.log',
    'debug-sisme.log' => __DIR__ . '/wp-content/debug-sisme.log'
];

foreach ($log_locations as $name => $path) {
    if (file_exists($path)) {
        $size = filesize($path);
        $modified = date('Y-m-d H:i:s', filemtime($path));
        echo "$name: ✓ EXISTS ($size bytes, modifié $modified)\n";
        
        // Afficher les dernières lignes si pas trop gros
        if ($size < 50000) { // Moins de 50KB
            echo "--- Dernières lignes de $name ---\n";
            $lines = file($path);
            $last_lines = array_slice($lines, -10);
            foreach ($last_lines as $line) {
                echo htmlspecialchars(trim($line)) . "\n";
            }
            echo "--- Fin $name ---\n\n";
        } else {
            echo "(Fichier trop volumineux pour affichage)\n\n";
        }
    } else {
        echo "$name: ✗ NOT FOUND\n";
    }
}
?>
            </div>
        </div>
        
        <div class="section">
            <h2>🧪 Tests de récupération</h2>
            <div class="code">
<?php
echo "TESTS DE FONCTIONNEMENT:\n\n";

// Test 1: Inclure wp-config sans exécuter WordPress
echo "1. Test wp-config (inclusion seulement):\n";
try {
    if (file_exists(__DIR__ . '/wp-config.php')) {
        // Capture des constantes sans charger WordPress
        $before_constants = get_defined_constants();
        include_once __DIR__ . '/wp-config.php';
        $after_constants = get_defined_constants();
        
        $wp_constants = array_diff_key($after_constants, $before_constants);
        echo "   ✓ wp-config.php inclus avec succès\n";
        echo "   Constantes WordPress définies: " . count($wp_constants) . "\n";
        
        // Vérifier les constantes importantes
        $important_constants = ['DB_NAME', 'DB_USER', 'DB_HOST', 'WP_DEBUG'];
        foreach ($important_constants as $const) {
            echo "   $const: " . (defined($const) ? '✓' : '✗') . "\n";
        }
    }
} catch (Exception $e) {
    echo "   ✗ ERREUR: " . $e->getMessage() . "\n";
} catch (ParseError $e) {
    echo "   ✗ ERREUR PARSE: " . $e->getMessage() . "\n";
} catch (Error $e) {
    echo "   ✗ ERREUR FATALE: " . $e->getMessage() . "\n";
}

echo "\n2. Test connexion base de données:\n";
try {
    if (defined('DB_NAME') && defined('DB_USER') && defined('DB_HOST')) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASSWORD ?? '');
        echo "   ✓ Connexion DB réussie\n";
        
        // Test simple
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = '" . DB_NAME . "'");
        $result = $stmt->fetch();
        echo "   Tables dans la DB: " . $result['count'] . "\n";
        
    } else {
        echo "   ✗ Constantes DB manquantes\n";
    }
} catch (Exception $e) {
    echo "   ✗ ERREUR DB: " . $e->getMessage() . "\n";
}

echo "\n3. Test inclusion WordPress:\n";
try {
    // Sauvegarder l'état actuel
    $original_error_reporting = error_reporting();
    $original_display_errors = ini_get('display_errors');
    
    // Mode silencieux pour le test
    error_reporting(0);
    ini_set('display_errors', 0);
    
    // Capturer la sortie
    ob_start();
    
    // Tenter de charger WordPress
    if (file_exists(__DIR__ . '/wp-load.php')) {
        include __DIR__ . '/wp-load.php';
        echo "   ✓ wp-load.php inclus\n";
        
        if (function_exists('get_bloginfo')) {
            echo "   ✓ Fonctions WordPress disponibles\n";
            echo "   Site Name: " . get_bloginfo('name') . "\n";
            echo "   WordPress Version: " . get_bloginfo('version') . "\n";
        }
    }
    
    // Récupérer la sortie
    $output = ob_get_clean();
    
    // Restaurer les paramètres d'erreur
    error_reporting($original_error_reporting);
    ini_set('display_errors', $original_display_errors);
    
    if (!empty($output)) {
        echo "   Sortie WordPress:\n" . htmlspecialchars($output) . "\n";
    }
    
} catch (Exception $e) {
    ob_end_clean();
    echo "   ✗ ERREUR WordPress: " . $e->getMessage() . "\n";
} catch (Error $e) {
    ob_end_clean();
    echo "   ✗ ERREUR FATALE WordPress: " . $e->getMessage() . "\n";
}
?>
            </div>
        </div>
        
        <div class="section">
            <h2>🛠️ Actions de récupération</h2>
            <p>Choisissez une action selon le diagnostic ci-dessus:</p>
            
            <a href="?action=create_maintenance" class="btn">Créer page maintenance.php</a>
            <a href="?action=backup_htaccess" class="btn">Sauvegarder .htaccess</a>
            <a href="?action=minimal_htaccess" class="btn">Créer .htaccess minimal</a>
            <a href="?action=check_plugins" class="btn">Désactiver plugins</a>
            <a href="?action=wp_debug_on" class="btn">Activer WP_DEBUG</a>
            
            <?php if (isset($_GET['action'])): ?>
            <div class="code">
<?php
switch ($_GET['action']) {
    case 'create_maintenance':
        echo "CRÉATION MAINTENANCE.PHP:\n";
        // Créer une page de maintenance simple
        $maintenance_content = '<?php
header("HTTP/1.1 503 Service Temporarily Unavailable");
header("Retry-After: 3600");
?>
<!DOCTYPE html>
<html><head><title>Maintenance</title></head>
<body style="font-family:Arial;text-align:center;padding:50px;">
<h1>Site en maintenance</h1>
<p>Nous reviendrons bientôt !</p>
<script>setTimeout(function(){location.reload();}, 60000);</script>
</body></html>';
        
        if (file_put_contents(__DIR__ . '/maintenance.php', $maintenance_content)) {
            echo "✓ maintenance.php créé avec succès\n";
        } else {
            echo "✗ Impossible de créer maintenance.php\n";
        }
        break;
        
    case 'minimal_htaccess':
        echo "CRÉATION .HTACCESS MINIMAL:\n";
        $minimal_htaccess = '# Minimal .htaccess
ErrorDocument 500 /maintenance.php
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase /
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
</IfModule>';
        
        if (file_put_contents(__DIR__ . '/.htaccess', $minimal_htaccess)) {
            echo "✓ .htaccess minimal créé\n";
        } else {
            echo "✗ Impossible de créer .htaccess\n";
        }
        break;
        
    case 'wp_debug_on':
        echo "ACTIVATION WP_DEBUG:\n";
        if (file_exists(__DIR__ . '/wp-config.php')) {
            $config = file_get_contents(__DIR__ . '/wp-config.php');
            
            // Remplacer ou ajouter WP_DEBUG
            if (strpos($config, 'WP_DEBUG') !== false) {
                $config = preg_replace(
                    "/define\s*\(\s*['\"]WP_DEBUG['\"],\s*[^)]+\)/",
                    "define('WP_DEBUG', true)",
                    $config
                );
            } else {
                $config = str_replace(
                    "<?php",
                    "<?php\ndefine('WP_DEBUG', true);\ndefine('WP_DEBUG_LOG', true);\n",
                    $config
                );
            }
            
            if (file_put_contents(__DIR__ . '/wp-config.php', $config)) {
                echo "✓ WP_DEBUG activé\n";
            } else {
                echo "✗ Impossible de modifier wp-config.php\n";
            }
        }
        break;
}
?>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="section">
            <h2>🔄 Navigation</h2>
            <a href="emergency.php" class="btn">Actualiser diagnostic</a>
            <a href="/" class="btn">Tenter retour site</a>
            <a href="maintenance.php" class="btn">Voir maintenance</a>
            <a href="/wp-admin/" class="btn">Tenter admin WP</a>
        </div>
    </div>
</body>
</html>