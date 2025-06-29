<?php
/**
 * File: /sisme-games-editor/includes/error-handler/error-500-redirect.php
 * Module de redirection spécialisé pour les erreurs 500
 * 
 * RESPONSABILITÉ:
 * - Intercepter les erreurs 500
 * - Rediriger vers une page personnalisée
 * - Logger les erreurs pour debugging
 * - Gestion gracieuse des pannes
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Error_500_Redirect {
    
    /**
     * Instance singleton
     */
    private static $instance = null;
    
    /**
     * Version du module
     */
    const VERSION = '1.0.0';
    
    /**
     * Options par défaut
     */
    private $default_options = [
        'redirect_enabled' => true,
        'redirect_url' => '',
        'custom_page_id' => 0,
        'log_errors' => true,
        'show_debug_info' => false,
        'bypass_admin' => true,
        'bypass_ajax' => true,
        'email_notifications' => false,
        'admin_email' => '',
        'max_redirects_per_hour' => 10
    ];
    
    /**
     * Compteur de redirections
     */
    private $redirect_count = 0;
    
    /**
     * Obtenir l'instance singleton
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructeur privé
     */
    private function __construct() {
        $this->init();
    }
    
    /**
     * Initialisation du module
     */
    private function init() {
        // Enregistrer le gestionnaire d'erreurs fatal
        register_shutdown_function([$this, 'handle_fatal_error']);
        
        // Hook pour intercepter les erreurs WordPress
        add_action('wp_die_handler', [$this, 'wp_die_handler']);
        
        // Intercepter les erreurs 500 via .htaccess si possible
        add_action('init', [$this, 'setup_htaccess_rules']);
        
        // Hook d'administration
        if (is_admin()) {
            add_action('admin_menu', [$this, 'add_admin_menu']);
            add_action('admin_init', [$this, 'register_settings']);
        }
        
        // Log de démarrage
        if ($this->get_option('log_errors')) {
            error_log('[Sisme Error 500 Redirect] Module initialisé v' . self::VERSION);
        }
    }
    
    /**
     * Gérer les erreurs fatales
     */
    public function handle_fatal_error() {
        $error = error_get_last();
        
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            // Vérifier si on doit bypasser certaines zones
            if ($this->should_bypass_redirect()) {
                return;
            }
            
            // Logger l'erreur
            $this->log_error($error);
            
            // Vérifier le rate limiting
            if (!$this->check_rate_limit()) {
                return;
            }
            
            // Effectuer la redirection
            $this->perform_redirect($error);
        }
    }
    
    /**
     * Gestionnaire pour wp_die
     */
    public function wp_die_handler($handler) {
        return [$this, 'custom_wp_die'];
    }
    
    /**
     * Gestionnaire wp_die personnalisé
     */
    public function custom_wp_die($message, $title = '', $args = []) {
        // Vérifier si c'est une erreur 500
        $status_code = isset($args['response']) ? $args['response'] : 500;
        
        if ($status_code === 500 && $this->get_option('redirect_enabled')) {
            if (!$this->should_bypass_redirect()) {
                $this->log_wp_die_error($message, $title, $args);
                
                if ($this->check_rate_limit()) {
                    $this->perform_redirect([
                        'message' => $message,
                        'title' => $title,
                        'type' => 'wp_die'
                    ]);
                }
            }
        }
        
        // Fallback vers le gestionnaire par défaut
        _default_wp_die_handler($message, $title, $args);
    }
    
    /**
     * Vérifier si on doit bypasser la redirection
     */
    private function should_bypass_redirect() {
        // Bypass pour l'admin
        if ($this->get_option('bypass_admin') && is_admin()) {
            return true;
        }
        
        // Bypass pour AJAX
        if ($this->get_option('bypass_ajax') && wp_doing_ajax()) {
            return true;
        }
        
        // Bypass pour WP-CLI
        if (defined('WP_CLI') && WP_CLI) {
            return true;
        }
        
        // Bypass pour les cron jobs
        if (wp_doing_cron()) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Vérifier le rate limiting
     */
    private function check_rate_limit() {
        $max_redirects = $this->get_option('max_redirects_per_hour');
        
        if ($max_redirects <= 0) {
            return true; // Pas de limite
        }
        
        $transient_key = 'sisme_error_500_redirects_' . md5($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $current_count = get_transient($transient_key) ?: 0;
        
        if ($current_count >= $max_redirects) {
            error_log('[Sisme Error 500 Redirect] Rate limit atteint pour IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
            return false;
        }
        
        // Incrémenter le compteur
        set_transient($transient_key, $current_count + 1, HOUR_IN_SECONDS);
        
        return true;
    }
    
    /**
     * Effectuer la redirection
     */
    private function perform_redirect($error_data) {
        // Nettoyer le buffer de sortie
        if (ob_get_length()) {
            ob_clean();
        }
        
        // Obtenir l'URL de redirection
        $redirect_url = $this->get_redirect_url();
        
        if (empty($redirect_url)) {
            $this->display_fallback_page($error_data);
            return;
        }
        
        // Ajouter des paramètres d'erreur si debug activé
        if ($this->get_option('show_debug_info')) {
            $redirect_url = add_query_arg([
                'error_type' => 'internal_server_error',
                'error_code' => 500,
                'timestamp' => time(),
                'hash' => substr(md5(serialize($error_data)), 0, 8)
            ], $redirect_url);
        }
        
        // Envoyer les headers
        http_response_code(500);
        header('Location: ' . $redirect_url, true, 302);
        
        // Message de fallback au cas où la redirection échoue
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Erreur temporaire</title></head><body>';
        echo '<h1>Service temporairement indisponible</h1>';
        echo '<p>Nous rencontrons actuellement des difficultés techniques. Vous allez être redirigé automatiquement.</p>';
        echo '<script>setTimeout(function(){ window.location.href = "' . esc_js($redirect_url) . '"; }, 3000);</script>';
        echo '</body></html>';
        
        exit;
    }
    
    /**
     * Afficher une page de fallback
     */
    private function display_fallback_page($error_data) {
        http_response_code(500);
        
        echo '<!DOCTYPE html><html><head>';
        echo '<meta charset="UTF-8">';
        echo '<title>Erreur - Service temporairement indisponible</title>';
        echo '<style>
            body { font-family: Arial, sans-serif; margin: 0; padding: 40px; background: #f5f5f5; }
            .error-container { max-width: 600px; margin: 0 auto; background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .error-icon { font-size: 48px; color: #d32f2f; margin-bottom: 20px; }
            h1 { color: #333; margin-bottom: 20px; }
            p { color: #666; line-height: 1.6; margin-bottom: 15px; }
            .back-link { display: inline-block; margin-top: 20px; padding: 10px 20px; background: #1976d2; color: white; text-decoration: none; border-radius: 4px; }
            .back-link:hover { background: #1565c0; }
        </style>';
        echo '</head><body>';
        
        echo '<div class="error-container">';
        echo '<div class="error-icon">⚠️</div>';
        echo '<h1>Service temporairement indisponible</h1>';
        echo '<p>Nous rencontrons actuellement des difficultés techniques. Nos équipes travaillent à résoudre le problème.</p>';
        echo '<p>Veuillez réessayer dans quelques minutes.</p>';
        
        if ($this->get_option('show_debug_info') && current_user_can('manage_options')) {
            echo '<hr style="margin: 30px 0;">';
            echo '<h3>Informations de debug (admin uniquement)</h3>';
            echo '<pre style="background: #f5f5f5; padding: 15px; border-radius: 4px; font-size: 12px; overflow: auto;">';
            print_r($error_data);
            echo '</pre>';
        }
        
        echo '<a href="' . home_url() . '" class="back-link">Retourner à l\'accueil</a>';
        echo '</div>';
        
        echo '</body></html>';
        exit;
    }
    
    /**
     * Obtenir l'URL de redirection
     */
    private function get_redirect_url() {
        // URL personnalisée d'abord
        $custom_url = $this->get_option('redirect_url');
        if (!empty($custom_url)) {
            return $custom_url;
        }
        
        // Page personnalisée ensuite
        $page_id = $this->get_option('custom_page_id');
        if ($page_id > 0) {
            $page_url = get_permalink($page_id);
            if ($page_url) {
                return $page_url;
            }
        }
        
        // Fallback vers l'accueil
        return home_url();
    }
    
    /**
     * Logger une erreur
     */
    private function log_error($error) {
        if (!$this->get_option('log_errors')) {
            return;
        }
        
        $log_message = sprintf(
            '[Sisme Error 500 Redirect] Fatal Error: %s in %s on line %d',
            $error['message'],
            $error['file'],
            $error['line']
        );
        
        error_log($log_message);
        
        // Envoyer une notification email si configuré
        $this->send_error_notification($error);
    }
    
    /**
     * Logger une erreur wp_die
     */
    private function log_wp_die_error($message, $title, $args) {
        if (!$this->get_option('log_errors')) {
            return;
        }
        
        $log_message = sprintf(
            '[Sisme Error 500 Redirect] WP Die Error: %s - %s',
            $title,
            strip_tags($message)
        );
        
        error_log($log_message);
    }
    
    /**
     * Envoyer une notification d'erreur par email
     */
    private function send_error_notification($error) {
        if (!$this->get_option('email_notifications')) {
            return;
        }
        
        $admin_email = $this->get_option('admin_email') ?: get_option('admin_email');
        
        if (!empty($admin_email)) {
            $subject = '[' . get_bloginfo('name') . '] Erreur 500 détectée';
            $message = sprintf(
                "Une erreur 500 a été détectée sur votre site :\n\n" .
                "Erreur: %s\n" .
                "Fichier: %s\n" .
                "Ligne: %d\n" .
                "Heure: %s\n" .
                "URL: %s\n" .
                "User-Agent: %s\n",
                $error['message'] ?? 'Inconnue',
                $error['file'] ?? 'Inconnu',
                $error['line'] ?? 0,
                date('Y-m-d H:i:s'),
                $_SERVER['REQUEST_URI'] ?? 'Inconnue',
                $_SERVER['HTTP_USER_AGENT'] ?? 'Inconnu'
            );
            
            wp_mail($admin_email, $subject, $message);
        }
    }
    
    /**
     * Obtenir une option avec fallback
     */
    private function get_option($key) {
        $options = get_option('sisme_error_500_options', $this->default_options);
        return isset($options[$key]) ? $options[$key] : $this->default_options[$key];
    }
    
    /**
     * Configuration du .htaccess amélioré
     */
    public function setup_htaccess_rules() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $htaccess_file = ABSPATH . '.htaccess';
        
        if (!file_exists($htaccess_file) || !is_writable($htaccess_file)) {
            return;
        }
        
        $content = file_get_contents($htaccess_file);
        
        // Règles personnalisées pour Sisme
        $sisme_rules = "
# BEGIN Sisme Error Handling
<IfModule mod_rewrite.c>
# Forcer PHP à utiliser notre gestionnaire d'erreurs
php_flag display_errors Off
php_flag log_errors On

# Redirection intelligente des erreurs 500
RewriteEngine On
RewriteCond %{ENV:REDIRECT_STATUS} =500
RewriteRule ^(.*)$ /maintenance.php?error=500&from=$1 [R=302,L]

# ErrorDocument en fallback
ErrorDocument 500 /maintenance.php
ErrorDocument 502 /maintenance.php
ErrorDocument 503 /maintenance.php
ErrorDocument 504 /maintenance.php
</IfModule>
# END Sisme Error Handling

";
        
        // Vérifier si nos règles sont déjà présentes
        if (strpos($content, '# BEGIN Sisme Error Handling') === false) {
            // Ajouter nos règles avant WordPress
            $wp_start = strpos($content, '# BEGIN WordPress');
            if ($wp_start !== false) {
                $new_content = substr($content, 0, $wp_start) . $sisme_rules . substr($content, $wp_start);
                file_put_contents($htaccess_file, $new_content);
            }
        }
    }
    
    /**
     * Ajouter le menu d'administration
     */
    public function add_admin_menu() {
        add_submenu_page(
            'tools.php',
            'Gestion Erreurs 500',
            'Erreurs 500',
            'manage_options',
            'sisme-error-500',
            [$this, 'admin_page']
        );
    }
    
    /**
     * Enregistrer les paramètres
     */
    public function register_settings() {
        register_setting('sisme_error_500_options', 'sisme_error_500_options');
    }
    
    /**
     * Page d'administration
     */
    public function admin_page() {
        if (isset($_POST['submit'])) {
            $options = [
                'redirect_enabled' => !empty($_POST['redirect_enabled']),
                'redirect_url' => sanitize_url($_POST['redirect_url'] ?? ''),
                'custom_page_id' => intval($_POST['custom_page_id'] ?? 0),
                'log_errors' => !empty($_POST['log_errors']),
                'show_debug_info' => !empty($_POST['show_debug_info']),
                'bypass_admin' => !empty($_POST['bypass_admin']),
                'bypass_ajax' => !empty($_POST['bypass_ajax']),
                'email_notifications' => !empty($_POST['email_notifications']),
                'admin_email' => sanitize_email($_POST['admin_email'] ?? ''),
                'max_redirects_per_hour' => intval($_POST['max_redirects_per_hour'] ?? 10)
            ];
            
            update_option('sisme_error_500_options', $options);
            echo '<div class="notice notice-success"><p>Paramètres sauvegardés !</p></div>';
        }
        
        $options = get_option('sisme_error_500_options', $this->default_options);
        ?>
        <div class="wrap">
            <h1>🚨 Gestion des Erreurs 500</h1>
            <p>Configuration du système de redirection pour les erreurs critiques.</p>
            
            <form method="post" action="">
                <?php wp_nonce_field('sisme_error_500_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Activer les redirections</th>
                        <td>
                            <label>
                                <input type="checkbox" name="redirect_enabled" <?php checked($options['redirect_enabled']); ?>>
                                Rediriger automatiquement lors d'erreurs 500
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">URL de redirection personnalisée</th>
                        <td>
                            <input type="url" name="redirect_url" value="<?php echo esc_attr($options['redirect_url']); ?>" class="regular-text">
                            <p class="description">Laisser vide pour utiliser la page personnalisée ci-dessous</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Page d'erreur personnalisée</th>
                        <td>
                            <?php 
                            wp_dropdown_pages([
                                'name' => 'custom_page_id',
                                'selected' => $options['custom_page_id'],
                                'show_option_none' => 'Aucune page sélectionnée'
                            ]);
                            ?>
                            <p class="description">Page à afficher en cas d'erreur (si pas d'URL personnalisée)</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Logging des erreurs</th>
                        <td>
                            <label>
                                <input type="checkbox" name="log_errors" <?php checked($options['log_errors']); ?>>
                                Enregistrer les erreurs dans les logs
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Informations de debug</th>
                        <td>
                            <label>
                                <input type="checkbox" name="show_debug_info" <?php checked($options['show_debug_info']); ?>>
                                Afficher les infos de debug aux administrateurs
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Bypasser l'administration</th>
                        <td>
                            <label>
                                <input type="checkbox" name="bypass_admin" <?php checked($options['bypass_admin']); ?>>
                                Ne pas rediriger dans l'admin WordPress
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Bypasser AJAX</th>
                        <td>
                            <label>
                                <input type="checkbox" name="bypass_ajax" <?php checked($options['bypass_ajax']); ?>>
                                Ne pas rediriger les requêtes AJAX
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Notifications email</th>
                        <td>
                            <label>
                                <input type="checkbox" name="email_notifications" <?php checked($options['email_notifications']); ?>>
                                Envoyer un email lors d'erreurs 500
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Email administrateur</th>
                        <td>
                            <input type="email" name="admin_email" value="<?php echo esc_attr($options['admin_email']); ?>" class="regular-text">
                            <p class="description">Laisser vide pour utiliser l'email admin du site</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Limite de redirections</th>
                        <td>
                            <input type="number" name="max_redirects_per_hour" value="<?php echo esc_attr($options['max_redirects_per_hour']); ?>" min="0" max="100">
                            <p class="description">Nombre maximum de redirections par IP par heure (0 = illimité)</p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button('Sauvegarder les paramètres'); ?>
            </form>
            
            <hr>
            
            <h2>🔧 Actions rapides</h2>
            <p>
                <a href="<?php echo admin_url('tools.php?page=sisme-error-500&action=test'); ?>" class="button">Tester la redirection</a>
                <a href="<?php echo admin_url('tools.php?page=sisme-error-500&action=setup_htaccess'); ?>" class="button">Configurer .htaccess</a>
                <a href="<?php echo home_url('/?force_maintenance=1'); ?>" class="button" target="_blank">Voir la page de maintenance</a>
            </p>
            
            <?php if (isset($_GET['action']) && $_GET['action'] === 'test'): ?>
                <div class="notice notice-info">
                    <p><strong>Test de redirection :</strong> Si vous voyez ce message, le module fonctionne correctement !</p>
                </div>
            <?php endif; ?>
            
            <h3>📋 Instructions d'installation</h3>
            <div style="background: #f1f1f1; padding: 15px; border-radius: 5px;">
                <ol>
                    <li>Assurez-vous que le fichier <code>maintenance.php</code> est à la racine de votre site</li>
                    <li>Activez les redirections ci-dessus</li>
                    <li>Cliquez sur "Configurer .htaccess" pour ajouter les règles automatiquement</li>
                    <li>Testez avec le bouton "Tester la redirection"</li>
                </ol>
                
                <p><strong>Règles .htaccess recommandées :</strong></p>
                <pre style="background: white; padding: 10px; border: 1px solid #ddd; font-size: 12px; overflow: auto;">
# BEGIN Sisme Error Handling
&lt;IfModule mod_rewrite.c&gt;
RewriteEngine On

# Gérer les erreurs PHP directement
php_flag display_errors Off
php_flag log_errors On

# Redirection intelligente des erreurs 500
RewriteCond %{ENV:REDIRECT_STATUS} =500
RewriteRule ^(.*)$ /maintenance.php?error=500&amp;from=$1 [R=302,L]

# ErrorDocument en fallback
ErrorDocument 500 /maintenance.php
ErrorDocument 502 /maintenance.php  
ErrorDocument 503 /maintenance.php
ErrorDocument 504 /maintenance.php
&lt;/IfModule&gt;
# END Sisme Error Handling</pre>
            </div>
        </div>
        <?php
    }
}

// Initialiser le module
add_action('plugins_loaded', function() {
    Sisme_Error_500_Redirect::get_instance();
});

/**
 * Fonction utilitaire pour déclencher une redirection manuelle
 */
function sisme_trigger_500_redirect($message = 'Erreur critique détectée') {
    $handler = Sisme_Error_500_Redirect::get_instance();
    $handler->perform_redirect(['message' => $message, 'type' => 'manual']);
}

/**
 * Hook pour wp-config.php - À ajouter en haut du fichier wp-config.php
 * 
 * // Gestionnaire d'erreurs Sisme
 * if (file_exists(__DIR__ . '/wp-content/plugins/sisme-games-editor/includes/error-handler/error-500-redirect.php')) {
 *     require_once __DIR__ . '/wp-content/plugins/sisme-games-editor/includes/error-handler/error-500-redirect.php';
 * }
 */