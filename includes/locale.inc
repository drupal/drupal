<?php
// $Id: locale.inc,v 1.260 2010/10/06 13:38:39 dries Exp $

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
        $host = parse_url($language->domain, PHP_URL_HOST);
        if ($host && ($_SERVER['HTTP_HOST'] == $host)) {
          $language_url = $language->language;
          break;
        }
      }
      break;
  }

  return $language_url;
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
  // Language can be passed as an option, or we go for current URL language.
  if (!isset($options['language'])) {
    global $language_url;
    $options['language'] = $language_url;
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
 * @defgroup locale-api-add Language addition API.
 * @{
 */

/**
 * API function to add a language.
 *
 * @param $langcode
 *   Language code.
 * @param $name
 *   English name of the language
 * @param $native
 *   Native name of the language
 * @param $direction
 *   LANGUAGE_LTR or LANGUAGE_RTL
 * @param $domain
 *   Optional custom domain name with protocol, without
 *   trailing slash (eg. http://de.example.com).
 * @param $prefix
 *   Optional path prefix for the language. Defaults to the
 *   language code if omitted.
 * @param $enabled
 *   Optionally TRUE to enable the language when created or FALSE to disable.
 * @param $default
 *   Optionally set this language to be the default.
 */
function locale_add_language($langcode, $name = NULL, $native = NULL, $direction = LANGUAGE_LTR, $domain = '', $prefix = '', $enabled = TRUE, $default = FALSE) {
  // Default prefix on language code.
  if (empty($prefix)) {
    $prefix = $langcode;
  }

  // If name was not set, we add a predefined language.
  if (!isset($name)) {
    include_once DRUPAL_ROOT . '/includes/iso.inc';
    $predefined = _locale_get_predefined_list();
    $name = $predefined[$langcode][0];
    $native = isset($predefined[$langcode][1]) ? $predefined[$langcode][1] : $predefined[$langcode][0];
    $direction = isset($predefined[$langcode][2]) ? $predefined[$langcode][2] : LANGUAGE_LTR;
  }

  db_insert('languages')
    ->fields(array(
      'language' => $langcode,
      'name' => $name,
      'native' => $native,
      'direction' => $direction,
      'domain' => $domain,
      'prefix' => $prefix,
      'enabled' => $enabled,
    ))
    ->execute();

  // Only set it as default if enabled.
  if ($enabled && $default) {
    variable_set('language_default', (object) array('language' => $langcode, 'name' => $name, 'native' => $native, 'direction' => $direction, 'enabled' => (int) $enabled, 'plurals' => 0, 'formula' => '', 'domain' => '', 'prefix' => $prefix, 'weight' => 0, 'javascript' => ''));
  }

  if ($enabled) {
    // Increment enabled language count if we are adding an enabled language.
    variable_set('language_count', variable_get('language_count', 1) + 1);
  }

  // Kill the static cache in language_list().
  drupal_static_reset('language_list');

  // Force JavaScript translation file creation for the newly added language.
  _locale_invalidate_js($langcode);

  watchdog('locale', 'The %language language (%code) has been created.', array('%language' => $name, '%code' => $langcode));

  module_invoke_all('multilingual_settings_changed');
}
/**
 * @} End of "locale-api-add"
 */

/**
 * @defgroup locale-api-import Translation import API.
 * @{
 */

/**
 * Parses Gettext Portable Object file information and inserts into database
 *
 * @param $file
 *   Drupal file object corresponding to the PO file to import.
 * @param $langcode
 *   Language code.
 * @param $mode
 *   Should existing translations be replaced LOCALE_IMPORT_KEEP or
 *   LOCALE_IMPORT_OVERWRITE.
 * @param $group
 *   Text group to import PO file into (eg. 'default' for interface
 *   translations).
 */
function _locale_import_po($file, $langcode, $mode, $group = NULL) {
  // Try to allocate enough time to parse and import the data.
  drupal_set_time_limit(240);

  // Check if we have the language already in the database.
  if (!db_query("SELECT COUNT(language) FROM {languages} WHERE language = :language", array(':language' => $langcode))->fetchField()) {
    drupal_set_message(t('The language selected for import is not supported.'), 'error');
    return FALSE;
  }

  // Get strings from file (returns on failure after a partial import, or on success)
  $status = _locale_import_read_po('db-store', $file, $mode, $langcode, $group);
  if ($status === FALSE) {
    // Error messages are set in _locale_import_read_po().
    return FALSE;
  }

  // Get status information on import process.
  list($header_done, $additions, $updates, $deletes, $skips) = _locale_import_one_string('db-report');

  if (!$header_done) {
    drupal_set_message(t('The translation file %filename appears to have a missing or malformed header.', array('%filename' => $file->filename)), 'error');
  }

  // Clear cache and force refresh of JavaScript translations.
  _locale_invalidate_js($langcode);
  cache_clear_all('locale:', 'cache', TRUE);

  // Rebuild the menu, strings may have changed.
  menu_rebuild();

  drupal_set_message(t('The translation was successfully imported. There are %number newly created translated strings, %update strings were updated and %delete strings were removed.', array('%number' => $additions, '%update' => $updates, '%delete' => $deletes)));
  watchdog('locale', 'Imported %file into %locale: %number new strings added, %update updated and %delete removed.', array('%file' => $file->filename, '%locale' => $langcode, '%number' => $additions, '%update' => $updates, '%delete' => $deletes));
  if ($skips) {
    $skip_message = format_plural($skips, 'One translation string was skipped because it contains disallowed HTML.', '@count translation strings were skipped because they contain disallowed HTML.');
    drupal_set_message($skip_message);
    watchdog('locale', '@count disallowed HTML string(s) in %file', array('@count' => $skips, '%file' => $file->uri), WATCHDOG_WARNING);
  }
  return TRUE;
}

/**
 * Parses Gettext Portable Object file into an array
 *
 * @param $op
 *   Storage operation type: db-store or mem-store.
 * @param $file
 *   Drupal file object corresponding to the PO file to import.
 * @param $mode
 *   Should existing translations be replaced LOCALE_IMPORT_KEEP or
 *   LOCALE_IMPORT_OVERWRITE.
 * @param $lang
 *   Language code.
 * @param $group
 *   Text group to import PO file into (eg. 'default' for interface
 *   translations).
 */
function _locale_import_read_po($op, $file, $mode = NULL, $lang = NULL, $group = 'default') {

  $fd = fopen($file->uri, "rb"); // File will get closed by PHP on return
  if (!$fd) {
    _locale_import_message('The translation import failed, because the file %filename could not be read.', $file);
    return FALSE;
  }

  $context = "COMMENT"; // Parser context: COMMENT, MSGID, MSGID_PLURAL, MSGSTR and MSGSTR_ARR
  $current = array();   // Current entry being read
  $plural = 0;          // Current plural form
  $lineno = 0;          // Current line

  while (!feof($fd)) {
    $line = fgets($fd, 10*1024); // A line should not be this long
    if ($lineno == 0) {
      // The first line might come with a UTF-8 BOM, which should be removed.
      $line = str_replace("\xEF\xBB\xBF", '', $line);
    }
    $lineno++;
    $line = trim(strtr($line, array("\\\n" => "")));

    if (!strncmp("#", $line, 1)) { // A comment
      if ($context == "COMMENT") { // Already in comment context: add
        $current["#"][] = substr($line, 1);
      }
      elseif (($context == "MSGSTR") || ($context == "MSGSTR_ARR")) { // End current entry, start a new one
        _locale_import_one_string($op, $current, $mode, $lang, $file, $group);
        $current = array();
        $current["#"][] = substr($line, 1);
        $context = "COMMENT";
      }
      else { // Parse error
        _locale_import_message('The translation file %filename contains an error: "msgstr" was expected but not found on line %line.', $file, $lineno);
        return FALSE;
      }
    }
    elseif (!strncmp("msgid_plural", $line, 12)) {
      if ($context != "MSGID") { // Must be plural form for current entry
        _locale_import_message('The translation file %filename contains an error: "msgid_plural" was expected but not found on line %line.', $file, $lineno);
        return FALSE;
      }
      $line = trim(substr($line, 12));
      $quoted = _locale_import_parse_quoted($line);
      if ($quoted === FALSE) {
        _locale_import_message('The translation file %filename contains a syntax error on line %line.', $file, $lineno);
        return FALSE;
      }
      $current["msgid"] = $current["msgid"] . "\0" . $quoted;
      $context = "MSGID_PLURAL";
    }
    elseif (!strncmp("msgid", $line, 5)) {
      if ($context == "MSGSTR") {   // End current entry, start a new one
        _locale_import_one_string($op, $current, $mode, $lang, $file, $group);
        $current = array();
      }
      elseif ($context == "MSGID") { // Already in this context? Parse error
        _locale_import_message('The translation file %filename contains an error: "msgid" is unexpected on line %line.', $file, $lineno);
        return FALSE;
      }
      $line = trim(substr($line, 5));
      $quoted = _locale_import_parse_quoted($line);
      if ($quoted === FALSE) {
        _locale_import_message('The translation file %filename contains a syntax error on line %line.', $file, $lineno);
        return FALSE;
      }
      $current["msgid"] = $quoted;
      $context = "MSGID";
    }
    elseif (!strncmp("msgctxt", $line, 7)) {
      if ($context == "MSGSTR") {   // End current entry, start a new one
        _locale_import_one_string($op, $current, $mode, $lang, $file, $group);
        $current = array();
      }
      elseif (!empty($current["msgctxt"])) { // Already in this context? Parse error
        _locale_import_message('The translation file %filename contains an error: "msgctxt" is unexpected on line %line.', $file, $lineno);
        return FALSE;
      }
      $line = trim(substr($line, 7));
      $quoted = _locale_import_parse_quoted($line);
      if ($quoted === FALSE) {
        _locale_import_message('The translation file %filename contains a syntax error on line %line.', $file, $lineno);
        return FALSE;
      }
      $current["msgctxt"] = $quoted;
      $context = "MSGCTXT";
    }
    elseif (!strncmp("msgstr[", $line, 7)) {
      if (($context != "MSGID") && ($context != "MSGCTXT") && ($context != "MSGID_PLURAL") && ($context != "MSGSTR_ARR")) { // Must come after msgid, msgxtxt, msgid_plural, or msgstr[]
        _locale_import_message('The translation file %filename contains an error: "msgstr[]" is unexpected on line %line.', $file, $lineno);
        return FALSE;
      }
      if (strpos($line, "]") === FALSE) {
        _locale_import_message('The translation file %filename contains a syntax error on line %line.', $file, $lineno);
        return FALSE;
      }
      $frombracket = strstr($line, "[");
      $plural = substr($frombracket, 1, strpos($frombracket, "]") - 1);
      $line = trim(strstr($line, " "));
      $quoted = _locale_import_parse_quoted($line);
      if ($quoted === FALSE) {
        _locale_import_message('The translation file %filename contains a syntax error on line %line.', $file, $lineno);
        return FALSE;
      }
      $current["msgstr"][$plural] = $quoted;
      $context = "MSGSTR_ARR";
    }
    elseif (!strncmp("msgstr", $line, 6)) {
      if (($context != "MSGID") && ($context != "MSGCTXT")) {   // Should come just after a msgid or msgctxt block
        _locale_import_message('The translation file %filename contains an error: "msgstr" is unexpected on line %line.', $file, $lineno);
        return FALSE;
      }
      $line = trim(substr($line, 6));
      $quoted = _locale_import_parse_quoted($line);
      if ($quoted === FALSE) {
        _locale_import_message('The translation file %filename contains a syntax error on line %line.', $file, $lineno);
        return FALSE;
      }
      $current["msgstr"] = $quoted;
      $context = "MSGSTR";
    }
    elseif ($line != "") {
      $quoted = _locale_import_parse_quoted($line);
      if ($quoted === FALSE) {
        _locale_import_message('The translation file %filename contains a syntax error on line %line.', $file, $lineno);
        return FALSE;
      }
      if (($context == "MSGID") || ($context == "MSGID_PLURAL")) {
        $current["msgid"] .= $quoted;
      }
      elseif ($context == "MSGCTXT") {
        $current["msgctxt"] .= $quoted;
      }
      elseif ($context == "MSGSTR") {
        $current["msgstr"] .= $quoted;
      }
      elseif ($context == "MSGSTR_ARR") {
        $current["msgstr"][$plural] .= $quoted;
      }
      else {
        _locale_import_message('The translation file %filename contains an error: there is an unexpected string on line %line.', $file, $lineno);
        return FALSE;
      }
    }
  }

  // End of PO file, flush last entry
  if (!empty($current) && !empty($current['msgstr'])) {
    _locale_import_one_string($op, $current, $mode, $lang, $file, $group);
  }
  elseif ($context != "COMMENT") {
    _locale_import_message('The translation file %filename ended unexpectedly at line %line.', $file, $lineno);
    return FALSE;
  }

}

/**
 * Sets an error message occurred during locale file parsing.
 *
 * @param $message
 *   The message to be translated.
 * @param $file
 *   Drupal file object corresponding to the PO file to import.
 * @param $lineno
 *   An optional line number argument.
 */
function _locale_import_message($message, $file, $lineno = NULL) {
  $vars = array('%filename' => $file->filename);
  if (isset($lineno)) {
    $vars['%line'] = $lineno;
  }
  $t = get_t();
  drupal_set_message($t($message, $vars), 'error');
}

/**
 * Imports a string into the database
 *
 * @param $op
 *   Operation to perform: 'db-store', 'db-report', 'mem-store' or 'mem-report'.
 * @param $value
 *   Details of the string stored.
 * @param $mode
 *   Should existing translations be replaced LOCALE_IMPORT_KEEP or
 *   LOCALE_IMPORT_OVERWRITE.
 * @param $lang
 *   Language to store the string in.
 * @param $file
 *   Object representation of file being imported, only required when op is
 *   'db-store'.
 * @param $group
 *   Text group to import PO file into (eg. 'default' for interface
 *   translations).
 */
function _locale_import_one_string($op, $value = NULL, $mode = NULL, $lang = NULL, $file = NULL, $group = 'default') {
  $report = &drupal_static(__FUNCTION__, array('additions' => 0, 'updates' => 0, 'deletes' => 0, 'skips' => 0));
  $header_done = &drupal_static(__FUNCTION__ . ':header_done', FALSE);
  $strings = &drupal_static(__FUNCTION__ . ':strings', array());

  switch ($op) {
    // Return stored strings
    case 'mem-report':
      return $strings;

    // Store string in memory (only supports single strings)
    case 'mem-store':
      $strings[isset($value['msgctxt']) ? $value['msgctxt'] : ''][$value['msgid']] = $value['msgstr'];
      return;

    // Called at end of import to inform the user
    case 'db-report':
      return array($header_done, $report['additions'], $report['updates'], $report['deletes'], $report['skips']);

    // Store the string we got in the database.
    case 'db-store':
      // We got header information.
      if ($value['msgid'] == '') {
        $languages = language_list();
        if (($mode != LOCALE_IMPORT_KEEP) || empty($languages[$lang]->plurals)) {
          // Since we only need to parse the header if we ought to update the
          // plural formula, only run this if we don't need to keep existing
          // data untouched or if we don't have an existing plural formula.
          $header = _locale_import_parse_header($value['msgstr']);

          // Get the plural formula and update in database.
          if (isset($header["Plural-Forms"]) && $p = _locale_import_parse_plural_forms($header["Plural-Forms"], $file->uri)) {
            list($nplurals, $plural) = $p;
            db_update('languages')
              ->fields(array(
                'plurals' => $nplurals,
                'formula' => $plural,
              ))
              ->condition('language', $lang)
              ->execute();
          }
          else {
            db_update('languages')
              ->fields(array(
                'plurals' => 0,
                'formula' => '',
              ))
              ->condition('language', $lang)
              ->execute();
          }
        }
        $header_done = TRUE;
      }

      else {
        // Some real string to import.
        $comments = _locale_import_shorten_comments(empty($value['#']) ? array() : $value['#']);

        if (strpos($value['msgid'], "\0")) {
          // This string has plural versions.
          $english = explode("\0", $value['msgid'], 2);
          $entries = array_keys($value['msgstr']);
          for ($i = 3; $i <= count($entries); $i++) {
            $english[] = $english[1];
          }
          $translation = array_map('_locale_import_append_plural', $value['msgstr'], $entries);
          $english = array_map('_locale_import_append_plural', $english, $entries);
          foreach ($translation as $key => $trans) {
            if ($key == 0) {
              $plid = 0;
            }
            $plid = _locale_import_one_string_db($report, $lang, isset($value['msgctxt']) ? $value['msgctxt'] : '', $english[$key], $trans, $group, $comments, $mode, $plid, $key);
          }
        }

        else {
          // A simple string to import.
          $english = $value['msgid'];
          $translation = $value['msgstr'];
          _locale_import_one_string_db($report, $lang, isset($value['msgctxt']) ? $value['msgctxt'] : '', $english, $translation, $group, $comments, $mode);
        }
      }
  } // end of db-store operation
}

/**
 * Import one string into the database.
 *
 * @param $report
 *   Report array summarizing the number of changes done in the form:
 *   array(inserts, updates, deletes).
 * @param $langcode
 *   Language code to import string into.
 * @param $context
 *   The context of this string.
 * @param $source
 *   Source string.
 * @param $translation
 *   Translation to language specified in $langcode.
 * @param $textgroup
 *   Name of textgroup to store translation in.
 * @param $location
 *   Location value to save with source string.
 * @param $mode
 *   Import mode to use, LOCALE_IMPORT_KEEP or LOCALE_IMPORT_OVERWRITE.
 * @param $plid
 *   Optional plural ID to use.
 * @param $plural
 *   Optional plural value to use.
 *
 * @return
 *   The string ID of the existing string modified or the new string added.
 */
function _locale_import_one_string_db(&$report, $langcode, $context, $source, $translation, $textgroup, $location, $mode, $plid = 0, $plural = 0) {
  $lid = db_query("SELECT lid FROM {locales_source} WHERE source = :source AND context = :context AND textgroup = :textgroup", array(':source' => $source, ':context' => $context, ':textgroup' => $textgroup))->fetchField();

  if (!empty($translation)) {
    // Skip this string unless it passes a check for dangerous code.
    // Text groups other than default still can contain HTML tags
    // (i.e. translatable blocks).
    if ($textgroup == "default" && !locale_string_is_safe($translation)) {
      $report['skips']++;
      $lid = 0;
    }
    elseif ($lid) {
      // We have this source string saved already.
      db_update('locales_source')
        ->fields(array(
          'location' => $location,
        ))
        ->condition('lid', $lid)
        ->execute();

      $exists = db_query("SELECT COUNT(lid) FROM {locales_target} WHERE lid = :lid AND language = :language", array(':lid' => $lid, ':language' => $langcode))->fetchField();

      if (!$exists) {
        // No translation in this language.
        db_insert('locales_target')
          ->fields(array(
            'lid' => $lid,
            'language' => $langcode,
            'translation' => $translation,
            'plid' => $plid,
            'plural' => $plural,
          ))
          ->execute();

        $report['additions']++;
      }
      elseif ($mode == LOCALE_IMPORT_OVERWRITE) {
        // Translation exists, only overwrite if instructed.
        db_update('locales_target')
          ->fields(array(
            'translation' => $translation,
            'plid' => $plid,
            'plural' => $plural,
          ))
          ->condition('language', $langcode)
          ->condition('lid', $lid)
          ->execute();

        $report['updates']++;
      }
    }
    else {
      // No such source string in the database yet.
      $lid = db_insert('locales_source')
        ->fields(array(
          'location' => $location,
          'source' => $source,
          'context' => (string) $context,
          'textgroup' => $textgroup,
        ))
        ->execute();

      db_insert('locales_target')
        ->fields(array(
           'lid' => $lid,
           'language' => $langcode,
           'translation' => $translation,
           'plid' => $plid,
           'plural' => $plural
        ))
        ->execute();

      $report['additions']++;
    }
  }
  elseif ($mode == LOCALE_IMPORT_OVERWRITE) {
    // Empty translation, remove existing if instructed.
    db_delete('locales_target')
      ->condition('language', $langcode)
      ->condition('lid', $lid)
      ->condition('plid', $plid)
      ->condition('plural', $plural)
      ->execute();

    $report['deletes']++;
  }

  return $lid;
}

/**
 * Parses a Gettext Portable Object file header
 *
 * @param $header
 *   A string containing the complete header.
 *
 * @return
 *   An associative array of key-value pairs.
 */
function _locale_import_parse_header($header) {
  $header_parsed = array();
  $lines = array_map('trim', explode("\n", $header));
  foreach ($lines as $line) {
    if ($line) {
      list($tag, $contents) = explode(":", $line, 2);
      $header_parsed[trim($tag)] = trim($contents);
    }
  }
  return $header_parsed;
}

/**
 * Parses a Plural-Forms entry from a Gettext Portable Object file header
 *
 * @param $pluralforms
 *   A string containing the Plural-Forms entry.
 * @param $filepath
 *   A string containing the filepath.
 *
 * @return
 *   An array containing the number of plurals and a
 *   formula in PHP for computing the plural form.
 */
function _locale_import_parse_plural_forms($pluralforms, $filepath) {
  // First, delete all whitespace
  $pluralforms = strtr($pluralforms, array(" " => "", "\t" => ""));

  // Select the parts that define nplurals and plural
  $nplurals = strstr($pluralforms, "nplurals=");
  if (strpos($nplurals, ";")) {
    $nplurals = substr($nplurals, 9, strpos($nplurals, ";") - 9);
  }
  else {
    return FALSE;
  }
  $plural = strstr($pluralforms, "plural=");
  if (strpos($plural, ";")) {
    $plural = substr($plural, 7, strpos($plural, ";") - 7);
  }
  else {
    return FALSE;
  }

  // Get PHP version of the plural formula
  $plural = _locale_import_parse_arithmetic($plural);

  if ($plural !== FALSE) {
    return array($nplurals, $plural);
  }
  else {
    drupal_set_message(t('The translation file %filepath contains an error: the plural formula could not be parsed.', array('%filepath' => $filepath)), 'error');
    return FALSE;
  }
}

/**
 * Parses and sanitizes an arithmetic formula into a PHP expression
 *
 * While parsing, we ensure, that the operators have the right
 * precedence and associativity.
 *
 * @param $string
 *   A string containing the arithmetic formula.
 *
 * @return
 *   The PHP version of the formula.
 */
function _locale_import_parse_arithmetic($string) {
  // Operator precedence table
  $precedence = array("(" => -1, ")" => -1, "?" => 1, ":" => 1, "||" => 3, "&&" => 4, "==" => 5, "!=" => 5, "<" => 6, ">" => 6, "<=" => 6, ">=" => 6, "+" => 7, "-" => 7, "*" => 8, "/" => 8, "%" => 8);
  // Right associativity
  $right_associativity = array("?" => 1, ":" => 1);

  $tokens = _locale_import_tokenize_formula($string);

  // Parse by converting into infix notation then back into postfix
  // Operator stack - holds math operators and symbols
  $operator_stack = array();
  // Element Stack - holds data to be operated on
  $element_stack = array();

  foreach ($tokens as $token) {
    $current_token = $token;

    // Numbers and the $n variable are simply pushed into $element_stack
    if (is_numeric($token)) {
      $element_stack[] = $current_token;
    }
    elseif ($current_token == "n") {
      $element_stack[] = '$n';
    }
    elseif ($current_token == "(") {
      $operator_stack[] = $current_token;
    }
    elseif ($current_token == ")") {
      $topop = array_pop($operator_stack);
      while (isset($topop) && ($topop != "(")) {
        $element_stack[] = $topop;
        $topop = array_pop($operator_stack);
      }
    }
    elseif (!empty($precedence[$current_token])) {
      // If it's an operator, then pop from $operator_stack into $element_stack until the
      // precedence in $operator_stack is less than current, then push into $operator_stack
      $topop = array_pop($operator_stack);
      while (isset($topop) && ($precedence[$topop] >= $precedence[$current_token]) && !(($precedence[$topop] == $precedence[$current_token]) && !empty($right_associativity[$topop]) && !empty($right_associativity[$current_token]))) {
        $element_stack[] = $topop;
        $topop = array_pop($operator_stack);
      }
      if ($topop) {
        $operator_stack[] = $topop;   // Return element to top
      }
      $operator_stack[] = $current_token;      // Parentheses are not needed
    }
    else {
      return FALSE;
    }
  }

  // Flush operator stack
  $topop = array_pop($operator_stack);
  while ($topop != NULL) {
    $element_stack[] = $topop;
    $topop = array_pop($operator_stack);
  }

  // Now extract formula from stack
  $previous_size = count($element_stack) + 1;
  while (count($element_stack) < $previous_size) {
    $previous_size = count($element_stack);
    for ($i = 2; $i < count($element_stack); $i++) {
      $op = $element_stack[$i];
      if (!empty($precedence[$op])) {
        $f = "";
        if ($op == ":") {
          $f = $element_stack[$i - 2] . "):" . $element_stack[$i - 1] . ")";
        }
        elseif ($op == "?") {
          $f = "(" . $element_stack[$i - 2] . "?(" . $element_stack[$i - 1];
        }
        else {
          $f = "(" . $element_stack[$i - 2] . $op . $element_stack[$i - 1] . ")";
        }
        array_splice($element_stack, $i - 2, 3, $f);
        break;
      }
    }
  }

  // If only one element is left, the number of operators is appropriate
  if (count($element_stack) == 1) {
    return $element_stack[0];
  }
  else {
    return FALSE;
  }
}

/**
 * Backward compatible implementation of token_get_all() for formula parsing
 *
 * @param $string
 *   A string containing the arithmetic formula.
 *
 * @return
 *   The PHP version of the formula.
 */
function _locale_import_tokenize_formula($formula) {
  $formula = str_replace(" ", "", $formula);
  $tokens = array();
  for ($i = 0; $i < strlen($formula); $i++) {
    if (is_numeric($formula[$i])) {
      $num = $formula[$i];
      $j = $i + 1;
      while ($j < strlen($formula) && is_numeric($formula[$j])) {
        $num .= $formula[$j];
        $j++;
      }
      $i = $j - 1;
      $tokens[] = $num;
    }
    elseif ($pos = strpos(" =<>!&|", $formula[$i])) { // We won't have a space
      $next = $formula[$i + 1];
      switch ($pos) {
        case 1:
        case 2:
        case 3:
        case 4:
          if ($next == '=') {
            $tokens[] = $formula[$i] . '=';
            $i++;
          }
          else {
            $tokens[] = $formula[$i];
          }
          break;
        case 5:
          if ($next == '&') {
            $tokens[] = '&&';
            $i++;
          }
          else {
            $tokens[] = $formula[$i];
          }
          break;
        case 6:
          if ($next == '|') {
            $tokens[] = '||';
            $i++;
          }
          else {
            $tokens[] = $formula[$i];
          }
          break;
      }
    }
    else {
      $tokens[] = $formula[$i];
    }
  }
  return $tokens;
}

/**
 * Modify a string to contain proper count indices
 *
 * This is a callback function used via array_map()
 *
 * @param $entry
 *   An array element.
 * @param $key
 *   Index of the array element.
 */
function _locale_import_append_plural($entry, $key) {
  // No modifications for 0, 1
  if ($key == 0 || $key == 1) {
    return $entry;
  }

  // First remove any possibly false indices, then add new ones
  $entry = preg_replace('/(@count)\[[0-9]\]/', '\\1', $entry);
  return preg_replace('/(@count)/', "\\1[$key]", $entry);
}

/**
 * Generate a short, one string version of the passed comment array
 *
 * @param $comment
 *   An array of strings containing a comment.
 *
 * @return
 *   Short one string version of the comment.
 */
function _locale_import_shorten_comments($comment) {
  $comm = '';
  while (count($comment)) {
    $test = $comm . substr(array_shift($comment), 1) . ', ';
    if (strlen($comm) < 130) {
      $comm = $test;
    }
    else {
      break;
    }
  }
  return trim(substr($comm, 0, -2));
}

/**
 * Parses a string in quotes
 *
 * @param $string
 *   A string specified with enclosing quotes.
 *
 * @return
 *   The string parsed from inside the quotes.
 */
function _locale_import_parse_quoted($string) {
  if (substr($string, 0, 1) != substr($string, -1, 1)) {
    return FALSE;   // Start and end quotes must be the same
  }
  $quote = substr($string, 0, 1);
  $string = substr($string, 1, -1);
  if ($quote == '"') {        // Double quotes: strip slashes
    return stripcslashes($string);
  }
  elseif ($quote == "'") {  // Simple quote: return as-is
    return $string;
  }
  else {
    return FALSE;             // Unrecognized quote
  }
}
/**
 * @} End of "locale-api-import"
 */

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

      $source = db_query("SELECT lid, location FROM {locales_source} WHERE source = :source AND textgroup = 'default'", array(':source' => $string))->fetchObject();
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
            'textgroup' => 'default',
          ))
          ->execute();
      }
    }
  }
}

/**
 * @defgroup locale-api-export Translation (template) export API.
 * @{
 */

/**
 * Generates a structured array of all strings with translations in
 * $language, if given. This array can be used to generate an export
 * of the string in the database.
 *
 * @param $language
 *   Language object to generate the output for, or NULL if generating
 *   translation template.
 * @param $group
 *   Text group to export PO file from (eg. 'default' for interface
 *   translations).
 */
function _locale_export_get_strings($language = NULL, $group = 'default') {
  if (isset($language)) {
    $result = db_query("SELECT s.lid, s.source, s.context, s.location, t.translation, t.plid, t.plural FROM {locales_source} s LEFT JOIN {locales_target} t ON s.lid = t.lid AND t.language = :language WHERE s.textgroup = :textgroup ORDER BY t.plid, t.plural", array(':language' => $language->language, ':textgroup' => $group));
  }
  else {
    $result = db_query("SELECT s.lid, s.source, s.context, s.location, t.plid, t.plural FROM {locales_source} s LEFT JOIN {locales_target} t ON s.lid = t.lid WHERE s.textgroup = :textgroup ORDER BY t.plid, t.plural", array(':textgroup' => $group));
  }
  $strings = array();
  foreach ($result as $child) {
    $string = array(
      'comment'     => $child->location,
      'source'      => $child->source,
      'context'     => $child->context,
      'translation' => isset($child->translation) ? $child->translation : '',
    );
    if ($child->plid) {
      // Has a parent lid. Since we process in the order of plids,
      // we already have the parent in the array, so we can add the
      // lid to the next plural version to it. This builds a linked
      // list of plurals.
      $string['child'] = TRUE;
      $strings[$child->plid]['plural'] = $child->lid;
    }
    $strings[$child->lid] = $string;
  }
  return $strings;
}

/**
 * Generates the PO(T) file contents for given strings.
 *
 * @param $language
 *   Language object to generate the output for, or NULL if generating
 *   translation template.
 * @param $strings
 *   Array of strings to export. See _locale_export_get_strings()
 *   on how it should be formatted.
 * @param $header
 *   The header portion to use for the output file. Defaults
 *   are provided for PO and POT files.
 */
function _locale_export_po_generate($language = NULL, $strings = array(), $header = NULL) {
  global $user;

  if (!isset($header)) {
    if (isset($language)) {
      $header = '# ' . $language->name . ' translation of ' . variable_get('site_name', 'Drupal') . "\n";
      $header .= '# Generated by ' . $user->name . ' <' . $user->mail . ">\n";
      $header .= "#\n";
      $header .= "msgid \"\"\n";
      $header .= "msgstr \"\"\n";
      $header .= "\"Project-Id-Version: PROJECT VERSION\\n\"\n";
      $header .= "\"POT-Creation-Date: " . date("Y-m-d H:iO") . "\\n\"\n";
      $header .= "\"PO-Revision-Date: " . date("Y-m-d H:iO") . "\\n\"\n";
      $header .= "\"Last-Translator: NAME <EMAIL@ADDRESS>\\n\"\n";
      $header .= "\"Language-Team: LANGUAGE <EMAIL@ADDRESS>\\n\"\n";
      $header .= "\"MIME-Version: 1.0\\n\"\n";
      $header .= "\"Content-Type: text/plain; charset=utf-8\\n\"\n";
      $header .= "\"Content-Transfer-Encoding: 8bit\\n\"\n";
      if ($language->formula && $language->plurals) {
        $header .= "\"Plural-Forms: nplurals=" . $language->plurals . "; plural=" . strtr($language->formula, array('$' => '')) . ";\\n\"\n";
      }
    }
    else {
      $header = "# LANGUAGE translation of PROJECT\n";
      $header .= "# Copyright (c) YEAR NAME <EMAIL@ADDRESS>\n";
      $header .= "#\n";
      $header .= "msgid \"\"\n";
      $header .= "msgstr \"\"\n";
      $header .= "\"Project-Id-Version: PROJECT VERSION\\n\"\n";
      $header .= "\"POT-Creation-Date: " . date("Y-m-d H:iO") . "\\n\"\n";
      $header .= "\"PO-Revision-Date: YYYY-mm-DD HH:MM+ZZZZ\\n\"\n";
      $header .= "\"Last-Translator: NAME <EMAIL@ADDRESS>\\n\"\n";
      $header .= "\"Language-Team: LANGUAGE <EMAIL@ADDRESS>\\n\"\n";
      $header .= "\"MIME-Version: 1.0\\n\"\n";
      $header .= "\"Content-Type: text/plain; charset=utf-8\\n\"\n";
      $header .= "\"Content-Transfer-Encoding: 8bit\\n\"\n";
      $header .= "\"Plural-Forms: nplurals=INTEGER; plural=EXPRESSION;\\n\"\n";
    }
  }

  $output = $header . "\n";

  foreach ($strings as $lid => $string) {
    // Only process non-children, children are output below their parent.
    if (!isset($string['child'])) {
      if ($string['comment']) {
        $output .= '#: ' . $string['comment'] . "\n";
      }
      if (!empty($string['context'])) {
        $output .= 'msgctxt ' . _locale_export_string($string['context']);
      }
      $output .= 'msgid ' . _locale_export_string($string['source']);
      if (!empty($string['plural'])) {
        $plural = $string['plural'];
        $output .= 'msgid_plural ' . _locale_export_string($strings[$plural]['source']);
        if (isset($language)) {
          $translation = $string['translation'];
          for ($i = 0; $i < $language->plurals; $i++) {
            $output .= 'msgstr[' . $i . '] ' . _locale_export_string($translation);
            if ($plural) {
              $translation = _locale_export_remove_plural($strings[$plural]['translation']);
              $plural = isset($strings[$plural]['plural']) ? $strings[$plural]['plural'] : 0;
            }
            else {
              $translation = '';
            }
          }
        }
        else {
          $output .= 'msgstr[0] ""' . "\n";
          $output .= 'msgstr[1] ""' . "\n";
        }
      }
      else {
        $output .= 'msgstr ' . _locale_export_string($string['translation']);
      }
      $output .= "\n";
    }
  }
  return $output;
}

/**
 * Write a generated PO or POT file to the output.
 *
 * @param $language
 *   Language object to generate the output for, or NULL if generating
 *   translation template.
 * @param $output
 *   The PO(T) file to output as a string. See _locale_export_generate_po()
 *   on how it can be generated.
 */
function _locale_export_po($language = NULL, $output = NULL) {
  // Log the export event.
  if (isset($language)) {
    $filename = $language->language . '.po';
    watchdog('locale', 'Exported %locale translation file: %filename.', array('%locale' => $language->name, '%filename' => $filename));
  }
  else {
    $filename = 'drupal.pot';
    watchdog('locale', 'Exported translation file: %filename.', array('%filename' => $filename));
  }
  // Download the file for the client.
  header("Content-Disposition: attachment; filename=$filename");
  header("Content-Type: text/plain; charset=utf-8");
  print $output;
  drupal_exit();
}

/**
 * Print out a string on multiple lines
 */
function _locale_export_string($str) {
  $stri = addcslashes($str, "\0..\37\\\"");
  $parts = array();

  // Cut text into several lines
  while ($stri != "") {
    $i = strpos($stri, "\\n");
    if ($i === FALSE) {
      $curstr = $stri;
      $stri = "";
    }
    else {
      $curstr = substr($stri, 0, $i + 2);
      $stri = substr($stri, $i + 2);
    }
    $curparts = explode("\n", _locale_export_wrap($curstr, 70));
    $parts = array_merge($parts, $curparts);
  }

  // Multiline string
  if (count($parts) > 1) {
    return "\"\"\n\"" . implode("\"\n\"", $parts) . "\"\n";
  }
  // Single line string
  elseif (count($parts) == 1) {
    return "\"$parts[0]\"\n";
  }
  // No translation
  else {
    return "\"\"\n";
  }
}

/**
 * Custom word wrapping for Portable Object (Template) files.
 */
function _locale_export_wrap($str, $len) {
  $words = explode(' ', $str);
  $return = array();

  $cur = "";
  $nstr = 1;
  while (count($words)) {
    $word = array_shift($words);
    if ($nstr) {
      $cur = $word;
      $nstr = 0;
    }
    elseif (strlen("$cur $word") > $len) {
      $return[] = $cur . " ";
      $cur = $word;
    }
    else {
      $cur = "$cur $word";
    }
  }
  $return[] = $cur;

  return implode("\n", $return);
}

/**
 * Removes plural index information from a string
 */
function _locale_export_remove_plural($entry) {
  return preg_replace('/(@count)\[[0-9]\]/', '\\1', $entry);
}
/**
 * @} End of "locale-api-export"
 */

/**
 * @defgroup locale-api-seek String search functions.
 * @{
 */

/**
 * Perform a string search and display results in a table
 */
function _locale_translate_seek() {
  $output = '';

  // We have at least one criterion to match
  if (!($query = _locale_translate_seek_query())) {
    $query = array(
      'translation' => 'all',
      'group' => 'all',
      'language' => 'all',
      'string' => '',
    );
  }

  $sql_query = db_select('locales_source', 's');
  $sql_query->leftJoin('locales_target', 't', 't.lid = s.lid');
  $sql_query->fields('s', array('source', 'location', 'context', 'lid', 'textgroup'));
  $sql_query->fields('t', array('translation', 'language'));

  // Compute LIKE section.
  switch ($query['translation']) {
    case 'translated':
      $sql_query->condition('t.translation', '%' . db_like($query['string']) . '%', 'LIKE');
      $sql_query->orderBy('t.translation', 'DESC');
      break;
    case 'untranslated':
      $sql_query->condition(db_and()
        ->condition('s.source', '%' . db_like($query['string']) . '%', 'LIKE')
        ->isNull('t.translation')
      );
      $sql_query->orderBy('s.source');
      break;
    case 'all' :
    default:
      $condition = db_or()
        ->condition('s.source', '%' . db_like($query['string']) . '%', 'LIKE');
      if ($query['language'] != 'en') {
        // Only search in translations if the language is not forced to English.
        $condition->condition('t.translation', '%' . db_like($query['string']) . '%', 'LIKE');
      }
      $sql_query->condition($condition);
      break;
  }

  $limit_language = NULL;
  if ($query['language'] != 'en' && $query['language'] != 'all') {
    $sql_query->condition('language', $query['language']);
    $limit_language = $query['language'];
  }

  // Add a condition on the text group.
  if (!empty($query['group']) && $query['group'] != 'all') {
    $sql_query->condition('s.textgroup', $query['group']);
  }

  $sql_query = $sql_query->extend('PagerDefault')->limit(50);
  $locales = $sql_query->execute();

  $groups = module_invoke_all('locale', 'groups');
  $header = array(t('Text group'), t('String'), t('Context'), ($limit_language) ? t('Language') : t('Languages'), array('data' => t('Operations'), 'colspan' => '2'));

  $strings = array();
  foreach ($locales as $locale) {
    if (!isset($strings[$locale->lid])) {
      $strings[$locale->lid] = array(
        'group' => $locale->textgroup,
        'languages' => array(),
        'location' => $locale->location,
        'source' => $locale->source,
        'context' => $locale->context,
      );
    }
    if (isset($locale->language)) {
      $strings[$locale->lid]['languages'][$locale->language] = $locale->translation;
    }
  }

  $rows = array();
  foreach ($strings as $lid => $string) {
    $rows[] = array(
      $groups[$string['group']],
      array('data' => check_plain(truncate_utf8($string['source'], 150, FALSE, TRUE)) . '<br /><small>' . $string['location'] . '</small>'),
      $string['context'],
      array('data' => _locale_translate_language_list($string['languages'], $limit_language), 'align' => 'center'),
      array('data' => l(t('edit'), "admin/config/regional/translate/edit/$lid", array('query' => drupal_get_destination())), 'class' => array('nowrap')),
      array('data' => l(t('delete'), "admin/config/regional/translate/delete/$lid", array('query' => drupal_get_destination())), 'class' => array('nowrap')),
    );
  }

  $output .= theme('table', array('header' => $header, 'rows' => $rows, 'empty' => t('No strings available.')));
  $output .= theme('pager');

  return $output;
}

/**
 * Build array out of search criteria specified in request variables
 */
function _locale_translate_seek_query() {
  $query = &drupal_static(__FUNCTION__);
  if (!isset($query)) {
    $query = array();
    $fields = array('string', 'language', 'translation', 'group');
    foreach ($fields as $field) {
      if (isset($_SESSION['locale_translation_filter'][$field])) {
        $query[$field] = $_SESSION['locale_translation_filter'][$field];
      }
    }
  }
  return $query;
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
  $result = db_query("SELECT s.lid, s.source, t.translation FROM {locales_source} s INNER JOIN {locales_target} t ON s.lid = t.lid AND t.language = :language WHERE s.location LIKE '%.js%' AND s.textgroup = :textgroup AND t.translation IS NOT NULL", array(':language' => $language->language, ':textgroup' => 'default'));

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
    $default_langcode = language_default('language');
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
 * List languages in search result table
 */
function _locale_translate_language_list($translation, $limit_language) {
  // Add CSS.
  drupal_add_css(drupal_get_path('module', 'locale') . '/locale.css');

  $languages = language_list();
  unset($languages['en']);
  $output = '';
  foreach ($languages as $langcode => $language) {
    if (!$limit_language || $limit_language == $langcode) {
      $output .= (!empty($translation[$langcode])) ? $langcode . ' ' : "<em class=\"locale-untranslated\">$langcode</em> ";
    }
  }

  return $output;
}
/**
 * @} End of "locale-api-seek"
 */

/**
 * @defgroup locale-api-predefined List of predefined languages
 * @{
 */

/**
 * Prepares the language code list for a select form item with only the unsupported ones
 */
function _locale_prepare_predefined_list() {
  include_once DRUPAL_ROOT . '/includes/iso.inc';
  $languages = language_list();
  $predefined = _locale_get_predefined_list();
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
 * @defgroup locale-autoimport Automatic interface translation import
 * @{
 */

/**
 * Prepare a batch to import translations for all enabled
 * modules in a given language.
 *
 * @param $langcode
 *   Language code to import translations for.
 * @param $finished
 *   Optional finished callback for the batch.
 * @param $skip
 *   Array of component names to skip. Used in the installer for the
 *   second pass import, when most components are already imported.
 *
 * @return
 *   A batch structure or FALSE if no files found.
 */
function locale_batch_by_language($langcode, $finished = NULL, $skip = array()) {
  // Collect all files to import for all enabled modules and themes.
  $files = array();
  $components = array();
  $query = db_select('system', 's');
  $query->fields('s', array('name', 'filename'));
  $query->condition('s.status', 1);
  if (count($skip)) {
    $query->condition('name', $skip, 'NOT IN');
  }
  $result = $query->execute();
  foreach ($result as $component) {
    // Collect all files for all components, names as $langcode.po or
    // with names ending with $langcode.po. This allows for filenames
    // like node-module.de.po to let translators use small files and
    // be able to import in smaller chunks.
    $files = array_merge($files, file_scan_directory(dirname($component->filename) . '/translations', '/(^|\.)' . $langcode . '\.po$/', array('recurse' => FALSE)));
    $components[] = $component->name;
  }

  return _locale_batch_build($files, $finished, $components);
}

/**
 * Prepare a batch to run when installing modules or enabling themes.
 *
 * This batch will import translations for the newly added components
 * in all the languages already set up on the site.
 *
 * @param $components
 *   An array of component (theme and/or module) names to import
 *   translations for.
 * @param $finished
 *   Optional finished callback for the batch.
 */
function locale_batch_by_component($components, $finished = '_locale_batch_system_finished') {
  $files = array();
  $languages = language_list('enabled');
  unset($languages[1]['en']);
  if (count($languages[1])) {
    $language_list = join('|', array_keys($languages[1]));
    // Collect all files to import for all $components.
    $result = db_query("SELECT name, filename FROM {system} WHERE status = 1");
    foreach ($result as $component) {
      if (in_array($component->name, $components)) {
        // Collect all files for this component in all enabled languages, named
        // as $langcode.po or with names ending with $langcode.po. This allows
        // for filenames like node-module.de.po to let translators use small
        // files and be able to import in smaller chunks.
        $files = array_merge($files, file_scan_directory(dirname($component->filename) . '/translations', '/(^|\.)(' . $language_list . ')\.po$/', array('recurse' => FALSE)));
      }
    }
    return _locale_batch_build($files, $finished);
  }
  return FALSE;
}

/**
 * Build a locale batch from an array of files.
 *
 * @param $files
 *   Array of files to import.
 * @param $finished
 *   Optional finished callback for the batch.
 * @param $components
 *   Optional list of component names the batch covers. Used in the installer.
 *
 * @return
 *   A batch structure.
 */
function _locale_batch_build($files, $finished = NULL, $components = array()) {
  $t = get_t();
  if (count($files)) {
    $operations = array();
    foreach ($files as $file) {
      // We call _locale_batch_import for every batch operation.
      $operations[] = array('_locale_batch_import', array($file->uri));
    }
    $batch = array(
      'operations'    => $operations,
      'title'         => $t('Importing interface translations'),
      'init_message'  => $t('Starting import'),
      'error_message' => $t('Error importing interface translations'),
      'file'          => 'includes/locale.inc',
      // This is not a batch API construct, but data passed along to the
      // installer, so we know what did we import already.
      '#components'   => $components,
    );
    if (isset($finished)) {
      $batch['finished'] = $finished;
    }
    return $batch;
  }
  return FALSE;
}

/**
 * Perform interface translation import as a batch step.
 *
 * @param $filepath
 *   Path to a file to import.
 * @param $results
 *   Contains a list of files imported.
 */
function _locale_batch_import($filepath, &$context) {
  // The filename is either {langcode}.po or {prefix}.{langcode}.po, so
  // we can extract the language code to use for the import from the end.
  if (preg_match('!(/|\.)([^\./]+)\.po$!', $filepath, $langcode)) {
    $file = (object) array('filename' => basename($filepath), 'uri' => $filepath);
    _locale_import_read_po('db-store', $file, LOCALE_IMPORT_KEEP, $langcode[2]);
    $context['results'][] = $filepath;
  }
}

/**
 * Finished callback of system page locale import batch.
 * Inform the user of translation files imported.
 */
function _locale_batch_system_finished($success, $results) {
  if ($success) {
    drupal_set_message(format_plural(count($results), 'One translation file imported for the newly installed modules.', '@count translation files imported for the newly installed modules.'));
  }
}

/**
 * Finished callback of language addition locale import batch.
 * Inform the user of translation files imported.
 */
function _locale_batch_language_finished($success, $results) {
  if ($success) {
    drupal_set_message(format_plural(count($results), 'One translation file imported for the enabled modules.', '@count translation files imported for the enabled modules.'));
  }
}

/**
 * @} End of "locale-autoimport"
 */

/**
 * Get list of all predefined and custom countries.
 *
 * @return
 *   An array of all country code => country name pairs.
 */
function country_get_list() {
  include_once DRUPAL_ROOT . '/includes/iso.inc';
  $countries = _country_get_predefined_list();
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
