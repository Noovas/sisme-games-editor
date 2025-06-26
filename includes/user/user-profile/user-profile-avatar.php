<?php
/**
 * File: /sisme-games-editor/includes/user/user-profile/user-profile-avatar.php
 * Gestion spécialisée des avatars utilisateur
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_User_Profile_Avatar {
    
    /**
     * Configuration par défaut
     */
    const MAX_FILE_SIZE = 2097152; // 2Mo
    const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/gif'];
    const AVATAR_SIZES = [
        'thumbnail' => 150,
        'medium' => 300,
        'large' => 600
    ];
    const BANNER_SIZES = [
        'medium' => [800, 200],
        'large' => [1200, 300],
        'full' => [1920, 480]
    ];
    
    /**
     * Traiter l'upload d'un avatar
     * @param array $files Données $_FILES
     * @return array|WP_Error Résultat ou erreur
     */
    public static function handle_avatar_upload($files) {
        return self::handle_image_upload($files, 'avatar');
    }
    
    /**
     * Traiter l'upload d'une bannière
     * @param array $files Données $_FILES
     * @return array|WP_Error Résultat ou erreur
     */
    public static function handle_banner_upload($files) {
        return self::handle_image_upload($files, 'banner');
    }
    
    /**
     * Traiter l'upload d'une image (avatar ou bannière)
     * @param array $files Données $_FILES
     * @param string $type Type d'image ('avatar' ou 'banner')
     * @return array|WP_Error Résultat ou erreur
     */
    private static function handle_image_upload($files, $type) {
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            return new WP_Error('not_logged_in', 'Vous devez être connecté.');
        }
        
        $file_key = $type . '_file';
        
        if (empty($files[$file_key]['tmp_name'])) {
            return new WP_Error('no_file', 'Aucun fichier fourni.');
        }
        
        $file = $files[$file_key];
        
        $validation = self::validate_upload_file($file, $type);
        if (is_wp_error($validation)) {
            return $validation;
        }
        
        $upload_result = self::process_image_upload($file, $user_id, $type);
        if (is_wp_error($upload_result)) {
            return $upload_result;
        }
        
        self::cleanup_old_image($user_id, $upload_result['attachment_id'], $type);
        
        $meta_key = 'sisme_user_' . $type;
        $meta_updated_key = 'sisme_user_' . $type . '_updated';
        
        update_user_meta($user_id, $meta_key, $upload_result['attachment_id']);
        update_user_meta($user_id, $meta_updated_key, current_time('mysql'));
        
        do_action('sisme_' . $type . '_uploaded', $user_id, $upload_result['attachment_id']);
        
        return [
            'success' => true,
            'attachment_id' => $upload_result['attachment_id'],
            'urls' => $upload_result['urls'],
            'message' => ucfirst($type) . ' uploadé avec succès'
        ];
    }
    
    /**
     * Valider le fichier uploadé
     * @param array $file Données du fichier
     * @param string $type Type d'image ('avatar' ou 'banner')
     * @return true|WP_Error Validation ou erreur
     */
    private static function validate_upload_file($file, $type = 'avatar') {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return new WP_Error('upload_error', 'Erreur lors de l\'upload du fichier.');
        }
        
        if ($file['size'] > self::MAX_FILE_SIZE) {
            return new WP_Error('file_too_large', 'Le fichier est trop volumineux (max 2Mo).');
        }
        
        $file_type = wp_check_filetype($file['name']);
        if (!in_array($file_type['type'], self::ALLOWED_TYPES)) {
            return new WP_Error('invalid_file_type', 'Type de fichier non autorisé. Utilisez JPG, PNG ou GIF.');
        }
        
        $image_info = getimagesize($file['tmp_name']);
        if (!$image_info) {
            return new WP_Error('invalid_image', 'Le fichier n\'est pas une image valide.');
        }
        
        if ($type === 'avatar') {
            if ($image_info[0] < 100 || $image_info[1] < 100) {
                return new WP_Error('image_too_small', 'L\'avatar doit faire au moins 100x100 pixels.');
            }
            
            if ($image_info[0] > 2000 || $image_info[1] > 2000) {
                return new WP_Error('image_too_large', 'L\'avatar ne peut pas dépasser 2000x2000 pixels.');
            }
        } elseif ($type === 'banner') {
            if ($image_info[0] < 400 || $image_info[1] < 100) {
                return new WP_Error('banner_too_small', 'La bannière doit faire au moins 400x100 pixels.');
            }
            
            if ($image_info[0] > 2400 || $image_info[1] > 600) {
                return new WP_Error('banner_too_large', 'La bannière ne peut pas dépasser 2400x600 pixels.');
            }
            
            $ratio = $image_info[0] / $image_info[1];
            if ($ratio < 2 || $ratio > 8) {
                return new WP_Error('banner_bad_ratio', 'La bannière doit avoir un ratio largeur/hauteur entre 2:1 et 8:1.');
            }
        }
        
        return true;
    }
    
    /**
     * Traiter l'upload et créer l'attachement
     * @param array $file Données du fichier
     * @param int $user_id ID de l'utilisateur
     * @param string $type Type d'image ('avatar' ou 'banner')
     * @return array|WP_Error Résultat ou erreur
     */
    private static function process_image_upload($file, $user_id, $type) {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        
        $upload_overrides = [
            'test_form' => false,
            'test_size' => true,
            'test_upload' => true
        ];
        
        $uploaded_file = wp_handle_upload($file, $upload_overrides);
        
        if (isset($uploaded_file['error'])) {
            return new WP_Error('upload_failed', $uploaded_file['error']);
        }
        
        $attachment_data = [
            'post_title' => ucfirst($type) . ' - Utilisateur ' . $user_id,
            'post_content' => ucfirst($type) . ' personnalisé pour l\'utilisateur ' . $user_id,
            'post_status' => 'inherit',
            'post_mime_type' => $uploaded_file['type'],
            'post_author' => $user_id
        ];
        
        $attachment_id = wp_insert_attachment($attachment_data, $uploaded_file['file']);
        
        if (is_wp_error($attachment_id)) {
            wp_delete_file($uploaded_file['file']);
            return $attachment_id;
        }
        
        $attachment_metadata = wp_generate_attachment_metadata($attachment_id, $uploaded_file['file']);
        wp_update_attachment_metadata($attachment_id, $attachment_metadata);
        
        $urls = ($type === 'avatar') ? self::get_avatar_urls($attachment_id) : self::get_banner_urls($attachment_id);
        
        return [
            'attachment_id' => $attachment_id,
            'file_path' => $uploaded_file['file'],
            'file_url' => $uploaded_file['url'],
            'urls' => $urls
        ];
    }
    
    /**
     * Nettoyer l'ancienne image
     * @param int $user_id ID de l'utilisateur
     * @param int $new_attachment_id ID du nouvel attachement
     * @param string $type Type d'image ('avatar' ou 'banner')
     * @return void
     */
    private static function cleanup_old_image($user_id, $new_attachment_id, $type) {
        $meta_key = 'sisme_user_' . $type;
        $old_attachment_id = get_user_meta($user_id, $meta_key, true);
        
        if ($old_attachment_id && $old_attachment_id != $new_attachment_id) {
            $old_attachment = get_post($old_attachment_id);
            
            if ($old_attachment && $old_attachment->post_author == $user_id) {
                wp_delete_attachment($old_attachment_id, true);
            }
        }
    }
    
    /**
     * Supprimer l'avatar d'un utilisateur
     * @param int $user_id ID de l'utilisateur
     * @return array|WP_Error Résultat ou erreur
     */
    public static function delete_user_avatar($user_id) {
        return self::delete_user_image($user_id, 'avatar');
    }
    
    /**
     * Supprimer la bannière d'un utilisateur
     * @param int $user_id ID de l'utilisateur
     * @return array|WP_Error Résultat ou erreur
     */
    public static function delete_user_banner($user_id) {
        return self::delete_user_image($user_id, 'banner');
    }
    
    /**
     * Supprimer une image d'un utilisateur
     * @param int $user_id ID de l'utilisateur
     * @param string $type Type d'image ('avatar' ou 'banner')
     * @return array|WP_Error Résultat ou erreur
     */
    private static function delete_user_image($user_id, $type) {
        if (!$user_id) {
            return new WP_Error('invalid_user', 'ID utilisateur invalide.');
        }
        
        $meta_key = 'sisme_user_' . $type;
        $meta_updated_key = 'sisme_user_' . $type . '_updated';
        
        $attachment_id = get_user_meta($user_id, $meta_key, true);
        
        if (!$attachment_id) {
            return new WP_Error('no_' . $type, 'Aucun ' . $type . ' à supprimer.');
        }
        
        $attachment = get_post($attachment_id);
        if (!$attachment) {
            delete_user_meta($user_id, $meta_key);
            return new WP_Error('attachment_not_found', 'Fichier ' . $type . ' introuvable.');
        }
        
        if ($attachment->post_author != $user_id && !current_user_can('delete_others_posts')) {
            return new WP_Error('permission_denied', 'Permission refusée.');
        }
        
        $deleted = wp_delete_attachment($attachment_id, true);
        
        if (!$deleted) {
            return new WP_Error('delete_failed', 'Échec de la suppression du fichier.');
        }
        
        delete_user_meta($user_id, $meta_key);
        delete_user_meta($user_id, $meta_updated_key);
        
        do_action('sisme_' . $type . '_deleted', $user_id, $attachment_id);
        
        return [
            'success' => true,
            'message' => ucfirst($type) . ' supprimé avec succès'
        ];
    }
    
    /**
     * Obtenir l'URL de l'avatar d'un utilisateur
     * @param int $user_id ID de l'utilisateur
     * @param string $size Taille demandée
     * @return string|false URL de l'avatar ou false
     */
    public static function get_user_avatar_url($user_id, $size = 'medium') {
        $attachment_id = get_user_meta($user_id, 'sisme_user_avatar', true);
        
        if (!$attachment_id) {
            return false;
        }
        
        $image_url = wp_get_attachment_image_url($attachment_id, $size);
        
        return $image_url ? $image_url : false;
    }
    
    /**
     * Obtenir l'URL de la bannière d'un utilisateur
     * @param int $user_id ID de l'utilisateur
     * @param string $size Taille demandée
     * @return string|false URL de la bannière ou false
     */
    public static function get_user_banner_url($user_id, $size = 'large') {
        $attachment_id = get_user_meta($user_id, 'sisme_user_banner', true);
        
        if (!$attachment_id) {
            return false;
        }
        
        $image_url = wp_get_attachment_image_url($attachment_id, $size);
        
        return $image_url ? $image_url : false;
    }
    
    /**
     * Obtenir toutes les URLs d'un avatar
     * @param int $attachment_id ID de l'attachement
     * @return array URLs par taille
     */
    public static function get_avatar_urls($attachment_id) {
        $urls = [];
        
        foreach (self::AVATAR_SIZES as $size => $dimension) {
            $url = wp_get_attachment_image_url($attachment_id, $size);
            if ($url) {
                $urls[$size] = $url;
            }
        }
        
        $urls['full'] = wp_get_attachment_url($attachment_id);
        
        return $urls;
    }
    
    /**
     * Obtenir toutes les URLs d'une bannière
     * @param int $attachment_id ID de l'attachement
     * @return array URLs par taille
     */
    public static function get_banner_urls($attachment_id) {
        $urls = [];
        
        foreach (self::BANNER_SIZES as $size => $dimensions) {
            $url = wp_get_attachment_image_url($attachment_id, $size);
            if ($url) {
                $urls[$size] = $url;
            }
        }
        
        $urls['full'] = wp_get_attachment_url($attachment_id);
        
        return $urls;
    }
    
    /**
     * Vérifier si un utilisateur a un avatar custom
     * @param int $user_id ID de l'utilisateur
     * @return bool True si avatar custom
     */
    public static function user_has_custom_avatar($user_id) {
        $attachment_id = get_user_meta($user_id, 'sisme_user_avatar', true);
        
        if (!$attachment_id) {
            return false;
        }
        
        return get_post($attachment_id) !== null;
    }
    
    /**
     * Vérifier si un utilisateur a une bannière custom
     * @param int $user_id ID de l'utilisateur
     * @return bool True si bannière custom
     */
    public static function user_has_custom_banner($user_id) {
        $attachment_id = get_user_meta($user_id, 'sisme_user_banner', true);
        
        if (!$attachment_id) {
            return false;
        }
        
        return get_post($attachment_id) !== null;
    }
    
    /**
     * Obtenir les informations complètes d'une image utilisateur
     * @param int $user_id ID de l'utilisateur
     * @param string $type Type d'image ('avatar' ou 'banner')
     * @return array|false Informations de l'image ou false
     */
    public static function get_user_image_info($user_id, $type = 'avatar') {
        $meta_key = 'sisme_user_' . $type;
        $meta_updated_key = 'sisme_user_' . $type . '_updated';
        
        $attachment_id = get_user_meta($user_id, $meta_key, true);
        
        if (!$attachment_id) {
            return false;
        }
        
        $attachment = get_post($attachment_id);
        if (!$attachment) {
            return false;
        }
        
        $metadata = wp_get_attachment_metadata($attachment_id);
        $urls = ($type === 'avatar') ? self::get_avatar_urls($attachment_id) : self::get_banner_urls($attachment_id);
        $updated = get_user_meta($user_id, $meta_updated_key, true);
        
        return [
            'attachment_id' => $attachment_id,
            'title' => $attachment->post_title,
            'description' => $attachment->post_content,
            'mime_type' => $attachment->post_mime_type,
            'uploaded_date' => $attachment->post_date,
            'updated_date' => $updated,
            'file_size' => filesize(get_attached_file($attachment_id)),
            'dimensions' => [
                'width' => $metadata['width'] ?? 0,
                'height' => $metadata['height'] ?? 0
            ],
            'urls' => $urls
        ];
    }
    
    /**
     * Rendu de l'interface d'upload d'avatar ou bannière
     * @param int $user_id ID de l'utilisateur
     * @param array $options Options d'affichage
     * @return string HTML de l'interface
     */
    public static function render_image_uploader($user_id = null, $options = []) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return '<div class="sisme-image-error">Vous devez être connecté.</div>';
        }
        
        $defaults = [
            'type' => 'avatar',
            'size' => 'medium',
            'show_delete' => true,
            'show_info' => false,
            'css_class' => 'sisme-image-uploader'
        ];
        
        $options = wp_parse_args($options, $defaults);
        $type = $options['type'];
        
        $current_image = self::get_user_image_info($user_id, $type);
        $has_image = (bool) $current_image;
        
        $output = '<div class="' . esc_attr($options['css_class']) . ' sisme-' . $type . '-uploader" data-user-id="' . esc_attr($user_id) . '" data-type="' . esc_attr($type) . '">';
        
        $output .= '<div class="sisme-' . $type . '-preview">';
        if ($has_image) {
            $image_url = $current_image['urls'][$options['size']] ?? $current_image['urls']['medium'] ?? $current_image['urls']['full'];
            $preview_class = ($type === 'banner') ? 'sisme-banner-current' : 'sisme-avatar-current';
            $output .= '<img src="' . esc_url($image_url) . '" alt="' . ucfirst($type) . ' actuel" class="' . $preview_class . '">';
        } else {
            $placeholder_icon = ($type === 'banner') ? '🖼️' : '👤';
            $placeholder_text = ($type === 'banner') ? 'Aucune bannière' : 'Aucun avatar';
            
            $output .= '<div class="sisme-' . $type . '-placeholder">';
            $output .= '<span class="sisme-' . $type . '-icon">' . $placeholder_icon . '</span>';
            $output .= '<p>' . $placeholder_text . '</p>';
            $output .= '</div>';
        }
        $output .= '</div>';
        
        $output .= '<div class="sisme-' . $type . '-actions">';
        
        $button_text = $has_image ? 'Changer' : 'Ajouter';
        $input_id = $type . '-file-input-' . $user_id;
        
        $output .= '<label for="' . esc_attr($input_id) . '" class="sisme-' . $type . '-upload-btn sisme-button sisme-button-primary">';
        $output .= '<span class="sisme-btn-icon">📤</span>';
        $output .= $button_text;
        $output .= '</label>';
        $output .= '<input type="file" id="' . esc_attr($input_id) . '" name="' . $type . '_file" accept="image/*" style="display: none;">';
        
        if ($has_image && $options['show_delete']) {
            $output .= '<button type="button" class="sisme-' . $type . '-delete-btn sisme-button sisme-button-danger">';
            $output .= '<span class="sisme-btn-icon">🗑️</span> Supprimer';
            $output .= '</button>';
        }
        
        $output .= '</div>';
        
        if ($options['show_info'] && $has_image) {
            $output .= '<div class="sisme-' . $type . '-info">';
            $output .= '<p class="sisme-' . $type . '-size">Taille : ' . number_format($current_image['file_size'] / 1024, 1) . ' Ko</p>';
            $output .= '<p class="sisme-' . $type . '-dimensions">Dimensions : ' . $current_image['dimensions']['width'] . 'x' . $current_image['dimensions']['height'] . 'px</p>';
            if ($current_image['updated_date']) {
                $output .= '<p class="sisme-' . $type . '-updated">Mis à jour : ' . date('d/m/Y H:i', strtotime($current_image['updated_date'])) . '</p>';
            }
            $output .= '</div>';
        }
        
        $output .= '<div class="sisme-' . $type . '-help">';
        if ($type === 'avatar') {
            $output .= '<p class="sisme-help-text">Formats acceptés : JPG, PNG, GIF | Taille max : 2Mo | Min : 100x100px</p>';
        } else {
            $output .= '<p class="sisme-help-text">Formats acceptés : JPG, PNG, GIF | Taille max : 2Mo | Min : 400x100px | Ratio : 2:1 à 8:1</p>';
        }
        $output .= '</div>';
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Rendu de l'interface d'upload d'avatar (rétrocompatibilité)
     * @param int $user_id ID de l'utilisateur
     * @param array $options Options d'affichage
     * @return string HTML de l'interface
     */
    public static function render_avatar_uploader($user_id = null, $options = []) {
        $options['type'] = 'avatar';
        return self::render_image_uploader($user_id, $options);
    }
    
    /**
     * Rendu de l'interface d'upload de bannière
     * @param int $user_id ID de l'utilisateur
     * @param array $options Options d'affichage
     * @return string HTML de l'interface
     */
    public static function render_banner_uploader($user_id = null, $options = []) {
        $options['type'] = 'banner';
        return self::render_image_uploader($user_id, $options);
    }
    
    /**
     * Obtenir l'avatar final à afficher (custom ou Gravatar)
     * @param int $user_id ID de l'utilisateur
     * @param string $size Taille demandée
     * @param string $default Avatar par défaut si aucun
     * @return string URL de l'avatar
     */
    public static function get_final_avatar_url($user_id, $size = 'medium', $default = '') {
        $custom_avatar = self::get_user_avatar_url($user_id, $size);
        
        if ($custom_avatar) {
            return $custom_avatar;
        }
        
        $user = get_userdata($user_id);
        if (!$user) {
            return $default;
        }
        
        $gravatar_size = self::AVATAR_SIZES[$size] ?? 150;
        return get_avatar_url($user->user_email, ['size' => $gravatar_size, 'default' => $default]);
    }
}