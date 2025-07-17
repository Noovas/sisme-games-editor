<?php
/**
 * File: /sisme-games-editor/includes/user/user-developer/submission/simple-image-cropper.php
 * Version minimaliste du crop d'image - Juste pour tester
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Simple_Image_Cropper {
    
    /**
     * Traiter l'upload simple d'une image
     */
    public static function process_upload($file) {
        // Validation basique
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png'];
        if (!in_array($file['type'], $allowed_types)) {
            return new WP_Error('invalid_file_type', 'Type de fichier non autorisé (JPG, PNG uniquement)');
        }

        // Taille max 5MB
        if ($file['size'] > 5 * 1024 * 1024) {
            return new WP_Error('file_too_large', 'Fichier trop volumineux (max 5MB)');
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

        // Créer l'attachement
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

        return $attachment_id;
    }

    /**
     * Crop une image existante
     */
    public static function crop_image($attachment_id, $x, $y, $width, $height, $target_width = 920, $target_height = 430) {
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

        // Redimensionner
        $resize_result = $image_editor->resize($target_width, $target_height, false);
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
}