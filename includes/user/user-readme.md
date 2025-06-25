# 🔐 Module User Auth - Sisme Games Editor

**Version:** 1.0.0  
**Date de création:** 25 Juin 2025  
**Auteur:** Développement Sisme Games Editor  

## 📋 Vue d'ensemble

Module d'authentification frontend pour le plugin Sisme Games Editor. Permet aux utilisateurs de s'inscrire, se connecter et gérer leur profil sans accès à l'administration WordPress.

### 🎯 Objectifs

- **Authentification frontend** - Système complet login/register
- **Sécurité renforcée** - Rate limiting, validation, nonces
- **Design cohérent** - Integration parfaite avec le thème gaming
- **Extensibilité** - Base pour les modules user-profile et user-library

## 🏗️ Architecture

### 📁 Structure des fichiers

```
includes/user/
├── user-loader.php                    # Master loader (point d'entrée système)
└── user-auth/                         # Module authentification
    ├── user-auth-loader.php           # Loader du module auth
    ├── user-auth-api.php              # Shortcodes et rendu HTML
    ├── user-auth-handlers.php         # Logique métier (login/register)
    ├── user-auth-security.php         # Sécurité et validation
    ├── user-auth-forms.php            # Formulaires utilisant le module existant
    ├── assets/
    │   ├── user-auth.css              # Styles gaming cohérents
    │   └── user-auth.js               # Interactions frontend
    └── README.md                      # Cette documentation
```

### 🔄 Flux d'initialisation

```
1. Système principal → SISME_GAMES_MODULES → 'user'
2. user-loader.php → Sisme_User_Loader::get_instance()
3. Sisme_User_Loader → charge user-auth-loader.php
4. user-auth-loader.php → Sisme_User_Auth_Loader::get_instance()
5. Sisme_User_Auth_Loader → charge tous les modules auth
6. Shortcodes enregistrés et système prêt
```

## 🚀 Fonctionnalités

### ✅ Authentification complète

- **Connexion** avec email/mot de passe
- **Inscription** avec validation email
- **Remember me** pour session persistante
- **Déconnexion** avec nettoyage session

### 🛡️ Sécurité avancée

- **Rate limiting** - Max 5 tentatives par 15 minutes
- **Validation stricte** - Email, mot de passe, données
- **Nonces WordPress** - Protection CSRF
- **Sanitisation** - Toutes les entrées utilisateur

### 🎨 Interface utilisateur

- **Design gaming dark** - Cohérent avec le plugin
- **Formulaires réactifs** - Feedback temps réel
- **Messages d'erreur** - Clairs et informatifs
- **Responsive design** - Mobile/tablet/desktop

## 📊 Données utilisateur

### 🗂️ Structure user_meta

```php
// Profil gaming de base
'sisme_favorite_games' => array()      // IDs des jeux favoris (term_ids)
'sisme_wishlist_games' => array()      // IDs wishlist (term_ids)
'sisme_completed_games' => array()     // IDs jeux terminés (term_ids)
'sisme_gaming_platforms' => array()   // ['PC', 'PS5', 'Xbox']
'sisme_favorite_genres' => array()    // IDs des genres préférés
'sisme_notifications_email' => true   // Préférences notifications
'sisme_profile_created' => string     // Date création profil
```

### 🔐 Sécurité utilisateur

- **Tentatives de connexion** stockées en transients WordPress
- **Sessions** gérées par WordPress nativement
- **Mots de passe** hashés par WordPress (wp_hash_password)

## 🎯 Shortcodes disponibles

### 1. `[sisme_user_login]` - Formulaire de connexion

```html
[sisme_user_login 
    redirect_to="/mon-profil/" 
    show_register_link="true" 
    show_remember="true"
    title="Connexion"
    submit_text="Se connecter"
    container_class="sisme-user-auth-container"]
```

**Paramètres:**
- `redirect_to` (string) - URL de redirection après connexion
- `show_register_link` (bool) - Afficher lien vers inscription
- `show_remember` (bool) - Afficher case "Se souvenir de moi"
- `title` (string) - Titre du formulaire
- `submit_text` (string) - Texte du bouton
- `container_class` (string) - Classes CSS du container

### 2. `[sisme_user_register]` - Formulaire d'inscription

```html
[sisme_user_register 
    redirect_to="/mon-profil/" 
    show_login_link="true"
    require_email_verification="true"
    title="Inscription"
    submit_text="Créer mon compte"
    container_class="sisme-user-auth-container"]
```

**Paramètres:**
- `redirect_to` (string) - URL de redirection après inscription
- `show_login_link` (bool) - Afficher lien vers connexion
- `require_email_verification` (bool) - Validation email obligatoire
- `title` (string) - Titre du formulaire
- `submit_text` (string) - Texte du bouton
- `container_class` (string) - Classes CSS du container

### 3. `[sisme_user_profile]` - Dashboard utilisateur

```html
[sisme_user_profile 
    show_favorites="true" 
    show_activity="true"
    show_recommendations="true"]
```

**Paramètres:**
- `show_favorites` (bool) - Afficher section favoris
- `show_activity` (bool) - Afficher activité récente
- `show_recommendations` (bool) - Afficher recommandations

### 4. `[sisme_user_menu]` - Menu utilisateur

```html
[sisme_user_menu 
    show_avatar="true" 
    show_logout="true"
    container_class="sisme-user-menu"]
```

## 🔧 API et hooks

### Classes principales

#### `Sisme_User_Auth_API`
- `render_login_form($atts)` - Rendu formulaire connexion
- `render_register_form($atts)` - Rendu formulaire inscription
- `render_profile_dashboard($atts)` - Rendu dashboard
- `render_user_menu($atts)` - Rendu menu utilisateur

#### `Sisme_User_Auth_Handlers`
- `handle_login($data)` - Traitement connexion
- `handle_register($data)` - Traitement inscription
- `init_user_gaming_meta($user_id)` - Initialisation métadonnées

#### `Sisme_User_Auth_Security`
- `validate_login_attempt($email)` - Validation tentative
- `validate_user_data($data, $context)` - Validation données
- `record_failed_attempt($email)` - Enregistrement échec

### Hooks WordPress disponibles

```php
// Actions
add_action('sisme_user_login_success', $callback, 10, 2);  // ($user_id, $user_data)
add_action('sisme_user_register_success', $callback, 10, 2); // ($user_id, $user_data)
add_action('sisme_user_logout', $callback, 10, 1); // ($user_id)

// Filtres
add_filter('sisme_user_login_redirect', $callback, 10, 2); // ($redirect_url, $user)
add_filter('sisme_user_register_redirect', $callback, 10, 2); // ($redirect_url, $user)
add_filter('sisme_user_default_meta', $callback, 10, 1); // ($default_meta)
```

## 🎨 Styling et CSS

### Variables CSS utilisées

Le module réutilise les variables CSS existantes du plugin :

```css
/* Variables gaming cohérentes */
--sisme-gaming-dark: #1a1a1a
--sisme-gaming-dark-lighter: #2d2d2d
--sisme-gaming-text-bright: #ffffff
--sisme-color-primary: #a1b78d
--sisme-color-primary-light: #b8c9a4
--sisme-radius-md: 8px
--sisme-radius-lg: 12px
--sisme-space-sm: 8px
--sisme-space-md: 16px
--sisme-space-lg: 24px
--sisme-space-xl: 32px
```

### Classes CSS principales

```css
.sisme-user-auth-container      /* Container principal */
.sisme-auth-card               /* Card de formulaire */
.sisme-auth-header             /* En-tête du formulaire */
.sisme-auth-title              /* Titre avec icône */
.sisme-auth-content            /* Contenu du formulaire */
.sisme-auth-footer             /* Pied avec liens */
.sisme-auth-error              /* Messages d'erreur */
.sisme-auth-success            /* Messages de succès */
```

## 🚧 Installation et configuration

### 1. Ajouter le module au système

Dans le fichier principal du plugin, ajouter `'user'` à la constante :

```php
const SISME_GAMES_MODULES = [
    'search',
    'cards', 
    'vedettes',
    'team-choice',
    'user'  // ← Nouveau module
];
```

### 2. Créer les fichiers

- Créer la structure des dossiers
- Copier tous les fichiers du module
- Vérifier les permissions des fichiers

### 3. Configurer les pages

Créer les pages WordPress avec les shortcodes :

- **Page "Connexion"** : `[sisme_user_login]`
- **Page "Inscription"** : `[sisme_user_register]`  
- **Page "Mon Profil"** : `[sisme_user_profile]`

## 🔍 Tests et debug

### Mode debug

Le module utilise le système de debug WordPress. Pour activer les logs :

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

### Logs disponibles

- `[Sisme User]` - Logs du master loader
- `[Sisme User Auth]` - Logs du module auth
- Tentatives de connexion échouées
- Créations de comptes
- Erreurs de sécurité

### Tests à effectuer

1. **Inscription** - Créer un nouveau compte
2. **Connexion** - Se connecter avec email/mot de passe
3. **Remember me** - Tester session persistante
4. **Rate limiting** - Tester blocage après 5 échecs
5. **Validation** - Tester données invalides
6. **Responsive** - Tester sur mobile/tablet

## 🔄 Évolutions prévues

### Version 1.1 (Prochaine)
- **Réinitialisation mot de passe** par email
- **Validation email** à l'inscription
- **Connexion sociale** (Google, Discord)

### Version 1.2 (Future)
- **2FA** authentification à deux facteurs
- **Historique connexions** pour sécurité
- **Préférences avancées** notifications/privacy

## 📞 Support et maintenance

### Problèmes connus
- Aucun pour le moment

### Dépendances
- WordPress 5.0+
- Plugin Sisme Games Editor
- Module formulaire existant du plugin

### Contact
Pour toute question ou amélioration, contacter l'équipe de développement Sisme Games Editor.

---

**Dernière mise à jour:** 25 Juin 2025  
**Statut:** En développement  
**Compatibilité:** WordPress 5.0+, PHP 7.4+