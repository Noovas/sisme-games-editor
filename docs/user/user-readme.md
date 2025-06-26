# 👤 Module User - API Reference

**Version:** 1.0.1  
**Status:** user-auth complete, other submodules planned  

## Architecture

```
includes/user/
├── user-loader.php          # Master singleton loader
└── user-auth/              # ✅ Auth submodule (complete)
    ├── user-auth-loader.php
    ├── user-auth-security.php  
    ├── user-auth-handlers.php
    ├── user-auth-forms.php
    ├── user-auth-api.php
    └── assets/
└── user-profile/           # 🚧 Planned
└── user-library/           # 🚧 Planned  
└── user-social/            # 🚧 Planned
```

## Master Loader

### `Sisme_User_Loader`

**Singleton Pattern:**
```php
$loader = Sisme_User_Loader::get_instance();
```

**Methods:**
```php
get_active_modules()           // Returns array of loaded modules
is_module_loaded($module_name) // Check if specific module loaded
```

**Auto-loading Logic:**
- Scans for `user-{name}/user-{name}-loader.php`
- Instantiates `Sisme_User_{Name}_Loader::get_instance()`
- Logs loading status if WP_DEBUG enabled

## Submodules

### ✅ user-auth (Authentication)

**Status:** Production ready  
**Provides:**
- Frontend login/register forms
- Session management  
- Rate limiting & security
- User dashboard
- AJAX endpoints

**Shortcodes:**
- `[sisme_user_login]`
- `[sisme_user_register]` 
- `[sisme_user_profile]`
- `[sisme_user_menu]`

**See:** `user-auth/README.md` for complete API

### 🚧 user-profile (Profile Management)

**Planned Features:**
- Complete profile editing
- Avatar upload/management
- Gaming preferences
- Privacy settings
- Activity statistics

### 🚧 user-library (Game Library)

**Planned Features:**
- Personal game collection
- Favorites/wishlist management
- Game completion tracking
- Private reviews/notes
- Play time statistics

### 🚧 user-social (Social Features)

**Planned Features:**
- Public profile pages
- Friend system
- Shared wishlists
- Public reviews
- Community features

## Global User Meta Schema

**Profile Data:**
```php
'sisme_user_avatar'         => attachment_id     // Custom avatar
'sisme_user_bio'           => text              // User biography  
'sisme_profile_created'    => mysql_date        // Profile creation
'sisme_last_login'         => mysql_date        // Last login time
'sisme_profile_version'    => string            // Schema version
```

**Gaming Data:**
```php
'sisme_favorite_games'     => array(term_ids)   // Favorite games
'sisme_wishlist_games'     => array(term_ids)   // Wishlist
'sisme_completed_games'    => array(term_ids)   // Completed games
'sisme_user_reviews'       => array(data)       // Private reviews
'sisme_gaming_platforms'   => array(strings)    // ['PC', 'PS5', ...]
'sisme_favorite_genres'    => array(term_ids)   // Preferred genres
```

**Settings:**
```php
'sisme_notifications_email'=> boolean           // Email notifications
'sisme_privacy_level'      => string            // 'public'|'friends'|'private'
```

## Integration Points

**Data Sources:**
- Uses existing term_ids from plugin taxonomy
- Compatible with existing game/genre structure
- Integrates with plugin's CSS variables

**WordPress Integration:**
- Standard user_meta for data storage
- WordPress nonces for security
- WordPress hooks for extensibility
- WordPress transients for caching

## Hooks & Extensions

**Global User Hooks:**
```php
// User registration (all modules)
do_action('sisme_user_register', $user_id);
do_action('sisme_user_login', $user_login, $user);

// Metadata initialization
do_action('sisme_user_init_meta', $user_id, $default_meta);
```

**Module-Specific Hooks:**
Each submodule provides its own hooks. See individual README files.

## Development

**Adding New Submodules:**

1. Create directory: `includes/user/user-{name}/`
2. Create loader: `user-{name}-loader.php`
3. Implement class: `Sisme_User_{Name}_Loader`
4. Add `get_instance()` method
5. Master loader auto-detects and loads

**Example Structure:**
```php
class Sisme_User_Profile_Loader {
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init();
    }
}
```

## Configuration

**Module Loading:**
Ensure 'user' is in `SISME_GAMES_MODULES` array in main plugin file.

**Required Constants:**
- `SISME_GAMES_EDITOR_PLUGIN_DIR`
- `SISME_GAMES_EDITOR_PLUGIN_URL` 
- `SISME_GAMES_EDITOR_VERSION`

**Dependencies:**
- WordPress 5.0+
- PHP 7.4+
- Main Sisme Games Editor plugin
- jQuery (WordPress core)

## Monitoring

**Debug Logs:**
```bash
[Sisme User] Master loader initialisé avec succès
[Sisme User] Module 'Authentification' initialisé : Sisme_User_Auth_Loader
[Sisme User] Module 'user-profile' non trouvé : {path}
```

**Statistics:**
```php
$stats = [
    'total_users' => wp_count_users()['total_users'],
    'users_with_profiles' => count(get_users(['meta_key' => 'sisme_profile_created'])),
    'active_modules' => $loader->get_active_modules()
];
```