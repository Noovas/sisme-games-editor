# 👤 User-Profile - REF API

**Module:** `includes/user/user-profile/` | **Version:** 1.0.0 | **Status:** ✅ Production

---

## 📂 Architecture

```
includes/user/user-profile/
├── user-profile-loader.php     # Singleton + chargement assets
├── user-profile-handlers.php   # Logique métier + traitement
├── user-profile-forms.php      # Générateur formulaires modulaires
├── user-profile-avatar.php     # Gestion avatar + bannière
├── user-profile-api.php        # Shortcodes + API publique
└── assets/
    ├── user-profile.css         # Styles profil utilisateur
    └── user-profile.js          # Interactions JavaScript + AJAX
```

---

## 🔧 API Principale

### `Sisme_User_Profile_Loader` - Singleton
```php
get_instance()                            // self - Instance unique
enqueue_frontend_assets()                 // void - Charge CSS/JS si non-admin
handle_profile_requests()                 // void - Traite formulaires
init_shortcodes()                        // void - Enregistre shortcodes WordPress

// AJAX Handlers  
ajax_update_profile()                    // void - JSON response
ajax_upload_avatar()                     // void - JSON response
ajax_delete_avatar()                     // void - JSON response
ajax_upload_banner()                     // void - JSON response
ajax_delete_banner()                     // void - JSON response
ajax_update_preferences()                // void - JSON response
```

### `Sisme_User_Profile_Handlers` - Core Business Logic
```php
// Traitement principal
handle_profile_update($data)             // array|WP_Error - Mise à jour profil
handle_preferences_update($data)         // array|WP_Error - Mise à jour préférences

// Session management
set_profile_message($message, $type)     // void - Store flash message  
get_profile_message()                    // array|null - Retrieve & clear message

// Initialisation utilisateur
get_default_user_meta()                  // array - Structure métadonnées par défaut
init_user_profile_meta($user_id)         // void - Initialise meta nouvel utilisateur

// Processing interne
sanitize_profile_data($data)             // array - Nettoie données formulaire
validate_profile_data($data)             // true|WP_Error - Valide données
```

### `Sisme_User_Profile_Forms` - Générateur Formulaires
```php
// Constructor
new Sisme_User_Profile_Forms($components, $options)

// Méthodes principales
render()                                 // string - HTML formulaire complet
is_submitted()                           // bool - Formulaire soumis ?
get_submitted_data()                     // array - Données soumises nettoyées
validate()                               // true|WP_Error - Validation formulaire

// Composants individuels
render_component($name, $component)      // string - HTML composant individuel
get_component_value($name)               // mixed - Valeur composant
sanitize_component_value($value, $comp)  // mixed - Valeur nettoyée
```

### `Sisme_User_Profile_Avatar` - Gestion Images
```php
// Avatar management
handle_avatar_upload($files)             // array|WP_Error - Traite upload avatar
delete_user_avatar($user_id)             // array|WP_Error - Supprime avatar
get_user_avatar_url($user_id, $size)     // string|false - URL avatar
user_has_custom_avatar($user_id)         // bool - Utilisateur a avatar custom ?

// Banner management  
handle_banner_upload($files)             // array|WP_Error - Traite upload bannière
delete_user_banner($user_id)             // array|WP_Error - Supprime bannière
get_user_banner_url($user_id, $size)     // string|false - URL bannière
user_has_custom_banner($user_id)         // bool - Utilisateur a bannière custom ?

// Informations complètes
get_user_image_info($user_id, $type)     // array - Infos image complètes
get_avatar_urls($attachment_id)          // array - URLs avatar toutes tailles
get_banner_urls($attachment_id)          // array - URLs bannière toutes tailles
get_final_avatar_url($user_id, $size)    // string - URL finale (custom ou Gravatar)
```

### `Sisme_User_Profile_API` - Shortcodes
```php
render_profile_edit_form($atts)          // string - Shortcode [sisme_user_profile_edit]
render_avatar_uploader($atts)            // string - Shortcode [sisme_user_avatar_uploader]  
render_banner_uploader($atts)            // string - Shortcode [sisme_user_banner_uploader]
render_preferences($atts)                // string - Shortcode [sisme_user_preferences]
render_profile_display($atts)            // string - Shortcode [sisme_user_profile_display]
```

---

## ⚡ JavaScript API

### Configuration Auto-injectée
```javascript
window.sismeUserProfile = {
    ajax_url: 'wp-admin/admin-ajax.php',
    nonce: 'security_nonce',
    messages: {
        profile_updated: 'Profil mis à jour avec succès !',
        avatar_updated: 'Avatar mis à jour !',
        banner_updated: 'Bannière mise à jour !',
        upload_error: 'Erreur lors de l\'upload',
        delete_confirm: 'Confirmer la suppression ?'
    }
};
```

### Fonctionnalités JavaScript
```javascript
// Upload automatique avatar/bannière via AJAX
// Prévisualisation instantanée des images
// Validation côté client pour tailles/formats
// Messages de feedback en temps réel
// Drag & drop support pour uploads
```

---

## 🗂️ Structure Données

### Meta Keys WordPress
```php
// Informations de base
'sisme_user_bio'                    // string - Biographie (500 chars max)
'sisme_user_profile_updated'        // mysql_date - Dernière mise à jour profil

// Avatar/bannière custom
'sisme_user_avatar'                 // attachment_id - Avatar custom
'sisme_user_banner'                 // attachment_id - Bannière custom  
'sisme_user_avatar_updated'         // mysql_date - Dernière MAJ avatar
'sisme_user_banner_updated'         // mysql_date - Dernière MAJ bannière

// Préférences gaming
'sisme_user_favorite_game_genres'   // array(term_ids) - Genres préférés
'sisme_user_skill_level'            // string - Niveau de jeu
'sisme_user_favorite_games'         // array(term_ids) - Jeux favoris

// Confidentialité
'sisme_user_privacy_profile_public' // bool - Profil public
'sisme_user_privacy_show_stats'     // bool - Afficher stats
'sisme_user_privacy_allow_friend_requests' // bool - Demandes d'amis

// Métadonnées héritées (user-auth)
'sisme_user_profile_created'        // mysql_date - Création profil
'sisme_user_last_login'             // mysql_date - Dernière connexion
'sisme_user_profile_version'        // string - Version schema
```

### Composants de Formulaire
```php
// Basic Info (3 composants)
'user_display_name' => [
    'type' => 'text',
    'required' => true,
    'output_var' => 'user_display_name'
],
'user_bio' => [
    'type' => 'textarea', 
    'maxlength' => 500,
    'output_var' => 'user_bio'
],
'user_website' => [
    'type' => 'url',
    'output_var' => 'user_website'
],

// Gaming Preferences (3 composants)
'favorite_game_genres' => [
    'type' => 'checkbox_group',
    'options' => 'wp_categories',
    'output_var' => 'favorite_game_genres'
],
'skill_level' => [
    'type' => 'select',
    'options' => ['Débutant', 'Intermédiaire', 'Avancé', 'Expert'],
    'output_var' => 'skill_level'
],
'favorite_games_display' => [
    'type' => 'display_only',
    'output_var' => 'favorite_games_display'
],

// Privacy Settings (3 composants)
'privacy_profile_public' => [
    'type' => 'checkbox',
    'output_var' => 'privacy_profile_public'
],
'privacy_show_stats' => [
    'type' => 'checkbox', 
    'output_var' => 'privacy_show_stats'
],
'privacy_allow_friend_requests' => [
    'type' => 'checkbox',
    'output_var' => 'privacy_allow_friend_requests'
]
```

### Format de Réponse
```php
// Succès handle_profile_update
[
    'success' => true,
    'user_id' => 123,
    'updated_fields' => ['display_name', 'bio', 'website'],
    'message' => 'Profil mis à jour avec succès'
]

// Succès handle_avatar_upload
[
    'success' => true,
    'attachment_id' => 456,
    'urls' => [
        'thumbnail' => 'url_thumb',
        'medium' => 'url_medium', 
        'large' => 'url_large'
    ],
    'message' => 'Avatar uploadé avec succès'
]
```

---

## 🎨 CSS Classes

### Formulaires
```css
.sisme-profile-form-wrapper         /* Container principal formulaire */
.sisme-profile-edit-container       /* Container édition profil */
.sisme-profile-edit-header          /* Header avec titre */
.sisme-profile-edit-title           /* Titre principal */

.sisme-profile-images-section       /* Section avatar/bannière */
.sisme-profile-avatar-section       /* Section avatar uniquement */
.sisme-profile-banner-section       /* Section bannière uniquement */
.sisme-profile-form-section         /* Section formulaire */

.sisme-profile-message              /* Messages de feedback */
.sisme-profile-message--success     /* Message succès */
.sisme-profile-message--error       /* Message erreur */
```

### Composants
```css
.sisme-form-component               /* Composant de formulaire de base */
.sisme-form-component--text         /* Composant texte */
.sisme-form-component--textarea     /* Composant textarea */
.sisme-form-component--select       /* Composant select */
.sisme-form-component--checkbox     /* Composant checkbox */

.sisme-component-label              /* Label composant */
.sisme-component-input              /* Input composant */
.sisme-component-description        /* Description composant */
.sisme-component-error              /* Erreur validation */
```

### Avatar/Bannière
```css
.sisme-avatar-uploader-container    /* Container upload avatar */
.sisme-banner-uploader-container    /* Container upload bannière */
.sisme-image-preview                /* Prévisualisation image */
.sisme-upload-buttons               /* Boutons upload/delete */
.sisme-image-info                   /* Infos image (taille, etc.) */
```

---

## 🚀 Usage Patterns

### Formulaire d'Édition Complet
```php
// Toutes les sections
echo do_shortcode('[sisme_user_profile_edit]');

// Sections spécifiques
echo do_shortcode('[sisme_user_profile_edit 
    sections="basic,gaming" 
    title="Mon Profil Gaming"
    show_avatar="true"
    redirect_to="/dashboard/"
]');
```

### Upload d'Avatar Standalone
```php
echo do_shortcode('[sisme_user_avatar_uploader 
    size="large" 
    show_delete="true" 
    show_info="true"
]');
```

### Préférences Gaming Uniquement
```php
echo do_shortcode('[sisme_user_preferences 
    title="Mes Préférences Gaming"
    compact="false"
]');
```

### Affichage Profil Public
```php
// Utilisateur courant
echo do_shortcode('[sisme_user_profile_display]');

// Utilisateur spécifique
echo do_shortcode('[sisme_user_profile_display 
    user_id="123" 
    sections="basic,gaming"
    show_avatar="true"
    show_banner="true"
]');
```

### Usage Programmatique
```php
// Vérifier avatars custom
$has_avatar = Sisme_User_Profile_Avatar::user_has_custom_avatar($user_id);
$has_banner = Sisme_User_Profile_Avatar::user_has_custom_banner($user_id);

// Récupérer URLs images
$avatar_url = Sisme_User_Profile_Avatar::get_final_avatar_url($user_id, 'large');
$banner_url = Sisme_User_Profile_Avatar::get_user_banner_url($user_id, 'large');

// Créer formulaire custom
$form = new Sisme_User_Profile_Forms(['user_display_name', 'user_bio'], [
    'type' => 'profile'
]);
echo $form->render();

// Traitement manuel
$result = Sisme_User_Profile_Handlers::handle_profile_update($_POST);
if (is_wp_error($result)) {
    echo $result->get_error_message();
}
```

---

## ⚡ Performance & Cache

### Chargement Conditionnel Assets
```php
// CSS/JS chargés uniquement sur frontend (non-admin)
// Dépendances : 'sisme-user-auth' (CSS), ['jquery', 'sisme-user-auth'] (JS)
// Localisation JavaScript avec nonce + messages
```

### Optimisations Intégrées
```php
// Images redimensionnées automatiquement
// Validation côté client ET serveur
// AJAX pour uploads sans rechargement
// Meta keys optimisées (pas de sérialisation excessive)
```

---

## 🐛 Debug & Hooks

### Messages Flash
```php
// Stockage temporaire en session
Sisme_User_Profile_Handlers::set_profile_message('Message', 'success');
$message = Sisme_User_Profile_Handlers::get_profile_message(); // Lit et efface
```

### Hooks WordPress
```php
// Actions profil
do_action('sisme_profile_updated', $user_id, $updated_fields);
do_action('sisme_preferences_updated', $user_id, $preferences);
do_action('sisme_avatar_uploaded', $user_id, $attachment_id);
do_action('sisme_banner_uploaded', $user_id, $attachment_id);

// AJAX endpoints
add_action('wp_ajax_sisme_update_profile', callback);
add_action('wp_ajax_sisme_upload_avatar', callback);
add_action('wp_ajax_sisme_delete_avatar', callback);
add_action('wp_ajax_sisme_upload_banner', callback);
add_action('wp_ajax_sisme_delete_banner', callback);
add_action('wp_ajax_sisme_update_preferences', callback);

// Assets
add_action('wp_enqueue_scripts', [$loader, 'enqueue_frontend_assets']);
add_action('wp_loaded', [$loader, 'handle_profile_requests']);
```

### Validation et Sécurité
```php
// Nonces WordPress obligatoires
wp_verify_nonce($_POST['_wpnonce'], 'sisme_user_profile_update');

// Sanitization automatique
sanitize_text_field(), sanitize_textarea_field(), esc_url_raw()

// Vérification utilisateur connecté partout
is_user_logged_in() || wp_die('Vous devez être connecté');
```

---

## 🔗 Intégrations

### Modules Liés
- **user-auth** - Système d'authentification parent
- **user-loader** - Chargeur master des modules user
- **cards** - Potentielle intégration jeux favoris
- **taxonomies** - Récupération genres via catégories

### Dépendances WordPress
- **User Meta API** pour stockage données
- **Media Library** pour avatar/bannière
- **Attachment API** pour gestion images
- **Nonces** pour sécurité CSRF
- **AJAX API** pour interactions temps réel

### Compatibilité
- **Gravatar** fallback si pas d'avatar custom
- **WordPress User API** (display_name, user_url)
- **Responsive images** WordPress (tailles multiples)
- **JavaScript ES5** compatible navigateurs anciens

---

## 🎯 Shortcodes Disponibles

| Shortcode | Status | Description | Usage Principal |
|-----------|--------|-------------|-----------------|
| `[sisme_user_profile_edit]` | ✅ Prod | Formulaire édition complet | Pages profil utilisateur |
| `[sisme_user_avatar_uploader]` | ✅ Prod | Upload avatar standalone | Widgets, zones spécifiques |
| `[sisme_user_banner_uploader]` | ✅ Prod | Upload bannière standalone | Pages profil avancées |
| `[sisme_user_preferences]` | ✅ Prod | Préférences gaming uniquement | Dashboard gaming |
| `[sisme_user_profile_display]` | ✅ Prod | Affichage profil public | Pages membres, annuaires |