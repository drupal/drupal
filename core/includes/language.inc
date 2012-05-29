<?php

/**
 * @file
 * Language Negotiation API.
 *
 * @see http://drupal.org/node/1497272
 */

/**
 * No language negotiation. The default language is used.
 */
const LANGUAGE_NEGOTIATION_DEFAULT = 'language-default';

/**
 * @defgroup language_negotiation Language Negotiation API functionality
 * @{
 * Functions to customize the language types and the negotiation process.
 *
 * The language negotiation API is based on two major concepts:
 * - Language types: types of translatable data (the types of data that a user
 *   can view or request).
 * - Language negotiation methods: functions for determining which language to
 *   use to present a particular piece of data to the user.
 * Both language types and language negotiation methods are customizable.
 *
 * Drupal defines three built-in language types:
 * - Interface language: The page's main language, used to present translated
 *   user interface elements such as titles, labels, help text, and messages.
 * - Content language: The language used to present content that is available
 *   in more than one language (see
 *   @link field_language Field Language API @endlink for details).
 * - URL language: The language associated with URLs. When generating a URL,
 *   this value will be used by url() as a default if no explicit preference is
 *   provided.
 * Modules can define additional language types through
 * hook_language_types_info(), and alter existing language type definitions
 * through hook_language_types_info_alter().
 *
 * Language types may be configurable or fixed. The language negotiation
 * methods associated with a configurable language type can be explicitly
 * set through the user interface. A fixed language type has predetermined
 * (module-defined) language negotiation settings and, thus, does not appear in
 * the configuration page. Here is a code snippet that makes the content
 * language (which by default inherits the interface language's values)
 * configurable:
 * @code
 * function mymodule_language_types_info_alter(&$language_types) {
 *   unset($language_types[LANGUAGE_TYPE_CONTENT]['fixed']);
 * }
 * @endcode
 *
 * Every language type can have a different set of language negotiation methods
 * assigned to it. Different language types often share the same language
 * negotiation settings, but they can have independent settings if needed. If
 * two language types are configured the same way, their language switcher
 * configuration will be functionally identical and the same settings will act
 * on both language types.
 *
 * Drupal defines the following built-in language negotiation methods:
 * - URL: Determine the language from the URL (path prefix or domain).
 * - Session: Determine the language from a request/session parameter.
 * - User: Follow the user's language preference.
 * - Browser: Determine the language from the browser's language settings.
 * - Default language: Use the default site language.
 * Language negotiation methods are simple callback functions that implement a
 * particular logic to return a language code. For instance, the URL method
 * searches for a valid path prefix or domain name in the current request URL.
 * If a language negotiation method does not return a valid language code, the
 * next method associated to the language type (based on method weight) is
 * invoked.
 *
 * Modules can define additional language negotiation methods through
 * hook_language_negotiation_info(), and alter existing methods through
 * hook_language_negotiation_info_alter(). Here is an example snippet that lets
 * path prefixes be ignored for administrative paths:
 * @code
 * function mymodule_language_negotiation_info_alter(&$negotiation_info) {
 *   // Replace the core function with our own function.
 *   module_load_include('language', 'inc', 'language.negotiation');
 *   $negotiation_info[LANGUAGE_NEGOTIATION_URL]['callbacks']['negotiation'] = 'mymodule_from_url';
 *   $negotiation_info[LANGUAGE_NEGOTIATION_URL]['file'] = drupal_get_path('module', 'mymodule') . '/mymodule.module';
 * }
 *
 * function mymodule_from_url($languages) {
 *   // Use the core URL language negotiation method to get a valid language
 *   // code.
 *   module_load_include('language', 'inc', 'language.negotiation');
 *   $langcode = language_from_url($languages);
 *
 *   // If we are on an administrative path, override with the default language.
 *   if (isset($_GET['q']) && strtok($_GET['q'], '/') == 'admin') {
 *     return language_default()->langcode;
 *   }
 *   return $langcode;
 * }
 * ?>
 * @endcode
 *
 * For more information, see
 * @link http://drupal.org/node/1497272 Language Negotiation API @endlink
 */

/**
 * Chooses a language based on language negotiation method settings.
 *
 * @param $type
 *   The language type key to find the language for.
 *
 * @return
 *   The negotiated language object.
 */
function language_types_initialize($type) {
  // Execute the language negotiation methods in the order they were set up and
  // return the first valid language found.
  $negotiation = variable_get("language_negotiation_$type", array());

  foreach ($negotiation as $method_id => $method) {
    // Skip negotiation methods not appropriate for this type.
    if (isset($method['types']) && !in_array($type, $method['types'])) {
      continue;
    }
    $language = language_negotiation_method_invoke($method_id, $method);
    if ($language) {
      // Remember the method ID used to detect the language.
      $language->method_id = $method_id;
      return $language;
    }
  }

  // If no other language was found use the default one.
  $language = language_default();
  $language->method_id = LANGUAGE_NEGOTIATION_DEFAULT;
  return $language;
}

/**
 * Returns information about all defined language types.
 *
 * @return
 *   An associative array of language type information arrays keyed by type
 *   names. Based on information from hook_language_types_info().
 *
 * @see hook_language_types_info().
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
 * Returns only the configurable language types.
 *
 * A language type maybe configurable or fixed. A fixed language type is a type
 * whose language negotiation methods are module-defined and not altered through
 * the user interface.
 *
 * @param $stored
 *   (optional) By default, retrieves values from the 'language_types' variable
 *   to avoid unnecessary hook invocations. If set to FALSE, retrieves values
 *   from the actual language type definitions. This allows reaction to
 *   alterations performed on the definitions by modules installed after the
 *   'language_types' variable is set.
 *
 * @return
 *   An array of language type names.
 */
function language_types_get_configurable($stored = TRUE) {
  $configurable = &drupal_static(__FUNCTION__);

  if ($stored && !isset($configurable)) {
    $types = variable_get('language_types', language_types_get_default());
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
 * Disables the given language types.
 *
 * @param $types
 *   An array of language types.
 */
function language_types_disable($types) {
  $enabled_types = variable_get('language_types', language_types_get_default());

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
  $language_types = array();
  $negotiation_info = language_negotiation_info();
  foreach (language_types_info() as $type => $info) {
    if (isset($info['fixed'])) {
      $language_types[$type] = FALSE;
      $method_weights = array();
      foreach ($info['fixed'] as $weight => $method_id) {
        if (isset($negotiation_info[$method_id])) {
          $method_weights[$method_id] = $weight;
        }
      }
      language_negotiation_set($type, $method_weights);
    }
    else {
      $language_types[$type] = TRUE;
    }
  }

  // Save enabled language types.
  variable_set('language_types', $language_types);

  // Ensure that subsequent calls of language_types_get_configurable() return
  // the updated language type information.
  drupal_static_reset('language_types_get_configurable');
}

/**
 * Returns the ID of the language type's first language negotiation method.
 *
 * @param $type
 *   The language type.
 *
 * @return
 *   The identifier of the first language negotiation method for the given
 *   language type, or the default method if none exists.
 */
function language_negotiation_method_get_first($type) {
  $negotiation = variable_get("language_negotiation_$type", array());
  return empty($negotiation) ? LANGUAGE_NEGOTIATION_DEFAULT : key($negotiation);
}

/**
 * Checks whether a language negotiation method is enabled for a language type.
 *
 * @param $method_id
 *   The language negotiation method ID.
 * @param $type
 *   (optional) The language type. If none is passed, all the configurable
 *   language types will be inspected.
 *
 * @return
 *   TRUE if the method is enabled for at least one of the given language
 *   types, or FALSE otherwise.
 */
function language_negotiation_method_enabled($method_id, $type = NULL) {
  $language_types = !empty($type) ? array($type) : language_types_get_configurable();

  foreach ($language_types as $type) {
    $negotiation = variable_get("language_negotiation_$type", array());
    if (isset($negotiation[$method_id])) {
      return TRUE;
    }
  }

  return FALSE;
}

/**
 * Returns the language switch links for the given language type.
 *
 * @param $type
 *   The language type.
 * @param $path
 *   The internal path the switch links will be relative to.
 *
 * @return
 *   A keyed array of links ready to be themed.
 */
function language_negotiation_get_switch_links($type, $path) {
  $links = FALSE;
  $negotiation = variable_get("language_negotiation_$type", array());

  foreach ($negotiation as $method_id => $method) {
    if (isset($method['callbacks']['language_switch'])) {
      if (isset($method['file'])) {
        require_once DRUPAL_ROOT . '/' . $method['file'];
      }

      $callback = $method['callbacks']['language_switch'];
      $result = $callback($type, $path);

      if (!empty($result)) {
        // Allow modules to provide translations for specific links.
        drupal_alter('language_switch_links', $result, $type, $path);
        $links = (object) array('links' => $result, 'method_id' => $method_id);
        break;
      }
    }
  }

  return $links;
}

/**
 * Removes any language negotiation methods that are no longer defined.
 */
function language_negotiation_purge() {
  // Ensure that we are getting the defined language negotiation information. An
  // invocation of module_enable() or module_disable() could outdate the cached
  // information.
  drupal_static_reset('language_negotiation_info');
  drupal_static_reset('language_types_info');

  $negotiation_info = language_negotiation_info();
  foreach (language_types_info() as $type => $type_info) {
    $weight = 0;
    $method_weights = array();
    foreach (variable_get("language_negotiation_$type", array()) as $method_id => $method) {
      if (isset($negotiation_info[$method_id])) {
        $method_weights[$method_id] = $weight++;
      }
    }
    language_negotiation_set($type, $method_weights);
  }
}

/**
 * Saves a list of language negotiation methods for a language type.
 *
 * @param $type
 *   The language type.
 * @param $method_weights
 *   An array of language negotiation method weights keyed by method ID.
 */
function language_negotiation_set($type, $method_weights) {
  // Save only the necessary fields.
  $method_fields = array('callbacks', 'file', 'cache');

  $negotiation = array();
  $negotiation_info = language_negotiation_info();
  $default_types = language_types_get_configurable(FALSE);

  // Order the language negotiation method list by weight.
  asort($method_weights);

  foreach ($method_weights as $method_id => $weight) {
    if (isset($negotiation_info[$method_id])) {
      $method = $negotiation_info[$method_id];
      // If the language negotiation method does not express any preference
      // about types, make it available for any configurable type.
      $types = array_flip(isset($method['types']) ? $method['types'] : $default_types);
      // Check whether the method is defined and has the right type.
      if (isset($types[$type])) {
        $method_data = array();
        foreach ($method_fields as $field) {
          if (isset($method[$field])) {
            $method_data[$field] = $method[$field];
          }
        }
        $negotiation[$method_id] = $method_data;
      }
    }
  }

  variable_set("language_negotiation_$type", $negotiation);
}

/**
 * Returns all defined language negotiation methods.
 *
 * @return
 *   An array of language negotiation methods.
 */
function language_negotiation_info() {
  $negotiation_info = &drupal_static(__FUNCTION__);

  if (!isset($negotiation_info)) {
    // Collect all the module-defined language negotiation methods.
    $negotiation_info = module_invoke_all('language_negotiation_info');

    // Add the default language negotiation method.
    $negotiation_info[LANGUAGE_NEGOTIATION_DEFAULT] = array(
      'callbacks' => array('language' => 'language_from_default'),
      'weight' => 10,
      'name' => t('Default language'),
      'description' => t('Use the default site language (@language_name).', array('@language_name' => language_default()->name)),
      'config' => 'admin/config/regional/language',
    );

     // Let other modules alter the list of language negotiation methods.
     drupal_alter('language_negotiation_info', $negotiation_info);
  }

  return $negotiation_info;
}

/**
 * Invokes a language negotiation method and caches the results.
 *
 * @param $method_id
 *   The language negotiation method's identifier.
 * @param $method
 *   (optional) An associative array of information about the method to be
 *   invoked (see hook_language_negotiation_info() for details). If not passed
 *   in, it will be loaded through language_negotiation_info().
 *
 * @return
 *   A language object representing the language chosen by the method.
 */
function language_negotiation_method_invoke($method_id, $method = NULL) {
  $results = &drupal_static(__FUNCTION__);

  if (!isset($results[$method_id])) {
    global $user;

    $languages = language_list();

    if (!isset($method)) {
      $negotiation_info = language_negotiation_info();
      $method = $negotiation_info[$method_id];
    }

    if (isset($method['file'])) {
      require_once DRUPAL_ROOT . '/' . $method['file'];
    }

    // If the language negotiation method has no cache preference or this is
    // satisfied we can execute the callback.
    $cache = !isset($method['cache']) || $user->uid || $method['cache'] == variable_get('cache', 0);
    $callback = isset($method['callbacks']['negotiation']) ? $method['callbacks']['negotiation'] : FALSE;
    $langcode = $cache && function_exists($callback) ? $callback($languages) : FALSE;
    $results[$method_id] = isset($languages[$langcode]) ? $languages[$langcode] : FALSE;
  }

  // Since objects are resources, we need to return a clone to prevent the
  // language negotiation method cache from being unintentionally altered. The
  // same methods might be used with different language types based on
  // configuration.
  return !empty($results[$method_id]) ? clone($results[$method_id]) : $results[$method_id];
}

/**
 * Returns the default language code.
 *
 * @return
 *   The default language code.
 */
function language_from_default() {
  return language_default()->langcode;
}

/**
 * Split the given path into prefix and actual path.
 *
 * Parse the given path and return the language object identified by the prefix
 * and the actual path.
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
  $prefixes = language_negotiation_url_prefixes();
  foreach ($languages as $language) {
    if (isset($prefixes[$language->langcode]) && $prefixes[$language->langcode] == $prefix) {
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
    // Get languages ordered by weight, add LANGUAGE_NOT_SPECIFIED at the end.
    $fallback_candidates = array_keys(language_list());
    $fallback_candidates[] = LANGUAGE_NOT_SPECIFIED;

    // Let other modules hook in and add/change candidates.
    drupal_alter('language_fallback_candidates', $fallback_candidates);
  }

  return $fallback_candidates;
}

/**
 * @} End of "language_negotiation"
 */
