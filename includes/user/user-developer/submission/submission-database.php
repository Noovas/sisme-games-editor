<?php
/**
 * File: /sisme-games-editor/includes/user/user-developer/submission/submission-database.php
 * Gestionnaire de base de données pour les soumissions de jeux
 * 
 * RESPONSABILITÉ:
 * - Création/mise à jour table submissions
 * - CRUD complet des soumissions
 * - Requêtes optimisées avec cache
 * - Validation données avant insertion
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Submission_Database {
    
    // Table et version
    const TABLE_NAME = 'sisme_game_submissions';
    const DB_VERSION = '1.0';
    const DB_VERSION_OPTION = 'sisme_submissions_db_version';
    
    // États disponibles
    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING = 'pending';
    const STATUS_PUBLISHED = 'published';
    const STATUS_REJECTED = 'rejected';
    const STATUS_REVISION = 'revision';
    
    // Limites
    const MAX_DRAFTS_PER_USER = 3;
    const MAX_SUBMISSIONS_PER_DAY = 1;
    
    /**
     * Créer ou mettre à jour la table lors de l'activation
     */
    public static function create_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            
            user_id BIGINT UNSIGNED NOT NULL,
            published_tag_id BIGINT UNSIGNED NULL,
            
            game_data LONGTEXT NOT NULL,
            
            status ENUM('draft','pending','published','rejected','revision') DEFAULT 'draft',
            submission_version INT DEFAULT 1,
            
            admin_user_id BIGINT UNSIGNED NULL,
            admin_notes TEXT NULL,
            admin_action_date DATETIME NULL,
            
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            submitted_at DATETIME NULL,
            published_at DATETIME NULL,
            
            INDEX idx_user_status (user_id, status),
            INDEX idx_status_submitted (status, submitted_at),
            INDEX idx_published_tag (published_tag_id),
            INDEX idx_created_at (created_at),
            
            FOREIGN KEY (user_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE,
            FOREIGN KEY (admin_user_id) REFERENCES {$wpdb->users}(ID) ON DELETE SET NULL
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Sauvegarder la version
        update_option(self::DB_VERSION_OPTION, self::DB_VERSION);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Sisme Submissions] Table créée/mise à jour : ' . $table_name);
        }
    }
    
    /**
     * Vérifier si la table existe et est à jour
     */
    public static function table_exists() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        $current_version = get_option(self::DB_VERSION_OPTION, '0.0');
        
        // Vérifier existence
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        // Vérifier version
        $version_ok = version_compare($current_version, self::DB_VERSION, '>=');
        
        return $table_exists && $version_ok;
    }
    
    /**
     * Créer une nouvelle soumission
     */
    public static function create_submission($user_id, $game_data = []) {
        global $wpdb;
        
        // Validation utilisateur
        if (!self::validate_user_can_submit($user_id)) {
            return new WP_Error('user_invalid', 'Utilisateur non autorisé à soumettre');
        }
        
        // Vérifier limites
        if (!self::check_submission_limits($user_id)) {
            return new WP_Error('limit_exceeded', 'Limite de soumissions atteinte');
        }
        
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        
        // Données par défaut
        $default_data = [
            'game_name' => '',
            'description' => '',
            'genres' => [],
            'platforms' => [],
            'modes' => [],
            'developers' => [],
            'publishers' => [],
            'release_date' => '',
            'covers' => [
                'horizontal' => '',
                'vertical' => ''
            ],
            'screenshots' => '',
            'trailer_link' => '',
            'external_links' => [],
            'metadata' => [
                'completion_percentage' => 0,
                'last_step_completed' => 'basic',
                'validation_errors' => []
            ]
        ];
        
        $merged_data = array_merge($default_data, $game_data);
        
        $result = $wpdb->insert(
            $table_name,
            [
                'user_id' => $user_id,
                'game_data' => wp_json_encode($merged_data),
                'status' => self::STATUS_DRAFT,
                'submission_version' => 1
            ],
            ['%d', '%s', '%s', '%d']
        );
        
        if ($result === false) {
            return new WP_Error('db_error', 'Erreur lors de la création de la soumission');
        }
        
        $submission_id = $wpdb->insert_id;
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[Sisme Submissions] Nouvelle soumission créée : ID $submission_id, User $user_id");
        }
        
        return $submission_id;
    }
    
    /**
     * Mettre à jour une soumission
     */
    public static function update_submission($submission_id, $game_data, $user_id = null) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        
        // Récupérer soumission existante
        $submission = self::get_submission($submission_id);
        if (!$submission) {
            return new WP_Error('not_found', 'Soumission introuvable');
        }
        
        // Vérifier permissions
        if ($user_id && $submission->user_id != $user_id) {
            return new WP_Error('permission_denied', 'Permission refusée');
        }
        
        // Vérifier que c'est modifiable
        if (!in_array($submission->status, [self::STATUS_DRAFT, self::STATUS_REVISION])) {
            return new WP_Error('not_editable', 'Soumission non modifiable');
        }
        
        // Merger avec données existantes
        $current_data = json_decode($submission->game_data, true) ?: [];
        $updated_data = array_merge($current_data, $game_data);
        
        // Calculer pourcentage de completion
        $updated_data['metadata']['completion_percentage'] = self::calculate_completion_percentage($updated_data);
        
        $result = $wpdb->update(
            $table_name,
            [
                'game_data' => wp_json_encode($updated_data)
            ],
            ['id' => $submission_id],
            ['%s'],
            ['%d']
        );
        
        if ($result === false) {
            return new WP_Error('db_error', 'Erreur lors de la mise à jour');
        }
        
        return true;
    }
    
    /**
     * Récupérer une soumission par ID
     */
    public static function get_submission($submission_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        
        $submission = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $submission_id)
        );
        
        if ($submission) {
            $submission->game_data_decoded = json_decode($submission->game_data, true);
        }
        
        return $submission;
    }
    
    /**
     * Récupérer les soumissions d'un utilisateur
     */
    public static function get_user_submissions($user_id, $status = null, $limit = 20) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        
        $where_clause = "WHERE user_id = %d";
        $params = [$user_id];
        
        if ($status) {
            $where_clause .= " AND status = %s";
            $params[] = $status;
        }
        
        $query = "SELECT * FROM $table_name $where_clause ORDER BY updated_at DESC LIMIT %d";
        $params[] = $limit;
        
        $submissions = $wpdb->get_results($wpdb->prepare($query, $params));
        
        // Decoder game_data pour chaque soumission
        foreach ($submissions as $submission) {
            $submission->game_data_decoded = json_decode($submission->game_data, true);
        }
        
        return $submissions;
    }
    
    /**
     * Récupérer les soumissions pour l'admin
     */
    public static function get_submissions_for_admin($status = null, $limit = 50, $offset = 0) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        
        $where_clause = "WHERE 1=1";
        $params = [];
        
        if ($status) {
            $where_clause .= " AND status = %s";
            $params[] = $status;
        }
        
        $query = "
            SELECT s.*, u.display_name as user_name, u.user_email as user_email
            FROM $table_name s
            LEFT JOIN {$wpdb->users} u ON s.user_id = u.ID
            $where_clause
            ORDER BY 
                CASE WHEN s.status = 'pending' THEN 1 ELSE 2 END,
                s.submitted_at DESC,
                s.updated_at DESC
            LIMIT %d OFFSET %d
        ";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $submissions = $wpdb->get_results($wpdb->prepare($query, $params));
        
        foreach ($submissions as $submission) {
            $submission->game_data_decoded = json_decode($submission->game_data, true);
        }
        
        return $submissions;
    }
    
    /**
     * Compter les soumissions par statut
     */
    public static function get_submissions_count($status = null) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        
        if ($status) {
            return $wpdb->get_var(
                $wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE status = %s", $status)
            );
        }
        
        return $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    }
    
    /**
     * Supprimer une soumission
     */
    public static function delete_submission($submission_id, $user_id = null) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        
        // Vérifier existence et permissions
        $submission = self::get_submission($submission_id);
        if (!$submission) {
            return new WP_Error('not_found', 'Soumission introuvable');
        }
        
        if ($user_id && $submission->user_id != $user_id) {
            return new WP_Error('permission_denied', 'Permission refusée');
        }
        
        // Seuls les drafts et rejected peuvent être supprimés
        if (!in_array($submission->status, [self::STATUS_DRAFT, self::STATUS_REJECTED])) {
            return new WP_Error('not_deletable', 'Soumission non supprimable');
        }
        
        $result = $wpdb->delete(
            $table_name,
            ['id' => $submission_id],
            ['%d']
        );
        
        if ($result === false) {
            return new WP_Error('db_error', 'Erreur lors de la suppression');
        }
        
        return true;
    }
    
    /**
     * Valider qu'un utilisateur peut soumettre
     */
    private static function validate_user_can_submit($user_id) {
        // Vérifier que l'utilisateur existe
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }
        
        // Vérifier qu'il est développeur approuvé
        $developer_status = get_user_meta($user_id, Sisme_Utils_Users::META_DEVELOPER_STATUS, true);
        
        return $developer_status === Sisme_Utils_Users::DEVELOPER_STATUS_APPROVED;
    }
    
    /**
     * Vérifier les limites de soumission
     */
    private static function check_submission_limits($user_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        
        // Vérifier limite drafts simultanés
        $draft_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND status = %s",
            $user_id, self::STATUS_DRAFT
        ));
        
        if ($draft_count >= self::MAX_DRAFTS_PER_USER) {
            return false;
        }
        
        // Vérifier limite soumissions par jour
        $today_submissions = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND DATE(created_at) = CURDATE()",
            $user_id
        ));
        
        if ($today_submissions >= self::MAX_SUBMISSIONS_PER_DAY) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Calculer le pourcentage de completion
     */
    private static function calculate_completion_percentage($game_data) {
        $required_fields = [
            'game_name' => 20,
            'description' => 15,
            'genres' => 10,
            'covers.horizontal' => 15,
            'covers.vertical' => 15,
            'screenshots' => 10,
            'platforms' => 10,
            'developers' => 5
        ];
        
        $completion = 0;
        
        foreach ($required_fields as $field => $weight) {
            if (strpos($field, '.') !== false) {
                // Champ imbriqué
                $parts = explode('.', $field);
                $value = $game_data[$parts[0]][$parts[1]] ?? '';
            } else {
                $value = $game_data[$field] ?? '';
            }
            
            if (!empty($value)) {
                if (is_array($value) && count($value) > 0) {
                    $completion += $weight;
                } elseif (!is_array($value) && trim($value) !== '') {
                    $completion += $weight;
                }
            }
        }
        
        return min(100, $completion);
    }
    
    /**
     * Nettoyer les anciennes soumissions (cron job)
     */
    public static function cleanup_old_submissions() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        
        // Supprimer les drafts de plus de 30 jours
        $wpdb->query("
            DELETE FROM $table_name 
            WHERE status = 'draft' 
            AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        
        // Supprimer les rejected de plus de 90 jours
        $wpdb->query("
            DELETE FROM $table_name 
            WHERE status = 'rejected' 
            AND admin_action_date < DATE_SUB(NOW(), INTERVAL 90 DAY)
        ");
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Sisme Submissions] Nettoyage automatique effectué');
        }
    }
}