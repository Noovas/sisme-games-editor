# 📚 Sisme Games Editor - Documentation API REF

**Version:** 1.0.0 | **Status:** Production  
Documentation technique condensée pour tous les modules du plugin Sisme Games Editor.

---

## 🚀 Accès Rapide

### Modules Core
- **[👤 User](docs/user/user-readme.md)** - Système utilisateur complet
  - [👤 User Auth](docs/user/user-auth-readme.md) - Authentification et sessions
  - [⚙️ User Preferences](docs/user/user-preferences-readme.md) - Préférences gaming
  - [📊 User Dashboard](docs/user/user-dashboard-readme.md) - Tableau de bord utilisateur
  - [🖼️ User Profile](docs/user/user-profile-readme.md) - Gestion profils et avatars
  - [⚡ User Actions](docs/user/user-actions-readme.md) - Actions utilisateur (favoris, owned, etc.)
  - [🔔 User Notifications](docs/user/user-notifications-readme.md) - **NEW** Notifications automatiques

- **[🎴 Cards](docs/cards/cards-readme.md)** - Rendu cartes de jeux

- **[🔧 Utils Registry](docs/utils/utils-functions-registry-readme.md)** Dictionnaire des fonctions 

---

# 🗺️ Roadmap Technique - Module User-Profile

## 🎯 Objectif
Créer un nouveau module **user-profile** pour afficher les profils publics d'utilisateurs avec réutilisation complète des composants du dashboard existant.

---

## 📋 Phase 1 : Refactorisation du Dashboard (Fondations)

### ✅ Étape 1.1 : Analyse des composants existants
- [x] Audit complet du module `user-dashboard`
- [x] Identification des méthodes de rendu réutilisables
- [x] Analyse du `Sisme_User_Dashboard_Data_Manager`

### ✅ Étape 1.2 : Création du Renderer commun
- [x] Créer `Sisme_User_Dashboard_Renderer` dans `/user-dashboard/`
- [x] Extraire toutes les 18 méthodes de rendu de `user-dashboard-api.php`
- [x] Ajouter paramètres de contexte (public/privé) aux méthodes
- [x] Maintenir compatibilité exacte avec l'existant

### ✅ Étape 1.3 : Adaptation du dashboard existant
- [x] Modifier `user-dashboard-loader.php` pour charger le renderer
- [x] Modifier `user-dashboard-api.php` pour utiliser le nouveau renderer
- [x] Tests de non-régression sur le dashboard actuel - **VALIDÉS**
- [x] Validation des assets CSS/JS inchangés

---

## 📋 Phase 2 : Création du Module User-Profile

### ✅ Étape 2.1 : Structure du nouveau module
- [x] Créer répertoire `/user-profile/` (nouveau module profils publics)
- [x] Créer `user-profile-loader.php` (singleton pattern + réutilisation assets dashboard)
- [ ] Créer `user-profile-api.php` (API de rendu utilisant le renderer partagé)
- [ ] Créer `user-profile-permissions.php` (gestion visibilité public/privé/amis)

### 📊 Étape 2.2 : Logique de données
- [ ] Adapter utilisation `Sisme_User_Dashboard_Data_Manager` pour contexte public
- [ ] Créer filtres de données selon permissions (public/privé/amis)
- [ ] Gérer les cas d'utilisateurs inexistants/privés
- [ ] Implémenter logique de visibilité dans le renderer via `$context`

### 🎨 Étape 2.3 : Interface utilisateur
- [x] Réutiliser les assets CSS du dashboard (héritage automatique)
- [ ] Adapter sections selon contexte public (masquer paramètres)
- [ ] Adapter navigation pour contexte consultation (pas d'édition)
- [ ] Tester rendu avec différents niveaux de permissions

---

## 📋 Phase 3 : Système de Permissions

### 🔐 Étape 3.1 : Niveaux de visibilité
- [ ] **Public** : Visible par tous (défaut)
- [ ] **Privé** : Visible uniquement par le propriétaire
- [ ] **Amis** : Visible par les amis (futur module user-social)

### ⚡ Étape 3.2 : Logique d'accès
- [ ] Fonction `can_view_profile($viewer_id, $profile_user_id)`
- [ ] Gestion des erreurs d'accès (403, utilisateur inexistant)
- [ ] Cache des permissions par session

---

## 📋 Phase 4 : Intégration & Finalisation

### 🔗 Étape 4.1 : Chargement automatique
- [ ] Ajouter `user-profile` dans `user-loader.php`
- [ ] Tests de chargement et compatibilité modules existants

### 🚀 Étape 4.2 : APIs d'utilisation
- [ ] API programmatique : `Sisme_User_Profile_API::render_profile($user_id, $options)`
- [ ] Shortcode optionnel : `[sisme_user_profile id="123"]`
- [ ] Documentation des paramètres et options

### ✅ Étape 4.3 : Tests & Validation
- [ ] Tests unitaires des permissions
- [ ] Tests d'intégration avec dashboard existant
- [ ] Validation responsive et accessibilité

## 🎯 État Actuel - PHASE 1 TERMINÉE ✅

**Migration dashboard réussie !** Le système de renderer partagé est opérationnel :
- ✅ Dashboard existant fonctionne avec le nouveau renderer
- ✅ Infrastructure prête pour réutilisation profils publics
- ✅ 18 fonctions de rendu centralisées et paramétrables

---

## 🚀 Prochaines Étapes - PHASE 2 EN COURS

**Priorité 1 :** Créer `user-profile-permissions.php`
- Logique `can_view_profile($viewer_id, $profile_user_id)`
- Gestion 3 niveaux : public/privé/amis
- Fonctions de filtrage données selon permissions

**Priorité 2 :** Créer `user-profile-api.php`
- API principale `render_profile($user_id, $options)`
- Utilisation du renderer avec `$context` approprié
- Shortcode `[sisme_user_profile id="123"]`

**Priorité 3 :** Tests d'intégration
- Validation réutilisation parfaite composants dashboard
- Test différents niveaux de permissions
- Vérification non-régression dashboard

### Réutilisation des composants
```php
// Nouveau renderer partagé
Sisme_User_Dashboard_Renderer::render_header($user_info, $gaming_stats, $context);
Sisme_User_Dashboard_Renderer::render_activity_feed($activity_feed, $context);
Sisme_User_Dashboard_Renderer::render_recent_games($recent_games, $context);
```

### Gestion des contextes
```php
$context = [
    'is_public' => true,
    'viewer_id' => get_current_user_id(),
    'profile_user_id' => $user_id,
    'can_edit' => false,
    'show_private_data' => false
];
```

### Architecture finale
```
includes/user/
├── user-dashboard/
│   ├── user-dashboard-renderer.php     # ← Nouveau (composants communs)
│   ├── user-dashboard-api.php          # ← Modifié (utilise renderer)
│   └── ...
└── user-profile/                       # ← Nouveau module
    ├── user-profile-loader.php
    ├── user-profile-api.php
    ├── user-profile-permissions.php
    └── ...
```

---

## ⏱️ Estimation
- **Phase 1** : ~2-3h (refactorisation critique)
- **Phase 2** : ~3-4h (nouveau module)
- **Phase 3** : ~1-2h (permissions de base)
- **Phase 4** : ~1h (intégration finale)

**Total estimé** : 7-10h de développement