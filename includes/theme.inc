<?php

/**
 * @file
 * The theme system, which controls the output of Drupal.
 *
 * The theme system allows for nearly all output of the Drupal system to be
 * customized by user themes.
 *
 * @ingroup themeable
 */

/**
 * @defgroup content_flags Content markers
 * @{
 * Markers used by theme_mark() and node_mark() to designate content.
 * @see theme_mark(), node_mark()
 */

/**
 * Mark content as read.
 */
define('MARK_READ', 0);

/**
 * Mark content as being new.
 */
define('MARK_NEW', 1);

/**
 * Mark content as being updated.
 */
define('MARK_UPDATED', 2);

/**
 * @} End of "Content markers".
 */

/**
 * Initialize the theme system by loading the theme.
 */
function init_theme() {
  global $theme, $user, $custom_theme, $theme_key;

  // If $theme is already set, assume the others are set, too, and do nothing
  if (isset($theme)) {
    return;
  }

  drupal_bootstrap(DRUPAL_BOOTSTRAP_DATABASE);
  $themes = list_themes();

  // Only select the user selected theme if it is available in the
  // list of enabled themes.
  $theme = !empty($user->theme) && !empty($themes[$user->theme]->status) ? $user->theme : variable_get('theme_default', 'garland');

  // Allow modules to override the present theme... only select custom theme
  // if it is available in the list of installed themes.
  $theme = $custom_theme && $themes[$custom_theme] ? $custom_theme : $theme;

  // Store the identifier for retrieving theme settings with.
  $theme_key = $theme;

  // Find all our ancestor themes and put them in an array.
  $base_theme = array();
  $ancestor = $theme;
  while ($ancestor && isset($themes[$ancestor]->base_theme)) {
    $base_theme[] = $new_base_theme = $themes[$themes[$ancestor]->base_theme];
    $ancestor = $themes[$ancestor]->base_theme;
  }
  _init_theme($themes[$theme], array_reverse($base_theme));
}

/**
 * Initialize the theme system given already loaded information. This
 * function is useful to initialize a theme when no database is present.
 *
 * @param $theme
 *   An object with the following information:
 *     filename
 *       The .info file for this theme. The 'path' to
 *       the theme will be in this file's directory. (Required)
 *     owner
 *       The path to the .theme file or the .engine file to load for
 *       the theme. (Required)
 *     stylesheet
 *       The primary stylesheet for the theme. (Optional)
 *     engine
 *       The name of theme engine to use. (Optional)
 * @param $base_theme
 *    An optional array of objects that represent the 'base theme' if the
 *    theme is meant to be derivative of another theme. It requires
 *    the same information as the $theme object. It should be in
 *    'oldest first' order, meaning the top level of the chain will
 *    be first.
 * @param $registry_callback
 *   The callback to invoke to set the theme registry.
 */
function _init_theme($theme, $base_theme = array(), $registry_callback = '_theme_load_registry') {
  global $theme_info, $base_theme_info, $theme_engine, $theme_path;
  $theme_info = $theme;
  $base_theme_info = $base_theme;

  $theme_path = dirname($theme->filename);

  // Prepare stylesheets from this theme as well as all ancestor themes.
  // We work it this way so that we can have child themes override parent
  // theme stylesheets easily.
  $final_stylesheets = array();

  // Grab stylesheets from base theme
  foreach ($base_theme as $base) {
    if (!empty($base->stylesheets)) {
      foreach ($base->stylesheets as $media => $stylesheets) {
        foreach ($stylesheets as $name => $stylesheet) {
          $final_stylesheets[$media][$name] = $stylesheet;
        }
      }
    }
  }

  // Add stylesheets used by this theme.
  if (!empty($theme->stylesheets)) {
    foreach ($theme->stylesheets as $media => $stylesheets) {
      foreach ($stylesheets as $name => $stylesheet) {
        $final_stylesheets[$media][$name] = $stylesheet;
      }
    }
  }

  // And now add the stylesheets properly
  foreach ($final_stylesheets as $media => $stylesheets) {
    foreach ($stylesheets as $stylesheet) {
      drupal_add_css($stylesheet, 'theme', $media);
    }
  }

  // Do basically the same as the above for scripts
  $final_scripts = array();

  // Grab scripts from base theme
  foreach ($base_theme as $base) {
    if (!empty($base->scripts)) {
      foreach ($base->scripts as $name => $script) {
        $final_scripts[$name] = $script;
      }
    }
  }

  // Add scripts used by this theme.
  if (!empty($theme->scripts)) {
    foreach ($theme->scripts as $name => $script) {
      $final_scripts[$name] = $script;
    }
  }

  // Add scripts used by this theme.
  foreach ($final_scripts as $script) {
    drupal_add_js($script, 'theme');
  }

  $theme_engine = NULL;

  // Initialize the theme.
  if (isset($theme->engine)) {
    // Include the engine.
    include_once './'. $theme->owner;

    $theme_engine = $theme->engine;
    if (function_exists($theme_engine .'_init')) {
      foreach ($base_theme as $base) {
        call_user_func($theme_engine .'_init', $base);
      }
      call_user_func($theme_engine .'_init', $theme);
    }
  }
  else {
    // include non-engine theme files
    foreach ($base_theme as $base) {
      // Include the theme file or the engine.
      if (!empty($base->owner)) {
        include_once './'. $base->owner;
      }
    }
    // and our theme gets one too.
    if (!empty($theme->owner)) {
      include_once './'. $theme->owner;
    }
  }

  $registry_callback($theme, $base_theme, $theme_engine);
}

/**
 * Retrieve the stored theme registry. If the theme registry is already
 * in memory it will be returned; otherwise it will attempt to load the
 * registry from cache. If this fails, it will construct the registry and
 * cache it.
 */
function theme_get_registry($registry = NULL) {
  static $theme_registry = NULL;
  if (isset($registry)) {
    $theme_registry = $registry;
  }

  return $theme_registry;
}

/**
 * Store the theme registry in memory.
 */
function _theme_set_registry($registry) {
  // Pass through for setting of static variable.
  return theme_get_registry($registry);
}

/**
 * Get the theme_registry cache from the database; if it doesn't exist, build
 * it.
 *
 * @param $theme
 *   The loaded $theme object.
 * @param $base_theme
 *   An array of loaded $theme objects representing the ancestor themes in
 *   oldest first order.
 * @param theme_engine
 *   The name of the theme engine.
 */
function _theme_load_registry($theme, $base_theme = NULL, $theme_engine = NULL) {
  // Check the theme registry cache; if it exists, use it.
  $cache = cache_get("theme_registry:$theme->name", 'cache');
  if (isset($cache->data)) {
    $registry = $cache->data;
  }
  else {
    // If not, build one and cache it.
    $registry = _theme_build_registry($theme, $base_theme, $theme_engine);
    _theme_save_registry($theme, $registry);
  }
  _theme_set_registry($registry);
}

/**
 * Write the theme_registry cache into the database.
 */
function _theme_save_registry($theme, $registry) {
  cache_set("theme_registry:$theme->name", $registry);
}

/**
 * Force the system to rebuild the theme registry; this should be called
 * when modules are added to the system, or when a dynamic system needs
 * to add more theme hooks.
 */
function drupal_rebuild_theme_registry() {
  cache_clear_all('theme_registry', 'cache', TRUE);
}

/**
 * Process a single invocation of the theme hook. $type will be one
 * of 'module', 'theme_engine', 'base_theme_engine', 'theme', or 'base_theme'
 * and it tells us some important information.
 *
 * Because $cache is a reference, the cache will be continually
 * expanded upon; new entries will replace old entries in the
 * array_merge, but we are careful to ensure some data is carried
 * forward, such as the arguments a theme hook needs.
 *
 * An override flag can be set for preprocess functions. When detected the
 * cached preprocessors for the hook will not be merged with the newly set.
 * This can be useful to themes and theme engines by giving them more control
 * over how and when the preprocess functions are run.
 */
function _theme_process_registry(&$cache, $name, $type, $theme, $path) {
  $result = array();
  $function = $name .'_theme';
  if (function_exists($function)) {
    $result = $function($cache, $type, $theme, $path);

    foreach ($result as $hook => $info) {
      $result[$hook]['type'] = $type;
      $result[$hook]['theme path'] = $path;
      // if function and file are left out, default to standard naming
      // conventions.
      if (!isset($info['template']) && !isset($info['function'])) {
        $result[$hook]['function'] = ($type == 'module' ? 'theme_' : $name .'_') . $hook;
      }

      // Make sure include files is set so we don't generate notices later.
      if (!isset($info['include files'])) {
        $result[$hook]['include files'] = array();
      }

      // If a path is set in the info, use what was set. Otherwise use the
      // default path. This is mostly so system.module can declare theme
      // functions on behalf of core .include files.
      // All files are included to be safe. Conditionally included
      // files can prevent them from getting registered.
      if (isset($info['file']) && !isset($info['path'])) {
        // First, check to see if the fully qualified file exists.
        $filename = './'. $path .'/'. $info['file'];
        if (file_exists($filename)) {
          require_once $filename;
          $result[$hook]['include files'][] = $filename;
        }
        else {
          $filename = './'. $info['file'];
          if (file_exists($filename)) {
            require_once $filename;
            $result[$hook]['include files'][] = $filename;
          }
        }
      }
      elseif (isset($info['file']) && isset($info['path'])) {
        $filename = './'. $info['path'] .'/'. $info['file'];
        if (file_exists($filename)) {
          require_once $filename;
          $result[$hook]['include files'][] = $filename;
        }
      }


      if (isset($info['template']) && !isset($info['path'])) {
        $result[$hook]['template'] = $path .'/'. $info['template'];
      }
      // If 'arguments' have been defined previously, carry them forward.
      // This should happen if a theme overrides a Drupal defined theme
      // function, for example.
      if (!isset($info['arguments']) && isset($cache[$hook])) {
        $result[$hook]['arguments'] = $cache[$hook]['arguments'];
      }
      // Likewise with theme paths. These are used for template naming suggestions.
      // Theme implementations can occur in multiple paths. Suggestions should follow.
      if (!isset($info['theme paths']) && isset($cache[$hook])) {
        $result[$hook]['theme paths'] = $cache[$hook]['theme paths'];
      }
      // Check for sub-directories.
      $result[$hook]['theme paths'][] = isset($info['path']) ? $info['path'] : $path;

      // Check for default _preprocess_ functions. Ensure arrayness.
      if (!isset($info['preprocess functions']) || !is_array($info['preprocess functions'])) {
        $info['preprocess functions'] = array();
        $prefixes = array();
        if ($type == 'module') {
          // Default preprocessor prefix.
          $prefixes[] = 'template';
          // Add all modules so they can intervene with their own preprocessors. This allows them
          // to provide preprocess functions even if they are not the owner of the current hook.
          $prefixes += module_list();
        }
        elseif ($type == 'theme_engine' || $type == 'base_theme_engine') {
          // Theme engines get an extra set that come before the normally named preprocessors.
          $prefixes[] = $name .'_engine';
          // The theme engine also registers on behalf of the theme. The theme or engine name can be used.
          $prefixes[] = $name;
          $prefixes[] = $theme;
        }
        else {
          // This applies when the theme manually registers their own preprocessors.
          $prefixes[] = $name;
        }

        foreach ($prefixes as $prefix) {
          if (function_exists($prefix .'_preprocess')) {
            $info['preprocess functions'][] = $prefix .'_preprocess';
          }

          if (function_exists($prefix .'_preprocess_'. $hook)) {
            $info['preprocess functions'][] = $prefix .'_preprocess_'. $hook;
          }

          if (!empty($info['original hook']) && function_exists($prefix .'_preprocess_'. $info['original hook'])) {
            $info['preprocess functions'][] = $prefix .'_preprocess_'. $info['original hook'];
          }
        }
      }
      // Check for the override flag and prevent the cached preprocess functions from being used.
      // This allows themes or theme engines to remove preprocessors set earlier in the registry build.
      if (!empty($info['override preprocess functions'])) {
        // Flag not needed inside the registry.
        unset($result[$hook]['override preprocess functions']);
      }
      elseif (isset($cache[$hook]['preprocess functions']) && is_array($cache[$hook]['preprocess functions'])) {
        $info['preprocess functions'] = array_merge($cache[$hook]['preprocess functions'], $info['preprocess functions']);
      }
      elseif (isset($info['original hook']) && isset($cache[$info['original hook']]['preprocess functions']) && is_array($cache[$info['original hook']]['preprocess functions'])) {
        $info['preprocess functions'] = array_merge($cache[$info['original hook']]['preprocess functions'], $info['preprocess functions']);
      }
      $result[$hook]['preprocess functions'] = $info['preprocess functions'];
    }

    // Merge the newly created theme hooks into the existing cache.
    $cache = array_merge($cache, $result);
  }

  // Let themes have preprocess functions even if they didn't register a template.
  if ($type == 'theme' || $type == 'base_theme') {
    foreach ($cache as $hook => $info) {
      // Check only if it's a template and not registered by the theme or engine.
      if (!empty($info['template']) && empty($result[$hook])) {
        if (!isset($info['preprocess functions'])) {
          $cache[$hook]['preprocess functions'] = array();
        }
        if (function_exists($name .'_preprocess')) {
          $cache[$hook]['preprocess functions'][] = $name .'_preprocess';
        }
        if (function_exists($name .'_preprocess_'. $hook)) {
          $cache[$hook]['preprocess functions'][] = $name .'_preprocess_'. $hook;
        }
        // Ensure uniqueness.
        $cache[$hook]['preprocess functions'] = array_unique($cache[$hook]['preprocess functions']);
      }
    }
  }
}

/**
 * Rebuild the hook theme_registry cache.
 *
 * @param $theme
 *   The loaded $theme object.
 * @param $base_theme
 *   An array of loaded $theme objects representing the ancestor themes in
 *   oldest first order.
 * @param theme_engine
 *   The name of the theme engine.
 */
function _theme_build_registry($theme, $base_theme, $theme_engine) {
  $cache = array();
  // First, process the theme hooks advertised by modules. This will
  // serve as the basic registry.
  foreach (module_implements('theme') as $module) {
    _theme_process_registry($cache, $module, 'module', $module, drupal_get_path('module', $module));
  }

  // Process each base theme.
  foreach ($base_theme as $base) {
    // If the base theme uses a theme engine, process its hooks.
    $base_path = dirname($base->filename);
    if ($theme_engine) {
      _theme_process_registry($cache, $theme_engine, 'base_theme_engine', $base->name, $base_path);
    }
    _theme_process_registry($cache, $base->name, 'base_theme', $base->name, $base_path);
  }

  // And then the same thing, but for the theme.
  if ($theme_engine) {
    _theme_process_registry($cache, $theme_engine, 'theme_engine', $theme->name, dirname($theme->filename));
  }

  // Finally, hooks provided by the theme itself.
  _theme_process_registry($cache, $theme->name, 'theme', $theme->name, dirname($theme->filename));

  // Let modules alter the registry
  drupal_alter('theme_registry', $cache);
  return $cache;
}

/**
 * Provides a list of currently available themes.
 *
 * If the database is active then it will be retrieved from the database.
 * Otherwise it will retrieve a new list.
 *
 * @param $refresh
 *   Whether to reload the list of themes from the database.
 * @return
 *   An array of the currently available themes.
 */
function list_themes($refresh = FALSE) {
  static $list = array();

  if ($refresh) {
    $list = array();
  }

  if (empty($list)) {
    $list = array();
    $themes = array();
    // Extract from the database only when it is available.
    // Also check that the site is not in the middle of an install or update.
    if (db_is_active() && !defined('MAINTENANCE_MODE')) {
      $result = db_query("SELECT * FROM {system} WHERE type = '%s'", 'theme');
      while ($theme = db_fetch_object($result)) {
        if (file_exists($theme->filename)) {
          $theme->info = unserialize($theme->info);
          $themes[] = $theme;
        }
      }
    }
    else {
      // Scan the installation when the database should not be read.
      $themes = _system_theme_data();
    }

    foreach ($themes as $theme) {
      foreach ($theme->info['stylesheets'] as $media => $stylesheets) {
        foreach ($stylesheets as $stylesheet => $path) {
          $theme->stylesheets[$media][$stylesheet] = $path;
        }
      }
      foreach ($theme->info['scripts'] as $script => $path) {
        if (file_exists($path)) {
          $theme->scripts[$script] = $path;
        }
      }
      if (isset($theme->info['engine'])) {
        $theme->engine = $theme->info['engine'];
      }
      if (isset($theme->info['base theme'])) {
        $theme->base_theme = $theme->info['base theme'];
      }
      // Status is normally retrieved from the database. Add zero values when
      // read from the installation directory to prevent notices.
      if (!isset($theme->status)) {
        $theme->status = 0;
      }
      $list[$theme->name] = $theme;
    }
  }

  return $list;
}

/**
 * Generates the themed output.
 *
 * All requests for theme hooks must go through this function. It examines
 * the request and routes it to the appropriate theme function. The theme
 * registry is checked to determine which implementation to use, which may
 * be a function or a template.
 *
 * If the implementation is a function, it is executed and its return value
 * passed along.
 *
 * If the implementation is a template, the arguments are converted to a
 * $variables array. This array is then modified by the module implementing
 * the hook, theme engine (if applicable) and the theme. The following
 * functions may be used to modify the $variables array. They are processed in
 * this order when available:
 *
 * - template_preprocess(&$variables, $hook)
 *   This sets a default set of variables for all template implementations.
 *
 * - template_preprocess_HOOK(&$variables)
 *   This is the first preprocessor called specific to the hook; it should be
 *   implemented by the module that registers it.
 *
 * - MODULE_preprocess(&$variables, $hook)
 *   This will be called for all templates; it should only be used if there
 *   is a real need. It's purpose is similar to template_preprocess().
 *
 * - MODULE_preprocess_HOOK(&$variables)
 *   This is for modules that want to alter or provide extra variables for
 *   theming hooks not registered to itself. For example, if a module named
 *   "foo" wanted to alter the $submitted variable for the hook "node" a
 *   preprocess function of foo_preprocess_node() can be created to intercept
 *   and alter the variable.
 *
 * - ENGINE_engine_preprocess(&$variables, $hook)
 *   This function should only be implemented by theme engines and exists
 *   so that it can set necessary variables for all hooks.
 *
 * - ENGINE_engine_preprocess_HOOK(&$variables)
 *   This is the same as the previous function, but it is called for a single
 *   theming hook.
 *
 * - ENGINE_preprocess(&$variables, $hook)
 *   This is meant to be used by themes that utilize a theme engine. It is
 *   provided so that the preprocessor is not locked into a specific theme.
 *   This makes it easy to share and transport code but theme authors must be
 *   careful to prevent fatal re-declaration errors when using sub-themes that
 *   have their own preprocessor named exactly the same as its base theme. In
 *   the default theme engine (PHPTemplate), sub-themes will load their own
 *   template.php file in addition to the one used for its parent theme. This
 *   increases the risk for these errors. A good practice is to use the engine
 *   name for the base theme and the theme name for the sub-themes to minimize
 *   this possibility.
 *
 * - ENGINE_preprocess_HOOK(&$variables)
 *   The same applies from the previous function, but it is called for a
 *   specific hook.
 *
 * - THEME_preprocess(&$variables, $hook)
 *   These functions are based upon the raw theme; they should primarily be
 *   used by themes that do not use an engine or by sub-themes. It serves the
 *   same purpose as ENGINE_preprocess().
 *
 * - THEME_preprocess_HOOK(&$variables)
 *   The same applies from the previous function, but it is called for a
 *   specific hook.
 *
 * There are two special variables that these hooks can set:
 *   'template_file' and 'template_files'. These will be merged together
 *   to form a list of 'suggested' alternate template files to use, in
 *   reverse order of priority. template_file will always be a higher
 *   priority than items in template_files. theme() will then look for these
 *   files, one at a time, and use the first one
 *   that exists.
 * @param $hook
 *   The name of the theme function to call. May be an array, in which
 *   case the first hook that actually has an implementation registered
 *   will be used. This can be used to choose 'fallback' theme implementations,
 *   so that if the specific theme hook isn't implemented anywhere, a more
 *   generic one will be used. This can allow themes to create specific theme
 *   implementations for named objects.
 * @param ...
 *   Additional arguments to pass along to the theme function.
 * 
 * @return
 *   An HTML string that generates the themed output.
 */
function theme() {
  $args = func_get_args();
  $hook = array_shift($args);

  static $hooks = NULL;
  if (!isset($hooks)) {
    init_theme();
    $hooks = theme_get_registry();
  }

  if (is_array($hook)) {
    foreach ($hook as $candidate) {
      if (isset($hooks[$candidate])) {
        break;
      }
    }
    $hook = $candidate;
  }

  if (!isset($hooks[$hook])) {
    return;
  }

  $info = $hooks[$hook];
  global $theme_path;
  $temp = $theme_path;
  // point path_to_theme() to the currently used theme path:
  $theme_path = $hooks[$hook]['theme path'];

  // Include a file if the theme function or preprocess function is held elsewhere.
  if (!empty($info['include files'])) {
    foreach ($info['include files'] as $include_file) {
      include_once($include_file);
    }
  }

  // Handle compatibility with theme_registry_alters to prevent failures.
  if (!empty($info['file'])) {
    static $included_files = array();
    $include_file = $info['file'];
    if (!empty($info['path'])) {
      $include_file = $info['path'] .'/'. $include_file;
    }

    if (empty($included_files[$include_file])) {
      // Statically cache files we've already tried to include so we don't
      // run unnecessary file_exists calls.
      $included_files[$include_file] = TRUE;
      if (file_exists('./'. $include_file)) {
        include_once('./'. $include_file);
      }
    }
  }

  if (isset($info['function'])) {
    // The theme call is a function.
    $output = call_user_func_array($info['function'], $args);
  }
  else {
    // The theme call is a template.
    $variables = array(
      'template_files' => array()
    );
    if (!empty($info['arguments'])) {
      $count = 0;
      foreach ($info['arguments'] as $name => $default) {
        $variables[$name] = isset($args[$count]) ? $args[$count] : $default;
        $count++;
      }
    }

    // default render function and extension.
    $render_function = 'theme_render_template';
    $extension = '.tpl.php';

    // Run through the theme engine variables, if necessary
    global $theme_engine;
    if (isset($theme_engine)) {
      // If theme or theme engine is implementing this, it may have
      // a different extension and a different renderer.
      if ($hooks[$hook]['type'] != 'module') {
        if (function_exists($theme_engine .'_render_template')) {
          $render_function = $theme_engine .'_render_template';
        }
        $extension_function = $theme_engine .'_extension';
        if (function_exists($extension_function)) {
          $extension = $extension_function();
        }
      }
    }

    if (isset($info['preprocess functions']) && is_array($info['preprocess functions'])) {
      // This construct ensures that we can keep a reference through
      // call_user_func_array.
      $args = array(&$variables, $hook);
      foreach ($info['preprocess functions'] as $preprocess_function) {
        if (function_exists($preprocess_function)) {
          call_user_func_array($preprocess_function, $args);
        }
      }
    }

    // Get suggestions for alternate templates out of the variables
    // that were set. This lets us dynamically choose a template
    // from a list. The order is FILO, so this array is ordered from
    // least appropriate first to most appropriate last.
    $suggestions = array();

    if (isset($variables['template_files'])) {
      $suggestions = $variables['template_files'];
    }
    if (isset($variables['template_file'])) {
      $suggestions[] = $variables['template_file'];
    }

    if ($suggestions) {
      $template_file = drupal_discover_template($info['theme paths'], $suggestions, $extension);
    }

    if (empty($template_file)) {
      $template_file = $hooks[$hook]['template'] . $extension;
      if (isset($hooks[$hook]['path'])) {
        $template_file = $hooks[$hook]['path'] .'/'. $template_file;
      }
    }
    $output = $render_function($template_file, $variables);
  }
  // restore path_to_theme()
  $theme_path = $temp;
  // Add final markup to the full page.
  if ($hook == 'page' || $hook == 'book_export_html') {
    $output = drupal_final_markup($output);
  }
  return $output;
}

/**
 * Choose which template file to actually render. These are all suggested
 * templates from themes and modules. Theming implementations can occur on
 * multiple levels. All paths are checked to account for this.
 */
function drupal_discover_template($paths, $suggestions, $extension = '.tpl.php') {
  global $theme_engine;

  // Remove slashes or null to prevent files from being included from
  // an unexpected location (especially on Windows servers).
  $extension = str_replace(array("/", "\\", "\0"), '', $extension);

  // Loop through all paths and suggestions in FIFO order.
  $suggestions = array_reverse($suggestions);
  $paths = array_reverse($paths);
  foreach ($suggestions as $suggestion) {
    if (!empty($suggestion)) {
      $suggestion = str_replace(array("/", "\\", "\0"), '', $suggestion);
      foreach ($paths as $path) {
        if (file_exists($file = $path .'/'. $suggestion . $extension)) {
          return $file;
        }
      }
    }
  }
}

/**
 * Return the path to the current themed element.
 *
 * It can point to the active theme or the module handling a themed implementation.
 * For example, when invoked within the scope of a theming call it will depend
 * on where the theming function is handled. If implemented from a module, it
 * will point to the module. If implemented from the active theme, it will point
 * to the active theme. When called outside the scope of a theming call, it will
 * always point to the active theme.
 */
function path_to_theme() {
  global $theme_path;

  if (!isset($theme_path)) {
    init_theme();
  }

  return $theme_path;
}

/**
 * Find overridden theme functions. Called by themes and/or theme engines to
 * easily discover theme functions.
 *
 * @param $cache
 *   The existing cache of theme hooks to test against.
 * @param $prefixes
 *   An array of prefixes to test, in reverse order of importance.
 *
 * @return $templates
 *   The functions found, suitable for returning from hook_theme;
 */
function drupal_find_theme_functions($cache, $prefixes) {
  $templates = array();
  $functions = get_defined_functions();

  foreach ($cache as $hook => $info) {
    foreach ($prefixes as $prefix) {
      if (!empty($info['pattern'])) {
        $matches = preg_grep('/^'. $prefix .'_'. $info['pattern'] .'/', $functions['user']);
        if ($matches) {
          foreach ($matches as $match) {
            $new_hook = str_replace($prefix .'_', '', $match);
            $templates[$new_hook] = array(
              'function' => $match,
              'arguments' => $info['arguments'],
              'original hook' => $hook,
              'include files' => $info['include files'],
            );
          }
        }
      }

      if (function_exists($prefix .'_'. $hook)) {
        $templates[$hook] = array(
          'function' => $prefix .'_'. $hook,
          'include files' => $info['include files'],
        );
        // Ensure that the pattern is maintained from base themes to its sub-themes.
        // Each sub-theme will have their functions scanned so the pattern must be
        // held for subsequent runs.
        if (isset($info['pattern'])) {
          $templates[$hook]['pattern'] = $info['pattern'];
        }
        // Also ensure that the 'file' property is maintained, because it probably
        // contains the preprocess.
      }
    }
  }

  return $templates;
}

/**
 * Find overridden theme templates. Called by themes and/or theme engines to
 * easily discover templates.
 *
 * @param $cache
 *   The existing cache of theme hooks to test against.
 * @param $extension
 *   The extension that these templates will have.
 * @param $path
 *   The path to search.
 */
function drupal_find_theme_templates($cache, $extension, $path) {
  $templates = array();

  // Collect paths to all sub-themes grouped by base themes. These will be
  // used for filtering. This allows base themes to have sub-themes in its
  // folder hierarchy without affecting the base themes template discovery.
  $theme_paths = array();
  foreach (list_themes() as $theme_info) {
    if (!empty($theme_info->base_theme)) {
      $theme_paths[$theme_info->base_theme][$theme_info->name] = dirname($theme_info->filename);
    }
  }
  foreach ($theme_paths as $basetheme => $subthemes) {
    foreach ($subthemes as $subtheme => $subtheme_path) {
      if (isset($theme_paths[$subtheme])) {
        $theme_paths[$basetheme] = array_merge($theme_paths[$basetheme], $theme_paths[$subtheme]);
      }
    }
  }
  global $theme;
  $subtheme_paths = isset($theme_paths[$theme]) ? $theme_paths[$theme] : array();

  // Escape the periods in the extension.
  $regex = str_replace('.', '\.', $extension) .'$';
  // Because drupal_system_listing works the way it does, we check for real
  // templates separately from checking for patterns.
  $files = drupal_system_listing($regex, $path, 'name', 0);
  foreach ($files as $template => $file) {
    // Ignore sub-theme templates for the current theme.
    if (strpos($file->filename, str_replace($subtheme_paths, '', $file->filename)) !== 0) {
      continue;
    }
    // Chop off the remaining extensions if there are any. $template already
    // has the rightmost extension removed, but there might still be more,
    // such as with .tpl.php, which still has .tpl in $template at this point.
    if (($pos = strpos($template, '.')) !== FALSE) {
      $template = substr($template, 0, $pos);
    }
    // Transform - in filenames to _ to match function naming scheme
    // for the purposes of searching.
    $hook = strtr($template, '-', '_');
    if (isset($cache[$hook])) {
      $templates[$hook] = array(
        'template' => $template,
        'path' => dirname($file->filename),
        'include files' => $cache[$hook]['include files'],
      );
    }
    // Ensure that the pattern is maintained from base themes to its sub-themes.
    // Each sub-theme will have their templates scanned so the pattern must be
    // held for subsequent runs.
    if (isset($cache[$hook]['pattern'])) {
      $templates[$hook]['pattern'] = $cache[$hook]['pattern'];
    }
  }

  $patterns = array_keys($files);

  foreach ($cache as $hook => $info) {
    if (!empty($info['pattern'])) {
      // Transform _ in pattern to - to match file naming scheme
      // for the purposes of searching.
      $pattern = strtr($info['pattern'], '_', '-');

      $matches = preg_grep('/^'. $pattern .'/', $patterns);
      if ($matches) {
        foreach ($matches as $match) {
          $file = substr($match, 0, strpos($match, '.'));
          // Put the underscores back in for the hook name and register this pattern.
          $templates[strtr($file, '-', '_')] = array(
            'template' => $file,
            'path' => dirname($files[$match]->filename),
            'arguments' => $info['arguments'],
            'original hook' => $hook,
            'include files' => $info['include files'],
          );
        }
      }
    }
  }
  return $templates;
}

/**
 * Retrieve an associative array containing the settings for a theme.
 *
 * The final settings are arrived at by merging the default settings,
 * the site-wide settings, and the settings defined for the specific theme.
 * If no $key was specified, only the site-wide theme defaults are retrieved.
 *
 * The default values for each of settings are also defined in this function.
 * To add new settings, add their default values here, and then add form elements
 * to system_theme_settings() in system.module.
 *
 * @param $key
 *  The template/style value for a given theme.
 *
 * @return
 *   An associative array containing theme settings.
 */
function theme_get_settings($key = NULL) {
  $defaults = array(
    'mission'                       =>  '',
    'default_logo'                  =>  1,
    'logo_path'                     =>  '',
    'default_favicon'               =>  1,
    'favicon_path'                  =>  '',
    'primary_links'                 =>  1,
    'secondary_links'               =>  1,
    'toggle_logo'                   =>  1,
    'toggle_favicon'                =>  1,
    'toggle_name'                   =>  1,
    'toggle_search'                 =>  1,
    'toggle_slogan'                 =>  0,
    'toggle_mission'                =>  1,
    'toggle_node_user_picture'      =>  0,
    'toggle_comment_user_picture'   =>  0,
    'toggle_primary_links'          =>  1,
    'toggle_secondary_links'        =>  1,
  );

  if (module_exists('node')) {
    foreach (node_get_types() as $type => $name) {
      $defaults['toggle_node_info_'. $type] = 1;
    }
  }
  $settings = array_merge($defaults, variable_get('theme_settings', array()));

  if ($key) {
    $settings = array_merge($settings, variable_get(str_replace('/', '_', 'theme_'. $key .'_settings'), array()));
  }

  // Only offer search box if search.module is enabled.
  if (!module_exists('search') || !user_access('search content')) {
    $settings['toggle_search'] = 0;
  }

  return $settings;
}

/**
 * Retrieve a setting for the current theme.
 * This function is designed for use from within themes & engines
 * to determine theme settings made in the admin interface.
 *
 * Caches values for speed (use $refresh = TRUE to refresh cache)
 *
 * @param $setting_name
 *  The name of the setting to be retrieved.
 *
 * @param $refresh
 *  Whether to reload the cache of settings.
 *
 * @return
 *   The value of the requested setting, NULL if the setting does not exist.
 */
function theme_get_setting($setting_name, $refresh = FALSE) {
  global $theme_key;
  static $settings;

  if (empty($settings) || $refresh) {
    $settings = theme_get_settings($theme_key);

    $themes = list_themes();
    $theme_object = $themes[$theme_key];

    if ($settings['mission'] == '') {
      $settings['mission'] = variable_get('site_mission', '');
    }

    if (!$settings['toggle_mission']) {
      $settings['mission'] = '';
    }

    if ($settings['toggle_logo']) {
      if ($settings['default_logo']) {
        $settings['logo'] = base_path() . dirname($theme_object->filename) .'/logo.png';
      }
      elseif ($settings['logo_path']) {
        $settings['logo'] = base_path() . $settings['logo_path'];
      }
    }

    if ($settings['toggle_favicon']) {
      if ($settings['default_favicon']) {
        if (file_exists($favicon = dirname($theme_object->filename) .'/favicon.ico')) {
          $settings['favicon'] = base_path() . $favicon;
        }
        else {
          $settings['favicon'] = base_path() .'misc/favicon.ico';
        }
      }
      elseif ($settings['favicon_path']) {
        $settings['favicon'] = base_path() . $settings['favicon_path'];
      }
      else {
        $settings['toggle_favicon'] = FALSE;
      }
    }
  }

  return isset($settings[$setting_name]) ? $settings[$setting_name] : NULL;
}

/**
 * Render a system default template, which is essentially a PHP template.
 *
 * @param $template_file
 *   The filename of the template to render. Note that this will overwrite
 *   anything stored in $variables['template_file'] if using a preprocess hook.
 * @param $variables
 *   A keyed array of variables that will appear in the output.
 *
 * @return
 *   The output generated by the template.
 */
function theme_render_template($template_file, $variables) {
  extract($variables, EXTR_SKIP);  // Extract the variables to a local namespace
  ob_start();                      // Start output buffering
  include "./$template_file";      // Include the template file
  $contents = ob_get_contents();   // Get the contents of the buffer
  ob_end_clean();                  // End buffering and discard
  return $contents;                // Return the contents
}

/**
 * @defgroup themeable Default theme implementations
 * @{
 * Functions and templates that present output to the user, and can be
 * implemented by themes.
 *
 * Drupal's presentation layer is a pluggable system known as the theme
 * layer. Each theme can take control over most of Drupal's output, and
 * has complete control over the CSS.
 *
 * Inside Drupal, the theme layer is utilized by the use of the theme()
 * function, which is passed the name of a component (the theme hook)
 * and several arguments. For example, theme('table', $header, $rows);
 * Additionally, the theme() function can take an array of theme
 * hooks, which can be used to provide 'fallback' implementations to
 * allow for more specific control of output. For example, the function:
 * theme(array('table__foo', 'table'), $header, $rows) would look to see if
 * 'table__foo' is registered anywhere; if it is not, it would 'fall back'
 * to the generic 'table' implementation. This can be used to attach specific
 * theme functions to named objects, allowing the themer more control over
 * specific types of output.
 *
 * As of Drupal 6, every theme hook is required to be registered by the
 * module that owns it, so that Drupal can tell what to do with it and
 * to make it simple for themes to identify and override the behavior
 * for these calls.
 *
 * The theme hooks are registered via hook_theme(), which returns an
 * array of arrays with information about the hook. It describes the
 * arguments the function or template will need, and provides
 * defaults for the template in case they are not filled in. If the default
 * implementation is a function, by convention it is named theme_HOOK().
 *
 * Each module should provide a default implementation for theme_hooks that
 * it registers. This implementation may be either a function or a template;
 * if it is a function it must be specified via hook_theme(). By convention,
 * default implementations of theme hooks are named theme_HOOK. Default
 * template implementations are stored in the module directory.
 *
 * Drupal's default template renderer is a simple PHP parsing engine that
 * includes the template and stores the output. Drupal's theme engines
 * can provide alternate template engines, such as XTemplate, Smarty and
 * PHPTal. The most common template engine is PHPTemplate (included with
 * Drupal and implemented in phptemplate.engine, which uses Drupal's default
 * template renderer.
 *
 * In order to create theme-specific implementations of these hooks,
 * themes can implement their own version of theme hooks, either as functions
 * or templates. These implementations will be used instead of the default
 * implementation. If using a pure .theme without an engine, the .theme is
 * required to implement its own version of hook_theme() to tell Drupal what
 * it is implementing; themes utilizing an engine will have their well-named
 * theming functions automatically registered for them. While this can vary
 * based upon the theme engine, the standard set by phptemplate is that theme
 * functions should be named either phptemplate_HOOK or THEMENAME_HOOK. For
 * example, for Drupal's default theme (Garland) to implement the 'table' hook,
 * the phptemplate.engine would find phptemplate_table() or garland_table().
 * The ENGINE_HOOK() syntax is preferred, as this can be used by sub-themes
 * (which are themes that share code but use different stylesheets).
 *
 * The theme system is described and defined in theme.inc.
 *
 * @see theme()
 * @see hook_theme()
 */

/**
 * Formats text for emphasized display in a placeholder inside a sentence.
 * Used automatically by t().
 *
 * @param $text
 *   The text to format (plain-text).
 * @return
 *   The formatted text (html).
 */
function theme_placeholder($text) {
  return '<em>'. check_plain($text) .'</em>';
}

/**
 * Return a themed set of status and/or error messages. The messages are grouped
 * by type.
 *
 * @param $display
 *   (optional) Set to 'status' or 'error' to display only messages of that type.
 *
 * @return
 *   A string containing the messages.
 */
function theme_status_messages($display = NULL) {
  $output = '';
  foreach (drupal_get_messages($display) as $type => $messages) {
    $output .= "<div class=\"messages $type\">\n";
    if (count($messages) > 1) {
      $output .= " <ul>\n";
      foreach ($messages as $message) {
        $output .= '  <li>'. $message ."</li>\n";
      }
      $output .= " </ul>\n";
    }
    else {
      $output .= $messages[0];
    }
    $output .= "</div>\n";
  }
  return $output;
}

/**
 * Return a themed set of links.
 *
 * @param $links
 *   A keyed array of links to be themed.
 * @param $attributes
 *   A keyed array of attributes
 * @return
 *   A string containing an unordered list of links.
 */
function theme_links($links, $attributes = array('class' => 'links')) {
  global $language;
  $output = '';

  if (count($links) > 0) {
    $output = '<ul'. drupal_attributes($attributes) .'>';

    $num_links = count($links);
    $i = 1;

    foreach ($links as $key => $link) {
      $class = $key;

      // Add first, last and active classes to the list of links to help out themers.
      if ($i == 1) {
        $class .= ' first';
      }
      if ($i == $num_links) {
        $class .= ' last';
      }
      if (isset($link['href']) && ($link['href'] == $_GET['q'] || ($link['href'] == '<front>' && drupal_is_front_page()))
          && (empty($link['language']) || $link['language']->language == $language->language)) {
        $class .= ' active';
      }
      $output .= '<li'. drupal_attributes(array('class' => $class)) .'>';

      if (isset($link['href'])) {
        // Pass in $link as $options, they share the same keys.
        $output .= l($link['title'], $link['href'], $link);
      }
      else if (!empty($link['title'])) {
        // Some links are actually not links, but we wrap these in <span> for adding title and class attributes
        if (empty($link['html'])) {
          $link['title'] = check_plain($link['title']);
        }
        $span_attributes = '';
        if (isset($link['attributes'])) {
          $span_attributes = drupal_attributes($link['attributes']);
        }
        $output .= '<span'. $span_attributes .'>'. $link['title'] .'</span>';
      }

      $i++;
      $output .= "</li>\n";
    }

    $output .= '</ul>';
  }

  return $output;
}

/**
 * Return a themed image.
 *
 * @param $path
 *   Either the path of the image file (relative to base_path()) or a full URL.
 *   If this is a full URL, $getsize must be set to FALSE or nothing will be returned.
 * @param $alt
 *   The alternative text for text-based browsers.
 * @param $title
 *   The title text is displayed when the image is hovered in some popular browsers.
 * @param $attributes
 *   Associative array of attributes to be placed in the img tag.
 * @param $getsize
 *   If set to TRUE, the image's dimension are fetched and added as width/height attributes.
 *   Defaults to TRUE. Must be set to FALSE if $path is a full URL.
 * 
 * @return
 *   A string containing the image tag.
 */
function theme_image($path, $alt = '', $title = '', $attributes = NULL, $getsize = TRUE) {
  if (!$getsize || (is_file($path) && (list($width, $height, $type, $image_attributes) = @getimagesize($path)))) {
    $attributes = drupal_attributes($attributes);
    $url = (url($path) == $path) ? $path : (base_path() . $path);
    return '<img src="'. check_url($url) .'" alt="'. check_plain($alt) .'" title="'. check_plain($title) .'" '. (isset($image_attributes) ? $image_attributes : '') . $attributes .' />';
  }
}

/**
 * Return a themed breadcrumb trail.
 *
 * @param $breadcrumb
 *   An array containing the breadcrumb links.
 * @return a string containing the breadcrumb output.
 */
function theme_breadcrumb($breadcrumb) {
  if (!empty($breadcrumb)) {
    return '<div class="breadcrumb">'. implode(' » ', $breadcrumb) .'</div>';
  }
}

/**
 * Return a themed help message.
 *
 * @return a string containing the helptext for the current page.
 */
function theme_help() {
  if ($help = menu_get_active_help()) {
    return '<div class="help">'. $help .'</div>';
  }
}

/**
 * Return a themed submenu, typically displayed under the tabs.
 *
 * @param $links
 *   An array of links.
 */
function theme_submenu($links) {
  return '<div class="submenu">'. implode(' | ', $links) .'</div>';
}

/**
 * Return a themed table.
 *
 * @param $header
 *   An array containing the table headers. Each element of the array can be
 *   either a localized string or an associative array with the following keys:
 *   - "data": The localized title of the table column.
 *   - "field": The database field represented in the table column (required if
 *     user is to be able to sort on this column).
 *   - "sort": A default sort order for this column ("asc" or "desc").
 *   - Any HTML attributes, such as "colspan", to apply to the column header cell.
 * @param $rows
 *   An array of table rows. Every row is an array of cells, or an associative
 *   array with the following keys:
 *   - "data": an array of cells
 *   - Any HTML attributes, such as "class", to apply to the table row.
 *
 *   Each cell can be either a string or an associative array with the following keys:
 *   - "data": The string to display in the table cell.
 *   - "header": Indicates this cell is a header.
 *   - Any HTML attributes, such as "colspan", to apply to the table cell.
 *
 *   Here's an example for $rows:
 *   @code
 *   $rows = array(
 *     // Simple row
 *     array(
 *       'Cell 1', 'Cell 2', 'Cell 3'
 *     ),
 *     // Row with attributes on the row and some of its cells.
 *     array(
 *       'data' => array('Cell 1', array('data' => 'Cell 2', 'colspan' => 2)), 'class' => 'funky'
 *     )
 *   );
 *   @endcode
 *
 * @param $attributes
 *   An array of HTML attributes to apply to the table tag.
 * @param $caption
 *   A localized string to use for the <caption> tag.
 * @return
 *   An HTML string representing the table.
 */
function theme_table($header, $rows, $attributes = array(), $caption = NULL) {

  // Add sticky headers, if applicable.
  if (count($header)) {
    drupal_add_js('misc/tableheader.js');
    // Add 'sticky-enabled' class to the table to identify it for JS.
    // This is needed to target tables constructed by this function.
    $attributes['class'] = empty($attributes['class']) ? 'sticky-enabled' : ($attributes['class'] .' sticky-enabled');
  }

  $output = '<table'. drupal_attributes($attributes) .">\n";

  if (isset($caption)) {
    $output .= '<caption>'. $caption ."</caption>\n";
  }

  // Format the table header:
  if (count($header)) {
    $ts = tablesort_init($header);
    // HTML requires that the thead tag has tr tags in it followed by tbody
    // tags. Using ternary operator to check and see if we have any rows.
    $output .= (count($rows) ? ' <thead><tr>' : ' <tr>');
    foreach ($header as $cell) {
      $cell = tablesort_header($cell, $header, $ts);
      $output .= _theme_table_cell($cell, TRUE);
    }
    // Using ternary operator to close the tags based on whether or not there are rows
    $output .= (count($rows) ? " </tr></thead>\n" : "</tr>\n");
  }
  else {
    $ts = array();
  }

  // Format the table rows:
  if (count($rows)) {
    $output .= "<tbody>\n";
    $flip = array('even' => 'odd', 'odd' => 'even');
    $class = 'even';
    foreach ($rows as $number => $row) {
      $attributes = array();

      // Check if we're dealing with a simple or complex row
      if (isset($row['data'])) {
        foreach ($row as $key => $value) {
          if ($key == 'data') {
            $cells = $value;
          }
          else {
            $attributes[$key] = $value;
          }
        }
      }
      else {
        $cells = $row;
      }
      if (count($cells)) {
        // Add odd/even class
        $class = $flip[$class];
        if (isset($attributes['class'])) {
          $attributes['class'] .= ' '. $class;
        }
        else {
          $attributes['class'] = $class;
        }

        // Build row
        $output .= ' <tr'. drupal_attributes($attributes) .'>';
        $i = 0;
        foreach ($cells as $cell) {
          $cell = tablesort_cell($cell, $header, $ts, $i++);
          $output .= _theme_table_cell($cell);
        }
        $output .= " </tr>\n";
      }
    }
    $output .= "</tbody>\n";
  }

  $output .= "</table>\n";
  return $output;
}

/**
 * Returns a header cell for tables that have a select all functionality.
 */
function theme_table_select_header_cell() {
  drupal_add_js('misc/tableselect.js');

  return array('class' => 'select-all');
}

/**
 * Return a themed sort icon.
 *
 * @param $style
 *   Set to either asc or desc. This sets which icon to show.
 * @return
 *   A themed sort icon.
 */
function theme_tablesort_indicator($style) {
  if ($style == "asc") {
    return theme('image', 'misc/arrow-asc.png', t('sort icon'), t('sort ascending'));
  }
  else {
    return theme('image', 'misc/arrow-desc.png', t('sort icon'), t('sort descending'));
  }
}

/**
 * Return a themed box.
 *
 * @param $title
 *   The subject of the box.
 * @param $content
 *   The content of the box.
 * @param $region
 *   The region in which the box is displayed.
 * @return
 *   A string containing the box output.
 */
function theme_box($title, $content, $region = 'main') {
  $output = '<h2 class="title">'. $title .'</h2><div>'. $content .'</div>';
  return $output;
}

/**
 * Return a themed marker, useful for marking new or updated
 * content.
 *
 * @param $type
 *   Number representing the marker type to display
 * @see MARK_NEW, MARK_UPDATED, MARK_READ
 * @return
 *   A string containing the marker.
 */
function theme_mark($type = MARK_NEW) {
  global $user;
  if ($user->uid) {
    if ($type == MARK_NEW) {
      return ' <span class="marker">'. t('new') .'</span>';
    }
    else if ($type == MARK_UPDATED) {
      return ' <span class="marker">'. t('updated') .'</span>';
    }
  }
}

/**
 * Return a themed list of items.
 *
 * @param $items
 *   An array of items to be displayed in the list. If an item is a string,
 *   then it is used as is. If an item is an array, then the "data" element of
 *   the array is used as the contents of the list item. If an item is an array
 *   with a "children" element, those children are displayed in a nested list.
 *   All other elements are treated as attributes of the list item element.
 * @param $title
 *   The title of the list.
 * @param $type
 *   The type of list to return (e.g. "ul", "ol")
 * @param $attributes
 *   The attributes applied to the list element.
 * @return
 *   A string containing the list output.
 */
function theme_item_list($items = array(), $title = NULL, $type = 'ul', $attributes = NULL) {
  $output = '<div class="item-list">';
  if (isset($title)) {
    $output .= '<h3>'. $title .'</h3>';
  }

  if (!empty($items)) {
    $output .= "<$type". drupal_attributes($attributes) .'>';
    $num_items = count($items);
    foreach ($items as $i => $item) {
      $attributes = array();
      $children = array();
      if (is_array($item)) {
        foreach ($item as $key => $value) {
          if ($key == 'data') {
            $data = $value;
          }
          elseif ($key == 'children') {
            $children = $value;
          }
          else {
            $attributes[$key] = $value;
          }
        }
      }
      else {
        $data = $item;
      }
      if (count($children) > 0) {
        $data .= theme_item_list($children, NULL, $type, $attributes); // Render nested list
      }
      if ($i == 0) {
        $attributes['class'] = empty($attributes['class']) ? 'first' : ($attributes['class'] .' first');
      }
      if ($i == $num_items - 1) {
        $attributes['class'] = empty($attributes['class']) ? 'last' : ($attributes['class'] .' last');
      }
      $output .= '<li'. drupal_attributes($attributes) .'>'. $data ."</li>\n";
    }
    $output .= "</$type>";
  }
  $output .= '</div>';
  return $output;
}

/**
 * Returns code that emits the 'more help'-link.
 */
function theme_more_help_link($url) {
  return '<div class="more-help-link">'. t('[<a href="@link">more help...</a>]', array('@link' => check_url($url))) .'</div>';
}

/**
 * Return code that emits an XML icon.
 *
 * For most use cases, this function has been superseded by theme_feed_icon().
 *
 * @see theme_feed_icon()
 * @param $url
 *   The url of the feed.
 */
function theme_xml_icon($url) {
  if ($image = theme('image', 'misc/xml.png', t('XML feed'), t('XML feed'))) {
    return '<a href="'. check_url($url) .'" class="xml-icon">'. $image .'</a>';
  }
}

/**
 * Return code that emits an feed icon.
 *
 * @param $url
 *   The url of the feed.
 * @param $title
 *   A descriptive title of the feed.
  */
function theme_feed_icon($url, $title) {
  if ($image = theme('image', 'misc/feed.png', t('Syndicate content'), $title)) {
    return '<a href="'. check_url($url) .'" class="feed-icon">'. $image .'</a>';
  }
}

/**
 * Returns code that emits the 'more' link used on blocks.
 *
 * @param $url
 *   The url of the main page
 * @param $title
 *   A descriptive verb for the link, like 'Read more'
 */
function theme_more_link($url, $title) {
  return '<div class="more-link">'. t('<a href="@link" title="@title">more</a>', array('@link' => check_url($url), '@title' => $title)) .'</div>';
}

/**
 * Execute hook_footer() which is run at the end of the page right before the
 * close of the body tag.
 *
 * @param $main (optional)
 *   Whether the current page is the front page of the site.
 * @return
 *   A string containing the results of the hook_footer() calls.
 */
function theme_closure($main = 0) {
  $footer = module_invoke_all('footer', $main);
  return implode("\n", $footer) . drupal_get_js('footer');
}

/**
 * Return a set of blocks available for the current user.
 *
 * @param $region
 *   Which set of blocks to retrieve.
 * @return
 *   A string containing the themed blocks for this region.
 */
function theme_blocks($region) {
  $output = '';

  if ($list = block_list($region)) {
    foreach ($list as $key => $block) {
      // $key == <i>module</i>_<i>delta</i>
      $output .= theme('block', $block);
    }
  }

  // Add any content assigned to this region through drupal_set_content() calls.
  $output .= drupal_get_content($region);

  return $output;
}

/**
 * Format a username.
 *
 * @param $object
 *   The user object to format, usually returned from user_load().
 * @return
 *   A string containing an HTML link to the user's page if the passed object
 *   suggests that this is a site user. Otherwise, only the username is returned.
 */
function theme_username($object) {

  if ($object->uid && $object->name) {
    // Shorten the name when it is too long or it will break many tables.
    if (drupal_strlen($object->name) > 20) {
      $name = drupal_substr($object->name, 0, 15) .'...';
    }
    else {
      $name = $object->name;
    }

    if (user_access('access user profiles')) {
      $output = l($name, 'user/'. $object->uid, array('attributes' => array('title' => t('View user profile.'))));
    }
    else {
      $output = check_plain($name);
    }
  }
  else if ($object->name) {
    // Sometimes modules display content composed by people who are
    // not registered members of the site (e.g. mailing list or news
    // aggregator modules). This clause enables modules to display
    // the true author of the content.
    if (!empty($object->homepage)) {
      $output = l($object->name, $object->homepage, array('attributes' => array('rel' => 'nofollow')));
    }
    else {
      $output = check_plain($object->name);
    }

    $output .= ' ('. t('not verified') .')';
  }
  else {
    $output = check_plain(variable_get('anonymous', t('Anonymous')));
  }

  return $output;
}

/**
 * Return a themed progress bar.
 *
 * @param $percent
 *   The percentage of the progress.
 * @param $message
 *   A string containing information to be displayed.
 * @return
 *   A themed HTML string representing the progress bar.
 */
function theme_progress_bar($percent, $message) {
  $output = '<div id="progress" class="progress">';
  $output .= '<div class="bar"><div class="filled" style="width: '. $percent .'%"></div></div>';
  $output .= '<div class="percentage">'. $percent .'%</div>';
  $output .= '<div class="message">'. $message .'</div>';
  $output .= '</div>';

  return $output;
}

/**
 * Create a standard indentation div. Used for drag and drop tables.
 *
 * @param $size
 *   Optional. The number of indentations to create.
 * @return
 *   A string containing indentations.
 */
function theme_indentation($size = 1) {
  $output = '';
  for ($n = 0; $n < $size; $n++) {
    $output .= '<div class="indentation">&nbsp;</div>';
  }
  return $output;
}

/**
 * @} End of "defgroup themeable".
 */

function _theme_table_cell($cell, $header = FALSE) {
  $attributes = '';

  if (is_array($cell)) {
    $data = isset($cell['data']) ? $cell['data'] : '';
    $header |= isset($cell['header']);
    unset($cell['data']);
    unset($cell['header']);
    $attributes = drupal_attributes($cell);
  }
  else {
    $data = $cell;
  }

  if ($header) {
    $output = "<th$attributes>$data</th>";
  }
  else {
    $output = "<td$attributes>$data</td>";
  }

  return $output;
}

/**
 * Adds a default set of helper variables for preprocess functions and
 * templates. This comes in before any other preprocess function which makes
 * it possible to be used in default theme implementations (non-overriden
 * theme functions).
 */
function template_preprocess(&$variables, $hook) {
  global $user;
  static $count = array();

  // Track run count for each hook to provide zebra striping.
  // See "template_preprocess_block()" which provides the same feature specific to blocks.
  $count[$hook] = isset($count[$hook]) && is_int($count[$hook]) ? $count[$hook] : 1;
  $variables['zebra'] = ($count[$hook] % 2) ? 'odd' : 'even';
  $variables['id'] = $count[$hook]++;

  // Tell all templates where they are located.
  $variables['directory'] = path_to_theme();

  // Set default variables that depend on the database.
  $variables['is_admin']            = FALSE;
  $variables['is_front']            = FALSE;
  $variables['logged_in']           = FALSE;
  if ($variables['db_is_active'] = db_is_active()  && !defined('MAINTENANCE_MODE')) {
    // Check for administrators.
    if (user_access('access administration pages')) {
      $variables['is_admin'] = TRUE;
    }
    // Flag front page status.
    $variables['is_front'] = drupal_is_front_page();
    // Tell all templates by which kind of user they're viewed.
    $variables['logged_in'] = ($user->uid > 0);
    // Provide user object to all templates
    $variables['user'] = $user;
  }
}

/**
 * Process variables for page.tpl.php
 *
 * Most themes utilize their own copy of page.tpl.php. The default is located
 * inside "modules/system/page.tpl.php". Look in there for the full list of
 * variables.
 *
 * Uses the arg() function to generate a series of page template suggestions
 * based on the current path.
 *
 * Any changes to variables in this preprocessor should also be changed inside
 * template_preprocess_maintenance_page() to keep all them consistent.
 *
 * The $variables array contains the following arguments:
 * - $content
 * - $show_blocks
 *
 * @see page.tpl.php
 */
function template_preprocess_page(&$variables) {
  // Add favicon
  if (theme_get_setting('toggle_favicon')) {
    drupal_set_html_head('<link rel="shortcut icon" href="'. check_url(theme_get_setting('favicon')) .'" type="image/x-icon" />');
  }

  global $theme;
  // Populate all block regions.
  $regions = system_region_list($theme);
  // Load all region content assigned via blocks.
  foreach (array_keys($regions) as $region) {
    // Prevent left and right regions from rendering blocks when 'show_blocks' == FALSE.
    if (!(!$variables['show_blocks'] && ($region == 'left' || $region == 'right'))) {
      $blocks = theme('blocks', $region);
    }
    else {
      $blocks = '';
    }
    // Assign region to a region variable.
    isset($variables[$region]) ? $variables[$region] .= $blocks : $variables[$region] = $blocks;
  }

  // Set up layout variable.
  $variables['layout'] = 'none';
  if (!empty($variables['left'])) {
    $variables['layout'] = 'left';
  }
  if (!empty($variables['right'])) {
    $variables['layout'] = ($variables['layout'] == 'left') ? 'both' : 'right';
  }

  // Set mission when viewing the frontpage.
  if (drupal_is_front_page()) {
    $mission = filter_xss_admin(theme_get_setting('mission'));
  }

  // Construct page title
  if (drupal_get_title()) {
    $head_title = array(strip_tags(drupal_get_title()), variable_get('site_name', 'Drupal'));
  }
  else {
    $head_title = array(variable_get('site_name', 'Drupal'));
    if (variable_get('site_slogan', '')) {
      $head_title[] = variable_get('site_slogan', '');
    }
  }
  $variables['head_title']        = implode(' | ', $head_title);
  $variables['base_path']         = base_path();
  $variables['front_page']        = url();
  $variables['breadcrumb']        = theme('breadcrumb', drupal_get_breadcrumb());
  $variables['feed_icons']        = drupal_get_feeds();
  $variables['footer_message']    = filter_xss_admin(variable_get('site_footer', FALSE));
  $variables['head']              = drupal_get_html_head();
  $variables['help']              = theme('help');
  $variables['language']          = $GLOBALS['language'];
  $variables['language']->dir     = $GLOBALS['language']->direction ? 'rtl' : 'ltr';
  $variables['logo']              = theme_get_setting('logo');
  $variables['messages']          = $variables['show_messages'] ? theme('status_messages') : '';
  $variables['mission']           = isset($mission) ? $mission : '';
  $variables['primary_links']     = theme_get_setting('toggle_primary_links') ? menu_primary_links() : array();
  $variables['secondary_links']   = theme_get_setting('toggle_secondary_links') ? menu_secondary_links() : array();
  $variables['search_box']        = (theme_get_setting('toggle_search') ? drupal_get_form('search_theme_form') : '');
  $variables['site_name']         = (theme_get_setting('toggle_name') ? filter_xss_admin(variable_get('site_name', 'Drupal')) : '');
  $variables['site_slogan']       = (theme_get_setting('toggle_slogan') ? filter_xss_admin(variable_get('site_slogan', '')) : '');
  $variables['css']               = drupal_add_css();
  $variables['styles']            = drupal_get_css();
  $variables['scripts']           = drupal_get_js();
  $variables['tabs']              = theme('menu_local_tasks');
  $variables['title']             = drupal_get_title();
  // Closure should be filled last.
  $variables['closure']           = theme('closure');

  if ($node = menu_get_object()) {
    $variables['node'] = $node;
  }

  // Compile a list of classes that are going to be applied to the body element.
  // This allows advanced theming based on context (home page, node of certain type, etc.).
  $body_classes = array();
  // Add a class that tells us whether we're on the front page or not.
  $body_classes[] = $variables['is_front'] ? 'front' : 'not-front';
  // Add a class that tells us whether the page is viewed by an authenticated user or not.
  $body_classes[] = $variables['logged_in'] ? 'logged-in' : 'not-logged-in';
  // Add arg(0) to make it possible to theme the page depending on the current page
  // type (e.g. node, admin, user, etc.). To avoid illegal characters in the class,
  // we're removing everything disallowed. We are not using 'a-z' as that might leave
  // in certain international characters (e.g. German umlauts).
  $body_classes[] = preg_replace('![^abcdefghijklmnopqrstuvwxyz0-9-_]+!s', '', 'page-'. form_clean_id(drupal_strtolower(arg(0))));
  // If on an individual node page, add the node type.
  if (isset($variables['node']) && $variables['node']->type) {
    $body_classes[] = 'node-type-'. form_clean_id($variables['node']->type);
  }
  // Add information about the number of sidebars.
  if ($variables['layout'] == 'both') {
    $body_classes[] = 'two-sidebars';
  }
  elseif ($variables['layout'] == 'none') {
    $body_classes[] = 'no-sidebars';
  }
  else {
    $body_classes[] = 'one-sidebar sidebar-'. $variables['layout'];
  }
  // Implode with spaces.
  $variables['body_classes'] = implode(' ', $body_classes);

  // Build a list of suggested template files in order of specificity. One
  // suggestion is made for every element of the current path, though
  // numeric elements are not carried to subsequent suggestions. For example,
  // http://www.example.com/node/1/edit would result in the following
  // suggestions:
  //
  // page-node-edit.tpl.php
  // page-node-1.tpl.php
  // page-node.tpl.php
  // page.tpl.php
  $i = 0;
  $suggestion = 'page';
  $suggestions = array();
  while ($arg = arg($i++)) {
    $arg = str_replace(array("/", "\\", "\0"), '', $arg);
    $suggestions[] = $suggestion .'-'. $arg;
    if (!is_numeric($arg)) {
      $suggestion .= '-'. $arg;
    }
  }
  if (drupal_is_front_page()) {
    $suggestions[] = 'page-front';
  }

  if ($suggestions) {
    $variables['template_files'] = $suggestions;
  }
}

/**
 * Process variables for node.tpl.php
 *
 * Most themes utilize their own copy of node.tpl.php. The default is located
 * inside "modules/node/node.tpl.php". Look in there for the full list of
 * variables.
 *
 * The $variables array contains the following arguments:
 * - $node
 * - $teaser
 * - $page
 *
 * @see node.tpl.php
 */
function template_preprocess_node(&$variables) {
  $node = $variables['node'];
  if (module_exists('taxonomy')) {
    $variables['taxonomy'] = taxonomy_link('taxonomy terms', $node);
  }
  else {
    $variables['taxonomy'] = array();
  }

  if ($variables['teaser'] && $node->teaser) {
    $variables['content'] = $node->teaser;
  }
  elseif (isset($node->body)) {
    $variables['content'] = $node->body;
  }
  else {
    $variables['content'] = '';
  }

  $variables['date']      = format_date($node->created);
  $variables['links']     = !empty($node->links) ? theme('links', $node->links, array('class' => 'links inline')) : '';
  $variables['name']      = theme('username', $node);
  $variables['node_url']  = url('node/'. $node->nid);
  $variables['terms']     = theme('links', $variables['taxonomy'], array('class' => 'links inline'));
  $variables['title']     = check_plain($node->title);

  // Flatten the node object's member fields.
  $variables = array_merge((array)$node, $variables);

  // Display info only on certain node types.
  if (theme_get_setting('toggle_node_info_'. $node->type)) {
    $variables['submitted'] = theme('node_submitted', $node);
    $variables['picture'] = theme_get_setting('toggle_node_user_picture') ? theme('user_picture', $node) : '';
  }
  else {
    $variables['submitted'] = '';
    $variables['picture'] = '';
  }
  // Clean up name so there are no underscores.
  $variables['template_files'][] = 'node-'. $node->type;
}

/**
 * Process variables for block.tpl.php
 *
 * Prepare the values passed to the theme_block function to be passed
 * into a pluggable template engine. Uses block properties to generate a
 * series of template file suggestions. If none are found, the default
 * block.tpl.php is used.
 *
 * Most themes utilize their own copy of block.tpl.php. The default is located
 * inside "modules/system/block.tpl.php". Look in there for the full list of
 * variables.
 *
 * The $variables array contains the following arguments:
 * - $block
 *
 * @see block.tpl.php
 */
function template_preprocess_block(&$variables) {
  static $block_counter = array();
  // All blocks get an independent counter for each region.
  if (!isset($block_counter[$variables['block']->region])) {
    $block_counter[$variables['block']->region] = 1;
  }
  // Same with zebra striping.
  $variables['block_zebra'] = ($block_counter[$variables['block']->region] % 2) ? 'odd' : 'even';
  $variables['block_id'] = $block_counter[$variables['block']->region]++;

  $variables['template_files'][] = 'block-'. $variables['block']->region;
  $variables['template_files'][] = 'block-'. $variables['block']->module;
  $variables['template_files'][] = 'block-'. $variables['block']->module .'-'. $variables['block']->delta;
}

