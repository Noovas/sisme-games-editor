<?php
/**
 * File: /sisme-games-editor/includes/user/user-developer/submission/simple-image-cropper.php
 * Version mise à jour avec support multi-ratio
 * 
 * RESPONSABILITÉ:
 * - Support des 3 ratios : cover_horizontal, cover_vertical, screenshot
 * - Upload et crop selon le ratio demandé
 * - Validation par type de ratio
 * 
 * DÉPENDANCES:
 * - WordPress Media API
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Simple_Image_Cropper {
    
    // Configuration des ratios supportés
    private static $ratio_configs = [
        'cover_horizontal' => [
            'width' => 920,
            'height' => 430,
            'max_size' => 5 * 1024 * 1024
        ],
        'cover_vertical' => [
            'width' => 600,
            'height' => 900,
            'max_size' => 5 * 1024 * 1024
        ],
        'screenshot' => [
            'width' => 1920,
            'height' => 1080,
            'max_size' => 8 * 1024 * 1024
        ]
    ];
    
    /**
     * Traiter l'upload avec support multi-ratio
     * @param array $file Données $_FILES['image']
     * @param string $ratio_type Type de ratio
     * @return int|WP_Error attachment_id ou erreur
     */
    public static function process_upload($file, $ratio_type = 'cover_horizontal') {
        // Valider le type de ratio
        if (!array_key_exists($ratio_type, self::$ratio_configs)) {
            return new WP_Error('invalid_ratio_type', 'Type de ratio non supporté: ' . $ratio_type);
        }
        
        $config = self::$ratio_configs[$ratio_type];
        
        // Validation basique
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png'];
        if (!in_array($file['type'], $allowed_types)) {
            return new WP_Error('invalid_file_type', 'Type de fichier non autorisé (JPG, PNG uniquement)');
        }

        // Taille max selon le ratio
        if ($file['size'] > $config['max_size']) {
            $max_mb = round($config['max_size'] / (1024 * 1024), 1);
            return new WP_Error('file_too_large', "Fichier trop volumineux (max {$max_mb}MB pour {$ratio_type})");
        }

        // Upload WordPress standard
        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }

        $upload_overrides = ['test_form' => false];
        $uploaded_file = wp_handle_upload($file, $upload_overrides);

        if (isset($uploaded_file['error'])) {
            return new WP_Error('upload_error', $uploaded_file['error']);
        }

        // Créer l'attachement avec métadonnées du ratio
        $attachment = [
            'post_mime_type' => $uploaded_file['type'],
            'post_title' => sanitize_file_name($uploaded_file['file']),
            'post_content' => '',
            'post_status' => 'inherit'
        ];

        $attachment_id = wp_insert_attachment($attachment, $uploaded_file['file']);

        if (is_wp_error($attachment_id)) {
            return $attachment_id;
        }

        // Générer les métadonnées
        if (!function_exists('wp_generate_attachment_metadata')) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
        }

        $metadata = wp_generate_attachment_metadata($attachment_id, $uploaded_file['file']);
        wp_update_attachment_metadata($attachment_id, $metadata);
        
        // Ajouter les métadonnées du ratio
        update_post_meta($attachment_id, '_sisme_ratio_type', $ratio_type);
        update_post_meta($attachment_id, '_sisme_ratio_config', $config);

        return $attachment_id;
    }

    /**
     * Crop une image avec les dimensions du ratio spécifié
     * @param int $attachment_id ID de l'attachement
     * @param int $x Position X du crop
     * @param int $y Position Y du crop
     * @param int $width Largeur du crop
     * @param int $height Hauteur du crop
     * @param string $ratio_type Type de ratio pour les dimensions finales
     * @return int|WP_Error
     */
    public static function crop_image($attachment_id, $x, $y, $width, $height, $ratio_type = 'cover_horizontal') {
        if (!array_key_exists($ratio_type, self::$ratio_configs)) {
            return new WP_Error('invalid_ratio_type', 'Type de ratio non supporté');
        }
        
        $config = self::$ratio_configs[$ratio_type];
        $image_path = get_attached_file($attachment_id);
        
        if (!$image_path) {
            return new WP_Error('image_not_found', 'Image introuvable');
        }

        // Utiliser WP_Image_Editor
        $image_editor = wp_get_image_editor($image_path);
        
        if (is_wp_error($image_editor)) {
            return $image_editor;
        }

        // Crop
        $crop_result = $image_editor->crop($x, $y, $width, $height);
        if (is_wp_error($crop_result)) {
            return $crop_result;
        }

        // Redimensionner selon la config du ratio
        $resize_result = $image_editor->resize($config['width'], $config['height'], false);
        if (is_wp_error($resize_result)) {
            return $resize_result;
        }

        // Sauvegarder
        $save_result = $image_editor->save();
        if (is_wp_error($save_result)) {
            return $save_result;
        }

        // Mettre à jour l'attachement
        update_attached_file($attachment_id, $save_result['path']);

        // Regénérer les métadonnées
        $metadata = wp_generate_attachment_metadata($attachment_id, $save_result['path']);
        wp_update_attachment_metadata($attachment_id, $metadata);

        return $attachment_id;
    }
    
    /**
     * Obtenir la configuration d'un ratio
     * @param string $ratio_type
     * @return array|null
     */
    public static function get_ratio_config($ratio_type) {
        return self::$ratio_configs[$ratio_type] ?? null;
    }
    
    /**
     * Obtenir tous les ratios disponibles
     * @return array
     */
    public static function get_available_ratios() {
        return array_keys(self::$ratio_configs);
    }
}