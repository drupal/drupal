<?php

/**
 * @file
 * Multiple language handling functionality.
 */

/**
 * No language negotiation. The default language is used.
 */
define('LANGUAGE_NEGOTIATION_DEFAULT', 'language-default');

/**
 * Return all the defined language types.
 *
 * @return
 *   An array of language type names. The name will be used as the global
 *   variable name the language value will be stored in.
 */
function language_types_info() {
  $language_types = &drupal_static(__FUNCTION__);

  if (!isset($language_types)) {
    $language_types = module_invoke_all('language_types_info');
    // Let other modules alter the list of language types.
    drupal_alter('language_types_info', $language_types);
  }

  return $language_types;
}

/**
 * Return only the configurable language types.
 *
 * A language type maybe configurable or fixed. A fixed language type is a type
 * whose negotiation values are unchangeable and defined while defining the
 * language type itself.
 *
 * @param $stored
 *   Optional. By default retrieves values from the 'language_types' variable to
 *   avoid unnecessary hook invocations.
 *   If set to FALSE retrieves values from the actual language type definitions.
 *   This allows to react to alterations performed on the definitions by modules
 *   installed after the 'language_types' variable is set.
 *
 * @return
 *   An array of language type names.
 */
function language_types_configurable($stored = TRUE) {
  $configurable = &drupal_static(__FUNCTION__);

  if ($stored && !isset($configurable)) {
    $types = variable_get('language_types', drupal_language_types());
    $configurable = array_keys(array_filter($types));
  }

  if (!$stored) {
    $result = array();
    foreach (language_types_info() as $type => $info) {
      if (!isset($info['fixed'])) {
        $result[] = $type;
      }
    }
    return $result;
  }

  return $configurable;
}

/**
 * Disable the given language types.
 *
 * @param $types
 *   An array of language types.
 */
function language_types_disable($types) {
  $enabled_types = variable_get('language_types', drupal_language_types());

  foreach ($types as $type) {
    unset($enabled_types[$type]);
  }

  variable_set('language_types', $enabled_types);
}

/**
 * Updates the language type configuration.
 */
function language_types_set() {
  // Ensure that we are getting the defined language negotiation information. An
  // invocation of module_enable() or module_disable() could outdate the cached
  // information.
  drupal_static_reset('language_types_info');
  drupal_static_reset('language_negotiation_info');

  // Determine which language types are configurable and which not by checking
  // whether the 'fixed' key is defined. Non-configurable (fixed) language types
  // have their language negotiation settings stored there.
  $defined_providers = language_negotiation_info();
  foreach (language_types_info() as $type => $info) {
    if (isset($info['fixed'])) {
      $language_types[$type] = FALSE;
      $negotiation = array();
      foreach ($info['fixed'] as $weight => $id) {
        if (isset($defined_providers[$id])) {
          $negotiation[$id] = $weight;
        }
      }
      language_negotiation_set($type, $negotiation);
    }
    else {
      $language_types[$type] = TRUE;
    }
  }

  // Save language types.
  variable_set('language_types', $language_types);

  // Ensure that subsequent calls of language_types_configurable() return the
  // updated language type information.
  drupal_static_reset('language_types_configurable');
}

/**
 * Check if a language provider is enabled.
 *
 * This has two possible behaviors:
 *  - If $provider_id is given return its ID if enabled, FALSE otherwise.
 *  - If no ID is passed the first enabled language provider is returned.
 *
 * @param $type
 *   The language negotiation type.
 * @param $provider_id
 *   The language provider ID.
 *
 * @return
 *   The provider ID if it is enabled, FALSE otherwise.
 */
function language_negotiation_get($type, $provider_id = NULL) {
  $negotiation = variable_get("language_negotiation_$type", array());

  if (empty($negotiation)) {
    return empty($provider_id) ? LANGUAGE_NEGOTIATION_DEFAULT : FALSE;
  }

  if (empty($provider_id)) {
    return key($negotiation);
  }

  if (isset($negotiation[$provider_id])) {
    return $provider_id;
  }

  return FALSE;
}

/**
 * Check if the given language provider is enabled for any configurable language
 * type.
 *
 * @param $provider_id
 *   The language provider ID.
 *
 * @return
 *   TRUE if there is at least one language type for which the give language
 *   provider is enabled, FALSE otherwise.
 */
function language_negotiation_get_any($provider_id) {
  foreach (language_types_configurable() as $type) {
    if (language_negotiation_get($type, $provider_id)) {
      return TRUE;
    }
  }

  return FALSE;
}

/**
 * Return the language switch links for the given language.
 *
 * @param $type
 *   The language negotiation type.
 * @param $path
 *   The internal path the switch links will be relative to.
 *
 * @return
 *   A keyed array of links ready to be themed.
 */
function language_negotiation_get_switch_links($type, $path) {
  $links = FALSE;
  $negotiation = variable_get("language_negotiation_$type", array());

  foreach ($negotiation as $id => $provider) {
    if (isset($provider['callbacks']['switcher'])) {
      if (isset($provider['file'])) {
        require_once DRUPAL_ROOT . '/' . $provider['file'];
      }

      $callback = $provider['callbacks']['switcher'];
      $result = $callback($type, $path);

      if (!empty($result)) {
        // Allow modules to provide translations for specific links.
        drupal_alter('language_switch_links', $result, $type, $path);
        $links = (object) array('links' => $result, 'provider' => $id);
        break;
      }
    }
  }

  return $links;
}

/**
 * Updates language configuration to remove any language provider that is no longer defined.
 */
function language_negotiation_purge() {
  // Ensure that we are getting the defined language negotiation information. An
  // invocation of module_enable() or module_disable() could outdate the cached
  // information.
  drupal_static_reset('language_negotiation_info');
  drupal_static_reset('language_types_info');

  $defined_providers = language_negotiation_info();
  foreach (language_types_info() as $type => $type_info) {
    $weight = 0;
    $negotiation = array();
    foreach (variable_get("language_negotiation_$type", array()) as $id => $provider) {
      if (isset($defined_providers[$id])) {
        $negotiation[$id] = $weight++;
      }
    }
    language_negotiation_set($type, $negotiation);
  }
}

/**
 * Save a list of language providers.
 *
 * @param $type
 *   The language negotiation type.
 * @param $language_providers
 *   An array of language provider weights keyed by id.
 *   @see language_provider_weight()
 */
function language_negotiation_set($type, $language_providers) {
  // Save only the necessary fields.
  $provider_fields = array('callbacks', 'file', 'cache');

  $negotiation = array();
  $providers_weight = array();
  $defined_providers = language_negotiation_info();
  $default_types = language_types_configurable(FALSE);

  // Initialize the providers weight list.
  foreach ($language_providers as $id => $provider) {
    $providers_weight[$id] = language_provider_weight($provider);
  }

  // Order providers list by weight.
  asort($providers_weight);

  foreach ($providers_weight as $id => $weight) {
    if (isset($defined_providers[$id])) {
      $provider = $defined_providers[$id];
      // If the provider does not express any preference about types, make it
      // available for any configurable type.
      $types = array_flip(isset($provider['types']) ? $provider['types'] : $default_types);
      // Check if the provider is defined and has the right type.
      if (isset($types[$type])) {
        $provider_data = array();
        foreach ($provider_fields as $field) {
          if (isset($provider[$field])) {
            $provider_data[$field] = $provider[$field];
          }
        }
        $negotiation[$id] = $provider_data;
      }
    }
  }

  variable_set("language_negotiation_$type", $negotiation);
}

/**
 * Return all the defined language providers.
 *
 * @return
 *   An array of language providers.
 */
function language_negotiation_info() {
  $language_providers = &drupal_static(__FUNCTION__);

  if (!isset($language_providers)) {
    // Collect all the module-defined language negotiation providers.
    $language_providers = module_invoke_all('language_negotiation_info');

    // Add the default language provider.
    $language_providers[LANGUAGE_NEGOTIATION_DEFAULT] = array(
      'callbacks' => array('language' => 'language_from_default'),
      'weight' => 10,
      'name' => t('Default'),
      'description' => t('Use the default site language (@language_name).', array('@language_name' => language_default()->native)),
    );

    // Let other modules alter the list of language providers.
    drupal_alter('language_negotiation_info', $language_providers);
  }

  return $language_providers;
}

/**
 * Helper function used to cache the language providers results.
 *
 * @param $provider_id
 *   The language provider ID.
 * @param $provider
 *   The language provider to be invoked. If not passed it will be explicitly
 *   loaded through language_negotiation_info().
 *
 * @return
 *   The language provider's return value.
 */
function language_provider_invoke($provider_id, $provider = NULL) {
  $results = &drupal_static(__FUNCTION__);

  if (!isset($results[$provider_id])) {
    global $user;

    // Get languages grouped by status and select only the enabled ones.
    $languages = language_list('enabled');
    $languages = $languages[1];

    if (!isset($provider)) {
      $providers = language_negotiation_info();
      $provider = $providers[$provider_id];
    }

    if (isset($provider['file'])) {
      require_once DRUPAL_ROOT . '/' . $provider['file'];
    }

    // If the language provider has no cache preference or this is satisfied
    // we can execute the callback.
    $cache = !isset($provider['cache']) || $user->uid || $provider['cache'] == variable_get('cache', 0);
    $callback = isset($provider['callbacks']['language']) ? $provider['callbacks']['language'] : FALSE;
    $langcode = $cache && function_exists($callback) ? $callback($languages) : FALSE;
    $results[$provider_id] = isset($languages[$langcode]) ? $languages[$langcode] : FALSE;
  }

  // Since objects are resources we need to return a clone to prevent the
  // provider cache to be unintentionally altered. The same providers might be
  // used with different language types based on configuration.
  return !empty($results[$provider_id]) ? clone($results[$provider_id]) : $results[$provider_id];
}

/**
 * Return the passed language provider weight or a default value.
 *
 * @param $provider
 *   A language provider data structure.
 *
 * @return
 *   A numeric weight.
 */
function language_provider_weight($provider) {
  $default = is_numeric($provider) ? $provider : 0;
  return isset($provider['weight']) && is_numeric($provider['weight']) ? $provider['weight'] : $default;
}

/**
 * Choose a language for the given type based on language negotiation settings.
 *
 * @param $type
 *   The language type.
 *
 * @return
 *   The negotiated language object.
 */
function language_initialize($type) {
  // Execute the language providers in the order they were set up and return the
  // first valid language found.
  $negotiation = variable_get("language_negotiation_$type", array());

  foreach ($negotiation as $provider_id => $provider) {
    $language = language_provider_invoke($provider_id, $provider);
    if ($language) {
      $language->provider = $provider_id;
      return $language;
    }
  }

  // If no other language was found use the default one.
  $language = language_default();
  $language->provider = LANGUAGE_NEGOTIATION_DEFAULT;
  return $language;
}

/**
 * Default language provider.
 *
 * @return
 *   The default language code.
 */
function language_from_default() {
  return language_default()->language;
}

/**
 * Split the given path into prefix and actual path.
 *
 * Parse the given path and return the language object identified by the
 * prefix and the actual path.
 *
 * @param $path
 *   The path to split.
 * @param $languages
 *   An array of valid languages.
 *
 * @return
 *   An array composed of:
 *    - A language object corresponding to the identified prefix on success,
 *      FALSE otherwise.
 *    - The path without the prefix on success, the given path otherwise.
 */
function language_url_split_prefix($path, $languages) {
  $args = empty($path) ? array() : explode('/', $path);
  $prefix = array_shift($args);

  // Search prefix within enabled languages.
  foreach ($languages as $language) {
    if (!empty($language->prefix) && $language->prefix == $prefix) {
      // Rebuild $path with the language removed.
      return array($language, implode('/', $args));
    }
  }

  return array(FALSE, $path);
}

/**
 * Return the possible fallback languages ordered by language weight.
 *
 * @param
 *   The language type.
 *
 * @return
 *   An array of language codes.
 */
function language_fallback_get_candidates($type = LANGUAGE_TYPE_CONTENT) {
  $fallback_candidates = &drupal_static(__FUNCTION__);

  if (!isset($fallback_candidates)) {
    $fallback_candidates = array();

    // Get languages ordered by weight.
    // Use array keys to avoid duplicated entries.
    foreach (language_list('weight') as $languages) {
      foreach ($languages as $language) {
        $fallback_candidates[$language->language] = NULL;
      }
    }

    $fallback_candidates = array_keys($fallback_candidates);
    $fallback_candidates[] = LANGUAGE_NONE;

    // Let other modules hook in and add/change candidates.
    drupal_alter('language_fallback_candidates', $fallback_candidates);
  }

  return $fallback_candidates;
}
