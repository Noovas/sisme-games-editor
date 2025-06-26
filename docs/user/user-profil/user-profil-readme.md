# 👤 Module User Profile - Structure et Architecture

## 🎯 Vue d'ensemble

Module de gestion complète du profil utilisateur dans l'écosystème Sisme Games Editor, suivant l'architecture modulaire établie par user-auth.

## 📁 Structure des fichiers proposée

```
includes/user/user-profile/
├── user-profile-loader.php      # 🎯 Singleton loader principal
├── user-profile-handlers.php    # 🔧 Logique métier et traitement
├── user-profile-forms.php       # 📝 Générateur de formulaires
├── user-profile-avatar.php      # 🖼️ Gestion avatar spécialisée
├── user-profile-api.php         # 🌐 Shortcodes et API publique
└── assets/
    ├── user-profile.css          # 🎨 Styles profil
    ├── user-profile.js           # ⚡ Interactions JS
    └── avatar-upload.js          # 📤 Upload avatar AJAX
```

## 🧩 Composants de formulaire proposés

### 📋 **Informations de base (4 composants)**
1. `user_display_name` - Nom d'affichage (text, requis)
2. `user_bio` - Biographie (textarea, 500 chars max)
3. `user_website` - Site web (url, optionnel)
4. `user_location` - Localisation (text, optionnel)

### 🎮 **Préférences gaming (5 composants)**
5. `gaming_platform_preference` - Plateforme préférée (select)
6. `favorite_game_genres` - Genres préférés (checkbox_group)
7. `gaming_level` - Niveau de jeu (select)
8. `gaming_playtime` - Temps de jeu par semaine (select)
9. `gaming_favorite_games` - Jeux favoris (select multiple)

### 🔒 **Confidentialité (3 composants)**
10. `privacy_profile_public` - Profil public (checkbox)
11. `privacy_show_gaming_stats` - Afficher stats (checkbox)
12. `privacy_allow_friend_requests` - Demandes d'amis (checkbox)

### 🖼️ **Avatar (composant spécial)**
- Gestion séparée dans `user-profile-avatar.php`
- Upload, crop, suppression
- Intégration avec WordPress Media Library

## 🚀 Shortcodes à implémenter

### `[sisme_user_profile_edit]`
**Formulaire d'édition complet**
```php
[sisme_user_profile_edit 
    sections="basic,gaming,privacy"  // Sections à afficher
    title="Modifier mon profil"
    show_avatar="true"
    redirect_to="/profil/"
]
```

### `[sisme_user_avatar_uploader]`
**Upload d'avatar standalone**
```php
[sisme_user_avatar_uploader 
    size="large"                     // thumbnail, medium, large
    show_delete="true"
    crop="true"
]
```

### `[sisme_user_gaming_preferences]`
**Section gaming uniquement**
```php
[sisme_user_gaming_preferences 
    title="Mes préférences gaming"
    compact="false"
]
```

### `[sisme_user_profile_display]`
**Affichage du profil public**
```php
[sisme_user_profile_display 
    user_id="123"                    // Si vide = utilisateur courant
    sections="basic,gaming"
    show_avatar="true"
    show_stats="true"
]
```

## 📊 Schéma des métadonnées utilisateur

### **Nouvelles meta_keys à créer**
```php
// Informations de base
'sisme_user_bio'                    => string(500)
'sisme_user_location'               => string
'sisme_user_website'                => url
'sisme_profile_updated'             => mysql_date

// Préférences gaming
'sisme_gaming_platform_preference'  => string
'sisme_favorite_game_genres'        => array(term_ids)
'sisme_gaming_level'                => string
'sisme_gaming_playtime'             => string
'sisme_gaming_favorite_games'       => array(term_ids)

// Confidentialité
'sisme_privacy_profile_public'      => boolean
'sisme_privacy_show_gaming_stats'   => boolean
'sisme_privacy_allow_friend_requests' => boolean

// Avatar custom
'sisme_user_avatar'                 => attachment_id
'sisme_avatar_updated'              => mysql_date
```

### **Réutilisation existante (user-auth)**
```php
'sisme_profile_created'             => mysql_date
'sisme_last_login'                  => mysql_date
'sisme_profile_version'             => string
```

## 🔧 Classes principales à implémenter

### `Sisme_User_Profile_Loader`
- Singleton pattern comme user-auth
- Chargement des modules dans l'ordre
- Enregistrement hooks et shortcodes
- Assets CSS/JS conditionnels

### `Sisme_User_Profile_Forms`
- 12 composants de formulaire
- Validation complète avec sécurité
- Sections modulaires (basic, gaming, privacy)
- Chargement automatique des données utilisateur

### `Sisme_User_Profile_Handlers`
- `handle_profile_update($data)` - Mise à jour profil
- `handle_avatar_upload($files)` - Upload avatar
- `handle_gaming_preferences($data)` - Préférences gaming
- Hooks d'actions pour extensions

### `Sisme_User_Profile_Avatar`
- `upload_avatar($file, $user_id)` - Upload et traitement
- `crop_avatar($attachment_id, $coords)` - Crop interactif
- `delete_user_avatar($user_id)` - Suppression propre
- `get_avatar_url($user_id, $size)` - URLs optimisées

### `Sisme_User_Profile_API`
- 4 shortcodes avec options complètes
- Détection automatique utilisateur courant
- Intégration avec système d'assets
- Gestion des permissions d'affichage

## 🎨 Interface utilisateur proposée

### **Formulaire d'édition (3 sections)**

```
┌─────────────────────────────────────┐
│ 🖼️ AVATAR                           │
│ [Photo actuelle] [Changer] [Suppr]  │
└─────────────────────────────────────┘

┌─────────────────────────────────────┐
│ 📋 INFORMATIONS DE BASE             │
│ • Nom d'affichage [________]        │
│ • Biographie [________________]     │
│ • Site web [___________________]    │
│ • Localisation [_______________]    │
└─────────────────────────────────────┘

┌─────────────────────────────────────┐
│ 🎮 PRÉFÉRENCES GAMING               │
│ • Plateforme [Sélectionnez ▼]      │
│ • Genres ☑ Action ☑ RPG ☐ Sport    │
│ • Niveau [Expérimenté ▼]           │
│ • Temps/semaine [10-20h ▼]         │
│ • Jeux favoris [Multi-select]      │
└─────────────────────────────────────┘

┌─────────────────────────────────────┐
│ 🔒 CONFIDENTIALITÉ                 │
│ ☑ Profil public                    │
│ ☑ Afficher statistiques gaming     │
│ ☐ Autoriser demandes d'amis        │
└─────────────────────────────────────┘

[Mettre à jour le profil]
```

## 🔄 Hooks d'action proposés

```php
// Avant/après mise à jour profil
do_action('sisme_before_profile_update', $user_id, $data);
do_action('sisme_profile_updated', $user_id, $updated_fields);

// Avatar
do_action('sisme_avatar_uploaded', $user_id, $attachment_id);
do_action('sisme_avatar_deleted', $user_id, $old_attachment_id);

// Préférences gaming
do_action('sisme_gaming_preferences_updated', $user_id, $preferences);
```

## 🚦 Endpoints AJAX proposés

```php
// Profile
wp_ajax_sisme_update_profile
wp_ajax_sisme_validate_profile_field

// Avatar
wp_ajax_sisme_upload_avatar
wp_ajax_sisme_crop_avatar
wp_ajax_sisme_delete_avatar

// Gaming
wp_ajax_sisme_update_gaming_preferences
wp_ajax_sisme_search_games        // Pour multi-select jeux favoris
```

## 🔒 Sécurité et validation

### **Validation côté serveur**
- Nonces pour tous les formulaires
- Sanitisation selon type de champ
- Validation longueur/format
- Vérification permissions utilisateur

### **Upload avatar sécurisé**
- Types MIME autorisés (jpeg, png, gif)
- Taille max 2Mo
- Scan antimalware WordPress
- Génération miniatures automatique

### **Rate limiting**
- Max 5 mises à jour profil/minute
- Max 3 uploads avatar/heure
- Logs des actions sensibles

## ❓ Questions pour validation

### 🤔 **Architecture**
1. **Structure des fichiers** - Les 5 fichiers proposés conviennent-ils ?
2. **Composants de formulaire** - Faut-il ajouter/supprimer des champs ?
3. **Shortcodes** - Les 4 shortcodes couvrent-ils tous les besoins ?

### 🤔 **Fonctionnalités**
4. **Avatar** - Faut-il un système de crop interactif ou simple upload ?
5. **Préférences gaming** - Intégration avec les termes existants du projet ?
6. **Confidentialité** - Niveaux de visibilité suffisants ?

### 🤔 **Technique**
7. **Métadonnées** - Le schéma proposé est-il cohérent avec l'existant ?
8. **Performance** - Chargement conditionnel des assets OK ?
9. **Extensibilité** - Architecture prête pour user-library et user-social ?

### 🤔 **UX/UI**
10. **Interface** - Layout en 3 sections (basic/gaming/privacy) pertinent ?
11. **AJAX** - Quelles interactions doivent être asynchrones ?
12. **Mobile** - Adaptations spécifiques pour responsive ?

---

## ✅ **Validation requise avant implémentation**

Merci de valider ces points pour s'assurer que l'architecture correspond exactement aux besoins du projet avant de commencer le développement !