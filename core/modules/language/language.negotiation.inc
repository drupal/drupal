<?php

/**
 * @file
 * Language negotiation functions.
 */

/**
 * The language is determined using path prefix or domain.
 */
const LANGUAGE_NEGOTIATION_URL = 'language-url';

/**
 * The language is set based on the browser language settings.
 */
const LANGUAGE_NEGOTIATION_BROWSER = 'language-browser';

/**
 * The language is determined using the current interface language.
 */
const LANGUAGE_NEGOTIATION_INTERFACE = 'language-interface';

/**
 * If no URL language, language is determined using an already detected one.
 */
const LANGUAGE_NEGOTIATION_URL_FALLBACK = 'language-url-fallback';

/**
 * The language is set based on the user language settings.
 */
const LANGUAGE_NEGOTIATION_USER = 'language-user';

/**
 * The language is set based on the request/session parameters.
 */
const LANGUAGE_NEGOTIATION_SESSION = 'language-session';

/**
 * URL language negotiation: use the path prefix as URL language indicator.
 */
const LANGUAGE_NEGOTIATION_URL_PREFIX = 0;

/**
 * URL language negotiation: use the domain as URL language indicator.
 */
const LANGUAGE_NEGOTIATION_URL_DOMAIN = 1;

/**
 * Identifies the language from the current interface language.
 *
 * @return
 *   The current interface language code.
 */
function language_from_interface() {
  return drupal_container()->get(LANGUAGE_TYPE_INTERFACE)->langcode;
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
function language_from_browser($languages) {
  if (empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
    return FALSE;
  }

  // The Accept-Language header contains information about the language
  // preferences configured in the user's browser / operating system. RFC 2616
  // (section 14.4) defines the Accept-Language header as follows:
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

  // Find the enabled language with the greatest qvalue, following the rules of
  // RFC 2616 (section 14.4). If several languages have the same qvalue, prefer
  // the one with the greatest weight.
  $best_match_langcode = FALSE;
  $max_qvalue = 0;
  foreach ($languages as $langcode => $language) {
    // Language tags are case insensitive (RFC2616, sec 3.10).
    $langcode = strtolower($langcode);

    // If nothing matches below, the default qvalue is the one of the wildcard
    // language, if set, or is 0 (which will never match).
    $qvalue = isset($browser_langcodes['*']) ? $browser_langcodes['*'] : 0;

    // Find the longest possible prefix of the browser-supplied language ('the
    // language-range') that matches this site language ('the language tag').
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
function language_from_user($languages) {
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
function language_from_session($languages) {
  $param = variable_get('language_negotiation_session_param', 'language');

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
function language_from_url($languages) {
  $language_url = FALSE;

  if (!language_negotiation_method_enabled(LANGUAGE_NEGOTIATION_URL)) {
    return $language_url;
  }

  switch (variable_get('language_negotiation_url_part', LANGUAGE_NEGOTIATION_URL_PREFIX)) {
    case LANGUAGE_NEGOTIATION_URL_PREFIX:
      // Language negotiation happens before the public function current_path()
      // is available.
      // @todo Refactor with Symfony's Request object.
      list($language, $path) = language_url_split_prefix(_current_path(), $languages);
      _current_path($path);
      if ($language !== FALSE) {
        $language_url = $language->langcode;
      }
      break;

    case LANGUAGE_NEGOTIATION_URL_DOMAIN:
      // Get only the host, not the port.
      $http_host= $_SERVER['HTTP_HOST'];
      if (strpos($http_host, ':') !== FALSE) {
        $http_host_tmp = explode(':', $http_host);
        $http_host = current($http_host_tmp);
      }
      $domains = language_negotiation_url_domains();
      foreach ($languages as $language) {
        // Skip the check if the language doesn't have a domain.
        if (!empty($domains[$language->langcode])) {
          // Ensure that there is exactly one protocol in the url when checking
          // the hostname.
          $host = 'http://' . str_replace(array('http://', 'https://'), '', $domains[$language->langcode]);
          $host = parse_url($host, PHP_URL_HOST);
          if ($http_host == $host) {
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
function language_url_fallback($language = NULL, $language_type = LANGUAGE_TYPE_INTERFACE) {
  $default = language_default();
  $prefix = (variable_get('language_negotiation_url_part', LANGUAGE_NEGOTIATION_URL_PREFIX) == LANGUAGE_NEGOTIATION_URL_PREFIX);

  // If the default language is not configured to convey language information,
  // a missing URL language information indicates that URL language should be
  // the default one, otherwise we fall back to an already detected language.
  $domains = language_negotiation_url_domains();
  $prefixes = language_negotiation_url_prefixes();
  if (($prefix && empty($prefixes[$default->langcode])) || (!$prefix && empty($domains[$default->langcode]))) {
    return $default->langcode;
  }
  else {
    return drupal_container()->get($language_type)->langcode;
  }
}

/**
 * Return links for the URL language switcher block.
 *
 * Translation links may be provided by other modules.
 */
function language_switcher_url($type, $path) {
  $languages = language_list();
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
function language_switcher_session($type, $path) {
  $param = variable_get('language_negotiation_session_param', 'language');
  $language_query = isset($_SESSION[$param]) ? $_SESSION[$param] : drupal_container()->get($type)->langcode;

  $languages = language_list();
  $links = array();

  $query = $_GET;

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
function language_url_rewrite_url(&$path, &$options) {
  static $drupal_static_fast;
  if (!isset($drupal_static_fast)) {
    $drupal_static_fast['languages'] = &drupal_static(__FUNCTION__);
  }
  $languages = &$drupal_static_fast['languages'];

  if (!isset($languages)) {
    $languages = language_list();
    $languages = array_flip(array_keys($languages));
  }

  // Language can be passed as an option, or we go for current URL language.
  if (!isset($options['language'])) {
    $language_url = drupal_container()->get(LANGUAGE_TYPE_URL);
    $options['language'] = $language_url;
  }
  // We allow only enabled languages here.
  elseif (is_object($options['language']) && !isset($languages[$options['language']->langcode])) {
    unset($options['language']);
    return;
  }

  if (isset($options['language'])) {
    switch (variable_get('language_negotiation_url_part', LANGUAGE_NEGOTIATION_URL_PREFIX)) {
      case LANGUAGE_NEGOTIATION_URL_DOMAIN:
        $domains = language_negotiation_url_domains();
        if (is_object($options['language']) && !empty($domains[$options['language']->langcode])) {
          // Ask for an absolute URL with our modified base_url.
          global $is_https;
          $url_scheme = ($is_https) ? 'https://' : 'http://';
          $options['absolute'] = TRUE;
          $options['base_url'] = $url_scheme . $domains[$options['language']->langcode];

          // HTTP_HOST will optionally contain the port. Preserve that for
          // the domain of the target language as well.
          $http_host = $_SERVER['HTTP_HOST'];
          if (strpos($http_host, ':') !== FALSE) {
            $http_host_tmp = explode(':', $http_host);
            $options['base_url'] .= ':' . end($http_host_tmp);
          }

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
        $prefixes = language_negotiation_url_prefixes();
        if (is_object($options['language']) &&!empty($prefixes[$options['language']->langcode])) {
          $options['prefix'] = $prefixes[$options['language']->langcode] . '/';
        }
        break;
    }
  }
}

/**
 * Reads language prefixes and uses the langcode if no prefix is set.
 */
function language_negotiation_url_prefixes() {
  return variable_get('language_negotiation_url_prefixes', array());
}

/**
 * Saves language prefix settings.
 */
function language_negotiation_url_prefixes_save(array $prefixes) {
  variable_set('language_negotiation_url_prefixes', $prefixes);
}

/**
 * Reads language domains.
 */
function language_negotiation_url_domains() {
  return variable_get('language_negotiation_url_domains', array());
}

/**
 * Saves the language domain settings.
 */
function language_negotiation_url_domains_save(array $domains) {
  variable_set('language_negotiation_url_domains', $domains);
}

/**
 * Rewrite URLs for the Session language negotiation method.
 */
function language_url_rewrite_session(&$path, &$options) {
  static $query_rewrite, $query_param, $query_value;

  // The following values are not supposed to change during a single page
  // request processing.
  if (!isset($query_rewrite)) {
    global $user;
    if (!$user->uid) {
      $languages = language_list();
      $query_param = check_plain(variable_get('language_negotiation_session_param', 'language'));
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
