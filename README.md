# 📚 Sisme Games Editor - Documentation API REF

**Version:** 1.0.0 | **Status:** Production

Documentation technique condensée pour tous les modules du plugin Sisme Games Editor.

---

## 📂 Architecture & Documentation

```
📁 sisme-games-editor/
├── 👤 user/                          # Système utilisateur modulaire
│   ├── 📄 user-readme.md             # → Master loader + sous-modules
│   ├── 🔐 user-auth-readme.md        # → Authentification & sécurité
│   ├── 👤 user-profile-readme.md     # → Profil complet + avatar
│   ├── ⚙️ user-preferences-readme.md # → Préférences gaming
│   └── 📊 user-dashboard-readme.md   # → Dashboard unifié
│
├── 🎴 cards/                         # Système de cartes de jeux
│   └── 📄 cards-readme.md           # → Cartes, grilles, carrousels
│
├── 🏷️ taxonomies/                   # Classification jeux & contenus
│   └── 📄 taxonomies-readme.md      # → Genres, plateformes, modes
│
├── 🎮 game-management/              # Gestion jeux & métadonnées
│   └── 📄 game-management-readme.md # → CRUD jeux, validation
│
└── 🔧 utils/                        # Utilitaires transversaux
    └── 📄 utils-readme.md           # → Helpers, formatage, cache
```

## 🚀 Accès Rapide

### Modules Core
- **[👤 User](docs/user/user-readme.md)** - Système utilisateur complet
- **[🎴 Cards](docs/cards/cards-readme.md)** - Rendu cartes de jeux

---

## 📖 Conventions

**Format :** Documentation technique condensée  
**Public :** Développeurs intégrant/étendant le plugin  
**Objectif :** Comprendre + utiliser en < 5 minutes

---

**🎯 Pour commencer :** Consultez [`👤 User`](docs/user/user-readme.md) ou [`🎴 Cards`](docs/cards/cards-readme.md)