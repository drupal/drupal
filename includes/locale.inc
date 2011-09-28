<?php

/**
 * @file
 * Administration functions for locale.module.
 */

/**
 * The language is determined using a URL language indicator:
 * path prefix or domain according to the configuration.
 */
define('LOCALE_LANGUAGE_NEGOTIATION_URL', 'locale-url');

/**
 * The language is set based on the browser language settings.
 */
define('LOCALE_LANGUAGE_NEGOTIATION_BROWSER', 'locale-browser');

/**
 * The language is determined using the current interface language.
 */
define('LOCALE_LANGUAGE_NEGOTIATION_INTERFACE', 'locale-interface');

/**
 * If no URL language is available language is determined using an already
 * detected one.
 */
define('LOCALE_LANGUAGE_NEGOTIATION_URL_FALLBACK', 'locale-url-fallback');

/**
 * The language is set based on the user language settings.
 */
define('LOCALE_LANGUAGE_NEGOTIATION_USER', 'locale-user');

/**
 * The language is set based on the request/session parameters.
 */
define('LOCALE_LANGUAGE_NEGOTIATION_SESSION', 'locale-session');

/**
 * Regular expression pattern used to localize JavaScript strings.
 */
define('LOCALE_JS_STRING', '(?:(?:\'(?:\\\\\'|[^\'])*\'|"(?:\\\\"|[^"])*")(?:\s*\+\s*)?)+');

/**
 * Translation import mode overwriting all existing translations
 * if new translated version available.
 */
define('LOCALE_IMPORT_OVERWRITE', 0);

/**
 * Translation import mode keeping existing translations and only
 * inserting new strings.
 */
define('LOCALE_IMPORT_KEEP', 1);

/**
 * URL language negotiation: use the path prefix as URL language
 * indicator.
 */
define('LOCALE_LANGUAGE_NEGOTIATION_URL_PREFIX', 0);

/**
 * URL language negotiation: use the domain as URL language
 * indicator.
 */
define('LOCALE_LANGUAGE_NEGOTIATION_URL_DOMAIN', 1);

/**
 * @defgroup locale-languages-negotiation Language negotiation options
 * @{
 * Functions for language negotiation.
 *
 * There are functions that provide the ability to identify the
 * language. This behavior can be controlled by various options.
 */

/**
 * Identifies the language from the current interface language.
 *
 * @return
 *   The current interface language code.
 */
function locale_language_from_interface() {
  global $language;
  return isset($language->language) ? $language->language : FALSE;
}

/**
 * Identify language from the Accept-language HTTP header we got.
 *
 * We perform browser accept-language parsing only if page cache is disabled,
 * otherwise we would cache a user-specific preference.
 *
 * @param $languages
 *   An array of valid language objects.
 *
 * @return
 *   A valid language code on success, FALSE otherwise.
 */
function locale_language_from_browser($languages) {
  // Specified by the user via the browser's Accept Language setting
  // Samples: "hu, en-us;q=0.66, en;q=0.33", "hu,en-us;q=0.5"
  $browser_langs = array();

  if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
    $browser_accept = explode(",", $_SERVER['HTTP_ACCEPT_LANGUAGE']);
    foreach ($browser_accept as $langpart) {
      // The language part is either a code or a code with a quality.
      // We cannot do anything with a * code, so it is skipped.
      // If the quality is missing, it is assumed to be 1 according to the RFC.
      if (preg_match("!([a-z-]+)(;q=([0-9\\.]+))?!", trim($langpart), $found)) {
        $browser_langs[$found[1]] = (isset($found[3]) ? (float) $found[3] : 1.0);
      }
    }
  }

  // Order the codes by quality
  arsort($browser_langs);

  // Try to find the first preferred language we have
  foreach ($browser_langs as $langcode => $q) {
    if (isset($languages[$langcode])) {
      return $langcode;
    }
  }

  return FALSE;
}

/**
 * Identify language from the user preferences.
 *
 * @param $languages
 *   An array of valid language objects.
 *
 * @return
 *   A valid language code on success, FALSE otherwise.
 */
function locale_language_from_user($languages) {
  // User preference (only for logged users).
  global $user;

  if ($user->uid) {
    return $user->language;
  }

  // No language preference from the user.
  return FALSE;
}

/**
 * Identify language from a request/session parameter.
 *
 * @param $languages
 *   An array of valid language objects.
 *
 * @return
 *   A valid language code on success, FALSE otherwise.
 */
function locale_language_from_session($languages) {
  $param = variable_get('locale_language_negotiation_session_param', 'language');

  // Request parameter: we need to update the session parameter only if we have
  // an authenticated user.
  if (isset($_GET[$param]) && isset($languages[$langcode = $_GET[$param]])) {
    global $user;
    if ($user->uid) {
      $_SESSION[$param] = $langcode;
    }
    return $langcode;
  }

  // Session parameter.
  if (isset($_SESSION[$param])) {
    return $_SESSION[$param];
  }

  return FALSE;
}

/**
 * Identify language via URL prefix or domain.
 *
 * @param $languages
 *   An array of valid language objects.
 *
 * @return
 *   A valid language code on success, FALSE otherwise.
 */
function locale_language_from_url($languages) {
  $language_url = FALSE;

  if (!language_negotiation_get_any(LOCALE_LANGUAGE_NEGOTIATION_URL)) {
    return $language_url;
  }

  switch (variable_get('locale_language_negotiation_url_part', LOCALE_LANGUAGE_NEGOTIATION_URL_PREFIX)) {
    case LOCALE_LANGUAGE_NEGOTIATION_URL_PREFIX:
      // $_GET['q'] might not be available at this time, because
      // path initialization runs after the language bootstrap phase.
      list($language, $_GET['q']) = language_url_split_prefix(isset($_GET['q']) ? $_GET['q'] : NULL, $languages);
      if ($language !== FALSE) {
        $language_url = $language->language;
      }
      break;

    case LOCALE_LANGUAGE_NEGOTIATION_URL_DOMAIN:
      foreach ($languages as $language) {
        // Skip check if the language doesn't have a domain.
        if ($language->domain) {
          // Only compare the domains not the protocols or ports.
          // Remove protocol and add http:// so parse_url works
          $host = 'http://' . str_replace(array('http://', 'https://'), '', $language->domain);
          $host = parse_url($host, PHP_URL_HOST);
          if ($_SERVER['HTTP_HOST'] == $host) {
            $language_url = $language->language;
            break;
          }
        }
      }
      break;
  }

  return $language_url;
}

/**
 * Determines the language to be assigned to URLs when none is detected.
 *
 * The language negotiation process has a fallback chain that ends with the
 * default language provider. Each built-in language type has a separate
 * initialization:
 * - Interface language, which is the only configurable one, always gets a valid
 *   value. If no request-specific language is detected, the default language
 *   will be used.
 * - Content language merely inherits the interface language by default.
 * - URL language is detected from the requested URL and will be used to rewrite
 *   URLs appearing in the page being rendered. If no language can be detected,
 *   there are two possibilities:
 *   - If the default language has no configured path prefix or domain, then the
 *     default language is used. This guarantees that (missing) URL prefixes are
 *     preserved when navigating through the site.
 *   - If the default language has a configured path prefix or domain, a
 *     requested URL having an empty prefix or domain is an anomaly that must be
 *     fixed. This is done by introducing a prefix or domain in the rendered
 *     page matching the detected interface language.
 *
 * @param $languages
 *   (optional) An array of valid language objects. This is passed by
 *   language_provider_invoke() to every language provider callback, but it is
 *   not actually needed here. Defaults to NULL.
 * @param $language_type
 *   (optional) The language type to fall back to. Defaults to the interface
 *   language.
 *
 * @return
 *   A valid language code.
 */
function locale_language_url_fallback($language = NULL, $language_type = LANGUAGE_TYPE_INTERFACE) {
  $default = language_default();
  $prefix = (variable_get('locale_language_negotiation_url_part', LOCALE_LANGUAGE_NEGOTIATION_URL_PREFIX) == LOCALE_LANGUAGE_NEGOTIATION_URL_PREFIX);

  // If the default language is not configured to convey language information,
  // a missing URL language information indicates that URL language should be
  // the default one, otherwise we fall back to an already detected language.
  if (($prefix && empty($default->prefix)) || (!$prefix && empty($default->domain))) {
    return $default->language;
  }
  else {
    return $GLOBALS[$language_type]->language;
  }
}

/**
 * Return the URL language switcher block. Translation links may be provided by
 * other modules.
 */
function locale_language_switcher_url($type, $path) {
  $languages = language_list('enabled');
  $links = array();

  foreach ($languages[1] as $language) {
    $links[$language->language] = array(
      'href'       => $path,
      'title'      => $language->native,
      'language'   => $language,
      'attributes' => array('class' => array('language-link')),
    );
  }

  return $links;
}

/**
 * Return the session language switcher block.
 */
function locale_language_switcher_session($type, $path) {
  drupal_add_css(drupal_get_path('module', 'locale') . '/locale.css');

  $param = variable_get('locale_language_negotiation_session_param', 'language');
  $language_query = isset($_SESSION[$param]) ? $_SESSION[$param] : $GLOBALS[$type]->language;

  $languages = language_list('enabled');
  $links = array();

  $query = $_GET;
  unset($query['q']);

  foreach ($languages[1] as $language) {
    $langcode = $language->language;
    $links[$langcode] = array(
      'href'       => $path,
      'title'      => $language->native,
      'attributes' => array('class' => array('language-link')),
      'query'      => $query,
    );
    if ($language_query != $langcode) {
      $links[$langcode]['query'][$param] = $langcode;
    }
    else {
      $links[$langcode]['attributes']['class'][] = ' session-active';
    }
  }

  return $links;
}

/**
 * Rewrite URLs for the URL language provider.
 */
function locale_language_url_rewrite_url(&$path, &$options) {
  static $drupal_static_fast;
  if (!isset($drupal_static_fast)) {
    $drupal_static_fast['languages'] = &drupal_static(__FUNCTION__);
  }
  $languages = &$drupal_static_fast['languages'];

  if (!isset($languages)) {
    $languages = language_list('enabled');
    $languages = array_flip(array_keys($languages[1]));
  }

  // Language can be passed as an option, or we go for current URL language.
  if (!isset($options['language'])) {
    global $language_url;
    $options['language'] = $language_url;
  }
  // We allow only enabled languages here.
  elseif (!isset($languages[$options['language']->language])) {
    unset($options['language']);
    return;
  }

  if (isset($options['language'])) {
    switch (variable_get('locale_language_negotiation_url_part', LOCALE_LANGUAGE_NEGOTIATION_URL_PREFIX)) {
      case LOCALE_LANGUAGE_NEGOTIATION_URL_DOMAIN:
        if ($options['language']->domain) {
          // Ask for an absolute URL with our modified base_url.
          $options['absolute'] = TRUE;
          $options['base_url'] = $options['language']->domain;
        }
        break;

      case LOCALE_LANGUAGE_NEGOTIATION_URL_PREFIX:
        if (!empty($options['language']->prefix)) {
          $options['prefix'] = $options['language']->prefix . '/';
        }
        break;
    }
  }
}

/**
 * Rewrite URLs for the Session language provider.
 */
function locale_language_url_rewrite_session(&$path, &$options) {
  static $query_rewrite, $query_param, $query_value;

  // The following values are not supposed to change during a single page
  // request processing.
  if (!isset($query_rewrite)) {
    global $user;
    if (!$user->uid) {
      $languages = language_list('enabled');
      $languages = $languages[1];
      $query_param = check_plain(variable_get('locale_language_negotiation_session_param', 'language'));
      $query_value = isset($_GET[$query_param]) ? check_plain($_GET[$query_param]) : NULL;
      $query_rewrite = isset($languages[$query_value]) && language_negotiation_get_any(LOCALE_LANGUAGE_NEGOTIATION_SESSION);
    }
    else {
      $query_rewrite = FALSE;
    }
  }

  // If the user is anonymous, the user language provider is enabled, and the
  // corresponding option has been set, we must preserve any explicit user
  // language preference even with cookies disabled.
  if ($query_rewrite) {
    if (is_string($options['query'])) {
      $options['query'] = drupal_get_query_array($options['query']);
    }
    if (!isset($options['query'][$query_param])) {
      $options['query'][$query_param] = $query_value;
    }
  }
}

/**
 * @} End of "locale-languages-negotiation"
 */

/**
 * Check that a string is safe to be added or imported as a translation.
 *
 * This test can be used to detect possibly bad translation strings. It should
 * not have any false positives. But it is only a test, not a transformation,
 * as it destroys valid HTML. We cannot reliably filter translation strings
 * on import because some strings are irreversibly corrupted. For example,
 * a &amp; in the translation would get encoded to &amp;amp; by filter_xss()
 * before being put in the database, and thus would be displayed incorrectly.
 *
 * The allowed tag list is like filter_xss_admin(), but omitting div and img as
 * not needed for translation and likely to cause layout issues (div) or a
 * possible attack vector (img).
 */
function locale_string_is_safe($string) {
  return decode_entities($string) == decode_entities(filter_xss($string, array('a', 'abbr', 'acronym', 'address', 'b', 'bdo', 'big', 'blockquote', 'br', 'caption', 'cite', 'code', 'col', 'colgroup', 'dd', 'del', 'dfn', 'dl', 'dt', 'em', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'hr', 'i', 'ins', 'kbd', 'li', 'ol', 'p', 'pre', 'q', 'samp', 'small', 'span', 'strong', 'sub', 'sup', 'table', 'tbody', 'td', 'tfoot', 'th', 'thead', 'tr', 'tt', 'ul', 'var')));
}

/**
 * API function to add or update a language.
 *
 * @param $language
 *   Language object with properties corresponding to 'languages' table columns.
 */
function locale_language_save($language) {
  $language->is_new = !(bool) db_query_range('SELECT 1 FROM {languages} WHERE language = :language', 0, 1, array(':language' => $language->language))->fetchField();
  // Default prefix on language code if not provided otherwise.
  if (!isset($language->prefix)) {
    $language->prefix = $language->language;
  }

  // If name was not set, we add a predefined language.
  if (!isset($language->name)) {
    include_once DRUPAL_ROOT . '/includes/standard.inc';
    $predefined = standard_language_list();
    $language->name = $predefined[$language->language][0];
    $language->native = isset($predefined[$language->language][1]) ? $predefined[$language->language][1] : $predefined[$language->language][0];
    $language->direction = isset($predefined[$language->language][2]) ? $predefined[$language->language][2] : LANGUAGE_LTR;
  }

  // Set to enabled for the default language and unless specified otherwise.
  if (!empty($language->default) || !isset($language->enabled)) {
    $language->enabled = TRUE;
  }
  // Let other modules modify $language before saved.
  module_invoke_all('locale_language_presave', $language);

  // Save the record and inform others about the change.
  if ($language->is_new) {
    drupal_write_record('languages', $language);
    module_invoke_all('locale_language_insert', $language);
    watchdog('locale', 'The %language (%langcode) language has been created.', array('%language' => $language->name, '%langcode' => $language->language));
  }
  else {
    drupal_write_record('languages', $language, array('language'));
    module_invoke_all('locale_language_update', $language);
    watchdog('locale', 'The %language (%langcode) language has been updated.', array('%language' => $language->name, '%langcode' => $language->language));
  }

  if (!empty($language->default)) {
    // Set the new version of this language as default in a variable.
    $default_language = language_default();
    variable_set('language_default', $language);
  }

  // Update language count based on enabled language count.
  variable_set('language_count', db_query('SELECT COUNT(language) FROM {languages} WHERE enabled = 1')->fetchField());

  // Kill the static cache in language_list().
  drupal_static_reset('language_list');

  // @todo move these two cache clears out. See http://drupal.org/node/1293252
  // Changing the language settings impacts the interface.
  cache_clear_all('*', 'cache_page', TRUE);
  // Force JavaScript translation file re-creation for the modified language.
  _locale_invalidate_js($language->language);

  return $language;
}

/**
 * Parses a JavaScript file, extracts strings wrapped in Drupal.t() and
 * Drupal.formatPlural() and inserts them into the database.
 */
function _locale_parse_js_file($filepath) {
  global $language;

  // The file path might contain a query string, so make sure we only use the
  // actual file.
  $parsed_url = drupal_parse_url($filepath);
  $filepath = $parsed_url['path'];
  // Load the JavaScript file.
  $file = file_get_contents($filepath);

  // Match all calls to Drupal.t() in an array.
  // Note: \s also matches newlines with the 's' modifier.
  preg_match_all('~[^\w]Drupal\s*\.\s*t\s*\(\s*(' . LOCALE_JS_STRING . ')\s*[,\)]~s', $file, $t_matches);

  // Match all Drupal.formatPlural() calls in another array.
  preg_match_all('~[^\w]Drupal\s*\.\s*formatPlural\s*\(\s*.+?\s*,\s*(' . LOCALE_JS_STRING . ')\s*,\s*((?:(?:\'(?:\\\\\'|[^\'])*@count(?:\\\\\'|[^\'])*\'|"(?:\\\\"|[^"])*@count(?:\\\\"|[^"])*")(?:\s*\+\s*)?)+)\s*[,\)]~s', $file, $plural_matches);

  // Loop through all matches and process them.
  $all_matches = array_merge($plural_matches[1], $t_matches[1]);
  foreach ($all_matches as $key => $string) {
    $strings = array($string);

    // If there is also a plural version of this string, add it to the strings array.
    if (isset($plural_matches[2][$key])) {
      $strings[] = $plural_matches[2][$key];
    }

    foreach ($strings as $key => $string) {
      // Remove the quotes and string concatenations from the string.
      $string = implode('', preg_split('~(?<!\\\\)[\'"]\s*\+\s*[\'"]~s', substr($string, 1, -1)));

      $source = db_query("SELECT lid, location FROM {locales_source} WHERE source = :source", array(':source' => $string))->fetchObject();
      if ($source) {
        // We already have this source string and now have to add the location
        // to the location column, if this file is not yet present in there.
        $locations = preg_split('~\s*;\s*~', $source->location);

        if (!in_array($filepath, $locations)) {
          $locations[] = $filepath;
          $locations = implode('; ', $locations);

          // Save the new locations string to the database.
          db_update('locales_source')
            ->fields(array(
              'location' => $locations,
            ))
            ->condition('lid', $source->lid)
            ->execute();
        }
      }
      else {
        // We don't have the source string yet, thus we insert it into the database.
        db_insert('locales_source')
          ->fields(array(
            'location' => $filepath,
            'source' => $string,
            'context' => '',
          ))
          ->execute();
      }
    }
  }
}

/**
 * Force the JavaScript translation file(s) to be refreshed.
 *
 * This function sets a refresh flag for a specified language, or all
 * languages except English, if none specified. JavaScript translation
 * files are rebuilt (with locale_update_js_files()) the next time a
 * request is served in that language.
 *
 * @param $langcode
 *   The language code for which the file needs to be refreshed.
 *
 * @return
 *   New content of the 'javascript_parsed' variable.
 */
function _locale_invalidate_js($langcode = NULL) {
  $parsed = variable_get('javascript_parsed', array());

  if (empty($langcode)) {
    // Invalidate all languages.
    $languages = language_list();
    unset($languages['en']);
    foreach ($languages as $lcode => $data) {
      $parsed['refresh:' . $lcode] = 'waiting';
    }
  }
  else {
    // Invalidate single language.
    $parsed['refresh:' . $langcode] = 'waiting';
  }

  variable_set('javascript_parsed', $parsed);
  return $parsed;
}

/**
 * (Re-)Creates the JavaScript translation file for a language.
 *
 * @param $language
 *   The language, the translation file should be (re)created for.
 */
function _locale_rebuild_js($langcode = NULL) {
  if (!isset($langcode)) {
    global $language;
  }
  else {
    // Get information about the locale.
    $languages = language_list();
    $language = $languages[$langcode];
  }

  // Construct the array for JavaScript translations.
  // Only add strings with a translation to the translations array.
  $result = db_query("SELECT s.lid, s.source, t.translation FROM {locales_source} s INNER JOIN {locales_target} t ON s.lid = t.lid AND t.language = :language WHERE s.location LIKE '%.js%'", array(':language' => $language->language));

  $translations = array();
  foreach ($result as $data) {
    $translations[$data->source] = $data->translation;
  }

  // Construct the JavaScript file, if there are translations.
  $data_hash = NULL;
  $data = $status = '';
  if (!empty($translations)) {

    $data = "Drupal.locale = { ";

    if (!empty($language->formula)) {
      $data .= "'pluralFormula': function (\$n) { return Number({$language->formula}); }, ";
    }

    $data .= "'strings': " . drupal_json_encode($translations) . " };";
    $data_hash = drupal_hash_base64($data);
  }

  // Construct the filepath where JS translation files are stored.
  // There is (on purpose) no front end to edit that variable.
  $dir = 'public://' . variable_get('locale_js_directory', 'languages');

  // Delete old file, if we have no translations anymore, or a different file to be saved.
  $changed_hash = $language->javascript != $data_hash;
  if (!empty($language->javascript) && (!$data || $changed_hash)) {
    file_unmanaged_delete($dir . '/' . $language->language . '_' . $language->javascript . '.js');
    $language->javascript = '';
    $status = 'deleted';
  }

  // Only create a new file if the content has changed or the original file got
  // lost.
  $dest = $dir . '/' . $language->language . '_' . $data_hash . '.js';
  if ($data && ($changed_hash || !file_exists($dest))) {
    // Ensure that the directory exists and is writable, if possible.
    file_prepare_directory($dir, FILE_CREATE_DIRECTORY);

    // Save the file.
    if (file_unmanaged_save_data($data, $dest)) {
      $language->javascript = $data_hash;
      // If we deleted a previous version of the file and we replace it with a
      // new one we have an update.
      if ($status == 'deleted') {
        $status = 'updated';
      }
      // If the file did not exist previously and the data has changed we have
      // a fresh creation.
      elseif ($changed_hash) {
        $status = 'created';
      }
      // If the data hash is unchanged the translation was lost and has to be
      // rebuilt.
      else {
        $status = 'rebuilt';
      }
    }
    else {
      $language->javascript = '';
      $status = 'error';
    }
  }

  // Save the new JavaScript hash (or an empty value if the file just got
  // deleted). Act only if some operation was executed that changed the hash
  // code.
  if ($status && $changed_hash) {
    db_update('languages')
      ->fields(array(
        'javascript' => $language->javascript,
      ))
      ->condition('language', $language->language)
      ->execute();

    // Update the default language variable if the default language has been altered.
    // This is necessary to keep the variable consistent with the database
    // version of the language and to prevent checking against an outdated hash.
    $default_langcode = language_default()->language;
    if ($default_langcode == $language->language) {
      $default = db_query("SELECT * FROM {languages} WHERE language = :language", array(':language' => $default_langcode))->fetchObject();
      variable_set('language_default', $default);
    }
  }

  // Log the operation and return success flag.
  switch ($status) {
    case 'updated':
      watchdog('locale', 'Updated JavaScript translation file for the language %language.', array('%language' => t($language->name)));
      return TRUE;
    case 'rebuilt':
      watchdog('locale', 'JavaScript translation file %file.js was lost.', array('%file' => $language->javascript), WATCHDOG_WARNING);
      // Proceed to the 'created' case as the JavaScript translation file has
      // been created again.
    case 'created':
      watchdog('locale', 'Created JavaScript translation file for the language %language.', array('%language' => t($language->name)));
      return TRUE;
    case 'deleted':
      watchdog('locale', 'Removed JavaScript translation file for the language %language, because no translations currently exist for that language.', array('%language' => t($language->name)));
      return TRUE;
    case 'error':
      watchdog('locale', 'An error occurred during creation of the JavaScript translation file for the language %language.', array('%language' => t($language->name)), WATCHDOG_ERROR);
      return FALSE;
    default:
      // No operation needed.
      return TRUE;
  }
}

/**
 * @defgroup locale-api-predefined List of predefined languages
 * @{
 * API to provide a list of predefined languages.
 */

/**
 * Prepares the language code list for a select form item with only the unsupported ones
 */
function _locale_prepare_predefined_list() {
  include_once DRUPAL_ROOT . '/includes/standard.inc';
  $languages = language_list();
  $predefined = standard_language_list();
  foreach ($predefined as $key => $value) {
    if (isset($languages[$key])) {
      unset($predefined[$key]);
      continue;
    }
    // Include native name in output, if possible
    if (count($value) > 1) {
      $tname = t($value[0]);
      $predefined[$key] = ($tname == $value[1]) ? $tname : "$tname ($value[1])";
    }
    else {
      $predefined[$key] = t($value[0]);
    }
  }
  asort($predefined);
  return $predefined;
}

/**
 * @} End of "locale-api-languages-predefined"
 */

/**
 * Get list of all predefined and custom countries.
 *
 * @return
 *   An array of all country code => country name pairs.
 */
function country_get_list() {
  include_once DRUPAL_ROOT . '/includes/standard.inc';
  $countries = standard_country_list();
  // Allow other modules to modify the country list.
  drupal_alter('countries', $countries);
  return $countries;
}

/**
 * Save locale specific date formats to the database.
 *
 * @param $langcode
 *   Language code, can be 2 characters, e.g. 'en' or 5 characters, e.g.
 *   'en-CA'.
 * @param $type
 *   Date format type, e.g. 'short', 'medium'.
 * @param $format
 *   The date format string.
 */
function locale_date_format_save($langcode, $type, $format) {
  $locale_format = array();
  $locale_format['language'] = $langcode;
  $locale_format['type'] = $type;
  $locale_format['format'] = $format;

  $is_existing = (bool) db_query_range('SELECT 1 FROM {date_format_locale} WHERE language = :langcode AND type = :type', 0, 1, array(':langcode' => $langcode, ':type' => $type))->fetchField();
  if ($is_existing) {
    $keys = array('type', 'language');
    drupal_write_record('date_format_locale', $locale_format, $keys);
  }
  else {
    drupal_write_record('date_format_locale', $locale_format);
  }
}

/**
 * Select locale date format details from database.
 *
 * @param $languages
 *   An array of language codes.
 *
 * @return
 *   An array of date formats.
 */
function locale_get_localized_date_format($languages) {
  $formats = array();

  // Get list of different format types.
  $format_types = system_get_date_types();
  $short_default = variable_get('date_format_short', 'm/d/Y - H:i');

  // Loop through each language until we find one with some date formats
  // configured.
  foreach ($languages as $language) {
    $date_formats = system_date_format_locale($language);
    if (!empty($date_formats)) {
      // We have locale-specific date formats, so check for their types. If
      // we're missing a type, use the default setting instead.
      foreach ($format_types as $type => $type_info) {
        // If format exists for this language, use it.
        if (!empty($date_formats[$type])) {
          $formats['date_format_' . $type] = $date_formats[$type];
        }
        // Otherwise get default variable setting. If this is not set, default
        // to the short format.
        else {
          $formats['date_format_' . $type] = variable_get('date_format_' . $type, $short_default);
        }
      }

      // Return on the first match.
      return $formats;
    }
  }

  // No locale specific formats found, so use defaults.
  $system_types = array('short', 'medium', 'long');
  // Handle system types separately as they have defaults if no variable exists.
  $formats['date_format_short'] = $short_default;
  $formats['date_format_medium'] = variable_get('date_format_medium', 'D, m/d/Y - H:i');
  $formats['date_format_long'] = variable_get('date_format_long', 'l, F j, Y - H:i');

  // For non-system types, get the default setting, otherwise use the short
  // format.
  foreach ($format_types as $type => $type_info) {
    if (!in_array($type, $system_types)) {
      $formats['date_format_' . $type] = variable_get('date_format_' . $type, $short_default);
    }
  }

  return $formats;
}
