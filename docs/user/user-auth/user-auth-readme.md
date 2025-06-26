# 🔐 Module User Auth - API Reference

**Version:** 1.0.3  
**Status:** Production Ready  

## Architecture

```
includes/user/user-auth/
├── user-auth-loader.php     # Singleton loader
├── user-auth-security.php   # Validation & rate limiting  
├── user-auth-handlers.php   # POST processing & user creation
├── user-auth-forms.php      # Form components & rendering
├── user-auth-api.php        # Shortcodes & public API
└── assets/                  # CSS/JS assets
```

## Classes & Methods

### `Sisme_User_Auth_Loader`

**Singleton Pattern**
```php
$loader = Sisme_User_Auth_Loader::get_instance();
```

**Methods:**
- `force_load_assets()` - Load CSS/JS manually
- `are_assets_loaded()` - Check if assets loaded
- `get_version()` - Get module version

### `Sisme_User_Auth_Security`

**Rate Limiting:**
```php
validate_login_attempt($email) // Returns true|WP_Error
record_failed_attempt($email)  // Increment attempt counter
clear_failed_attempts($email)  // Reset on success
```

**Validation:**
```php
validate_user_data($data, $context) // $context: 'login'|'register'
sanitize_user_data($data)          // Clean all inputs
get_security_stats()               // Stats array
```

**Configuration:**
- Max attempts: 5
- Lockout time: 15 minutes  
- Cleanup: hourly cron

### `Sisme_User_Auth_Handlers`

**Core Functions:**
```php
handle_login($data)    // Process login, returns array|WP_Error
handle_register($data) // Create user, returns array|WP_Error
handle_logout($user_id) // Custom logout with hooks
```

**Session Management:**
```php
set_auth_message($message, $type)  // Store flash message
get_auth_message()                 // Retrieve & clear message
```

**User Meta Initialization:**
```php
get_default_user_meta() // Default metadata structure
```

### `Sisme_User_Auth_Forms`

**Constructor:**
```php
new Sisme_User_Auth_Forms($components, $options)
```

**Components Available:**
- `user_email` - Email field (required)
- `user_password` - Password field (required)  
- `user_confirm_password` - Password confirmation
- `user_display_name` - Display name (optional)
- `remember_me` - Remember checkbox
- `redirect_to` - Hidden redirect URL

**Options:**
- `type` - 'login'|'register'
- `submit_text` - Button text
- `action` - Form action URL
- `redirect_to` - Redirect URL

**Methods:**
```php
$form->render()              // Output complete form HTML
$form->is_submitted()        // Check if form was submitted  
$form->get_submitted_data()  // Get sanitized form data
$form->validate()           // Validate form data
```

**Static Helpers:**
```php
Sisme_User_Auth_Forms::create_login_form($options)
Sisme_User_Auth_Forms::create_register_form($options)
```

### `Sisme_User_Auth_API`

**Shortcodes:**

#### `[sisme_user_login]`
```php
[sisme_user_login 
    title="Connexion"
    subtitle="Subtitle text"
    submit_text="Se connecter"
    show_register_link="true"
    register_link_text="Pas de compte ?"
    show_remember="true"
    container_class="custom-class"]
```

#### `[sisme_user_register]`  
```php
[sisme_user_register
    title="Inscription" 
    subtitle="Subtitle text"
    submit_text="Créer compte"
    show_login_link="true"
    login_link_text="Déjà membre ?"
    container_class="custom-class"]
```

#### `[sisme_user_profile]`
```php
[sisme_user_profile
    show_favorites="true"
    show_activity="true" 
    show_recommendations="false"
    container_class="custom-class"]
```

#### `[sisme_user_menu]`
```php
[sisme_user_menu
    show_avatar="true"
    show_logout="true"
    login_text="Connexion"
    profile_text="Mon profil"
    logout_text="Déconnexion"]
```

## WordPress Integration

**Required Pages:**
- `/sisme-user-login/` → `[sisme_user_login]`
- `/sisme-user-register/` → `[sisme_user_register]`
- `/sisme-user-tableau-de-bord/` → `[sisme_user_profile]`

**Hooks Available:**
```php
// Actions
do_action('sisme_user_login_success', $user_id, $data);
do_action('sisme_user_register_success', $user_id, $data);
do_action('sisme_user_logout', $user_id);
do_action('sisme_user_init_meta', $user_id, $default_meta);

// Filters  
apply_filters('sisme_user_login_redirect', $url);
apply_filters('sisme_user_register_redirect', $url);
apply_filters('sisme_user_logout_redirect', $url);
```

**AJAX Endpoints:**
- `wp_ajax_nopriv_sisme_user_login`
- `wp_ajax_nopriv_sisme_user_register`

## User Metadata Structure

```php
'sisme_user_avatar'         => attachment_id
'sisme_user_bio'           => text
'sisme_profile_created'    => mysql_date
'sisme_last_login'         => mysql_date
'sisme_last_logout'        => mysql_date
'sisme_favorite_games'     => array(term_ids)
'sisme_wishlist_games'     => array(term_ids)  
'sisme_completed_games'    => array(term_ids)
'sisme_user_reviews'       => array(data)
'sisme_gaming_platforms'   => array(strings)
'sisme_favorite_genres'    => array(term_ids)
'sisme_notifications_email'=> boolean
'sisme_privacy_level'      => 'public'|'friends'|'private'
'sisme_profile_version'    => string
```

## CSS Classes

**Form Structure:**
```css
.sisme-user-auth-container
  .sisme-auth-card
    .sisme-auth-header
    .sisme-auth-content
      .sisme-user-auth-form
        .sisme-auth-form-fields
          .sisme-auth-field
            .sisme-auth-label
            .sisme-auth-input
        .sisme-auth-form-actions
          .sisme-auth-submit
    .sisme-auth-footer
```

**State Classes:**
- `.sisme-auth-input--error`
- `.sisme-auth-input--valid` 
- `.sisme-auth-input--focus`
- `.sisme-auth-message--error`
- `.sisme-auth-message--success`

## JavaScript API

**Global Object:**
```javascript
window.sismeUserAuth = {
    ajax_url: string,
    nonce: string,
    messages: object,
    config: object,
    debug: boolean
}
```

**Form Validation:**
- Real-time validation with 300ms debounce
- Password confirmation matching
- Email format validation
- Required field validation

## Dependencies

- WordPress 5.0+
- PHP 7.4+
- jQuery (WordPress core)
- Parent User module
- CSS variables from main plugin

## Technical Notes

**Form Processing:**
- Uses `wp_loaded` hook + direct call for compatibility
- Hidden input for submit detection (button name unreliable)
- Session-based flash messages
- Nonce validation on all forms

**Security:**
- Rate limiting via WordPress transients
- IP + email combined tracking
- Password strength validation
- Email domain blacklisting
- XSS protection via `esc_*` functions