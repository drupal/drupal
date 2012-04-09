<?php

/**
 * @file
 * Administration functions for locale.module.
 */

/**
 * The language is determined using a URL language indicator:
 * path prefix or domain according to the configuration.
 */
const LANGUAGE_NEGOTIATION_URL = 'locale-url';

/**
 * The language is set based on the browser language settings.
 */
const LANGUAGE_NEGOTIATION_BROWSER = 'locale-browser';

/**
 * The language is determined using the current interface language.
 */
const LANGUAGE_NEGOTIATION_INTERFACE = 'locale-interface';

/**
 * If no URL language is available language is determined using an already
 * detected one.
 */
const LANGUAGE_NEGOTIATION_URL_FALLBACK = 'locale-url-fallback';

/**
 * The language is set based on the user language settings.
 */
const LANGUAGE_NEGOTIATION_USER = 'locale-user';

/**
 * The language is set based on the request/session parameters.
 */
const LANGUAGE_NEGOTIATION_SESSION = 'locale-session';

/**
 * Regular expression pattern used to localize JavaScript strings.
 */
const LOCALE_JS_STRING = '(?:(?:\'(?:\\\\\'|[^\'])*\'|"(?:\\\\"|[^"])*")(?:\s*\+\s*)?)+';

/**
 * Regular expression pattern used to match simple JS object literal.
 *
 * This pattern matches a basic JS object, but will fail on an object with
 * nested objects. Used in JS file parsing for string arg processing.
 */
const LOCALE_JS_OBJECT = '\{.*?\}';

/**
 * Regular expression to match an object containing a key 'context'.
 *
 * Pattern to match a JS object containing a 'context key' with a string value,
 * which is captured. Will fail if there are nested objects.
 */
define('LOCALE_JS_OBJECT_CONTEXT', '
  \{              # match object literal start
  .*?             # match anything, non-greedy
  (?:             # match a form of "context"
    \'context\'
    |
    "context"
    |
    context
  )
  \s*:\s*         # match key-value separator ":"
  (' . LOCALE_JS_STRING . ')  # match context string
  .*?             # match anything, non-greedy
  \}              # match end of object literal
');

/**
 * Flag for locally not customized interface translation.
 *
 * Such translations are imported from .po files downloaded from
 * localize.drupal.org for example.
 */
const LOCALE_NOT_CUSTOMIZED = 0;

/**
 * Flag for locally customized interface translation.
 *
 * Such translations are edited from their imported originals on the user
 * interface or are imported as customized.
 */
const LOCALE_CUSTOMIZED = 1;

/**
 * URL language negotiation: use the path prefix as URL language indicator.
 */
const LANGUAGE_NEGOTIATION_URL_PREFIX = 0;

/**
 * URL language negotiation: use the domain as URL language indicator.
 */
const LANGUAGE_NEGOTIATION_URL_DOMAIN = 1;

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
  global $language_interface;
  return isset($language_interface->langcode) ? $language_interface->langcode : FALSE;
}

/**
 * Identify language from the Accept-language HTTP header we got.
 *
 * We perform browser accept-language parsing only if page cache is disabled,
 * otherwise we would cache a user-specific preference.
 *
 * @param $languages
 *   An array of language objects for enabled languages ordered by weight.
 *
 * @return
 *   A valid language code on success, FALSE otherwise.
 */
function locale_language_from_browser($languages) {
  if (empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
    return FALSE;
  }

  // The Accept-Language header contains information about the language
  // preferences configured in the user's browser / operating system.
  // RFC 2616 (section 14.4) defines the Accept-Language header as follows:
  //   Accept-Language = "Accept-Language" ":"
  //                  1#( language-range [ ";" "q" "=" qvalue ] )
  //   language-range  = ( ( 1*8ALPHA *( "-" 1*8ALPHA ) ) | "*" )
  // Samples: "hu, en-us;q=0.66, en;q=0.33", "hu,en-us;q=0.5"
  $browser_langcodes = array();
  if (preg_match_all('@(?<=[, ]|^)([a-zA-Z-]+|\*)(?:;q=([0-9.]+))?(?:$|\s*,\s*)@', trim($_SERVER['HTTP_ACCEPT_LANGUAGE']), $matches, PREG_SET_ORDER)) {
    foreach ($matches as $match) {
      // We can safely use strtolower() here, tags are ASCII.
      // RFC2616 mandates that the decimal part is no more than three digits,
      // so we multiply the qvalue by 1000 to avoid floating point comparisons.
      $langcode = strtolower($match[1]);
      $qvalue = isset($match[2]) ? (float) $match[2] : 1;
      $browser_langcodes[$langcode] = (int) ($qvalue * 1000);
    }
  }

  // We should take pristine values from the HTTP headers, but Internet Explorer
  // from version 7 sends only specific language tags (eg. fr-CA) without the
  // corresponding generic tag (fr) unless explicitly configured. In that case,
  // we assume that the lowest value of the specific tags is the value of the
  // generic language to be as close to the HTTP 1.1 spec as possible.
  // See http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.4 and
  // http://blogs.msdn.com/b/ie/archive/2006/10/17/accept-language-header-for-internet-explorer-7.aspx
  asort($browser_langcodes);
  foreach ($browser_langcodes as $langcode => $qvalue) {
    $generic_tag = strtok($langcode, '-');
    if (!isset($browser_langcodes[$generic_tag])) {
      $browser_langcodes[$generic_tag] = $qvalue;
    }
  }

  // Find the enabled language with the greatest qvalue, following the rules
  // of RFC 2616 (section 14.4). If several languages have the same qvalue,
  // prefer the one with the greatest weight.
  $best_match_langcode = FALSE;
  $max_qvalue = 0;
  foreach ($languages as $langcode => $language) {
    // Language tags are case insensitive (RFC2616, sec 3.10).
    $langcode = strtolower($langcode);

    // If nothing matches below, the default qvalue is the one of the wildcard
    // language, if set, or is 0 (which will never match).
    $qvalue = isset($browser_langcodes['*']) ? $browser_langcodes['*'] : 0;

    // Find the longest possible prefix of the browser-supplied language
    // ('the language-range') that matches this site language ('the language tag').
    $prefix = $langcode;
    do {
      if (isset($browser_langcodes[$prefix])) {
        $qvalue = $browser_langcodes[$prefix];
        break;
      }
    }
    while ($prefix = substr($prefix, 0, strrpos($prefix, '-')));

    // Find the best match.
    if ($qvalue > $max_qvalue) {
      $best_match_langcode = $language->langcode;
      $max_qvalue = $qvalue;
    }
  }

  return $best_match_langcode;
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

  if ($user->uid && !empty($user->preferred_langcode)) {
    return $user->preferred_langcode;
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

  if (!language_negotiation_method_enabled(LANGUAGE_NEGOTIATION_URL)) {
    return $language_url;
  }

  switch (variable_get('locale_language_negotiation_url_part', LANGUAGE_NEGOTIATION_URL_PREFIX)) {
    case LANGUAGE_NEGOTIATION_URL_PREFIX:
      // $_GET['q'] might not be available at this time, because
      // path initialization runs after the language bootstrap phase.
      list($language, $_GET['q']) = language_url_split_prefix(isset($_GET['q']) ? $_GET['q'] : NULL, $languages);
      if ($language !== FALSE) {
        $language_url = $language->langcode;
      }
      break;

    case LANGUAGE_NEGOTIATION_URL_DOMAIN:
      $domains = locale_language_negotiation_url_domains();
      foreach ($languages as $language) {
        // Skip check if the language doesn't have a domain.
        if (!empty($domains[$language->langcode])) {
          // Only compare the domains not the protocols or ports.
          // Remove protocol and add http:// so parse_url works
          $host = 'http://' . str_replace(array('http://', 'https://'), '', $domains[$language->langcode]);
          $host = parse_url($host, PHP_URL_HOST);
          if ($_SERVER['HTTP_HOST'] == $host) {
            $language_url = $language->langcode;
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
 * default language negotiation method. Each built-in language type has a
 * separate initialization:
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
 *   language_negotiation_method_invoke() to every language method callback,
 *   but it is not actually needed here. Defaults to NULL.
 * @param $language_type
 *   (optional) The language type to fall back to. Defaults to the interface
 *   language.
 *
 * @return
 *   A valid language code.
 */
function locale_language_url_fallback($language = NULL, $language_type = LANGUAGE_TYPE_INTERFACE) {
  $default = language_default();
  $prefix = (variable_get('locale_language_negotiation_url_part', LANGUAGE_NEGOTIATION_URL_PREFIX) == LANGUAGE_NEGOTIATION_URL_PREFIX);

  // If the default language is not configured to convey language information,
  // a missing URL language information indicates that URL language should be
  // the default one, otherwise we fall back to an already detected language.
  $domains = locale_language_negotiation_url_domains();
  $prefixes = locale_language_negotiation_url_prefixes();
  if (($prefix && empty($prefixes[$default->langcode])) || (!$prefix && empty($domains[$default->langcode]))) {
    return $default->langcode;
  }
  else {
    return $GLOBALS[$language_type]->langcode;
  }
}

/**
 * Return links for the URL language switcher block.
 *
 * Translation links may be provided by other modules.
 */
function locale_language_switcher_url($type, $path) {
  // Get the enabled languages only.
  $languages = language_list(TRUE);
  $links = array();

  foreach ($languages as $language) {
    $links[$language->langcode] = array(
      'href'       => $path,
      'title'      => $language->name,
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
  $language_query = isset($_SESSION[$param]) ? $_SESSION[$param] : $GLOBALS[$type]->langcode;

  // Get the enabled languages only.
  $languages = language_list(TRUE);
  $links = array();

  $query = $_GET;
  unset($query['q']);

  foreach ($languages as $language) {
    $langcode = $language->langcode;
    $links[$langcode] = array(
      'href'       => $path,
      'title'      => $language->name,
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
 * Rewrite URLs for the URL language negotiation method.
 */
function locale_language_url_rewrite_url(&$path, &$options) {
  static $drupal_static_fast;
  if (!isset($drupal_static_fast)) {
    $drupal_static_fast['languages'] = &drupal_static(__FUNCTION__);
  }
  $languages = &$drupal_static_fast['languages'];

  if (!isset($languages)) {
    // Get the enabled languages only.
    $languages = language_list(TRUE);
    $languages = array_flip(array_keys($languages));
  }

  // Language can be passed as an option, or we go for current URL language.
  if (!isset($options['language'])) {
    global $language_url;
    $options['language'] = $language_url;
  }
  // We allow only enabled languages here.
  elseif (!isset($languages[$options['language']->langcode])) {
    unset($options['language']);
    return;
  }

  if (isset($options['language'])) {
    switch (variable_get('locale_language_negotiation_url_part', LANGUAGE_NEGOTIATION_URL_PREFIX)) {
      case LANGUAGE_NEGOTIATION_URL_DOMAIN:
        $domains = locale_language_negotiation_url_domains();
        if (!empty($domains[$options['language']->langcode])) {
          // Ask for an absolute URL with our modified base_url.
          global $is_https;
          $url_scheme = ($is_https) ? 'https://' : 'http://';
          $options['absolute'] = TRUE;
          $options['base_url'] = $url_scheme . $domains[$options['language']->langcode];
          if (isset($options['https']) && variable_get('https', FALSE)) {
            if ($options['https'] === TRUE) {
              $options['base_url'] = str_replace('http://', 'https://', $options['base_url']);
            }
            elseif ($options['https'] === FALSE) {
              $options['base_url'] = str_replace('https://', 'http://', $options['base_url']);
            }
          }
        }
        break;

      case LANGUAGE_NEGOTIATION_URL_PREFIX:
        $prefixes = locale_language_negotiation_url_prefixes();
        if (!empty($prefixes[$options['language']->langcode])) {
          $options['prefix'] = $prefixes[$options['language']->langcode] . '/';
        }
        break;
    }
  }
}

/**
 * Reads language prefixes and uses the langcode if no prefix is set.
 */
function locale_language_negotiation_url_prefixes() {
  return variable_get('locale_language_negotiation_url_prefixes', array());
}

/**
 * Saves language prefix settings.
 */
function locale_language_negotiation_url_prefixes_save(array $prefixes) {
  variable_set('locale_language_negotiation_url_prefixes', $prefixes);
}

/**
 * Reads language domains.
 */
function locale_language_negotiation_url_domains() {
  return variable_get('locale_language_negotiation_url_domains', array());
}

/**
 * Saves the language domain settings.
 */
function locale_language_negotiation_url_domains_save(array $domains) {
  variable_set('locale_language_negotiation_url_domains', $domains);
}

/**
 * Rewrite URLs for the Session language negotiation method.
 */
function locale_language_url_rewrite_session(&$path, &$options) {
  static $query_rewrite, $query_param, $query_value;

  // The following values are not supposed to change during a single page
  // request processing.
  if (!isset($query_rewrite)) {
    global $user;
    if (!$user->uid) {
      // Get the enabled languages only.
      $languages = language_list(TRUE);
      $query_param = check_plain(variable_get('locale_language_negotiation_session_param', 'language'));
      $query_value = isset($_GET[$query_param]) ? check_plain($_GET[$query_param]) : NULL;
      $query_rewrite = isset($languages[$query_value]) && language_negotiation_method_enabled(LANGUAGE_NEGOTIATION_SESSION);
    }
    else {
      $query_rewrite = FALSE;
    }
  }

  // If the user is anonymous, the user language negotiation method is enabled,
  // and the corresponding option has been set, we must preserve any explicit
  // user language preference even with cookies disabled.
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
 * Parses a JavaScript file, extracts strings wrapped in Drupal.t() and
 * Drupal.formatPlural() and inserts them into the database.
 */
function _locale_parse_js_file($filepath) {
  // The file path might contain a query string, so make sure we only use the
  // actual file.
  $parsed_url = drupal_parse_url($filepath);
  $filepath = $parsed_url['path'];
  // Load the JavaScript file.
  $file = file_get_contents($filepath);

  // Match all calls to Drupal.t() in an array.
  // Note: \s also matches newlines with the 's' modifier.
  preg_match_all('~
    [^\w]Drupal\s*\.\s*t\s*                       # match "Drupal.t" with whitespace
    \(\s*                                         # match "(" argument list start
    (' . LOCALE_JS_STRING . ')\s*                 # capture string argument
    (?:,\s*' . LOCALE_JS_OBJECT . '\s*            # optionally capture str args
      (?:,\s*' . LOCALE_JS_OBJECT_CONTEXT . '\s*) # optionally capture context
    ?)?                                           # close optional args
    [,\)]                                         # match ")" or "," to finish
    ~sx', $file, $t_matches);

  // Match all Drupal.formatPlural() calls in another array.
  preg_match_all('~
    [^\w]Drupal\s*\.\s*formatPlural\s*  # match "Drupal.formatPlural" with whitespace
    \(                                  # match "(" argument list start
    \s*.+?\s*,\s*                       # match count argument
    (' . LOCALE_JS_STRING . ')\s*,\s*   # match singular string argument
    (                             # capture plural string argument
      (?:                         # non-capturing group to repeat string pieces
        (?:
          \'                      # match start of single-quoted string
          (?:\\\\\'|[^\'])*       # match any character except unescaped single-quote
          @count                  # match "@count"
          (?:\\\\\'|[^\'])*       # match any character except unescaped single-quote
          \'                      # match end of single-quoted string
          |
          "                       # match start of double-quoted string
          (?:\\\\"|[^"])*         # match any character except unescaped double-quote
          @count                  # match "@count"
          (?:\\\\"|[^"])*         # match any character except unescaped double-quote
          "                       # match end of double-quoted string
        )
        (?:\s*\+\s*)?             # match "+" with possible whitespace, for str concat
      )+                          # match multiple because we supports concatenating strs
    )\s*                          # end capturing of plural string argument
    (?:,\s*' . LOCALE_JS_OBJECT . '\s*          # optionally capture string args
      (?:,\s*' . LOCALE_JS_OBJECT_CONTEXT . '\s*)?  # optionally capture context
    )?
    [,\)]
    ~sx', $file, $plural_matches);

  $matches = array();

  // Add strings from Drupal.t().
  foreach ($t_matches[1] as $key => $string) {
    $matches[] = array(
      'string'  => $string,
      'context' => $t_matches[2][$key],
    );
  }

  // Add string from Drupal.formatPlural().
  foreach ($plural_matches[1] as $key => $string) {
    $matches[] = array(
      'string'  => $string,
      'context' => $plural_matches[3][$key],
    );

    // If there is also a plural version of this string, add it to the strings array.
    if (isset($plural_matches[2][$key])) {
      $matches[] = array(
        'string'  => $plural_matches[2][$key],
        'context' => $plural_matches[3][$key],
      );
    }
  }

  // Loop through all matches and process them.
  foreach ($matches as $key => $match) {

    // Remove the quotes and string concatenations from the string and context.
    $string =  implode('', preg_split('~(?<!\\\\)[\'"]\s*\+\s*[\'"]~s', substr($match['string'], 1, -1)));
    $context = implode('', preg_split('~(?<!\\\\)[\'"]\s*\+\s*[\'"]~s', substr($match['context'], 1, -1)));

    $source = db_query("SELECT lid, location FROM {locales_source} WHERE source = :source AND context = :context", array(':source' => $string, ':context' => $context))->fetchObject();
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
          'location'  => $filepath,
          'source'    => $string,
          'context'   => $context,
        ))
        ->execute();
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
    if (!locale_translate_english()) {
      unset($languages['en']);
    }
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
 * @param $langcode
 *   The language, the translation file should be (re)created for.
 */
function _locale_rebuild_js($langcode = NULL) {
  if (!isset($langcode)) {
    global $language_interface;
    $language = $language_interface;
  }
  else {
    // Get information about the locale.
    $languages = language_list();
    $language = $languages[$langcode];
  }

  // Construct the array for JavaScript translations.
  // Only add strings with a translation to the translations array.
  $result = db_query("SELECT s.lid, s.source, s.context, t.translation FROM {locales_source} s INNER JOIN {locales_target} t ON s.lid = t.lid AND t.language = :language WHERE s.location LIKE '%.js%'", array(':language' => $language->langcode));

  $translations = array();
  foreach ($result as $data) {
    $translations[$data->context][$data->source] = $data->translation;
  }

  // Construct the JavaScript file, if there are translations.
  $data_hash = NULL;
  $data = $status = '';
  if (!empty($translations)) {

    $data = "Drupal.locale = { ";

    $locale_plurals = variable_get('locale_translation_plurals', array());
    if (!empty($locale_plurals[$language->langcode])) {
      $data .= "'pluralFormula': function (\$n) { return Number({$locale_plurals[$language->langcode]['formula']}); }, ";
    }

    $data .= "'strings': " . drupal_json_encode($translations) . " };";
    $data_hash = drupal_hash_base64($data);
  }

  // Construct the filepath where JS translation files are stored.
  // There is (on purpose) no front end to edit that variable.
  $dir = 'public://' . variable_get('locale_js_directory', 'languages');

  // Delete old file, if we have no translations anymore, or a different file to be saved.
  $locale_javascripts = variable_get('locale_translation_javascript', array());
  $changed_hash = !isset($locale_javascripts[$language->langcode]) || ($locale_javascripts[$language->langcode] != $data_hash);
  if (!empty($locale_javascripts[$language->langcode]) && (!$data || $changed_hash)) {
    file_unmanaged_delete($dir . '/' . $language->langcode . '_' . $locale_javascripts[$language->langcode] . '.js');
    $locale_javascripts[$language->langcode] = '';
    $status = 'deleted';
  }

  // Only create a new file if the content has changed or the original file got
  // lost.
  $dest = $dir . '/' . $language->langcode . '_' . $data_hash . '.js';
  if ($data && ($changed_hash || !file_exists($dest))) {
    // Ensure that the directory exists and is writable, if possible.
    file_prepare_directory($dir, FILE_CREATE_DIRECTORY);

    // Save the file.
    if (file_unmanaged_save_data($data, $dest)) {
      $locale_javascripts[$language->langcode] = $data_hash;
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
      $locale_javascripts[$language->langcode] = '';
      $status = 'error';
    }
  }

  // Save the new JavaScript hash (or an empty value if the file just got
  // deleted). Act only if some operation was executed that changed the hash
  // code.
  if ($status && $changed_hash) {
    variable_set('locale_translation_javascript', $locale_javascripts);
  }

  // Log the operation and return success flag.
  switch ($status) {
    case 'updated':
      watchdog('locale', 'Updated JavaScript translation file for the language %language.', array('%language' => $language->name));
      return TRUE;
    case 'rebuilt':
      watchdog('locale', 'JavaScript translation file %file.js was lost.', array('%file' => $locale_javascripts[$language->langcode]), WATCHDOG_WARNING);
      // Proceed to the 'created' case as the JavaScript translation file has
      // been created again.
    case 'created':
      watchdog('locale', 'Created JavaScript translation file for the language %language.', array('%language' => $language->name));
      return TRUE;
    case 'deleted':
      watchdog('locale', 'Removed JavaScript translation file for the language %language because no translations currently exist for that language.', array('%language' => $language->name));
      return TRUE;
    case 'error':
      watchdog('locale', 'An error occurred during creation of the JavaScript translation file for the language %language.', array('%language' => $language->name), WATCHDOG_ERROR);
      return FALSE;
    default:
      // No operation needed.
      return TRUE;
  }
}

/**
 * Get list of all predefined and custom countries.
 *
 * @return
 *   An array of all country code => country name pairs.
 */
function country_get_list() {
  include_once DRUPAL_ROOT . '/core/includes/standard.inc';
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
