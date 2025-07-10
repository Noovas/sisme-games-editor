# 📋 Spécifications - Module "Devenir Développeur"

## 🎯 **Objectif**
Permettre aux utilisateurs de candidater pour devenir développeur et soumettre leurs jeux via le dashboard utilisateur.

---

## 🏗️ **Architecture Technique**

### **Structure des fichiers**
```
includes/user/user-developer/
├── user-developer-loader.php          # Loader principal
├── user-developer-api.php             # API et shortcodes
├── user-developer-data-manager.php    # Gestion données
├── user-developer-ajax.php            # Handlers AJAX
└── assets/
    ├── user-developer.css             # Styles
    └── user-developer.js              # JavaScript
```

### **Intégration dashboard**
- **Nouvel onglet** : "Développeur" (visible selon statut)
- **Sections** : Candidature, Mes Jeux, Statistiques
- **Réutilisation** : Renderer dashboard existant

---

## 📊 **Données et Métadonnées**

### **Métadonnées utilisateur**
```php
// Statut développeur
'sisme_user_developer_status' => 'none|pending|approved|rejected'

// Données candidature
'sisme_user_developer_application' => [
    'studio_name' => 'Mon Studio',
    'website' => 'https://monstudio.com',
    'description' => 'Description du studio...',
    'portfolio_links' => ['https://...', 'https://...'],
    'experience' => 'Expérience en développement...',
    'motivation' => 'Pourquoi rejoindre Sisme Games...',
    'contact_email' => 'contact@monstudio.com',
    'social_links' => [
        'twitter' => '@monstudio',
        'discord' => 'monstudio#1234'
    ],
    'submitted_date' => '2025-01-15 14:30:25',
    'reviewed_date' => '2025-01-16 09:15:00',
    'admin_notes' => 'Notes administrateur...'
]

// Profil développeur (une fois approuvé)
'sisme_user_developer_profile' => [
    'studio_name' => 'Mon Studio',
    'website' => 'https://monstudio.com',
    'bio' => 'Bio publique du studio...',
    'avatar_studio' => 'attachment_id',
    'verified' => true,
    'public_contact' => 'contact@monstudio.com'
]
```

### **Liaison jeu ↔ développeur**
```php
// Dans les game_meta (existant)
'game_submitted_by' => $user_id  // ID utilisateur soumetteur
'game_submission_status' => 'draft|pending|approved|rejected'
'game_submission_date' => '2025-01-15 14:30:25'
'game_admin_notes' => 'Notes admin sur le jeu...'
```

---

## 🔄 **Workflow Développeur**

### **Phase 1 : Candidature**
1. **Utilisateur** : Remplit formulaire candidature
2. **Système** : Sauvegarde avec statut `pending`
3. **Admin** : Valide/rejette depuis interface admin
4. **Notification** : Email + notification dashboard

### **Phase 2 : Développeur approuvé**
1. **Changement rôle** : Ajout capacité `submit_games`
2. **Onglet "Mes Jeux"** : Devient visible dans dashboard
3. **Interface soumission** : Formulaire création jeu
4. **Modération** : Jeux en attente validation admin

### **Phase 3 : Gestion continue**
1. **Mes Jeux** : Liste des jeux soumis avec statuts
2. **Statistiques** : Vues, téléchargements, likes
3. **Profil public** : Page développeur avec jeux

---

## 🎨 **Interface Utilisateur**

### **Onglet "Développeur" - États**

#### **État 1 : Utilisateur lambda**
```
[📝 Devenir Développeur]
- Formulaire candidature
- Avantages développeur
- Exemples profils
```

#### **État 2 : Candidature en cours**
```
[⏳ Candidature en cours]
- Statut de la demande
- Informations soumises
- Bouton "Modifier candidature"
```

#### **État 3 : Développeur approuvé**
```
[🎮 Mes Jeux]
- Liste jeux soumis
- Bouton "Ajouter un jeu"
- Statistiques globales
```

### **Sous-sections développeur**
- **📝 Candidature** : Formulaire initial
- **🎮 Mes Jeux** : Gestion jeux soumis
- **📊 Statistiques** : Analytics développeur
- **⚙️ Paramètres** : Profil développeur

---

## 🔒 **Permissions et Sécurité**

### **Capacités WordPress**
```php
// Nouvelles capacités
'submit_games'     // Soumettre des jeux
'edit_own_games'   // Modifier ses propres jeux
'view_game_stats'  // Voir statistiques jeux
```

### **Validations**
- **Anti-spam** : Limite 1 candidature par utilisateur
- **Validation email** : Vérification adresse studio
- **Modération** : Tous les jeux passent par admin
- **Sécurité** : Nonces, sanitisation, validation

---

## 🔧 **Intégration Système Existant**

### **Réutilisation dashboard**
- **Renderer** : `Sisme_User_Dashboard_Renderer` (nouvelles méthodes)
- **Data Manager** : Extension pour données développeur
- **Navigation** : Ajout onglet conditionnel

### **Réutilisation création jeu**
- **Formulaire** : `Sisme_Game_Form_Module` (adaptation frontend)
- **Validation** : Fonctions utils existantes
- **Sauvegarde** : Système meta existant

### **Notifications**
- **Candidature** : Email confirmation + admin
- **Validation** : Notification dashboard
- **Jeu soumis** : Notification admin

---

## 📋 **Admin Interface**

### **Page "Candidatures Développeur"**
- **Liste** : Toutes les candidatures avec statuts
- **Détails** : Vue complète candidature
- **Actions** : Approuver/Rejeter avec notes
- **Filtres** : Par statut, date, etc.

### **Extension "Game Data"**
- **Colonne** : Soumis par développeur
- **Filtre** : Jeux par développeur
- **Statut** : Validation jeux soumis

---

## 🎯 **Fonctionnalités Futures**

### **V1 - MVP**
- ✅ Candidature développeur
- ✅ Validation admin
- ✅ Soumission jeux basique
- ✅ Interface dashboard

### **V2 - Améliorations**
- 📊 Statistiques avancées
- 🏆 Système de badges
- 💬 Commentaires/reviews
- 🔄 Workflow publication

### **V3 - Avancé**
- 💰 Système de revenus
- 🎯 Analytics détaillées
- 🤝 Collaboration développeurs
- 📈 Promotion jeux

---

## 🔍 **Points d'Attention**

### **Technique**
- **Performance** : Cache pour listes de jeux
- **Sécurité** : Validation stricte uploads
- **Maintenance** : Code réutilisable et modulaire

### **UX**
- **Simplicité** : Processus candidature clair
- **Feedback** : Statuts visibles et explicites
- **Guidage** : Aide contextuelle

### **Business**
- **Qualité** : Processus de validation efficace
- **Engagement** : Inciter participation développeurs
- **Communauté** : Favoriser interactions

---

## 🚀 **Étapes d'Implémentation**

### **Phase 1 : Infrastructure**
1. Créer structure fichiers
2. Intégrer au dashboard
3. Système de candidature

### **Phase 2 : Soumission**
1. Adapter formulaire création jeu
2. Workflow validation admin
3. Interface "Mes Jeux"

### **Phase 3 : Optimisation**
1. Notifications système
2. Statistiques développeur
3. Profils publics

---

## 💡 **Dépendances Critiques**

### **À vérifier/débugger avant**
- ✅ Système création jeu admin
- ✅ Validation formulaire jeu
- ✅ Sauvegarde métadonnées
- ✅ Upload images/fichiers
- ✅ Système notifications

### **Utils nécessaires**
- `Sisme_Utils_Users` (gestion rôles)
- `Sisme_Utils_Games` (création jeux)
- `Sisme_Utils_Validation` (sécurité)
- `Sisme_Utils_Notifications` (alertes)

---

## 🎯 **Résultat Final**

Un système complet permettant aux développeurs de :
- **Candidater** facilement depuis leur dashboard
- **Soumettre** leurs jeux avec interface dédiée
- **Gérer** leur catalogue de jeux
- **Suivre** leurs performances

Tout en conservant la **qualité** et le **contrôle** pour les administrateurs.