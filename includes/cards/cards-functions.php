<?php
/**
 * File: /sisme-games-editor/includes/cards/cards-functions.php
 * Fonctions utilitaires partagées pour tous les types de cartes
 * 
 * RESPONSABILITÉ:
 * - Récupération des données de jeux
 * - Fonctions de formatage
 * - Utilitaires de calcul (badges, dates, etc.)
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('DAY_IN_SECONDS')) {
    define('DAY_IN_SECONDS', 24 * 60 * 60); // 86400 secondes
}

class Sisme_Cards_Functions {


    
    /**
     * 📊 Récupérer les données complètes d'un jeu
     * 
     * @param int $term_id ID du jeu
     * @return array|false Données du jeu ou false si incomplet
     */
    public static function get_game_data($term_id) {
        
        // Vérifications de base
        $description = get_term_meta($term_id, 'game_description', true);
        $cover_id = get_term_meta($term_id, 'cover_main', true);

        $release_date = get_term_meta($term_id, 'release_date', true);
    	$game_data['release_date'] = $release_date ?: '';

    	if (!empty($release_date)) {
	        $game_data['timestamp'] = strtotime($release_date);
	    } else {
	        $game_data['timestamp'] = 0;
	    }
	        
        if (empty($description) || empty($cover_id)) {
            return false;
        }
        
        // URL de la cover
        $cover_url = wp_get_attachment_image_url($cover_id, 'full');
        if (!$cover_url) {
            return false;
        }
        
        // Récupérer les infos de base
        $term = get_term($term_id);
        
        // Construire les données
        $game_data = array(
            'term_id' => $term_id,
            'name' => $term->name,
            'slug' => $term->slug,
            'description' => wp_strip_all_tags($description),
            'cover_url' => $cover_url,
            'game_url' => home_url($term->slug . '/'),
            'genres' => self::get_game_genres($term_id),
            'modes' => self::get_game_modes($term_id),               
        	'platforms' => self::get_game_platforms_grouped($term_id),   
            'release_date' => get_term_meta($term_id, 'release_date', true),
            'last_update' => get_term_meta($term_id, 'last_update', true),
            'timestamp' => self::get_game_timestamp($term_id),

        );
        
        return $game_data;
    }

    /**
	 * 🛠️ FONCTION UTILITAIRE : Déterminer le statut depuis une date
	 * 
	 * @param string $release_date Date au format YYYY-MM-DD
	 * @return array Informations sur le statut
	 */
	private static function get_game_release_status_from_date($release_date) {
	    if (empty($release_date)) {
	        return array('is_released' => true);
	    }
	    
	    $release_timestamp = strtotime($release_date);
	    $current_timestamp = current_time('timestamp');
	    
	    return array(
	        'is_released' => $current_timestamp >= $release_timestamp,
	        'days_diff' => floor(($current_timestamp - $release_timestamp) / DAY_IN_SECONDS)
	    );
	}

    /**
	 * 🆕 FONCTION AVEC STATUT : Affichage avec indication du statut
	 * 
	 * @param string $release_date Date au format YYYY-MM-DD
	 * @param bool $show_status Afficher le statut (Sorti/À venir)
	 * @return string Date formatée avec statut optionnel
	 */
	public static function format_release_date_with_status($release_date, $show_status = false) {
	    if (empty($release_date)) {
	        return '';
	    }
	    
	    $date = DateTime::createFromFormat('Y-m-d', $release_date);
	    if (!$date) {
	        return '';
	    }
	    
	    // Formater la date
	    $formatted_date = self::format_release_date($release_date);
	    
	    if (!$show_status) {
	        return $formatted_date;
	    }
	    
	    // Ajouter le statut si demandé
	    $status_info = self::get_game_release_status_from_date($release_date);
	    
	    if ($status_info['is_released']) {
	        return '✅ ' . $formatted_date;
	    } else {
	        return '📅 ' . $formatted_date;
	    }
	}

    /**
	 * Formater la date de sortie pour affichage
	 * 
	 * @param string $release_date Date au format YYYY-MM-DD
	 * @return string Date formatée pour affichage ou chaîne vide
	 */
	public static function format_release_date($release_date) {
	    if (empty($release_date)) {
	        return '';
	    }
	    
	    // Vérifier le format de la date
	    $date = DateTime::createFromFormat('Y-m-d', $release_date);
	    if (!$date) {
	        return '';
	    }
	    
	    // Mois en français
	    $mois_fr = array(
	        1 => 'janv', 2 => 'févr', 3 => 'mars', 4 => 'avr',
	        5 => 'mai', 6 => 'juin', 7 => 'juil', 8 => 'août',
	        9 => 'sept', 10 => 'oct', 11 => 'nov', 12 => 'déc'
	    );
	    
	    $jour = $date->format('j');
	    $mois_num = (int)$date->format('n');
	    $annee = $date->format('Y');
	    
	    // Format compact : "15 déc 2024"
	    return $jour . ' ' . $mois_fr[$mois_num] . ' ' . $annee;
	}

	/**
	 * Format long pour certains contextes
	 * 
	 * @param string $release_date Date au format YYYY-MM-DD
	 * @return string Date formatée format long
	 */
	public static function format_release_date_long($release_date) {
	    if (empty($release_date)) {
	        return '';
	    }
	    
	    $date = DateTime::createFromFormat('Y-m-d', $release_date);
	    if (!$date) {
	        return '';
	    }
	    
	    // Mois complets en français
	    $mois_complets = array(
	        1 => 'janvier', 2 => 'février', 3 => 'mars', 4 => 'avril',
	        5 => 'mai', 6 => 'juin', 7 => 'juillet', 8 => 'août',
	        9 => 'septembre', 10 => 'octobre', 11 => 'novembre', 12 => 'décembre'
	    );
	    
	    $jour = $date->format('j');
	    $mois_num = (int)$date->format('n');
	    $annee = $date->format('Y');
	    
	    // Format long : "15 décembre 2024"
	    return $jour . ' ' . $mois_complets[$mois_num] . ' ' . $annee;
	}

    /**
	 * 🎮 Récupérer les plateformes groupées par famille
	 * Retourne un tableau avec les groupes et les détails pour tooltips
	 */
	public static function get_game_platforms_grouped($term_id) {
	    $platforms = get_term_meta($term_id, 'game_platforms', true) ?: array();
	    
	    if (empty($platforms)) {
	        return array();
	    }
	    
	    // Définition des groupes
	    $groups = array(
	        'pc' => array(
	            'platforms' => array('windows', 'mac', 'linux'),
	            'icon' => '💻',
	            'label' => 'PC'
	        ),
	        'console' => array(
	            'platforms' => array('xbox', 'playstation', 'switch'),
	            'icon' => '🎮', 
	            'label' => 'Console'
	        ),
	        'mobile' => array(
	            'platforms' => array('ios', 'android'),
	            'icon' => '📱',
	            'label' => 'Mobile'
	        ),
	        'web' => array(
	            'platforms' => array('web'),
	            'icon' => '🌐',
	            'label' => 'Web'
	        )
	    );
	    
	    // Noms complets pour tooltips
	    $platform_names = array(
	        'windows' => 'Windows',
	        'mac' => 'macOS', 
	        'linux' => 'Linux',
	        'xbox' => 'Xbox',
	        'playstation' => 'PlayStation',
	        'switch' => 'Nintendo Switch',
	        'ios' => 'iOS',
	        'android' => 'Android',
	        'web' => 'Navigateur Web'
	    );
	    
	    $grouped_platforms = array();
	    
	    foreach ($groups as $group_key => $group_data) {
	        $found_platforms = array_intersect($platforms, $group_data['platforms']);
	        
	        if (!empty($found_platforms)) {
	            $platform_details = array();
	            foreach ($found_platforms as $platform) {
	                if (isset($platform_names[$platform])) {
	                    $platform_details[] = $platform_names[$platform];
	                }
	            }
	            
	            $grouped_platforms[] = array(
	                'group' => $group_key,
	                'icon' => $group_data['icon'],
	                'label' => $group_data['label'],
	                'platforms' => $found_platforms,
	                'tooltip' => implode(', ', $platform_details)
	            );
	        }
	    }
	    
	    return $grouped_platforms;
	}

	/**
	 * 🎯 Récupérer les modes de jeu
	 */
	public static function get_game_modes($term_id) {
	    $modes = get_term_meta($term_id, 'game_modes', true) ?: array();
	    
	    if (empty($modes)) {
	        return array();
	    }
	    
	    // Traduction des modes
	    $mode_labels = array(
	        'solo' => 'Solo',
	        'multijoueur' => 'Multijoueur',
	        'coop' => 'Coopération',
	        'competitif' => 'Compétitif',
	        'online' => 'En ligne',
	        'local' => 'Local'
	    );
	    
	    $formatted_modes = array();
	    foreach ($modes as $mode) {
	        if (isset($mode_labels[$mode])) {
	            $formatted_modes[] = array(
	                'key' => $mode,
	                'label' => $mode_labels[$mode]
	            );
	        }
	    }
	    
	    return $formatted_modes;
	}
    
    /**
     * 🏷️ Récupérer les genres du jeu
     */
    public static function get_game_genres($term_id) {
        $genre_ids = get_term_meta($term_id, 'game_genres', true) ?: array();
        $genres = array();
        foreach ($genre_ids as $genre_id) {
            $genre = get_category($genre_id);
            if ($genre) {
                $genres[] = array(
                    'id' => $genre_id,
                    'name' => str_replace('jeux-', '', $genre->name), // Nettoyer le préfixe
                    'slug' => $genre->slug
                );
            }
        }
        
        return $genres;
    }
    
    /**
     * 🎮 Récupérer les plateformes du jeu
     */
    public static function get_game_platforms($term_id) {
        return get_term_meta($term_id, 'game_platforms', true) ?: array();
    }
    
    /**
     * ⏰ Déterminer le timestamp de référence du jeu
     */
    public static function get_game_timestamp($term_id) {
        $last_update = get_term_meta($term_id, 'last_update', true);
        $release_date = get_term_meta($term_id, 'release_date', true);
        
        // Priorité : last_update > release_date > maintenant
        if ($last_update) {
            return strtotime($last_update);
        } elseif ($release_date) {
            return strtotime($release_date);
        } else {
            return time();
        }
    }
    
    /**
	 * 🏷️ Déterminer le badge du jeu selon sa fraîcheur
	 */
	public static function get_game_badge($game_data) {
	    $now = time();
	    $release_time = strtotime($game_data['release_date']); 
	    $last_update_time = strtotime($game_data['last_update']);
	    
	    // Calcul basé sur la date de sortie
	    $diff_days = floor(($now - $release_time) / 86400);
	    
	    if ($diff_days > 0 && $diff_days <= 7) {
	        return array('class' => 'sisme-badge-new', 'text' => 'NOUVEAU');
	    } elseif ($diff_days < 0) {
	        return array('class' => 'sisme-badge-futur', 'text' => 'À VENIR');
	    } elseif ($diff_days == 0) {
	        return array('class' => 'sisme-badge-today', 'text' => 'AUJOURD\'HUI');
	    }
	    
	    // Vérifier les mises à jour séparément
	    if ($last_update_time) {
	        $update_diff = floor(($now - $last_update_time) / 86400);
	        if ($update_diff <= 30) {
	            return array('class' => 'sisme-badge-updated', 'text' => 'MIS À JOUR');
	        }
	    }
	    
	    return array('class' => 'sisme-display__none', 'text' => '');
	}
    
    /**
	 * ⚠️ CONSERVER L'ANCIENNE FONCTION pour compatibilité (mais marquée comme deprecated)
	 * 
	 * @deprecated Utiliser format_release_date() à la place
	 */
	public static function format_relative_date($timestamp) {
	    // Conserver pour compatibilité avec autres modules
	    $now = current_time('timestamp');
	    $diff = $now - $timestamp;
	    
	    if ($diff < DAY_IN_SECONDS) {
	        return 'Aujourd\'hui';
	    } elseif ($diff < 2 * DAY_IN_SECONDS) {
	        return 'Hier';
	    } elseif ($diff < 7 * DAY_IN_SECONDS) {
	        return 'Il y a ' . floor($diff / DAY_IN_SECONDS) . ' jours';
	    } elseif ($diff < 30 * DAY_IN_SECONDS) {
	        return 'Il y a ' . floor($diff / (7 * DAY_IN_SECONDS)) . ' semaines';
	    } else {
	        return 'Il y a ' . floor($diff / (30 * DAY_IN_SECONDS)) . ' mois';
	    }
	}
    
    /**
     * ✂️ Tronquer intelligemment un texte sur les mots
     */
    public static function truncate_smart($text, $max_length) {
        if (strlen($text) <= $max_length) {
            return $text;
        }
        
        $truncated = substr($text, 0, $max_length);
        $last_space = strrpos($truncated, ' ');
        
        if ($last_space !== false) {
            $truncated = substr($truncated, 0, $last_space);
        }
        
        return rtrim($truncated, '.,;:!?') . '...';
    }
    
    /**
     * 🎮 Obtenir l'icône d'une plateforme
     */
    public static function get_platform_icon($platform) {
        $icons = array(
            'windows' => '🖥️',
            'mac' => '🖥️',
            'linux' => '🖥️',
            'playstation' => '🎮',
            'xbox' => '🎮',
            'nintendo-switch' => '🎮',
            'ios' => '📱',
            'android' => '📱',
            'web' => '🌐'
        );
        
        return isset($icons[$platform]) ? $icons[$platform] : '💻';
    }
    
    /**
     * 🎨 Générer une classe CSS avec options
     */
    public static function build_css_class($base_class, $modifiers = array(), $custom_class = '') {
        $classes = array($base_class);
        
        // Ajouter les modificateurs
        foreach ($modifiers as $modifier) {
            if (!empty($modifier)) {
                $classes[] = $base_class . '--' . $modifier;
            }
        }
        
        // Ajouter la classe personnalisée
        if (!empty($custom_class)) {
            $classes[] = $custom_class;
        }
        
        return implode(' ', $classes);
    }

    /**
	 * 🔍 Récupérer les IDs des jeux selon les critères
	 * 
	 * @param array $criteria Critères de recherche
	 * @return array IDs des jeux trouvés
	 */
	public static function get_games_by_criteria($criteria = array()) {
        
        // Critères par défaut (MODIFIÉ)
        $default_criteria = array(
            'genres' => array(),
            'is_team_choice' => false,
            'sort_by_date' => true,
            'sort_order' => 'desc',        // ✅ NOUVEAU : ordre de tri
            'max_results' => -1,
            'released' => 0,
            'debug' => false
        );
        
        $criteria = array_merge($default_criteria, $criteria);
        
        // Validation du paramètre sort_order
        if (!in_array($criteria['sort_order'], ['asc', 'desc'])) {
            $criteria['sort_order'] = 'desc';
        }
        
        if ($criteria['debug']) {
            error_log('[Sisme Cards Functions] Critères reçus: ' . print_r($criteria, true));
        }
        
        // Récupérer tous les jeux avec métadonnées
        $all_games = get_terms(array(
            'taxonomy' => 'post_tag',
            'hide_empty' => false,
            'fields' => 'ids',
            'meta_query' => array(
                array(
                    'key' => 'game_description',
                    'compare' => 'EXISTS'
                )
            )
        ));
        
        if (is_wp_error($all_games) || empty($all_games)) {
            return array();
        }
        
        // Filtrer par genres si spécifiés
        if (!empty($criteria['genres'])) {
            $genre_query = self::build_genres_meta_query($criteria['genres']);
            if (!empty($genre_query)) {
                $genre_filtered = get_terms(array(
                    'taxonomy' => 'post_tag',
                    'hide_empty' => false,
                    'fields' => 'ids',
                    'meta_query' => $genre_query
                ));
                
                // Intersection des deux résultats
                $all_games = array_intersect($all_games, $genre_filtered);
            }
        }
        
        // Filtrer par choix équipe
        if ($criteria['is_team_choice']) {
            $team_choice_games = array();
            foreach ($all_games as $game_id) {
                $is_team_choice = get_term_meta($game_id, 'is_team_choice', true);
                if ($is_team_choice) {
                    $team_choice_games[] = $game_id;
                }
            }
            $all_games = $team_choice_games;
        }
        
        // Filtrer par statut de sortie
        $filtered_games = array();
        foreach ($all_games as $game_id) {
            $should_include = true;
            
            if ($criteria['released'] !== 0) {
                $release_status = self::get_game_release_status($game_id);
                
                if ($criteria['released'] === 1 && !$release_status['is_released']) {
                    $should_include = false;
                } elseif ($criteria['released'] === -1 && $release_status['is_released']) {
                    $should_include = false;
                }
                
                if ($criteria['debug'] && $should_include) {
                    error_log('[Sisme Cards Functions] Jeu ' . $game_id . ' inclus: ' . 
                             ($release_status['is_released'] ? 'SORTI' : 'PAS ENCORE SORTI') . 
                             " (date: {$release_status['release_date']})");
                }
            }
            
            // Validation données complètes
            if ($should_include) {
                $game_data = self::get_game_data($game_id);
                if ($game_data) {
                    $filtered_games[] = $game_id;
                }
            }
        }
        
        if ($criteria['debug']) {
            error_log('[Sisme Cards Functions] ' . count($filtered_games) . ' jeux après filtrage (released=' . $criteria['released'] . ')');
        }
        
        // ✅ TRI PAR DATE avec ordre spécifique (MODIFIÉ)
        if ($criteria['sort_by_date']) {
            $filtered_games = self::sort_games_by_release_date($filtered_games, $criteria['sort_order']);
            
            if ($criteria['debug']) {
                error_log('[Sisme Cards Functions] Tri appliqué: ordre ' . $criteria['sort_order']);
            }
        }
        
        // Limite si spécifiée
        if ($criteria['max_results'] > 0) {
            $filtered_games = array_slice($filtered_games, 0, $criteria['max_results']);
        }
        
        return $filtered_games;
    }

	/**
	 * Déterminer le statut de sortie d'un jeu
	 * 
	 * @param int $term_id ID du jeu
	 * @return array ['is_released' => bool, 'release_date' => string, 'days_diff' => int]
	 */
	public static function get_game_release_status($term_id) {
	    $release_date = get_term_meta($term_id, 'release_date', true);
	    
	    // Valeurs par défaut si pas de date
	    if (empty($release_date)) {
	        return array(
	            'is_released' => true,      // Par défaut, considérer comme sorti
	            'release_date' => '',
	            'days_diff' => 0,
	            'status_text' => 'Date inconnue'
	        );
	    }
	    
	    // Convertir en timestamp
	    $release_timestamp = strtotime($release_date);
	    $current_timestamp = current_time('timestamp');
	    
	    // Calculer la différence en jours
	    $days_diff = floor(($current_timestamp - $release_timestamp) / DAY_IN_SECONDS);
	    
	    $is_released = $current_timestamp >= $release_timestamp;
	    
	    // Texte de statut pour debug/affichage
	    if ($is_released) {
	        if ($days_diff === 0) {
	            $status_text = 'Sorti aujourd\'hui';
	        } elseif ($days_diff === 1) {
	            $status_text = 'Sorti hier';
	        } else {
	            $status_text = "Sorti il y a {$days_diff} jours";
	        }
	    } else {
	        $abs_days = abs($days_diff);
	        if ($abs_days === 0) {
	            $status_text = 'Sort aujourd\'hui';
	        } elseif ($abs_days === 1) {
	            $status_text = 'Sort demain';
	        } else {
	            $status_text = "Sort dans {$abs_days} jours";
	        }
	    }
	    
	    return array(
	        'is_released' => $is_released,
	        'release_date' => $release_date,
	        'days_diff' => $days_diff,
	        'status_text' => $status_text
	    );
	}

	/**
	 * Obtenir des statistiques par statut de sortie
	 * 
	 * @return array Statistiques complètes
	 */
	public static function get_release_status_stats() {
	    $all_games = get_terms(array(
	        'taxonomy' => 'post_tag',
	        'hide_empty' => false,
	        'meta_query' => array(
	            array(
	                'key' => 'game_description',
	                'compare' => 'EXISTS'
	            )
	        )
	    ));
	    
	    $stats = array(
	        'total' => 0,
	        'released' => 0,
	        'unreleased' => 0,
	        'no_date' => 0,
	        'released_this_week' => 0,
	        'releasing_this_week' => 0
	    );
	    
	    if (is_wp_error($all_games) || empty($all_games)) {
	        return $stats;
	    }
	    
	    $current_timestamp = current_time('timestamp');
	    $week_start = $current_timestamp - (7 * DAY_IN_SECONDS);
	    $week_end = $current_timestamp + (7 * DAY_IN_SECONDS);
	    
	    foreach ($all_games as $game) {
	        $stats['total']++;
	        
	        $status = self::get_game_release_status($game->term_id);
	        
	        if (empty($status['release_date'])) {
	            $stats['no_date']++;
	            continue;
	        }
	        
	        $release_timestamp = strtotime($status['release_date']);
	        
	        if ($status['is_released']) {
	            $stats['released']++;
	            
	            // Sorti cette semaine ?
	            if ($release_timestamp >= $week_start) {
	                $stats['released_this_week']++;
	            }
	        } else {
	            $stats['unreleased']++;
	            
	            // Sort cette semaine ?
	            if ($release_timestamp <= $week_end) {
	                $stats['releasing_this_week']++;
	            }
	        }
	    }
	    
	    return $stats;
	}

	/**
	 * ✅ Vérifier si un terme a les genres demandés
	 * 
	 * @param int $term_id ID du terme
	 * @param array $requested_genres Genres demandés (noms, slugs ou IDs)
	 * @return bool True si le terme a au moins un des genres demandés
	 */
	private static function term_has_genres($term_id, $requested_genres) {
	    
	    // Récupérer les IDs de genres du jeu
	    $game_genre_ids = get_term_meta($term_id, 'game_genres', true);
	    if (empty($game_genre_ids) || !is_array($game_genre_ids)) {
	        return false;
	    }
	    
	    // Convertir les genres demandés en IDs
	    $requested_genre_ids = self::convert_genres_to_ids($requested_genres);
	    if (empty($requested_genre_ids)) {
	        return false;
	    }
	    
	    // Vérifier s'il y a une intersection
	    $intersection = array_intersect($game_genre_ids, $requested_genre_ids);
	    return !empty($intersection);
	}

	/**
	 * 🎨 Convertir une liste de genres (noms/slugs/IDs) en IDs de catégories
	 * 
	 * @param array $genres Liste des genres
	 * @return array IDs de catégories
	 */
	private static function convert_genres_to_ids($genres) {
	    $genre_ids = array();
	    
	    foreach ($genres as $genre) {
	        $genre = trim($genre);
	        if (empty($genre)) {
	            continue;
	        }
	        
	        // Si c'est déjà un ID numérique
	        if (is_numeric($genre)) {
	            $genre_ids[] = intval($genre);
	            continue;
	        }
	        
	        // Sinon, chercher la catégorie par nom/slug
	        $category = null;
	        
	        // Essayer par slug exact
	        $category = get_category_by_slug($genre);
	        
	        // Essayer par slug avec préfixe "jeux-"
	        if (!$category) {
	            $category = get_category_by_slug('jeux-' . strtolower($genre));
	        }
	        
	        // Essayer par nom exact
	        if (!$category) {
	            $category = get_term_by('name', $genre, 'category');
	        }
	        
	        // Essayer par nom avec préfixe "jeux-"
	        if (!$category) {
	            $category = get_term_by('name', 'jeux-' . ucfirst(strtolower($genre)), 'category');
	        }
	        
	        // Essayer par nom sans casse
	        if (!$category) {
	            $category = get_term_by('name', ucfirst(strtolower($genre)), 'category');
	        }
	        
	        if ($category) {
	            $genre_ids[] = $category->term_id;
	        }
	    }
	    
	    return array_unique($genre_ids);
	}

	/**
	 * 🎭 Construire la meta_query selon les critères
	 * 
	 * @param array $criteria Critères de filtrage
	 * @return array Meta query pour get_terms()
	 */
	private static function build_criteria_meta_query($criteria) {
	    $meta_query = array();
	    
	    // Filtre par genres
	    if (!empty($criteria['genres'])) {
	        $genre_query = self::build_genres_meta_query($criteria['genres']);
	        if (!empty($genre_query)) {
	            $meta_query[] = $genre_query;
	        }
	    }
	    
	    // Filtre is_team_choice (quand disponible)
	    if ($criteria['is_team_choice']) {
	        $meta_query[] = array(
	            'key' => 'is_team_choice',
	            'value' => '1',
	            'compare' => '='
	        );
	        
	        if ($criteria['debug']) {
	            error_log('[Sisme Cards Functions] Filtre is_team_choice ajouté (si meta existe)');
	        }
	    }
	    
	    // Relation ET entre les critères
	    if (count($meta_query) > 1) {
	        $meta_query['relation'] = 'AND';
	    }
	    
	    return $meta_query;
	}

	/**
	 * 🎨 Construire la meta_query pour filtrer par genres
	 * 
	 * @param array $genres Liste des genres (slugs, IDs ou noms)
	 * @return array Meta query pour les genres
	 */
	private static function build_genres_meta_query($genres) {
	    if (empty($genres)) {
	        return array();
	    }
	    
	    // Convertir tous les genres en IDs de catégories
	    $genre_ids = array();
	    
	    foreach ($genres as $genre) {
	        $genre = trim($genre);
	        if (empty($genre)) {
	            continue;
	        }
	        
	        // Si c'est un ID numérique, l'utiliser directement
	        if (is_numeric($genre)) {
	            $genre_ids[] = intval($genre);
	        } else {
	            // Si c'est un nom/slug, trouver l'ID de la catégorie
	            $category = null;
	            
	            // Essayer par slug
	            $category = get_category_by_slug($genre);
	            if (!$category) {
	                // Essayer par slug avec préfixe "jeux-"
	                $category = get_category_by_slug('jeux-' . strtolower($genre));
	            }
	            if (!$category) {
	                // Essayer par nom exact
	                $category = get_term_by('name', $genre, 'category');
	            }
	            if (!$category) {
	                // Essayer par nom avec préfixe "jeux-"
	                $category = get_term_by('name', 'jeux-' . ucfirst(strtolower($genre)), 'category');
	            }
	            
	            if ($category) {
	                $genre_ids[] = $category->term_id;
	            }
	        }
	    }
	    
	    if (empty($genre_ids)) {
	        return array();
	    }
	    
	    // Construire la meta_query pour rechercher dans le tableau sérialisé
	    $genre_query = array('relation' => 'OR');
	    
	    foreach ($genre_ids as $genre_id) {
	        // game_genres est un tableau sérialisé d'IDs
	        // On cherche l'ID dans le tableau sérialisé
	        $genre_query[] = array(
	            'key' => 'game_genres',
	            'value' => sprintf('"%d"', $genre_id),
	            'compare' => 'LIKE'
	        );
	        
	        // Alternative : chercher aussi avec i:ID; (format sérialisé)
	        $genre_query[] = array(
	            'key' => 'game_genres',
	            'value' => sprintf('i:%d;', $genre_id),
	            'compare' => 'LIKE'
	        );
	    }
	    
	    return $genre_query;
	}

	/**
	 * ✅ Filtrer pour ne garder que les jeux avec données complètes
	 * 
	 * @param array $term_ids Liste d'IDs de termes
	 * @return array IDs des jeux avec données complètes
	 */
	private static function filter_games_with_complete_data($term_ids) {
	    $valid_games = array();
	    
	    foreach ($term_ids as $term_id) {
	        // Utiliser get_game_data pour vérifier la complétude
	        $game_data = self::get_game_data($term_id);
	        if ($game_data !== false) {
	            $valid_games[] = $term_id;
	        }
	    }
	    
	    return $valid_games;
	}

	/**
     * 📅 Trier les jeux par date de sortie
     * 
     * @param array $term_ids IDs des termes
     * @param string $order Ordre de tri : 'desc' (défaut) ou 'asc'
     * @return array IDs triés par date
     */
    private static function sort_games_by_release_date($term_ids, $order = 'desc') {
        
        // Récupérer les dates pour chaque jeu
        $games_with_dates = array();
        
        foreach ($term_ids as $term_id) {
            $release_date = get_term_meta($term_id, 'release_date', true);
            
            // Convertir en timestamp pour le tri
            if (!empty($release_date)) {
                $timestamp = strtotime($release_date);
            } else {
                // Si pas de date, utiliser une date très ancienne/récente selon l'ordre
                $timestamp = ($order === 'desc') ? 0 : PHP_INT_MAX;
            }
            
            $games_with_dates[] = array(
                'term_id' => $term_id,
                'timestamp' => $timestamp,
                'release_date' => $release_date // Pour debug
            );
        }
        
        // Trier selon l'ordre demandé
        if ($order === 'asc') {
            // Tri croissant (plus anciens en premier)
            usort($games_with_dates, function($a, $b) {
                return $a['timestamp'] - $b['timestamp'];
            });
        } else {
            // Tri décroissant (plus récents en premier) - COMPORTEMENT ACTUEL
            usort($games_with_dates, function($a, $b) {
                return $b['timestamp'] - $a['timestamp'];
            });
        }
        
        // Extraire les IDs triés
        return array_column($games_with_dates, 'term_id');
    }

	/**
	 * 📊 Obtenir les statistiques des jeux selon critères (pour debug)
	 * 
	 * @param array $criteria Critères de recherche
	 * @return array Statistiques détaillées
	 */
	public static function get_games_stats_by_criteria($criteria = array()) {
	    $stats = array(
	        'total_games' => 0,
	        'games_with_data' => 0,
	        'games_by_genre' => array(),
	        'games_with_team_choice' => 0,
	        'date_range' => array('oldest' => null, 'newest' => null)
	    );
	    
	    // ✅ Récupérer tous les jeux avec game_description (comme dans get_games_by_criteria)
	    $all_terms = get_terms(array(
	        'taxonomy' => 'post_tag',
	        'hide_empty' => false,
	        'fields' => 'ids',
	        'meta_query' => array(
	            array(
	                'key' => 'game_description',
	                'compare' => 'EXISTS'
	            )
	        )
	    ));
	    
	    if (is_wp_error($all_terms)) {
	        return $stats;
	    }
	    
	    $stats['total_games'] = count($all_terms);
	    
	    // Analyser chaque jeu
	    foreach ($all_terms as $term_id) {
	        $game_data = self::get_game_data($term_id);
	        
	        if ($game_data) {
	            $stats['games_with_data']++;
	            
	            // ✅ CORRECTION : Analyser TOUS les genres via get_term_meta directement
	            $all_genre_ids = get_term_meta($term_id, 'game_genres', true);
	            
	            if (!empty($all_genre_ids) && is_array($all_genre_ids)) {
	                foreach ($all_genre_ids as $genre_id) {
	                    $category = get_category($genre_id);
	                    if ($category) {
	                        $genre_name = str_replace('jeux-', '', $category->name);
	                        $genre_name = ucfirst($genre_name);
	                        if (!isset($stats['games_by_genre'][$genre_name])) {
	                            $stats['games_by_genre'][$genre_name] = 0;
	                        }
	                        $stats['games_by_genre'][$genre_name]++;
	                    }
	                }
	            }
	            
	            // Analyser les dates
	            if (!empty($game_data['release_date'])) {
	                $date = $game_data['release_date'];
	                if (is_null($stats['date_range']['oldest']) || $date < $stats['date_range']['oldest']) {
	                    $stats['date_range']['oldest'] = $date;
	                }
	                if (is_null($stats['date_range']['newest']) || $date > $stats['date_range']['newest']) {
	                    $stats['date_range']['newest'] = $date;
	                }
	            }
	        }
	    }
	    arsort($stats['games_by_genre']);
	    return $stats;
	}

	/**
	 * 🎯 Fonction helper pour tester les critères
	 * 
	 * @param array $criteria Critères à tester
	 * @return array Résultat du test avec détails
	 */
	public static function test_criteria($criteria = array()) {
	    $start_time = microtime(true);
	    
	    $result = array(
	        'criteria' => $criteria,
	        'game_ids' => self::get_games_by_criteria($criteria),
	        'execution_time' => 0,
	        'stats' => array()
	    );
	    
	    $result['stats']['found_count'] = count($result['game_ids']);
	    $result['execution_time'] = round((microtime(true) - $start_time) * 1000, 2) . 'ms';
	    
	    // Ajouter les données des premiers jeux pour debug
	    $result['sample_games'] = array();
	    $sample_ids = array_slice($result['game_ids'], 0, 3);
	    
	    foreach ($sample_ids as $game_id) {
	        $game_data = self::get_game_data($game_id);
	        if ($game_data) {
	            $result['sample_games'][] = array(
	                'id' => $game_id,
	                'name' => $game_data['name'],
	                'release_date' => $game_data['release_date'],
	                'genres' => array_column($game_data['genres'], 'name')
	            );
	        }
	    }
	    
	    return $result;
	}
}
