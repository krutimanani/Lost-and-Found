<?php
/**
 * Internationalization (i18n) Engine
 * Supports Hindi, English, and Gujarati translations
 */

// Load translation arrays
$GLOBALS['translations'] = [];
$GLOBALS['current_language'] = 'en';

/**
 * Initialize i18n system
 */
function initI18n() {
    // Get language from session, user profile, or default
    if (isset($_SESSION['language'])) {
        $GLOBALS['current_language'] = $_SESSION['language'];
    } else {
        $GLOBALS['current_language'] = 'en';
        $_SESSION['language'] = 'en';
    }

    // Load translation file
    loadTranslations($GLOBALS['current_language']);
}

/**
 * Load translations for a specific language
 */
function loadTranslations($lang) {
    $lang = in_array($lang, ['en', 'hi', 'gu']) ? $lang : 'en';
    $translationFile = __DIR__ . '/' . $lang . '.php';

    if (file_exists($translationFile)) {
        $GLOBALS['translations'] = require $translationFile;
    }
}

/**
 * Translate a key
 * @param string $key Translation key (e.g., 'common.nav.dashboard')
 * @param array $replacements Key-value pairs for placeholder replacement
 * @return string Translated text
 */
function t($key, $replacements = []) {
    $keys = explode('.', $key);
    $translation = $GLOBALS['translations'];

    // Navigate through nested array
    foreach ($keys as $k) {
        if (isset($translation[$k])) {
            $translation = $translation[$k];
        } else {
            // Fallback to key itself if translation not found
            return $key;
        }
    }

    // Replace placeholders
    if (!empty($replacements) && is_string($translation)) {
        foreach ($replacements as $placeholder => $value) {
            $translation = str_replace('{' . $placeholder . '}', $value, $translation);
        }
    }

    return is_string($translation) ? $translation : $key;
}

/**
 * Get current language
 * @return string Current language code ('en', 'hi', or 'gu')
 */
function getCurrentLanguage() {
    return $GLOBALS['current_language'] ?? 'en';
}

/**
 * Set language
 * @param string $lang Language code ('en', 'hi', or 'gu')
 */
function setLanguage($lang) {
    if (in_array($lang, ['en', 'hi', 'gu'])) {
        $GLOBALS['current_language'] = $lang;
        $_SESSION['language'] = $lang;
        loadTranslations($lang);

        // Update in database if user is logged in
        updateUserLanguagePreference($lang);
    }
}

/**
 * Update user's language preference in database
 * @param string $lang Language code
 */
function updateUserLanguagePreference($lang) {
    global $pdo;

    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
        return;
    }

    try {
        $user_id = $_SESSION['user_id'];
        $user_type = $_SESSION['user_type'];

        if ($user_type === 'citizen') {
            $stmt = $pdo->prepare("UPDATE users SET language = ? WHERE user_id = ?");
            $stmt->execute([$lang, $user_id]);
        } elseif ($user_type === 'police') {
            $stmt = $pdo->prepare("UPDATE police SET language = ? WHERE police_id = ?");
            $stmt->execute([$lang, $user_id]);
        } elseif ($user_type === 'admin') {
            $stmt = $pdo->prepare("UPDATE admins SET language = ? WHERE admin_id = ?");
            $stmt->execute([$lang, $user_id]);
        }
    } catch (PDOException $e) {
        // Silently fail if columns don't exist yet
    }
}

/**
 * Load user's saved language preference from database
 */
function loadUserLanguagePreference() {
    global $pdo;

    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
        return;
    }

    try {
        $user_id = $_SESSION['user_id'];
        $user_type = $_SESSION['user_type'];
        $lang = null;

        if ($user_type === 'citizen') {
            $stmt = $pdo->prepare("SELECT language FROM users WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch();
            $lang = $result['language'] ?? null;
        } elseif ($user_type === 'police') {
            $stmt = $pdo->prepare("SELECT language FROM police WHERE police_id = ?");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch();
            $lang = $result['language'] ?? null;
        } elseif ($user_type === 'admin') {
            $stmt = $pdo->prepare("SELECT language FROM admins WHERE admin_id = ?");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch();
            $lang = $result['language'] ?? null;
        }

        if ($lang && in_array($lang, ['en', 'hi', 'gu'])) {
            setLanguage($lang);
        }
    } catch (PDOException $e) {
        // Silently fail if columns don't exist yet
    }
}

/**
 * Get supported languages
 * @return array Language codes with names
 */
function getSupportedLanguages() {
    return [
        'en' => 'English',
        'hi' => 'हिंदी',
        'gu' => 'ગુજરાતી'
    ];
}

/**
 * Get localized category name
 * @param array|string $category Category data from database or category name string
 * @return string Localized category name
 */
function getLocalizedCategoryName($category) {
    // Handle string input (return as-is since we can't localize without DB data)
    if (is_string($category)) {
        return $category;
    }

    // Handle null or empty input
    if (empty($category)) {
        return '';
    }

    // Handle array input (original logic)
    $lang = getCurrentLanguage();
    if ($lang === 'hi' && !empty($category['category_name_hi'])) {
        return $category['category_name_hi'];
    }
    return $category['category_name'] ?? '';
}

/**
 * Get localized category description
 * @param array|string $category Category data from database or description string
 * @return string Localized category description
 */
function getLocalizedCategoryDescription($category) {
    // Handle string input (return as-is since we can't localize without DB data)
    if (is_string($category)) {
        return $category;
    }

    // Handle null or empty input
    if (empty($category)) {
        return '';
    }

    // Handle array input (original logic)
    $lang = getCurrentLanguage();
    if ($lang === 'hi' && !empty($category['description_hi'])) {
        return $category['description_hi'];
    }
    return $category['description'] ?? '';
}

/**
 * Get localized location name
 * @param array|string $location Location data from database or location name string
 * @return string Localized location name
 */
function getLocalizedLocationName($location) {
    // Handle string input (return as-is since we can't localize without DB data)
    if (is_string($location)) {
        return $location;
    }

    // Handle null or empty input
    if (empty($location)) {
        return '';
    }

    // Handle array input (original logic)
    $lang = getCurrentLanguage();
    if ($lang === 'hi' && !empty($location['location_name_hi'])) {
        return $location['location_name_hi'];
    }
    return $location['location_name'] ?? '';
}

// Handle language switching via URL parameter
if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'hi', 'gu'])) {
    setLanguage($_GET['lang']);

    // Redirect to remove the lang parameter from URL
    $redirect_url = strtok($_SERVER['REQUEST_URI'], '?');
    if (!empty($_SERVER['QUERY_STRING'])) {
        parse_str($_SERVER['QUERY_STRING'], $params);
        unset($params['lang']);
        if (!empty($params)) {
            $redirect_url .= '?' . http_build_query($params);
        }
    }
    header('Location: ' . $redirect_url);
    exit();
}
