<?php

/**
 * @file
 * The theme system, which controls the output of Drupal.
 *
 * The theme system allows for nearly all output of the Drupal system to be
 * customized by user themes.
 */

use Drupal\Core\Utility\ThemeRegistry;

/**
 * @defgroup content_flags Content markers
 * @{
 * Markers used by theme_mark() and node_mark() to designate content.
 * @see theme_mark(), node_mark()
 */

/**
 * Mark content as read.
 */
const MARK_READ = 0;

/**
 * Mark content as being new.
 */
const MARK_NEW = 1;

/**
 * Mark content as being updated.
 */
const MARK_UPDATED = 2;

/**
 * @} End of "defgroup content_flags".
 */

/**
 * Determines if a theme is available to use.
 *
 * @param $theme
 *   Either the name of a theme or a full theme object.
 *
 * @return
 *   Boolean TRUE if the theme is enabled or is the site administration theme;
 *   FALSE otherwise.
 */
function drupal_theme_access($theme) {
  if (is_object($theme)) {
    return _drupal_theme_access($theme);
  }
  else {
    $themes = list_themes();
    return isset($themes[$theme]) && _drupal_theme_access($themes[$theme]);
  }
}

/**
 * Helper function for determining access to a theme.
 *
 * @see drupal_theme_access()
 */
function _drupal_theme_access($theme) {
  $admin_theme = variable_get('admin_theme');
  return !empty($theme->status) || ($admin_theme && $theme->name == $admin_theme);
}

/**
 * Initialize the theme system by loading the theme.
 */
function drupal_theme_initialize() {
  global $theme, $user, $theme_key;

  // If $theme is already set, assume the others are set, too, and do nothing
  if (isset($theme)) {
    return;
  }

  drupal_bootstrap(DRUPAL_BOOTSTRAP_DATABASE);
  $themes = list_themes();

  // Only select the user selected theme if it is available in the
  // list of themes that can be accessed.
  $theme = !empty($user->theme) && drupal_theme_access($user->theme) ? $user->theme : variable_get('theme_default', 'stark');

  // Allow modules to override the theme. Validation has already been performed
  // inside menu_get_custom_theme(), so we do not need to check it again here.
  $custom_theme = menu_get_custom_theme();
  $theme = !empty($custom_theme) ? $custom_theme : $theme;

  // Store the identifier for retrieving theme settings with.
  $theme_key = $theme;

  // Find all our ancestor themes and put them in an array.
  $base_theme = array();
  $ancestor = $theme;
  while ($ancestor && isset($themes[$ancestor]->base_theme)) {
    $ancestor = $themes[$ancestor]->base_theme;
    $base_theme[] = $themes[$ancestor];
  }
  _drupal_theme_initialize($themes[$theme], array_reverse($base_theme));

  // Themes can have alter functions, so reset the drupal_alter() cache.
  drupal_static_reset('drupal_alter');

  // Provide the page with information about the theme that's used, so that a
  // later Ajax request can be rendered using the same theme.
  // @see ajax_base_page_theme()
  $setting['ajaxPageState'] = array(
    'theme' => $theme_key,
    'theme_token' => drupal_get_token($theme_key),
  );
  drupal_add_js($setting, 'setting');
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
function _drupal_theme_initialize($theme, $base_theme = array(), $registry_callback = '_theme_load_registry') {
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
      drupal_add_css($stylesheet, array('group' => CSS_THEME, 'every_page' => TRUE, 'media' => $media));
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
    drupal_add_js($script, array('group' => JS_THEME, 'every_page' => TRUE));
  }

  $theme_engine = NULL;

  // Initialize the theme.
  if (isset($theme->engine)) {
    // Include the engine.
    include_once DRUPAL_ROOT . '/' . $theme->owner;

    $theme_engine = $theme->engine;
    if (function_exists($theme_engine . '_init')) {
      foreach ($base_theme as $base) {
        call_user_func($theme_engine . '_init', $base);
      }
      call_user_func($theme_engine . '_init', $theme);
    }
  }
  else {
    // include non-engine theme files
    foreach ($base_theme as $base) {
      // Include the theme file or the engine.
      if (!empty($base->owner)) {
        include_once DRUPAL_ROOT . '/' . $base->owner;
      }
    }
    // and our theme gets one too.
    if (!empty($theme->owner)) {
      include_once DRUPAL_ROOT . '/' . $theme->owner;
    }
  }

  if (isset($registry_callback)) {
    _theme_registry_callback($registry_callback, array($theme, $base_theme, $theme_engine));
  }
}

/**
 * Get the theme registry.
 *
 * @param bool $complete
 *   Optional boolean to indicate whether to return the complete theme registry
 *   array or an instance of the Drupal\Core\Utility\ThemeRegistry class.
 *   If TRUE, the complete theme registry array will be returned. This is useful
 *   if you want to foreach over the whole registry, use array_* functions or
 *   inspect it in a debugger. If FALSE, an instance of the
 *   Drupal\Core\Utility\ThemeRegistry class will be returned, this provides an
 *   ArrayObject which allows it to be accessed with array syntax and isset(),
 *   and should be more lightweight than the full registry. Defaults to TRUE.
 *
 * @return
 *   The complete theme registry array, or an instance of the
 *   Drupal\Core\Utility\ThemeRegistry class.
 */
function theme_get_registry($complete = TRUE) {
  // Use the advanced drupal_static() pattern, since this is called very often.
  static $drupal_static_fast;
  if (!isset($drupal_static_fast)) {
    $drupal_static_fast['registry'] = &drupal_static('theme_get_registry');
  }
  $theme_registry = &$drupal_static_fast['registry'];

  // Initialize the theme, if this is called early in the bootstrap, or after
  // static variables have been reset.
  if (!is_array($theme_registry)) {
    drupal_theme_initialize();
    $theme_registry = array();
  }

  $key = (int) $complete;

  if (!isset($theme_registry[$key])) {
    list($callback, $arguments) = _theme_registry_callback();
    if (!$complete) {
      $arguments[] = FALSE;
    }
    $theme_registry[$key] = call_user_func_array($callback, $arguments);
  }

  return $theme_registry[$key];
}

/**
 * Set the callback that will be used by theme_get_registry() to fetch the registry.
 *
 * @param $callback
 *   The name of the callback function.
 * @param $arguments
 *   The arguments to pass to the function.
 */
function _theme_registry_callback($callback = NULL, array $arguments = array()) {
  static $stored;
  if (isset($callback)) {
    $stored = array($callback, $arguments);
  }
  return $stored;
}

/**
 * Get the theme_registry cache; if it doesn't exist, build it.
 *
 * @param $theme
 *   The loaded $theme object as returned by list_themes().
 * @param $base_theme
 *   An array of loaded $theme objects representing the ancestor themes in
 *   oldest first order.
 * @param $theme_engine
 *   The name of the theme engine.
 * @param $complete
 *   Whether to load the complete theme registry or an instance of the
 *   Drupal\Core\Utility\ThemeRegistry class.
 *
 * @return
 *   The theme registry array, or an instance of the
 *   Drupal\Core\Utility\ThemeRegistry class.
 */
function _theme_load_registry($theme, $base_theme = NULL, $theme_engine = NULL, $complete = TRUE) {
  if ($complete) {
    // Check the theme registry cache; if it exists, use it.
    $cached = cache()->get("theme_registry:$theme->name");
    if (isset($cached->data)) {
      $registry = $cached->data;
    }
    else {
      // If not, build one and cache it.
      $registry = _theme_build_registry($theme, $base_theme, $theme_engine);
      // Only persist this registry if all modules are loaded. This assures a
      // complete set of theme hooks.
      if (module_load_all(NULL)) {
        _theme_save_registry($theme, $registry);
      }
    }
    return $registry;
  }
  else {
    return new ThemeRegistry('theme_registry:runtime:' . $theme->name, 'cache');
  }
}

/**
 * Write the theme_registry cache into the database.
 */
function _theme_save_registry($theme, $registry) {
  cache()->set("theme_registry:$theme->name", $registry);
}

/**
 * Force the system to rebuild the theme registry; this should be called
 * when modules are added to the system, or when a dynamic system needs
 * to add more theme hooks.
 */
function drupal_theme_rebuild() {
  drupal_static_reset('theme_get_registry');
  cache()->deletePrefix('theme_registry');
}

/**
 * Process a single implementation of hook_theme().
 *
 * @param $cache
 *   The theme registry that will eventually be cached; It is an associative
 *   array keyed by theme hooks, whose values are associative arrays describing
 *   the hook:
 *   - 'type': The passed-in $type.
 *   - 'theme path': The passed-in $path.
 *   - 'function': The name of the function generating output for this theme
 *     hook. Either defined explicitly in hook_theme() or, if neither 'function'
 *     nor 'template' is defined, then the default theme function name is used.
 *     The default theme function name is the theme hook prefixed by either
 *     'theme_' for modules or '$name_' for everything else. If 'function' is
 *     defined, 'template' is not used.
 *   - 'template': The filename of the template generating output for this
 *     theme hook. The template is in the directory defined by the 'path' key of
 *     hook_theme() or defaults to $path.
 *   - 'variables': The variables for this theme hook as defined in
 *     hook_theme(). If there is more than one implementation and 'variables' is
 *     not specified in a later one, then the previous definition is kept.
 *   - 'render element': The renderable element for this theme hook as defined
 *     in hook_theme(). If there is more than one implementation and
 *     'render element' is not specified in a later one, then the previous
 *     definition is kept.
 *   - 'preprocess functions': See theme() for detailed documentation.
 *   - 'process functions': See theme() for detailed documentation.
 * @param $name
 *   The name of the module, theme engine, base theme engine, theme or base
 *   theme implementing hook_theme().
 * @param $type
 *   One of 'module', 'theme_engine', 'base_theme_engine', 'theme', or
 *   'base_theme'. Unlike regular hooks that can only be implemented by modules,
 *   each of these can implement hook_theme(). _theme_process_registry() is
 *   called in aforementioned order and new entries override older ones. For
 *   example, if a theme hook is both defined by a module and a theme, then the
 *   definition in the theme will be used.
 * @param $theme
 *   The loaded $theme object as returned from list_themes().
 * @param $path
 *   The directory where $name is. For example, modules/system or
 *   themes/bartik.
 *
 * @see theme()
 * @see _theme_process_registry()
 * @see hook_theme()
 * @see list_themes()
 */
function _theme_process_registry(&$cache, $name, $type, $theme, $path) {
  $result = array();

  // Processor functions work in two distinct phases with the process
  // functions always being executed after the preprocess functions.
  $variable_process_phases = array(
    'preprocess functions' => 'preprocess',
    'process functions'    => 'process',
  );

  $hook_defaults = array(
    'variables' => TRUE,
    'render element' => TRUE,
    'pattern' => TRUE,
    'base hook' => TRUE,
  );

  // Invoke the hook_theme() implementation, process what is returned, and
  // merge it into $cache.
  $function = $name . '_theme';
  if (function_exists($function)) {
    $result = $function($cache, $type, $theme, $path);
    foreach ($result as $hook => $info) {
      // When a theme or engine overrides a module's theme function
      // $result[$hook] will only contain key/value pairs for information being
      // overridden.  Pull the rest of the information from what was defined by
      // an earlier hook.

      // Fill in the type and path of the module, theme, or engine that
      // implements this theme function.
      $result[$hook]['type'] = $type;
      $result[$hook]['theme path'] = $path;

      // If function and file are omitted, default to standard naming
      // conventions.
      if (!isset($info['template']) && !isset($info['function'])) {
        $result[$hook]['function'] = ($type == 'module' ? 'theme_' : $name . '_') . $hook;
      }

      if (isset($cache[$hook]['includes'])) {
        $result[$hook]['includes'] = $cache[$hook]['includes'];
      }

      // If the theme implementation defines a file, then also use the path
      // that it defined. Otherwise use the default path. This allows
      // system.module to declare theme functions on behalf of core .include
      // files.
      if (isset($info['file'])) {
        $include_file = isset($info['path']) ? $info['path'] : $path;
        $include_file .= '/' . $info['file'];
        include_once DRUPAL_ROOT . '/' . $include_file;
        $result[$hook]['includes'][] = $include_file;
      }

      // If the default keys are not set, use the default values registered
      // by the module.
      if (isset($cache[$hook])) {
        $result[$hook] += array_intersect_key($cache[$hook], $hook_defaults);
      }

      // The following apply only to theming hooks implemented as templates.
      if (isset($info['template'])) {
        // Prepend the current theming path when none is set.
        if (!isset($info['path'])) {
          $result[$hook]['template'] = $path . '/' . $info['template'];
        }
      }

      // Allow variable processors for all theming hooks, whether the hook is
      // implemented as a template or as a function.
      foreach ($variable_process_phases as $phase_key => $phase) {
        // Check for existing variable processors. Ensure arrayness.
        if (!isset($info[$phase_key]) || !is_array($info[$phase_key])) {
          $info[$phase_key] = array();
          $prefixes = array();
          if ($type == 'module') {
            // Default variable processor prefix.
            $prefixes[] = 'template';
            // Add all modules so they can intervene with their own variable
            // processors. This allows them to provide variable processors even
            // if they are not the owner of the current hook.
            $prefixes += module_list();
          }
          elseif ($type == 'theme_engine' || $type == 'base_theme_engine') {
            // Theme engines get an extra set that come before the normally
            // named variable processors.
            $prefixes[] = $name . '_engine';
            // The theme engine registers on behalf of the theme using the
            // theme's name.
            $prefixes[] = $theme;
          }
          else {
            // This applies when the theme manually registers their own variable
            // processors.
            $prefixes[] = $name;
          }
          foreach ($prefixes as $prefix) {
            // Only use non-hook-specific variable processors for theming hooks
            // implemented as templates. See theme().
            if (isset($info['template']) && function_exists($prefix . '_' . $phase)) {
              $info[$phase_key][] = $prefix . '_' . $phase;
            }
            if (function_exists($prefix . '_' . $phase . '_' . $hook)) {
              $info[$phase_key][] = $prefix . '_' . $phase . '_' . $hook;
            }
          }
        }
        // Check for the override flag and prevent the cached variable
        // processors from being used. This allows themes or theme engines to
        // remove variable processors set earlier in the registry build.
        if (!empty($info['override ' . $phase_key])) {
          // Flag not needed inside the registry.
          unset($result[$hook]['override ' . $phase_key]);
        }
        elseif (isset($cache[$hook][$phase_key]) && is_array($cache[$hook][$phase_key])) {
          $info[$phase_key] = array_merge($cache[$hook][$phase_key], $info[$phase_key]);
        }
        $result[$hook][$phase_key] = $info[$phase_key];
      }
    }

    // Merge the newly created theme hooks into the existing cache.
    $cache = $result + $cache;
  }

  // Let themes have variable processors even if they didn't register a template.
  if ($type == 'theme' || $type == 'base_theme') {
    foreach ($cache as $hook => $info) {
      // Check only if not registered by the theme or engine.
      if (empty($result[$hook])) {
        foreach ($variable_process_phases as $phase_key => $phase) {
          if (!isset($info[$phase_key])) {
            $cache[$hook][$phase_key] = array();
          }
          // Only use non-hook-specific variable processors for theming hooks
          // implemented as templates. See theme().
          if (isset($info['template']) && function_exists($name . '_' . $phase)) {
            $cache[$hook][$phase_key][] = $name . '_' . $phase;
          }
          if (function_exists($name . '_' . $phase . '_' . $hook)) {
            $cache[$hook][$phase_key][] = $name . '_' . $phase . '_' . $hook;
            $cache[$hook]['theme path'] = $path;
          }
          // Ensure uniqueness.
          $cache[$hook][$phase_key] = array_unique($cache[$hook][$phase_key]);
        }
      }
    }
  }
}

/**
 * Build the theme registry cache.
 *
 * @param $theme
 *   The loaded $theme object as returned by list_themes().
 * @param $base_theme
 *   An array of loaded $theme objects representing the ancestor themes in
 *   oldest first order.
 * @param $theme_engine
 *   The name of the theme engine.
 */
function _theme_build_registry($theme, $base_theme, $theme_engine) {
  $cache = array();
  // First, process the theme hooks advertised by modules. This will
  // serve as the basic registry. Since the list of enabled modules is the same
  // regardless of the theme used, this is cached in its own entry to save
  // building it for every theme.
  if ($cached = cache()->get('theme_registry:build:modules')) {
    $cache = $cached->data;
  }
  else {
    foreach (module_implements('theme') as $module) {
      _theme_process_registry($cache, $module, 'module', $module, drupal_get_path('module', $module));
    }
    // Only cache this registry if all modules are loaded.
    if (module_load_all(NULL)) {
      cache()->set('theme_registry:build:modules', $cache);
    }
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

  // Let modules alter the registry.
  drupal_alter('theme_registry', $cache);

  // Optimize the registry to not have empty arrays for functions.
  foreach ($cache as $hook => $info) {
    foreach (array('preprocess functions', 'process functions') as $phase) {
      if (empty($info[$phase])) {
        unset($cache[$hook][$phase]);
      }
    }
  }
  return $cache;
}

/**
 * Return a list of all currently available themes.
 *
 * Retrieved from the database, if available and the site is not in maintenance
 * mode; otherwise compiled freshly from the filesystem.
 *
 * @param $refresh
 *   Whether to reload the list of themes from the database. Defaults to FALSE.
 *
 * @return
 *   An associative array of the currently available themes. The keys are the
 *   names of the themes and the values are objects having the following
 *   properties:
 *   - 'filename': The name of the .info file.
 *   - 'name': The machine name of the theme.
 *   - 'status': 1 for enabled, 0 for disabled themes.
 *   - 'info': The contents of the .info file.
 *   - 'stylesheets': A two dimensional array, using the first key for the
 *     'media' attribute (e.g. 'all'), the second for the name of the file
 *     (e.g. style.css). The value is a complete filepath
 *     (e.g. themes/bartik/style.css).
 *   - 'scripts': An associative array of JavaScripts, using the filename as key
 *     and the complete filepath as value.
 *   - 'engine': The name of the theme engine.
 *   - 'base_theme': The name of the base theme.
 *   - 'base_themes': An ordered array of all the base themes. If the first item
 *     is NULL, a base theme is missing for this theme.
 *   - 'sub_themes': An unordered array of sub-themes of this theme.
 */
function list_themes($refresh = FALSE) {
  $list = &drupal_static(__FUNCTION__, array());

  if ($refresh) {
    $list = array();
    system_list_reset();
  }

  if (empty($list)) {
    $list = array();
    $themes = array();
    // Extract from the database only when it is available.
    // Also check that the site is not in the middle of an install or update.
    if (!defined('MAINTENANCE_MODE')) {
      try {
        $themes = system_list('theme');
      }
      catch (Exception $e) {
        // If the database is not available, rebuild the theme data.
        $themes = _system_rebuild_theme_data();
      }
    }
    else {
      // Scan the installation when the database should not be read.
      $themes = _system_rebuild_theme_data();
    }

    foreach ($themes as $theme) {
      foreach ($theme->info['stylesheets'] as $media => $stylesheets) {
        foreach ($stylesheets as $stylesheet => $path) {
          $theme->stylesheets[$media][$stylesheet] = $path;
        }
      }
      foreach ($theme->info['scripts'] as $script => $path) {
        $theme->scripts[$script] = $path;
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
 * Find all the base themes for the specified theme.
 *
 * Themes can inherit templates and function implementations from earlier themes.
 *
 * @param $themes
 *   An array of available themes.
 * @param $key
 *   The name of the theme whose base we are looking for.
 * @param $used_keys
 *   A recursion parameter preventing endless loops.
 * @return
 *   Returns an array of all of the theme's ancestors; the first element's value
 *   will be NULL if an error occurred.
 */
function drupal_find_base_themes($themes, $key, $used_keys = array()) {
  $base_key = $themes[$key]->info['base theme'];
  // Does the base theme exist?
  if (!isset($themes[$base_key])) {
    return array($base_key => NULL);
  }

  $current_base_theme = array($base_key => $themes[$base_key]->info['name']);

  // Is the base theme itself a child of another theme?
  if (isset($themes[$base_key]->info['base theme'])) {
    // Do we already know the base themes of this theme?
    if (isset($themes[$base_key]->base_themes)) {
      return $themes[$base_key]->base_themes + $current_base_theme;
    }
    // Prevent loops.
    if (!empty($used_keys[$base_key])) {
      return array($base_key => NULL);
    }
    $used_keys[$base_key] = TRUE;
    return drupal_find_base_themes($themes, $base_key, $used_keys) + $current_base_theme;
  }
  // If we get here, then this is our parent theme.
  return $current_base_theme;
}

/**
 * Generates themed output.
 *
 * All requests for themed output must go through this function. It examines
 * the request and routes it to the appropriate
 * @link themeable theme function or template @endlink, by checking the theme
 * registry.
 *
 * The first argument to this function is the name of the theme hook. For
 * instance, to theme a table, the theme hook name is 'table'. By default, this
 * theme hook could be implemented by a function called 'theme_table' or a
 * template file called 'table.tpl.php', but hook_theme() can override the
 * default function or template name.
 *
 * If the implementation is a template file, several functions are called
 * before the template file is invoked, to modify the $variables array. These
 * fall into the "preprocessing" phase and the "processing" phase, and are
 * executed (if they exist), in the following order (note that in the following
 * list, HOOK indicates the theme hook name, MODULE indicates a module name,
 * THEME indicates a theme name, and ENGINE indicates a theme engine name):
 * - template_preprocess(&$variables, $hook): Creates a default set of variables
 *   for all theme hooks.
 * - template_preprocess_HOOK(&$variables): Should be implemented by
 *   the module that registers the theme hook, to set up default variables.
 * - MODULE_preprocess(&$variables, $hook): hook_preprocess() is invoked on all
 *   implementing modules.
 * - MODULE_preprocess_HOOK(&$variables): hook_preprocess_HOOK() is invoked on
 *   all implementing modules, so that modules that didn't define the theme hook
 *   can alter the variables.
 * - ENGINE_engine_preprocess(&$variables, $hook): Allows the theme engine to
 *   set necessary variables for all theme hooks.
 * - ENGINE_engine_preprocess_HOOK(&$variables): Allows the theme engine to set
 *   necessary variables for the particular theme hook.
 * - THEME_preprocess(&$variables, $hook): Allows the theme to set necessary
 *   variables for all theme hooks.
 * - THEME_preprocess_HOOK(&$variables): Allows the theme to set necessary
 *   variables specific to the particular theme hook.
 * - template_process(&$variables, $hook): Creates a default set of variables
 *   for all theme hooks.
 * - template_process_HOOK(&$variables): This is the first processor specific
 *   to the theme hook; it should be implemented by the module that registers
 *   it.
 * - MODULE_process(&$variables, $hook): hook_process() is invoked on all
 *   implementing modules.
 * - MODULE_process_HOOK(&$variables): hook_process_HOOK() is invoked on
 *   on all implementing modules, so that modules that didn't define the theme
 *   hook can alter the variables.
 * - ENGINE_engine_process(&$variables, $hook): Allows the theme engine to set
 *   necessary variables for all theme hooks.
 * - ENGINE_engine_process_HOOK(&$variables): Allows the theme engine to set
 *   necessary variables for the particular theme hook.
 * - ENGINE_process(&$variables, $hook): Allows the theme engine to process the
 *   variables.
 * - ENGINE_process_HOOK(&$variables): Allows the theme engine to process the
 *   variables specific to the theme hook.
 * - THEME_process(&$variables, $hook):  Allows the theme to process the
 *   variables.
 * - THEME_process_HOOK(&$variables):  Allows the theme to process the
 *   variables specific to the theme hook.
 *
 * If the implementation is a function, only the theme-hook-specific preprocess
 * and process functions (the ones ending in _HOOK) are called from the
 * list above. This is because theme hooks with function implementations
 * need to be fast, and calling the non-theme-hook-specific preprocess and
 * process functions for them would incur a noticeable performance penalty.
 *
 * There are two special variables that these preprocess and process functions
 * can set: 'theme_hook_suggestion' and 'theme_hook_suggestions'. These will be
 * merged together to form a list of 'suggested' alternate theme hooks to use,
 * in reverse order of priority. theme_hook_suggestion will always be a higher
 * priority than items in theme_hook_suggestions. theme() will use the
 * highest priority implementation that exists. If none exists, theme() will
 * use the implementation for the theme hook it was called with. These
 * suggestions are similar to and are used for similar reasons as calling
 * theme() with an array as the $hook parameter (see below). The difference
 * is whether the suggestions are determined by the code that calls theme() or
 * by a preprocess or process function.
 *
 * @param $hook
 *   The name of the theme hook to call. If the name contains a
 *   double-underscore ('__') and there isn't an implementation for the full
 *   name, the part before the '__' is checked. This allows a fallback to a more
 *   generic implementation. For example, if theme('links__node', ...) is
 *   called, but there is no implementation of that theme hook, then the 'links'
 *   implementation is used. This process is iterative, so if
 *   theme('links__contextual__node', ...) is called, theme() checks for the
 *   following implementations, and uses the first one that exists:
 *   - links__contextual__node
 *   - links__contextual
 *   - links
 *   This allows themes to create specific theme implementations for named
 *   objects and contexts of otherwise generic theme hooks. The $hook parameter
 *   may also be an array, in which case the first theme hook that has an
 *   implementation is used. This allows for the code that calls theme() to
 *   explicitly specify the fallback order in a situation where using the '__'
 *   convention is not desired or is insufficient.
 * @param $variables
 *   An associative array of variables to merge with defaults from the theme
 *   registry, pass to preprocess and process functions for modification, and
 *   finally, pass to the function or template implementing the theme hook.
 *   Alternatively, this can be a renderable array, in which case, its
 *   properties are mapped to variables expected by the theme hook
 *   implementations.
 *
 * @return
 *   An HTML string representing the themed output.
 *
 * @see themeable
 */
function theme($hook, $variables = array()) {
  // If called before all modules are loaded, we do not necessarily have a full
  // theme registry to work with, and therefore cannot process the theme
  // request properly. See also _theme_load_registry().
  if (!module_load_all(NULL) && !defined('MAINTENANCE_MODE')) {
    throw new Exception(t('theme() may not be called until all modules are loaded.'));
  }

  $hooks = theme_get_registry(FALSE);

  // If an array of hook candidates were passed, use the first one that has an
  // implementation.
  if (is_array($hook)) {
    foreach ($hook as $candidate) {
      if (isset($hooks[$candidate])) {
        break;
      }
    }
    $hook = $candidate;
  }

  // If there's no implementation, check for more generic fallbacks. If there's
  // still no implementation, log an error and return an empty string.
  if (!isset($hooks[$hook])) {
    // Iteratively strip everything after the last '__' delimiter, until an
    // implementation is found.
    while ($pos = strrpos($hook, '__')) {
      $hook = substr($hook, 0, $pos);
      if (isset($hooks[$hook])) {
        break;
      }
    }
    if (!isset($hooks[$hook])) {
      // Only log a message when not trying theme suggestions ($hook being an
      // array).
      if (!isset($candidate)) {
        watchdog('theme', 'Theme key "@key" not found.', array('@key' => $hook), WATCHDOG_WARNING);
      }
      return '';
    }
  }

  $info = $hooks[$hook];
  global $theme_path;
  $temp = $theme_path;
  // point path_to_theme() to the currently used theme path:
  $theme_path = $info['theme path'];

  // Include a file if the theme function or variable processor is held elsewhere.
  if (!empty($info['includes'])) {
    foreach ($info['includes'] as $include_file) {
      include_once DRUPAL_ROOT . '/' . $include_file;
    }
  }

  // If a renderable array is passed as $variables, then set $variables to
  // the arguments expected by the theme function.
  if (isset($variables['#theme']) || isset($variables['#theme_wrappers'])) {
    $element = $variables;
    $variables = array();
    if (isset($info['variables'])) {
      foreach (array_keys($info['variables']) as $name) {
        if (isset($element["#$name"])) {
          $variables[$name] = $element["#$name"];
        }
      }
    }
    else {
      $variables[$info['render element']] = $element;
    }
  }

  // Merge in argument defaults.
  if (!empty($info['variables'])) {
    $variables += $info['variables'];
  }
  elseif (!empty($info['render element'])) {
    $variables += array($info['render element'] => array());
  }

  // Invoke the variable processors, if any. The processors may specify
  // alternate suggestions for which hook's template/function to use. If the
  // hook is a suggestion of a base hook, invoke the variable processors of
  // the base hook, but retain the suggestion as a high priority suggestion to
  // be used unless overridden by a variable processor function.
  if (isset($info['base hook'])) {
    $base_hook = $info['base hook'];
    $base_hook_info = $hooks[$base_hook];
    // Include files required by the base hook, since its variable processors
    // might reside there.
    if (!empty($base_hook_info['includes'])) {
      foreach ($base_hook_info['includes'] as $include_file) {
        include_once DRUPAL_ROOT . '/' . $include_file;
      }
    }
    if (isset($base_hook_info['preprocess functions']) || isset($base_hook_info['process functions'])) {
      $variables['theme_hook_suggestion'] = $hook;
      $hook = $base_hook;
      $info = $base_hook_info;
    }
  }
  if (isset($info['preprocess functions']) || isset($info['process functions'])) {
    $variables['theme_hook_suggestions'] = array();
    foreach (array('preprocess functions', 'process functions') as $phase) {
      if (!empty($info[$phase])) {
        foreach ($info[$phase] as $processor_function) {
          if (function_exists($processor_function)) {
            // We don't want a poorly behaved process function changing $hook.
            $hook_clone = $hook;
            $processor_function($variables, $hook_clone);
          }
        }
      }
    }
    // If the preprocess/process functions specified hook suggestions, and the
    // suggestion exists in the theme registry, use it instead of the hook that
    // theme() was called with. This allows the preprocess/process step to
    // route to a more specific theme hook. For example, a function may call
    // theme('node', ...), but a preprocess function can add 'node__article' as
    // a suggestion, enabling a theme to have an alternate template file for
    // article nodes. Suggestions are checked in the following order:
    // - The 'theme_hook_suggestion' variable is checked first. It overrides
    //   all others.
    // - The 'theme_hook_suggestions' variable is checked in FILO order, so the
    //   last suggestion added to the array takes precedence over suggestions
    //   added earlier.
    $suggestions = array();
    if (!empty($variables['theme_hook_suggestions'])) {
      $suggestions = $variables['theme_hook_suggestions'];
    }
    if (!empty($variables['theme_hook_suggestion'])) {
      $suggestions[] = $variables['theme_hook_suggestion'];
    }
    foreach (array_reverse($suggestions) as $suggestion) {
      if (isset($hooks[$suggestion])) {
        $info = $hooks[$suggestion];
        break;
      }
    }
  }

  // Generate the output using either a function or a template.
  $output = '';
  if (isset($info['function'])) {
    if (function_exists($info['function'])) {
      $output = $info['function']($variables);
    }
  }
  else {
    // Default render function and extension.
    $render_function = 'theme_render_template';
    $extension = '.tpl.php';

    // The theme engine may use a different extension and a different renderer.
    global $theme_engine;
    if (isset($theme_engine)) {
      if ($info['type'] != 'module') {
        if (function_exists($theme_engine . '_render_template')) {
          $render_function = $theme_engine . '_render_template';
        }
        $extension_function = $theme_engine . '_extension';
        if (function_exists($extension_function)) {
          $extension = $extension_function();
        }
      }
    }

    // In some cases, a template implementation may not have had
    // template_preprocess() run (for example, if the default implementation is
    // a function, but a template overrides that default implementation). In
    // these cases, a template should still be able to expect to have access to
    // the variables provided by template_preprocess(), so we add them here if
    // they don't already exist. We don't want to run template_preprocess()
    // twice (it would be inefficient and mess up zebra striping), so we use the
    // 'directory' variable to determine if it has already run, which while not
    // completely intuitive, is reasonably safe, and allows us to save on the
    // overhead of adding some new variable to track that.
    if (!isset($variables['directory'])) {
      $default_template_variables = array();
      template_preprocess($default_template_variables, $hook);
      $variables += $default_template_variables;
    }

    // Render the output using the template file.
    $template_file = $info['template'] . $extension;
    if (isset($info['path'])) {
      $template_file = $info['path'] . '/' . $template_file;
    }
    $output = $render_function($template_file, $variables);
  }

  // restore path_to_theme()
  $theme_path = $temp;
  return $output;
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
    drupal_theme_initialize();
  }

  return $theme_path;
}

/**
 * Allow themes and/or theme engines to easily discover overridden theme functions.
 *
 * @param $cache
 *   The existing cache of theme hooks to test against.
 * @param $prefixes
 *   An array of prefixes to test, in reverse order of importance.
 *
 * @return $implementations
 *   The functions found, suitable for returning from hook_theme;
 */
function drupal_find_theme_functions($cache, $prefixes) {
  $implementations = array();
  $functions = get_defined_functions();

  foreach ($cache as $hook => $info) {
    foreach ($prefixes as $prefix) {
      // Find theme functions that implement possible "suggestion" variants of
      // registered theme hooks and add those as new registered theme hooks.
      // The 'pattern' key defines a common prefix that all suggestions must
      // start with. The default is the name of the hook followed by '__'. An
      // 'base hook' key is added to each entry made for a found suggestion,
      // so that common functionality can be implemented for all suggestions of
      // the same base hook. To keep things simple, deep hierarchy of
      // suggestions is not supported: each suggestion's 'base hook' key
      // refers to a base hook, not to another suggestion, and all suggestions
      // are found using the base hook's pattern, not a pattern from an
      // intermediary suggestion.
      $pattern = isset($info['pattern']) ? $info['pattern'] : ($hook . '__');
      if (!isset($info['base hook']) && !empty($pattern)) {
        $matches = preg_grep('/^' . $prefix . '_' . $pattern . '/', $functions['user']);
        if ($matches) {
          foreach ($matches as $match) {
            $new_hook = substr($match, strlen($prefix) + 1);
            $arg_name = isset($info['variables']) ? 'variables' : 'render element';
            $implementations[$new_hook] = array(
              'function' => $match,
              $arg_name => $info[$arg_name],
              'base hook' => $hook,
            );
          }
        }
      }
      // Find theme functions that implement registered theme hooks and include
      // that in what is returned so that the registry knows that the theme has
      // this implementation.
      if (function_exists($prefix . '_' . $hook)) {
        $implementations[$hook] = array(
          'function' => $prefix . '_' . $hook,
        );
      }
    }
  }

  return $implementations;
}

/**
 * Allow themes and/or theme engines to easily discover overridden templates.
 *
 * @param $cache
 *   The existing cache of theme hooks to test against.
 * @param $extension
 *   The extension that these templates will have.
 * @param $path
 *   The path to search.
 */
function drupal_find_theme_templates($cache, $extension, $path) {
  $implementations = array();

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
  $regex = '/' . str_replace('.', '\.', $extension) . '$/';
  // Get a listing of all template files in the path to search.
  $files = file_scan_directory($path, $regex, array('key' => 'name'));

  // Find templates that implement registered theme hooks and include that in
  // what is returned so that the registry knows that the theme has this
  // implementation.
  foreach ($files as $template => $file) {
    // Ignore sub-theme templates for the current theme.
    if (strpos($file->uri, str_replace($subtheme_paths, '', $file->uri)) !== 0) {
      continue;
    }
    // Chop off the remaining '.tpl' extension. $template already has the
    // rightmost extension removed, but there might still be more, such as with
    // .tpl.php, which still has .tpl in $template at this point.
    if (($pos = strpos($template, '.tpl')) !== FALSE) {
      $template = substr($template, 0, $pos);
    }
    // Transform - in filenames to _ to match function naming scheme
    // for the purposes of searching.
    $hook = strtr($template, '-', '_');
    if (isset($cache[$hook])) {
      $implementations[$hook] = array(
        'template' => $template,
        'path' => dirname($file->uri),
      );
    }

    // Match templates based on the 'template' filename.
    foreach ($cache as $hook => $info) {
      if (isset($info['template'])) {
        $template_candidates = array($info['template'], str_replace($info['theme path'] . '/', '', $info['template']));
        if (in_array($template, $template_candidates)) {
          $implementations[$hook] = array(
            'template' => $template,
            'path' => dirname($file->uri),
          );
        }
      }
    }
  }

  // Find templates that implement possible "suggestion" variants of registered
  // theme hooks and add those as new registered theme hooks. See
  // drupal_find_theme_functions() for more information about suggestions and
  // the use of 'pattern' and 'base hook'.
  $patterns = array_keys($files);
  foreach ($cache as $hook => $info) {
    $pattern = isset($info['pattern']) ? $info['pattern'] : ($hook . '__');
    if (!isset($info['base hook']) && !empty($pattern)) {
      // Transform _ in pattern to - to match file naming scheme
      // for the purposes of searching.
      $pattern = strtr($pattern, '_', '-');

      $matches = preg_grep('/^' . $pattern . '/', $patterns);
      if ($matches) {
        foreach ($matches as $match) {
          $file = substr($match, 0, strpos($match, '.'));
          // Put the underscores back in for the hook name and register this pattern.
          $arg_name = isset($info['variables']) ? 'variables' : 'render element';
          $implementations[strtr($file, '-', '_')] = array(
            'template' => $file,
            'path' => dirname($files[$match]->uri),
            $arg_name => $info[$arg_name],
            'base hook' => $hook,
          );
        }
      }
    }
  }
  return $implementations;
}

/**
 * Retrieve a setting for the current theme or for a given theme.
 *
 * The final setting is obtained from the last value found in the following
 * sources:
 * - the default global settings specified in this function
 * - the default theme-specific settings defined in any base theme's .info file
 * - the default theme-specific settings defined in the theme's .info file
 * - the saved values from the global theme settings form
 * - the saved values from the theme's settings form
 * To only retrieve the default global theme setting, an empty string should be
 * given for $theme.
 *
 * @param $setting_name
 *   The name of the setting to be retrieved.
 * @param $theme
 *   The name of a given theme; defaults to the current theme.
 *
 * @return
 *   The value of the requested setting, NULL if the setting does not exist.
 */
function theme_get_setting($setting_name, $theme = NULL) {
  $cache = &drupal_static(__FUNCTION__, array());

  // If no key is given, use the current theme if we can determine it.
  if (!isset($theme)) {
    $theme = !empty($GLOBALS['theme_key']) ? $GLOBALS['theme_key'] : '';
  }

  if (empty($cache[$theme])) {
    // Set the default values for each global setting.
    // To add new global settings, add their default values below, and then
    // add form elements to system_theme_settings() in system.admin.inc.
    $cache[$theme] = array(
      'default_logo'                     =>  1,
      'logo_path'                        =>  '',
      'default_favicon'                  =>  1,
      'favicon_path'                     =>  '',
      // Use the IANA-registered MIME type for ICO files as default.
      'favicon_mimetype'                 =>  'image/vnd.microsoft.icon',
    );
    // Turn on all default features.
    $features = _system_default_theme_features();
    foreach ($features as $feature) {
      $cache[$theme]['toggle_' . $feature] = 1;
    }

    // Get the values for the theme-specific settings from the .info files of
    // the theme and all its base themes.
    if ($theme) {
      $themes = list_themes();
      $theme_object = $themes[$theme];

      // Create a list which includes the current theme and all its base themes.
      if (isset($theme_object->base_themes)) {
        $theme_keys = array_keys($theme_object->base_themes);
        $theme_keys[] = $theme;
      }
      else {
        $theme_keys = array($theme);
      }
      foreach ($theme_keys as $theme_key) {
        if (!empty($themes[$theme_key]->info['settings'])) {
          $cache[$theme] = array_merge($cache[$theme], $themes[$theme_key]->info['settings']);
        }
      }
    }

    // Get the saved global settings from the database.
    $cache[$theme] = array_merge($cache[$theme], variable_get('theme_settings', array()));

    if ($theme) {
      // Get the saved theme-specific settings from the database.
      $cache[$theme] = array_merge($cache[$theme], variable_get('theme_' . $theme . '_settings', array()));

      // If the theme does not support a particular feature, override the global
      // setting and set the value to NULL.
      if (!empty($theme_object->info['features'])) {
        foreach ($features as $feature) {
          if (!in_array($feature, $theme_object->info['features'])) {
            $cache[$theme]['toggle_' . $feature] = NULL;
          }
        }
      }

      // Generate the path to the logo image.
      if ($cache[$theme]['toggle_logo']) {
        if ($cache[$theme]['default_logo']) {
          $cache[$theme]['logo'] = file_create_url(dirname($theme_object->filename) . '/logo.png');
        }
        elseif ($cache[$theme]['logo_path']) {
          $cache[$theme]['logo'] = file_create_url($cache[$theme]['logo_path']);
        }
      }

      // Generate the path to the favicon.
      if ($cache[$theme]['toggle_favicon']) {
        if ($cache[$theme]['default_favicon']) {
          if (file_exists($favicon = dirname($theme_object->filename) . '/favicon.ico')) {
            $cache[$theme]['favicon'] = file_create_url($favicon);
          }
          else {
            $cache[$theme]['favicon'] = file_create_url('core/misc/favicon.ico');
          }
        }
        elseif ($cache[$theme]['favicon_path']) {
          $cache[$theme]['favicon'] = file_create_url($cache[$theme]['favicon_path']);
        }
        else {
          $cache[$theme]['toggle_favicon'] = FALSE;
        }
      }
    }
  }

  return isset($cache[$theme][$setting_name]) ? $cache[$theme][$setting_name] : NULL;
}

/**
 * Render a system default template, which is essentially a PHP template.
 *
 * @param $template_file
 *   The filename of the template to render.
 * @param $variables
 *   A keyed array of variables that will appear in the output.
 *
 * @return
 *   The output generated by the template.
 */
function theme_render_template($template_file, $variables) {
  extract($variables, EXTR_SKIP);               // Extract the variables to a local namespace
  ob_start();                                   // Start output buffering
  include DRUPAL_ROOT . '/' . $template_file;   // Include the template file
  return ob_get_clean();                        // End buffering and return its contents
}

/**
 * Enable a given list of themes.
 *
 * @param $theme_list
 *   An array of theme names.
 */
function theme_enable($theme_list) {
  drupal_clear_css_cache();

  foreach ($theme_list as $key) {
    db_update('system')
      ->fields(array('status' => 1))
      ->condition('type', 'theme')
      ->condition('name', $key)
      ->execute();
  }

  list_themes(TRUE);
  menu_router_rebuild();
  drupal_theme_rebuild();

  // Invoke hook_themes_enabled() after the themes have been enabled.
  module_invoke_all('themes_enabled', $theme_list);
}

/**
 * Disable a given list of themes.
 *
 * @param $theme_list
 *   An array of theme names.
 */
function theme_disable($theme_list) {
  // Don't disable the default theme.
  if ($pos = array_search(variable_get('theme_default', 'stark'), $theme_list) !== FALSE) {
    unset($theme_list[$pos]);
    if (empty($theme_list)) {
      return;
    }
  }

  drupal_clear_css_cache();

  foreach ($theme_list as $key) {
    db_update('system')
      ->fields(array('status' => 0))
      ->condition('type', 'theme')
      ->condition('name', $key)
      ->execute();
  }

  list_themes(TRUE);
  menu_router_rebuild();
  drupal_theme_rebuild();

  // Invoke hook_themes_disabled after the themes have been disabled.
  module_invoke_all('themes_disabled', $theme_list);
}

/**
 * @addtogroup themeable
 * @{
 */

/**
 * Preprocess variables for theme_datetime().
 */
function template_preprocess_datetime(&$variables) {
  // Format the 'datetime' attribute based on the timestamp.
  // @see http://www.w3.org/TR/html5-author/the-time-element.html#attr-time-datetime
  if (!isset($variables['attributes']['datetime']) && isset($variables['timestamp'])) {
    $variables['attributes']['datetime'] = format_date($variables['timestamp'], 'html_datetime', '', 'UTC');
  }

  // If no text was provided, try to auto-generate it.
  if (!isset($variables['text'])) {
    // Format and use a human-readable version of the timestamp, if any.
    if (isset($variables['timestamp'])) {
      $variables['text'] = format_date($variables['timestamp']);
      $variables['html'] = FALSE;
    }
    // Otherwise, use the literal datetime attribute.
    elseif (isset($variables['attributes']['datetime'])) {
      $variables['text'] = $variables['attributes']['datetime'];
      $variables['html'] = FALSE;
    }
  }
}

/**
 * Returns HTML for a date / time.
 *
 * @param $variables
 *   An associative array containing:
 *   - timestamp: (optional) A UNIX timestamp for the datetime attribute. If the
 *     datetime cannot be represented as a UNIX timestamp, use a valid datetime
 *     attribute value in $variables['attributes']['datetime'].
 *   - text: (optional) The content to display within the <time> element. Set
 *     'html' to TRUE if this value is already sanitized for output in HTML.
 *     Defaults to a human-readable representation of the timestamp value or the
 *     datetime attribute value using format_date().
 *     When invoked as #theme or #theme_wrappers of a render element, the
 *     rendered #children are autoamtically taken over as 'text', unless #text
 *     is explicitly set.
 *   - attributes: (optional) An associative array of HTML attributes to apply
 *     to the <time> element. A datetime attribute in 'attributes' overrides the
 *     'timestamp'. To create a valid datetime attribute value from a UNIX
 *     timestamp, use format_date() with one of the predefined 'html_*' formats.
 *   - html: (optional) Whether 'text' is HTML markup (TRUE) or plain-text
 *     (FALSE). Defaults to FALSE. For example, to use a SPAN tag within the
 *     TIME element, this must be set to TRUE, or the SPAN tag will be escaped.
 *     It is the responsibility of the caller to properly sanitize the value
 *     contained in 'text' (or within the SPAN tag in aforementioned example).
 *
 * @see template_preprocess_datetime()
 * @see http://www.w3.org/TR/html5-author/the-time-element.html#attr-time-datetime
 */
function theme_datetime($variables) {
  $output = '<time' . drupal_attributes($variables['attributes']) . '>';
  $output .= !empty($variables['html']) ? $variables['text'] : check_plain($variables['text']);
  $output .= '</time>';
  return $output;
}

/**
 * Returns HTML for status and/or error messages, grouped by type.
 *
 * An invisible heading identifies the messages for assistive technology.
 * Sighted users see a colored box. See http://www.w3.org/TR/WCAG-TECHS/H69.html
 * for info.
 *
 * @param $variables
 *   An associative array containing:
 *   - display: (optional) Set to 'status' or 'error' to display only messages
 *     of that type.
 */
function theme_status_messages($variables) {
  $display = $variables['display'];
  $output = '';

  $status_heading = array(
    'status' => t('Status message'),
    'error' => t('Error message'),
    'warning' => t('Warning message'),
  );
  foreach (drupal_get_messages($display) as $type => $messages) {
    $output .= "<div class=\"messages $type\">\n";
    if (!empty($status_heading[$type])) {
      $output .= '<h2 class="element-invisible">' . $status_heading[$type] . "</h2>\n";
    }
    if (count($messages) > 1) {
      $output .= " <ul>\n";
      foreach ($messages as $message) {
        $output .= '  <li>' . $message . "</li>\n";
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
 * Returns HTML for a link.
 *
 * All Drupal code that outputs a link should call the l() function. That
 * function performs some initial preprocessing, and then, if necessary, calls
 * theme('link') for rendering the anchor tag.
 *
 * To optimize performance for sites that don't need custom theming of links,
 * the l() function includes an inline copy of this function, and uses that copy
 * if none of the enabled modules or the active theme implement any preprocess
 * or process functions or override this theme implementation.
 *
 * @param $variables
 *   An associative array containing the keys 'text', 'path', and 'options'. See
 *   the l() function for information about these variables.
 *
 * @see l()
 */
function theme_link($variables) {
  return '<a href="' . check_plain(url($variables['path'], $variables['options'])) . '"' . drupal_attributes($variables['options']['attributes']) . '>' . ($variables['options']['html'] ? $variables['text'] : check_plain($variables['text'])) . '</a>';
}

/**
 * Returns HTML for a set of links.
 *
 * @param $variables
 *   An associative array containing:
 *   - links: An associative array of links to be themed. The key for each link
 *     is used as its CSS class. Each link should be itself an array, with the
 *     following elements:
 *     - title: The link text.
 *     - href: The link URL. If omitted, the 'title' is shown as a plain text
 *       item in the links list.
 *     - html: (optional) Whether or not 'title' is HTML. If set, the title
 *       will not be passed through check_plain().
 *     - attributes: (optional) Attributes for the anchor, or for the <span> tag
 *       used in its place if no 'href' is supplied. If element 'class' is
 *       included, it must be an array of one or more class names.
 *     If the 'href' element is supplied, the entire link array is passed to l()
 *     as its $options parameter.
 *   - attributes: A keyed array of attributes for the UL containing the
 *     list of links.
 *   - heading: (optional) A heading to precede the links. May be an associative
 *     array or a string. If it's an array, it can have the following elements:
 *     - text: The heading text.
 *     - level: The heading level (e.g. 'h2', 'h3').
 *     - class: (optional) An array of the CSS classes for the heading.
 *     When using a string it will be used as the text of the heading and the
 *     level will default to 'h2'. Headings should be used on navigation menus
 *     and any list of links that consistently appears on multiple pages. To
 *     make the heading invisible use the 'element-invisible' CSS class. Do not
 *     use 'display:none', which removes it from screen-readers and assistive
 *     technology. Headings allow screen-reader and keyboard only users to
 *     navigate to or skip the links. See
 *     http://juicystudio.com/article/screen-readers-display-none.php and
 *     http://www.w3.org/TR/WCAG-TECHS/H42.html for more information.
 */
function theme_links($variables) {
  $language_url = drupal_container()->get(LANGUAGE_TYPE_URL);

  $links = $variables['links'];
  $attributes = $variables['attributes'];
  $heading = $variables['heading'];
  $output = '';

  if (!empty($links)) {
    // Prepend the heading to the list, if any.
    if (!empty($heading)) {
      // Convert a string heading into an array, using a H2 tag by default.
      if (is_string($heading)) {
        $heading = array('text' => $heading);
      }
      // Merge in default array properties into $heading.
      $heading += array(
        'level' => 'h2',
        'attributes' => array(),
      );
      // @todo Remove backwards compatibility for $heading['class'].
      if (isset($heading['class'])) {
        $heading['attributes']['class'] = $heading['class'];
      }

      $output .= '<' . $heading['level'] . drupal_attributes($heading['attributes']) . '>';
      $output .= check_plain($heading['text']);
      $output .= '</' . $heading['level'] . '>';
    }

    $output .= '<ul' . drupal_attributes($attributes) . '>';

    $num_links = count($links);
    $i = 0;
    foreach ($links as $key => $link) {
      $i++;

      $class = array();
      // Use the array key as class name.
      $class[] = drupal_html_class($key);
      // Add odd/even, first, and last classes.
      $class[] = ($i % 2 ? 'odd' : 'even');
      if ($i == 1) {
        $class[] = 'first';
      }
      if ($i == $num_links) {
        $class[] = 'last';
      }

      // Handle links.
      if (isset($link['href'])) {
        $is_current_path = ($link['href'] == current_path() || ($link['href'] == '<front>' && drupal_is_front_page()));
        $is_current_language = (empty($link['language']) || $link['language']->langcode == $language_url->langcode);
        if ($is_current_path && $is_current_language) {
          $class[] = 'active';
        }
        // Pass in $link as $options, they share the same keys.
        $item = l($link['title'], $link['href'], $link);
      }
      // Handle title-only text items.
      else {
        // Merge in default array properties into $link.
        $link += array(
          'html' => FALSE,
          'attributes' => array(),
        );
        $item = '<span' . drupal_attributes($link['attributes']) . '>';
        $item .= ($link['html'] ? $link['title'] : check_plain($link['title']));
        $item .= '</span>';
      }

      $output .= '<li' . drupal_attributes(array('class' => $class)) . '>';
      $output .= $item;
      $output .= '</li>';
    }

    $output .= '</ul>';
  }

  return $output;
}

/**
 * Returns HTML for an image.
 *
 * @param $variables
 *   An associative array containing:
 *   - uri: Either the path of the image file (relative to base_path()) or a
 *     full URL.
 *   - width: The width of the image (if known).
 *   - height: The height of the image (if known).
 *   - alt: The alternative text for text-based browsers. HTML 4 and XHTML 1.0
 *     always require an alt attribute. The HTML 5 draft allows the alt
 *     attribute to be omitted in some cases. Therefore, this variable defaults
 *     to an empty string, but can be set to NULL for the attribute to be
 *     omitted. Usually, neither omission nor an empty string satisfies
 *     accessibility requirements, so it is strongly encouraged for code calling
 *     theme('image') to pass a meaningful value for this variable.
 *     - http://www.w3.org/TR/REC-html40/struct/objects.html#h-13.8
 *     - http://www.w3.org/TR/xhtml1/dtds.html
 *     - http://dev.w3.org/html5/spec/Overview.html#alt
 *   - title: The title text is displayed when the image is hovered in some
 *     popular browsers.
 *   - attributes: Associative array of attributes to be placed in the img tag.
 */
function theme_image($variables) {
  $attributes = $variables['attributes'];
  $attributes['src'] = file_create_url($variables['uri']);

  foreach (array('width', 'height', 'alt', 'title') as $key) {
    if (isset($variables[$key])) {
      $attributes[$key] = $variables[$key];
    }
  }

  return '<img' . drupal_attributes($attributes) . ' />';
}

/**
 * Returns HTML for a breadcrumb trail.
 *
 * @param $variables
 *   An associative array containing:
 *   - breadcrumb: An array containing the breadcrumb links.
 */
function theme_breadcrumb($variables) {
  $breadcrumb = $variables['breadcrumb'];
  $output = '';
  if (!empty($breadcrumb)) {
    $output .= '<nav role="navigation" class="breadcrumb">';
    // Provide a navigational heading to give context for breadcrumb links to
    // screen-reader users. Make the heading invisible with .element-invisible.
    $output .= '<h2 class="element-invisible">' . t('You are here') . '</h2>';
    $output .= '<ol><li>' . implode(' » </li><li>', $breadcrumb) . '</li></ol>';
    $output .= '</nav>';
  }
  return $output;
}

/**
 * Returns HTML for a table.
 *
 * @param $variables
 *   An associative array containing:
 *   - header: An array containing the table headers. Each element of the array
 *     can be either a localized string or an associative array with the
 *     following keys:
 *     - "data": The localized title of the table column.
 *     - "field": The database field represented in the table column (required
 *       if user is to be able to sort on this column).
 *     - "sort": A default sort order for this column ("asc" or "desc").
 *     - Any HTML attributes, such as "colspan", to apply to the column header
 *       cell.
 *   - rows: An array of table rows. Every row is an array of cells, or an
 *     associative array with the following keys:
 *     - "data": an array of cells
 *     - Any HTML attributes, such as "class", to apply to the table row.
 *     - "no_striping": a boolean indicating that the row should receive no
 *       'even / odd' styling. Defaults to FALSE.
 *     Each cell can be either a string or an associative array with the
 *     following keys:
 *     - "data": The string to display in the table cell.
 *     - "header": Indicates this cell is a header.
 *     - Any HTML attributes, such as "colspan", to apply to the table cell.
 *     Here's an example for $rows:
 *     @code
 *     $rows = array(
 *       // Simple row
 *       array(
 *         'Cell 1', 'Cell 2', 'Cell 3'
 *       ),
 *       // Row with attributes on the row and some of its cells.
 *       array(
 *         'data' => array('Cell 1', array('data' => 'Cell 2', 'colspan' => 2)), 'class' => array('funky')
 *       )
 *     );
 *     @endcode
 *   - attributes: An array of HTML attributes to apply to the table tag.
 *   - caption: A localized string to use for the <caption> tag.
 *   - colgroups: An array of column groups. Each element of the array can be
 *     either:
 *     - An array of columns, each of which is an associative array of HTML
 *       attributes applied to the COL element.
 *     - An array of attributes applied to the COLGROUP element, which must
 *       include a "data" attribute. To add attributes to COL elements, set the
 *       "data" attribute with an array of columns, each of which is an
 *       associative array of HTML attributes.
 *     Here's an example for $colgroup:
 *     @code
 *     $colgroup = array(
 *       // COLGROUP with one COL element.
 *       array(
 *         array(
 *           'class' => array('funky'), // Attribute for the COL element.
 *         ),
 *       ),
 *       // Colgroup with attributes and inner COL elements.
 *       array(
 *         'data' => array(
 *           array(
 *             'class' => array('funky'), // Attribute for the COL element.
 *           ),
 *         ),
 *         'class' => array('jazzy'), // Attribute for the COLGROUP element.
 *       ),
 *     );
 *     @endcode
 *     These optional tags are used to group and set properties on columns
 *     within a table. For example, one may easily group three columns and
 *     apply same background style to all.
 *   - sticky: Use a "sticky" table header.
 *   - empty: The message to display in an extra row if table does not have any
 *     rows.
 */
function theme_table($variables) {
  $header = $variables['header'];
  $rows = $variables['rows'];
  $attributes = $variables['attributes'];
  $caption = $variables['caption'];
  $colgroups = $variables['colgroups'];
  $sticky = $variables['sticky'];
  $empty = $variables['empty'];

  // Add sticky headers, if applicable.
  if (count($header) && $sticky) {
    drupal_add_js('core/misc/tableheader.js');
    // Add 'sticky-enabled' class to the table to identify it for JS.
    // This is needed to target tables constructed by this function.
    $attributes['class'][] = 'sticky-enabled';
  }

  $output = '<table' . drupal_attributes($attributes) . ">\n";

  if (isset($caption)) {
    $output .= '<caption>' . $caption . "</caption>\n";
  }

  // Format the table columns:
  if (count($colgroups)) {
    foreach ($colgroups as $number => $colgroup) {
      $attributes = array();

      // Check if we're dealing with a simple or complex column
      if (isset($colgroup['data'])) {
        foreach ($colgroup as $key => $value) {
          if ($key == 'data') {
            $cols = $value;
          }
          else {
            $attributes[$key] = $value;
          }
        }
      }
      else {
        $cols = $colgroup;
      }

      // Build colgroup
      if (is_array($cols) && count($cols)) {
        $output .= ' <colgroup' . drupal_attributes($attributes) . '>';
        $i = 0;
        foreach ($cols as $col) {
          $output .= ' <col' . drupal_attributes($col) . ' />';
        }
        $output .= " </colgroup>\n";
      }
      else {
        $output .= ' <colgroup' . drupal_attributes($attributes) . " />\n";
      }
    }
  }

  // Add the 'empty' row message if available.
  if (!count($rows) && $empty) {
    $header_count = 0;
    foreach ($header as $header_cell) {
      if (is_array($header_cell)) {
        $header_count += isset($header_cell['colspan']) ? $header_cell['colspan'] : 1;
      }
      else {
        $header_count++;
      }
    }
    $rows[] = array(array('data' => $empty, 'colspan' => $header_count, 'class' => array('empty', 'message')));
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
        if (empty($row['no_striping'])) {
          $class = $flip[$class];
          $attributes['class'][] = $class;
        }

        // Build row
        $output .= ' <tr' . drupal_attributes($attributes) . '>';
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
 * Returns HTML for a sort icon.
 *
 * @param $variables
 *   An associative array containing:
 *   - style: Set to either 'asc' or 'desc', this determines which icon to show.
 */
function theme_tablesort_indicator($variables) {
  if ($variables['style'] == "asc") {
    return theme('image', array('uri' => 'core/misc/arrow-asc.png', 'width' => 13, 'height' => 13, 'alt' => t('sort ascending'), 'title' => t('sort ascending')));
  }
  else {
    return theme('image', array('uri' => 'core/misc/arrow-desc.png', 'width' => 13, 'height' => 13, 'alt' => t('sort descending'), 'title' => t('sort descending')));
  }
}

/**
 * Returns HTML for a marker for new or updated content.
 *
 * @param $variables
 *   An associative array containing:
 *   - type: Number representing the marker type to display. See MARK_NEW,
 *     MARK_UPDATED, MARK_READ.
 */
function theme_mark($variables) {
  $type = $variables['type'];
  global $user;
  if ($user->uid) {
    if ($type == MARK_NEW) {
      return ' <span class="marker">' . t('new') . '</span>';
    }
    elseif ($type == MARK_UPDATED) {
      return ' <span class="marker">' . t('updated') . '</span>';
    }
  }
}

/**
 * Returns HTML for a list or nested list of items.
 *
 * @param $variables
 *   An associative array containing:
 *   - items: A list of items to render. String values are rendered as is. Each
 *     item can also be an associative array containing:
 *     - data: The string content of the list item.
 *     - children: A list of nested child items to render that behave
 *       identically to 'items', but any non-numeric string keys are treated as
 *       HTML attributes for the child list that wraps 'children'.
 *     Any other key/value pairs are used as HTML attributes for the list item
 *     in 'data'.
 *   - title: The title of the list.
 *   - type: The type of list to return (e.g. "ul", "ol").
 *   - attributes: The attributes applied to the list element.
 */
function theme_item_list($variables) {
  $items = $variables['items'];
  $title = (string) $variables['title'];
  $type = $variables['type'];
  $list_attributes = $variables['attributes'];

  $output = '';
  if ($items) {
    $output .= '<' . $type . drupal_attributes($list_attributes) . '>';

    $num_items = count($items);
    $i = 0;
    foreach ($items as $key => $item) {
      $i++;
      $attributes = array();

      if (is_array($item)) {
        $value = '';
        if (isset($item['data'])) {
          $value .= $item['data'];
        }
        $attributes = array_diff_key($item, array('data' => 0, 'children' => 0));

        // Append nested child list, if any.
        if (isset($item['children'])) {
          // HTML attributes for the outer list are defined in the 'attributes'
          // theme variable, but not inherited by children. For nested lists,
          // all non-numeric keys in 'children' are used as list attributes.
          $child_list_attributes = array();
          foreach ($item['children'] as $child_key => $child_item) {
            if (is_string($child_key)) {
              $child_list_attributes[$child_key] = $child_item;
              unset($item['children'][$child_key]);
            }
          }
          $value .= theme('item_list', array(
            'items' => $item['children'],
            'type' => $type,
            'attributes' => $child_list_attributes,
          ));
        }
      }
      else {
        $value = $item;
      }

      $attributes['class'][] = ($i % 2 ? 'odd' : 'even');
      if ($i == 1) {
        $attributes['class'][] = 'first';
      }
      if ($i == $num_items) {
        $attributes['class'][] = 'last';
      }

      $output .= '<li' . drupal_attributes($attributes) . '>' . $value . '</li>';
    }
    $output .= "</$type>";
  }

  // Only output the list container and title, if there are any list items.
  // Check to see whether the block title exists before adding a header.
  // Empty headers are not semantic and present accessibility challenges.
  if ($output !== '') {
    if ($title !== '') {
      $title = '<h3>' . $title . '</h3>';
    }
    $output = '<div class="item-list">' . $title . $output . '</div>';
  }

  return $output;
}

/**
 * Returns HTML for a "more help" link.
 *
 * @param $variables
 *   An associative array containing:
 *   - url: The url for the link.
 */
function theme_more_help_link($variables) {
  return '<div class="more-help-link">' . l(t('More help'), $variables['url']) . '</div>';
}

/**
 * Returns HTML for a feed icon.
 *
 * @param $variables
 *   An associative array containing:
 *   - url: An internal system path or a fully qualified external URL of the
 *     feed.
 *   - title: A descriptive title of the feed.
 */
function theme_feed_icon($variables) {
  $text = t('Subscribe to @feed-title', array('@feed-title' => $variables['title']));
  if ($image = theme('image', array('uri' => 'core/misc/feed.png', 'width' => 16, 'height' => 16, 'alt' => $text))) {
    return l($image, $variables['url'], array('html' => TRUE, 'attributes' => array('class' => array('feed-icon'), 'title' => $text)));
  }
}

/**
 * Returns HTML for a generic HTML tag with attributes.
 *
 * @param $variables
 *   An associative array containing:
 *   - element: An associative array describing the tag:
 *     - #tag: The tag name to output. Typical tags added to the HTML HEAD:
 *       - meta: To provide meta information, such as a page refresh.
 *       - link: To refer to stylesheets and other contextual information.
 *       - script: To load JavaScript.
 *     - #attributes: (optional) An array of HTML attributes to apply to the
 *       tag.
 *     - #value: (optional) A string containing tag content, such as inline CSS.
 *     - #value_prefix: (optional) A string to prepend to #value, e.g. a CDATA
 *       wrapper prefix.
 *     - #value_suffix: (optional) A string to append to #value, e.g. a CDATA
 *       wrapper suffix.
 */
function theme_html_tag($variables) {
  $element = $variables['element'];
  $attributes = isset($element['#attributes']) ? drupal_attributes($element['#attributes']) : '';
  if (!isset($element['#value'])) {
    return '<' . $element['#tag'] . $attributes . " />\n";
  }
  else {
    $output = '<' . $element['#tag'] . $attributes . '>';
    if (isset($element['#value_prefix'])) {
      $output .= $element['#value_prefix'];
    }
    $output .= $element['#value'];
    if (isset($element['#value_suffix'])) {
      $output .= $element['#value_suffix'];
    }
    $output .= '</' . $element['#tag'] . ">\n";
    return $output;
  }
}

/**
 * Returns HTML for a "more" link, like those used in blocks.
 *
 * @param $variables
 *   An associative array containing:
 *   - url: The url of the main page.
 *   - title: A descriptive verb for the link, like 'Read more'.
 */
function theme_more_link($variables) {
  return '<div class="more-link">' . l(t('More'), $variables['url'], array('attributes' => array('title' => $variables['title']))) . '</div>';
}

/**
 * Returns HTML for a progress bar.
 *
 * Note that the core Batch API uses this only for non-JavaScript batch jobs.
 *
 * @param $variables
 *   An associative array containing:
 *   - percent: The percentage of the progress.
 *   - message: A string containing information to be displayed.
 */
function theme_progress_bar($variables) {
  $output = '<div id="progress" class="progress">';
  $output .= '<div class="bar"><div class="filled" style="width: ' . $variables['percent'] . '%"></div></div>';
  $output .= '<div class="percentage">' . $variables['percent'] . '%</div>';
  $output .= '<div class="message">' . $variables['message'] . '</div>';
  $output .= '</div>';

  return $output;
}

/**
 * Returns HTML for a meter.
 *
 * @param $variables
 *   An associative array containing:
 *   - display_value: The textual representation of the meter bar.
 *   - form: A string specifying one or more forms to which the <meter> element
 *     belongs separated by spaces.
 *   - high: A number specifying the range that is considered to be a high
 *     value.
 *   - low: A number specifying the range that is considered to be a low value.
 *   - max: A number specifying the maximum value of the range.
 *   - min: A number specifying the minimum value of the range.
 *   - optimum: A number specifying what value is the optimal value for the
 *     gauge.
 *   - value: A number specifying the current value of the gauge.
 *   - percentage: A number specifying the current percentage of the gauge.
 *   - attributes: Associative array of attributes to be placed in the meter
 *     tag.
 */
function theme_meter($variables) {
  $attributes = $variables['attributes'];

  foreach (array('form', 'high', 'low', 'max', 'min', 'optimum', 'value') as $attribute) {
    if (!empty($variables[$attribute])) {
      // This function was initially designed for the <meter> tag, but due to
      // the lack of browser and styling support for it, we're currently using
      // it's attributes as HTML5 data attributes.
      $attributes['data-' . $attribute] = $variables[$attribute];
    }
  }

  $output = '<div' . drupal_attributes($attributes) . '>';
  $output .= '  <div style="width: '. $variables['percentage'] .'%;" class="foreground"></div>';
  $output .= "</div>\n";

  if (!empty($variables['display_value'])) {
    $output .= '<div class="percent">' . $variables['display_value'] . '</div>';
  }

  return $output;
}

/**
 * Returns HTML for an indentation div; used for drag and drop tables.
 *
 * @param $variables
 *   An associative array containing:
 *   - size: Optional. The number of indentations to create.
 */
function theme_indentation($variables) {
  $output = '';
  for ($n = 0; $n < $variables['size']; $n++) {
    $output .= '<div class="indentation">&nbsp;</div>';
  }
  return $output;
}

/**
 * @} End of "addtogroup themeable".
 */

/**
 * Returns HTML output for a single table cell for theme_table().
 *
 * @param $cell
 *   Array of cell information, or string to display in cell.
 * @param bool $header
 *   TRUE if this cell is a table header cell, FALSE if it is an ordinary
 *   table cell. If $cell is an array with element 'header' set to TRUE, that
 *   will override the $header parameter.
 *
 * @return
 *   HTML for the cell.
 */
function _theme_table_cell($cell, $header = FALSE) {
  $attributes = '';

  if (is_array($cell)) {
    $data = isset($cell['data']) ? $cell['data'] : '';
    // Cell's data property can be a string or a renderable array.
    if (is_array($data)) {
      $data = drupal_render($data);
    }
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
 * Adds a default set of helper variables for variable processors and templates.
 * This comes in before any other preprocess function which makes it possible to
 * be used in default theme implementations (non-overridden theme functions).
 *
 * For more detailed information, see theme().
 *
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

  // Initialize html class attribute for the current hook.
  $variables['classes_array'] = array(drupal_html_class($hook));

  // Merge in variables that don't depend on hook and don't change during a
  // single page request.
  // Use the advanced drupal_static() pattern, since this is called very often.
  static $drupal_static_fast;
  if (!isset($drupal_static_fast)) {
    $drupal_static_fast['default_variables'] = &drupal_static(__FUNCTION__);
  }
  $default_variables = &$drupal_static_fast['default_variables'];
  // Global $user object shouldn't change during a page request once rendering
  // has started, but if there's an edge case where it does, re-fetch the
  // variables appropriate for the new user.
  if (!isset($default_variables) || ($user !== $default_variables['user'])) {
    $default_variables = _template_preprocess_default_variables();
  }
  $variables += $default_variables;
}

/**
 * Returns hook-independent variables to template_preprocess().
 */
function _template_preprocess_default_variables() {
  global $user;

  // Variables that don't depend on a database connection.
  $variables = array(
    'attributes_array' => array(),
    'title_attributes_array' => array(),
    'content_attributes_array' => array(),
    'title_prefix' => array(),
    'title_suffix' => array(),
    'user' => $user,
    'db_is_active' => !defined('MAINTENANCE_MODE'),
    'is_admin' => FALSE,
    'logged_in' => FALSE,
  );

  // The user object has no uid property when the database does not exist during
  // install. The user_access() check deals with issues when in maintenance mode
  // as uid is set but the user.module has not been included.
  if (isset($user->uid) && function_exists('user_access')) {
    $variables['is_admin'] = user_access('access administration pages');
    $variables['logged_in'] = ($user->uid > 0);
  }

  // drupal_is_front_page() might throw an exception.
  try {
    $variables['is_front'] = drupal_is_front_page();
  }
  catch (Exception $e) {
    // If the database is not yet available, set default values for these
    // variables.
    $variables['is_front'] = FALSE;
    $variables['db_is_active'] = FALSE;
  }

  return $variables;
}

/**
 * A default process function used to alter variables as late as possible.
 *
 * For more detailed information, see theme().
 *
 */
function template_process(&$variables, $hook) {
  // Flatten out classes.
  $variables['classes'] = implode(' ', $variables['classes_array']);

  // Flatten out attributes, title_attributes, and content_attributes.
  // Because this function can be called very often, and often with empty
  // attributes, optimize performance by only calling drupal_attributes() if
  // necessary.
  $variables['attributes'] = $variables['attributes_array'] ? drupal_attributes($variables['attributes_array']) : '';
  $variables['title_attributes'] = $variables['title_attributes_array'] ? drupal_attributes($variables['title_attributes_array']) : '';
  $variables['content_attributes'] = $variables['content_attributes_array'] ? drupal_attributes($variables['content_attributes_array']) : '';
}

/**
 * Preprocess variables for html.tpl.php
 *
 * @see system_elements()
 * @see html.tpl.php
 */
function template_preprocess_html(&$variables) {
  $language_interface = drupal_container()->get(LANGUAGE_TYPE_INTERFACE);

  // Compile a list of classes that are going to be applied to the body element.
  // This allows advanced theming based on context (home page, node of certain type, etc.).
  // Add a class that tells us whether we're on the front page or not.
  $variables['classes_array'][] = $variables['is_front'] ? 'front' : 'not-front';
  // Add a class that tells us whether the page is viewed by an authenticated user or not.
  $variables['classes_array'][] = $variables['logged_in'] ? 'logged-in' : 'not-logged-in';

  // Add information about the number of sidebars.
  if (!empty($variables['page']['sidebar_first']) && !empty($variables['page']['sidebar_second'])) {
    $variables['classes_array'][] = 'two-sidebars';
  }
  elseif (!empty($variables['page']['sidebar_first'])) {
    $variables['classes_array'][] = 'one-sidebar sidebar-first';
  }
  elseif (!empty($variables['page']['sidebar_second'])) {
    $variables['classes_array'][] = 'one-sidebar sidebar-second';
  }
  else {
    $variables['classes_array'][] = 'no-sidebars';
  }

  // Populate the body classes.
  if ($suggestions = theme_get_suggestions(arg(), 'page', '-')) {
    foreach ($suggestions as $suggestion) {
      if ($suggestion != 'page-front') {
        // Add current suggestion to page classes to make it possible to theme
        // the page depending on the current page type (e.g. node, admin, user,
        // etc.) as well as more specific data like node-12 or node-edit.
        $variables['classes_array'][] = drupal_html_class($suggestion);
      }
    }
  }

  // If on an individual node page, add the node type to body classes.
  if ($node = menu_get_object()) {
    $variables['classes_array'][] = drupal_html_class('node-type-' . $node->type);
  }

  // Initializes attributes which are specific to the html and body elements.
  $variables['html_attributes_array'] = array();
  $variables['body_attributes_array'] = array();

  // HTML element attributes.
  $variables['html_attributes_array']['lang'] = $language_interface->langcode;
  $variables['html_attributes_array']['dir'] = $language_interface->direction ? 'rtl' : 'ltr';

  // Add favicon.
  if (theme_get_setting('toggle_favicon')) {
    $favicon = theme_get_setting('favicon');
    $type = theme_get_setting('favicon_mimetype');
    drupal_add_html_head_link(array('rel' => 'shortcut icon', 'href' => drupal_strip_dangerous_protocols($favicon), 'type' => $type));
  }

  // Construct page title.
  if (drupal_get_title()) {
    $head_title = array(
      'title' => strip_tags(drupal_get_title()),
      'name' => check_plain(variable_get('site_name', 'Drupal')),
    );
  }
  else {
    $head_title = array('name' => check_plain(variable_get('site_name', 'Drupal')));
    if (variable_get('site_slogan', '')) {
      $head_title['slogan'] = filter_xss_admin(variable_get('site_slogan', ''));
    }
  }
  $variables['head_title_array'] = $head_title;
  $variables['head_title'] = implode(' | ', $head_title);

  // Display the html.tpl.php's default mobile metatags for responsive design.
  $elements = array(
    'MobileOptimized' => array(
      '#tag' => 'meta',
      '#attributes' => array(
        'name' => 'MobileOptimized',
        'content' => 'width',
      ),
    ),
    'HandheldFriendly' => array(
      '#tag' => 'meta',
      '#attributes' => array(
        'name' => 'HandheldFriendly',
        'content' => 'true',
      ),
    ),
    'viewport' => array(
      '#tag' => 'meta',
      '#attributes' => array(
        'name' => 'viewport',
        'content' => 'width=device-width',
      ),
    ),
    'cleartype' => array(
      '#tag' => 'meta',
      '#attributes' => array(
        'http-equiv' => 'cleartype',
        'content' => 'on',
      ),
    ),
  );
  foreach ($elements as $name => $element) {
    drupal_add_html_head($element, $name);
  }

  // Populate the page template suggestions.
  if ($suggestions = theme_get_suggestions(arg(), 'html')) {
    $variables['theme_hook_suggestions'] = $suggestions;
  }
}

/**
 * Preprocess variables for page.tpl.php
 *
 * Most themes utilize their own copy of page.tpl.php. The default is located
 * inside "modules/system/page.tpl.php". Look in there for the full list of
 * variables.
 *
 * Uses the arg() function to generate a series of page template suggestions
 * based on the current path.
 *
 * Any changes to variables in this preprocessor should also be changed inside
 * template_preprocess_maintenance_page() to keep all of them consistent.
 *
 * @see drupal_render_page()
 * @see template_process_page()
 * @see page.tpl.php
 */
function template_preprocess_page(&$variables) {
  $language_interface = drupal_container()->get(LANGUAGE_TYPE_INTERFACE);

  // Move some variables to the top level for themer convenience and template cleanliness.
  $variables['show_messages'] = $variables['page']['#show_messages'];

  foreach (system_region_list($GLOBALS['theme']) as $region_key => $region_name) {
    if (!isset($variables['page'][$region_key])) {
      $variables['page'][$region_key] = array();
    }
  }

  // Set up layout variable.
  $variables['layout'] = 'none';
  if (!empty($variables['page']['sidebar_first'])) {
    $variables['layout'] = 'first';
  }
  if (!empty($variables['page']['sidebar_second'])) {
    $variables['layout'] = ($variables['layout'] == 'first') ? 'both' : 'second';
  }

  $variables['base_path']         = base_path();
  $variables['front_page']        = url();
  $variables['feed_icons']        = drupal_get_feeds();
  $variables['language']          = $language_interface;
  $variables['language']->dir     = $language_interface->direction ? 'rtl' : 'ltr';
  $variables['logo']              = theme_get_setting('logo');
  $variables['main_menu']         = theme_get_setting('toggle_main_menu') ? menu_main_menu() : array();
  $variables['secondary_menu']    = theme_get_setting('toggle_secondary_menu') ? menu_secondary_menu() : array();
  $variables['action_links']      = menu_local_actions();
  $variables['site_name']         = (theme_get_setting('toggle_name') ? filter_xss_admin(variable_get('site_name', 'Drupal')) : '');
  $variables['site_slogan']       = (theme_get_setting('toggle_slogan') ? filter_xss_admin(variable_get('site_slogan', '')) : '');
  $variables['tabs']              = menu_local_tabs();

  if ($node = menu_get_object()) {
    $variables['node'] = $node;
  }

  // Populate the page template suggestions.
  if ($suggestions = theme_get_suggestions(arg(), 'page')) {
    $variables['theme_hook_suggestions'] = $suggestions;
  }
}

/**
 * Process variables for page.tpl.php
 *
 * Perform final addition of variables before passing them into the template.
 * To customize these variables, simply set them in an earlier step.
 *
 * @see template_preprocess_page()
 * @see page.tpl.php
 */
function template_process_page(&$variables) {
  if (!isset($variables['breadcrumb'])) {
    // Build the breadcrumb last, so as to increase the chance of being able to
    // re-use the cache of an already rendered menu containing the active link
    // for the current page.
    // @see menu_tree_page_data()
    $variables['breadcrumb'] = theme('breadcrumb', array('breadcrumb' => drupal_get_breadcrumb()));
  }
  if (!isset($variables['title'])) {
    $variables['title'] = drupal_get_title();
  }

  // Generate messages last in order to capture as many as possible for the
  // current page.
  if (!isset($variables['messages'])) {
    $variables['messages'] = $variables['show_messages'] ? theme('status_messages') : '';
  }
}

/**
 * Process variables for html.tpl.php
 *
 * Perform final addition and modification of variables before passing into
 * the template. To customize these variables, call drupal_render() on elements
 * in $variables['page'] during THEME_preprocess_page().
 *
 * @see template_preprocess_html()
 * @see html.tpl.php
 */
function template_process_html(&$variables) {
  // Flatten out html_attributes and body_attributes.
  $variables['html_attributes'] = drupal_attributes($variables['html_attributes_array']);
  $variables['body_attributes'] = drupal_attributes($variables['body_attributes_array']);

  // Render page_top and page_bottom into top level variables.
  $variables['page_top'] = drupal_render($variables['page']['page_top']);
  $variables['page_bottom'] = drupal_render($variables['page']['page_bottom']);
  // Place the rendered HTML for the page body into a top level variable.
  $variables['page']              = $variables['page']['#children'];
  $variables['page_bottom'] .= drupal_get_js('footer');

  $variables['head']    = drupal_get_html_head();
  $variables['css']     = drupal_add_css();
  $variables['styles']  = drupal_get_css();
  $variables['scripts'] = drupal_get_js();
}

/**
 * Generate an array of suggestions from path arguments.
 *
 * This is typically called for adding to the 'theme_hook_suggestions' or
 * 'classes_array' variables from within preprocess functions, when wanting to
 * base the additional suggestions on the path of the current page.
 *
 * @param $args
 *   An array of path arguments, such as from function arg().
 * @param $base
 *   A string identifying the base 'thing' from which more specific suggestions
 *   are derived. For example, 'page' or 'html'.
 * @param $delimiter
 *   The string used to delimit increasingly specific information. The default
 *   of '__' is appropriate for theme hook suggestions. '-' is appropriate for
 *   extra classes.
 *
 * @return
 *   An array of suggestions, suitable for adding to
 *   $variables['theme_hook_suggestions'] within a preprocess function or to
 *   $variables['classes_array'] if the suggestions represent extra CSS classes.
 */
function theme_get_suggestions($args, $base, $delimiter = '__') {

  // Build a list of suggested theme hooks or body classes in order of
  // specificity. One suggestion is made for every element of the current path,
  // though numeric elements are not carried to subsequent suggestions. For
  // example, for $base='page', http://www.example.com/node/1/edit would result
  // in the following suggestions and body classes:
  //
  // page__node              page-node
  // page__node__%           page-node-%
  // page__node__1           page-node-1
  // page__node__edit        page-node-edit

  $suggestions = array();
  $prefix = $base;
  foreach ($args as $arg) {
    // Remove slashes or null per SA-CORE-2009-003 and change - (hyphen) to _
    // (underscore).
    //
    // When we discover templates in @see drupal_find_theme_templates,
    // hyphens (-) are converted to underscores (_) before the theme hook
    // is registered. We do this because the hyphens used for delimiters
    // in hook suggestions cannot be used in the function names of the
    // associated preprocess functions. Any page templates designed to be used
    // on paths that contain a hyphen are also registered with these hyphens
    // converted to underscores so here we must convert any hyphens in path
    // arguments to underscores here before fetching theme hook suggestions
    // to ensure the templates are appropriately recognized.
    $arg = str_replace(array("/", "\\", "\0", '-'), array('', '', '', '_'), $arg);
    // The percent acts as a wildcard for numeric arguments since
    // asterisks are not valid filename characters on many filesystems.
    if (is_numeric($arg)) {
      $suggestions[] = $prefix . $delimiter . '%';
    }
    $suggestions[] = $prefix . $delimiter . $arg;
    if (!is_numeric($arg)) {
      $prefix .= $delimiter . $arg;
    }
  }
  if (drupal_is_front_page()) {
    // Front templates should be based on root only, not prefixed arguments.
    $suggestions[] = $base . $delimiter . 'front';
  }

  return $suggestions;
}

/**
 * The variables array generated here is a mirror of template_preprocess_page().
 * This preprocessor will run its course when theme_maintenance_page() is
 * invoked.
 *
 * An alternate template file of "maintenance-page--offline.tpl.php" can be
 * used when the database is offline to hide errors and completely replace the
 * content.
 *
 * The $variables array contains the following arguments:
 * - $content
 *
 * @see maintenance-page.tpl.php
 */
function template_preprocess_maintenance_page(&$variables) {
  global $theme;
  $language_interface = drupal_container()->get(LANGUAGE_TYPE_INTERFACE);
  // Retrieve the theme data to list all available regions.
  $theme_data = list_themes();
  $regions = $theme_data[$theme]->info['regions'];

  // Add favicon
  if (theme_get_setting('toggle_favicon')) {
    $favicon = theme_get_setting('favicon');
    $type = theme_get_setting('favicon_mimetype');
    drupal_add_html_head_link(array('rel' => 'shortcut icon', 'href' => drupal_strip_dangerous_protocols($favicon), 'type' => $type));
  }

  // Get all region content set with drupal_add_region_content().
  foreach (array_keys($regions) as $region) {
    // Assign region to a region variable.
    $region_content = drupal_get_region_content($region);
    isset($variables[$region]) ? $variables[$region] .= $region_content : $variables[$region] = $region_content;
  }

  // Setup layout variable.
  $variables['layout'] = 'none';
  if (!empty($variables['sidebar_first'])) {
    $variables['layout'] = 'first';
  }
  if (!empty($variables['sidebar_second'])) {
    $variables['layout'] = ($variables['layout'] == 'first') ? 'both' : 'second';
  }

  // Construct page title
  if (drupal_get_title()) {
    $head_title = array(
      'title' => strip_tags(drupal_get_title()),
      'name' => variable_get('site_name', 'Drupal'),
    );
  }
  else {
    $head_title = array('name' => variable_get('site_name', 'Drupal'));
    if (variable_get('site_slogan', '')) {
      $head_title['slogan'] = variable_get('site_slogan', '');
    }
  }

  $variables['head_title_array']  = $head_title;
  $variables['head_title']        = implode(' | ', $head_title);
  $variables['base_path']         = base_path();
  $variables['front_page']        = url();
  $variables['breadcrumb']        = '';
  $variables['feed_icons']        = '';
  $variables['help']              = '';
  $variables['language']          = $language_interface;
  $variables['language']->dir     = $language_interface->direction ? 'rtl' : 'ltr';
  $variables['logo']              = theme_get_setting('logo');
  $variables['messages']          = $variables['show_messages'] ? theme('status_messages') : '';
  $variables['main_menu']         = array();
  $variables['secondary_menu']    = array();
  $variables['site_name']         = (theme_get_setting('toggle_name') ? variable_get('site_name', 'Drupal') : '');
  $variables['site_slogan']       = (theme_get_setting('toggle_slogan') ? variable_get('site_slogan', '') : '');
  $variables['tabs']              = '';
  $variables['title']             = drupal_get_title();

  // Compile a list of classes that are going to be applied to the body element.
  $variables['classes_array'][] = 'in-maintenance';
  if (isset($variables['db_is_active']) && !$variables['db_is_active']) {
    $variables['classes_array'][] = 'db-offline';
  }
  if ($variables['layout'] == 'both') {
    $variables['classes_array'][] = 'two-sidebars';
  }
  elseif ($variables['layout'] == 'none') {
    $variables['classes_array'][] = 'no-sidebars';
  }
  else {
    $variables['classes_array'][] = 'one-sidebar sidebar-' . $variables['layout'];
  }

  // Dead databases will show error messages so supplying this template will
  // allow themers to override the page and the content completely.
  if (isset($variables['db_is_active']) && !$variables['db_is_active']) {
    $variables['theme_hook_suggestion'] = 'maintenance_page__offline';
  }
}

/**
 * The variables array generated here is a mirror of template_process_html().
 * This processor will run its course when theme_maintenance_page() is invoked.
 *
 * @see maintenance-page.tpl.php
 */
function template_process_maintenance_page(&$variables) {
  $variables['head']    = drupal_get_html_head();
  $variables['css']     = drupal_add_css();
  $variables['styles']  = drupal_get_css();
  $variables['scripts'] = drupal_get_js();
}

/**
 * Preprocess variables for region.tpl.php
 *
 * Prepare the values passed to the theme_region function to be passed into a
 * pluggable template engine. Uses the region name to generate a template file
 * suggestions. If none are found, the default region.tpl.php is used.
 *
 * @see drupal_region_class()
 * @see region.tpl.php
 */
function template_preprocess_region(&$variables) {
  // Create the $content variable that templates expect.
  $variables['content'] = $variables['elements']['#children'];
  $variables['region'] = $variables['elements']['#region'];

  $variables['classes_array'][] = drupal_region_class($variables['region']);
  $variables['theme_hook_suggestions'][] = 'region__' . $variables['region'];
}
