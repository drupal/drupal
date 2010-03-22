<?php
// $Id$

/**
 * @file
 * Multiple language handling functionality.
 */

/**
 *  Choose a language for the page, based on language negotiation settings.
 */
function language_initialize() {
  global $user;

  // Configured presentation language mode.
  $mode = variable_get('language_negotiation', LANGUAGE_NEGOTIATION_NONE);
  // Get a list of enabled languages.
  $languages = language_list('enabled');
  $languages = $languages[1];
  
  switch ($mode) {
    case LANGUAGE_NEGOTIATION_NONE:
      return language_default();

    case LANGUAGE_NEGOTIATION_DOMAIN:
      foreach ($languages as $language) {
        $parts = parse_url($language->domain);
        if (!empty($parts['host']) && ($_SERVER['HTTP_HOST'] == $parts['host'])) {
          return $language;
        }
      }
      return language_default();

    case LANGUAGE_NEGOTIATION_PATH_DEFAULT:
    case LANGUAGE_NEGOTIATION_PATH:
      // $_GET['q'] might not be available at this time, because
      // path initialization runs after the language bootstrap phase.
      $args = isset($_GET['q']) ? explode('/', $_GET['q']) : array();
      $prefix = array_shift($args);
      // Search prefix within enabled languages.
      foreach ($languages as $language) {
        if (!empty($language->prefix) && $language->prefix == $prefix) {
          // Rebuild $GET['q'] with the language removed.
          $_GET['q'] = implode('/', $args);
          return $language;
        }
      }
      if ($mode == LANGUAGE_NEGOTIATION_PATH_DEFAULT) {
        // If we did not found the language by prefix, choose the default.
        return language_default();
      }
      break;
  }

  // User language.
  if ($user->uid && isset($languages[$user->language])) {
    return $languages[$user->language];
  }

  // Browser accept-language parsing.
  if ($language = language_from_browser()) {
    return $language;
  }

  // Fall back on the default if everything else fails.
  return language_default();
}

/**
 * Identify language from the Accept-language HTTP header we got.
 */
function language_from_browser() {
  // Specified by the user via the browser's Accept Language setting
  // Samples: "hu, en-us;q=0.66, en;q=0.33", "hu,en-us;q=0.5"
  $browser_langs = array();

  if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
    $browser_accept = explode(",", $_SERVER['HTTP_ACCEPT_LANGUAGE']);
    for ($i = 0; $i < count($browser_accept); $i++) {
      // The language part is either a code or a code with a quality.
      // We cannot do anything with a * code, so it is skipped.
      // If the quality is missing, it is assumed to be 1 according to the RFC.
      if (preg_match("!([a-z-]+)(;q=([0-9\\.]+))?!", trim($browser_accept[$i]), $found)) {
        $browser_langs[$found[1]] = (isset($found[3]) ? (float) $found[3] : 1.0);
      }
    }
  }

  // Order the codes by quality
  arsort($browser_langs);

  // Try to find the first preferred language we have
  $languages = language_list('enabled');
  foreach ($browser_langs as $langcode => $q) {
    if (isset($languages['1'][$langcode])) {
      return $languages['1'][$langcode];
    }
  }
}

/**
 * Rewrite URL's with language based prefix. Parameters are the same
 * as those of the url() function.
 */
function language_url_rewrite(&$path, &$options) {
  global $language;

  // Only modify relative (insite) URLs.
  if (empty($options['external'])) {

    // Language can be passed as an option, or we go for current language.
    if (!isset($options['language'])) {
      $options['language'] = $language;
    }

    switch (variable_get('language_negotiation', LANGUAGE_NEGOTIATION_NONE)) {
      case LANGUAGE_NEGOTIATION_NONE:
        // No language dependent path allowed in this mode.
        unset($options['language']);
        break;

      case LANGUAGE_NEGOTIATION_DOMAIN:
        if ($options['language']->domain) {
          // Ask for an absolute URL with our modified base_url.
          $options['absolute'] = TRUE;
          $options['base_url'] = $options['language']->domain;
        }
        break;

      case LANGUAGE_NEGOTIATION_PATH_DEFAULT:
        $default = language_default();
        if ($options['language']->language == $default->language) {
          break;
        }
        // Intentionally no break here.

      case LANGUAGE_NEGOTIATION_PATH:
        if (!empty($options['language']->prefix)) {
          $options['prefix'] = $options['language']->prefix .'/';
        }
        break;
    }
  }
}
