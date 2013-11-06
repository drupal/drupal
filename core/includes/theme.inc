<?php

/**
 * @file
 * The theme system, which controls the output of Drupal.
 *
 * The theme system allows for nearly all output of the Drupal system to be
 * customized by user themes.
 */

use Drupal\Component\Utility\String;
use Drupal\Component\Utility\Url;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\Config;
use Drupal\Core\Language\Language;
use Drupal\Core\Extension\ExtensionNameLengthException;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Template\RenderWrapper;
use Drupal\Core\Utility\ThemeRegistry;
use Drupal\Core\Theme\ThemeSettings;
use Drupal\Component\Utility\NestedArray;

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
 * A responsive table class; hide table cell on narrow devices.
 *
 * Indicates that a column has medium priority and thus can be hidden on narrow
 * width devices and shown on medium+ width devices (i.e. tablets and desktops).
 */
const RESPONSIVE_PRIORITY_MEDIUM = 'priority-medium';

/**
 * A responsive table class; only show table cell on wide devices.
 *
 * Indicates that a column has low priority and thus can be hidden on narrow
 * and medium viewports and shown on wide devices (i.e. desktops).
 */
const RESPONSIVE_PRIORITY_LOW = 'priority-low';

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
 *
 * @deprecated Use \Drupal::service('access_check.theme')->checkAccess().
 */
function drupal_theme_access($theme) {
  if (is_object($theme)) {
    $theme = $theme->name;
  }
  return \Drupal::service('access_check.theme')->checkAccess($theme);
}

/**
 * Initializes the theme system by loading the theme.
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
  $theme = !empty($user->theme) && drupal_theme_access($user->theme) ? $user->theme : \Drupal::config('system.theme')->get('default');

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
}

/**
 * Initializes the theme system given already loaded information.
 *
 * This function is useful to initialize a theme when no database is present.
 *
 * @param $theme
 *   An object with the following information:
 *     filename
 *       The .info.yml file for this theme. The 'path' to
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
  // CSS file basenames to override, pointing to the final, overridden filepath.
  $theme->stylesheets_override = array();
  // CSS file basenames to remove.
  $theme->stylesheets_remove = array();

  // Grab stylesheets from base theme
  foreach ($base_theme as $base) {
    if (!empty($base->stylesheets)) {
      foreach ($base->stylesheets as $media => $stylesheets) {
        foreach ($stylesheets as $name => $stylesheet) {
          $final_stylesheets[$media][$name] = $stylesheet;
        }
      }
    }
    $base_theme_path = dirname($base->filename);
    if (!empty($base->info['stylesheets-remove'])) {
      foreach ($base->info['stylesheets-remove'] as $basename) {
        $theme->stylesheets_remove[$basename] = $base_theme_path . '/' . $basename;
      }
    }
    if (!empty($base->info['stylesheets-override'])) {
      foreach ($base->info['stylesheets-override'] as $name) {
        $basename = drupal_basename($name);
        $theme->stylesheets_override[$basename] = $base_theme_path . '/' . $name;
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
  if (!empty($theme->info['stylesheets-remove'])) {
    foreach ($theme->info['stylesheets-remove'] as $basename) {
      $theme->stylesheets_remove[$basename] = $theme_path . '/' . $basename;

      if (isset($theme->stylesheets_override[$basename])) {
        unset($theme->stylesheets_override[$basename]);
      }
    }
  }
  if (!empty($theme->info['stylesheets-override'])) {
    foreach ($theme->info['stylesheets-override'] as $name) {
      $basename = drupal_basename($name);
      $theme->stylesheets_override[$basename] = $theme_path . '/' . $name;

      if (isset($theme->stylesheets_remove[$basename])) {
        unset($theme->stylesheets_remove[$basename]);
      }
    }
  }

  // And now add the stylesheets properly
  foreach ($final_stylesheets as $media => $stylesheets) {
    foreach ($stylesheets as $stylesheet) {
      drupal_add_css($stylesheet, array('group' => CSS_AGGREGATE_THEME, 'every_page' => TRUE, 'media' => $media));
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

  // Always include Twig as the default theme engine.
  include_once DRUPAL_ROOT . '/core/themes/engines/twig/twig.engine';

  if (isset($registry_callback)) {
    _theme_registry_callback($registry_callback, array($theme, $base_theme, $theme_engine));
  }
}

/**
 * Gets the theme registry.
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
 * Sets the callback that will be used by theme_get_registry().
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
 * Gets the theme_registry cache; if it doesn't exist, builds it.
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
      if (\Drupal::moduleHandler()->isLoaded()) {
        _theme_save_registry($theme, $registry);
      }
    }
    return $registry;
  }
  else {
    return new ThemeRegistry('theme_registry:runtime:' . $theme->name, 'cache', array('theme_registry' => TRUE), \Drupal::moduleHandler()->isLoaded());
  }
}

/**
 * Writes the theme_registry cache into the database.
 */
function _theme_save_registry($theme, $registry) {
  cache()->set("theme_registry:$theme->name", $registry, CacheBackendInterface::CACHE_PERMANENT, array('theme_registry' => TRUE));
}

/**
 * Forces the system to rebuild the theme registry.
 *
 * This function should be called when modules are added to the system, or when
 * a dynamic system needs to add more theme hooks.
 */
function drupal_theme_rebuild() {
  drupal_static_reset('theme_get_registry');
  cache()->invalidateTags(array('theme_registry' => TRUE));
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
 *     hook. Either defined explicitly in hook_theme() or, if neither
 *     'function' nor 'template' is defined, then the default theme function
 *     name is used. The default theme function name is the theme hook prefixed
 *     by either 'theme_' for modules or '$name_' for everything else. If
 *     'function' is defined, 'template' is not used.
 *   - 'template': The filename of the template generating output for this
 *     theme hook. The template is in the directory defined by the 'path' key of
 *     hook_theme() or defaults to "$path/templates".
 *   - 'variables': The variables for this theme hook as defined in
 *     hook_theme(). If there is more than one implementation and 'variables'
 *     is not specified in a later one, then the previous definition is kept.
 *   - 'render element': The renderable element for this theme hook as defined
 *     in hook_theme(). If there is more than one implementation and
 *     'render element' is not specified in a later one, then the previous
 *     definition is kept.
 *   - 'preprocess functions': See theme() for detailed documentation.
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
 * @see hook_theme()
 * @see list_themes()
 */
function _theme_process_registry(&$cache, $name, $type, $theme, $path) {
  $result = array();

  $hook_defaults = array(
    'variables' => TRUE,
    'render element' => TRUE,
    'pattern' => TRUE,
    'base hook' => TRUE,
  );

  $module_list = array_keys(\Drupal::moduleHandler()->getModuleList());

  // Invoke the hook_theme() implementation, preprocess what is returned, and
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
          $result[$hook]['template'] = $path . '/templates/' . $info['template'];
        }
      }

      // Preprocess variables for all theming hooks, whether the hook is
      // implemented as a template or as a function. Ensure they are arrays.
      if (!isset($info['preprocess functions']) || !is_array($info['preprocess functions'])) {
        $info['preprocess functions'] = array();
        $prefixes = array();
        if ($type == 'module') {
          // Default variable preprocessor prefix.
          $prefixes[] = 'template';
          // Add all modules so they can intervene with their own variable
          // preprocessors. This allows them to provide variable preprocessors
          // even if they are not the owner of the current hook.
          $prefixes = array_merge($prefixes, $module_list);
        }
        elseif ($type == 'theme_engine' || $type == 'base_theme_engine') {
          // Theme engines get an extra set that come before the normally
          // named variable preprocessors.
          $prefixes[] = $name . '_engine';
          // The theme engine registers on behalf of the theme using the
          // theme's name.
          $prefixes[] = $theme;
        }
        else {
          // This applies when the theme manually registers their own variable
          // preprocessors.
          $prefixes[] = $name;
        }
        foreach ($prefixes as $prefix) {
          // Only use non-hook-specific variable preprocessors for theming
          // hooks implemented as templates. See theme().
          if (isset($info['template']) && function_exists($prefix . '_preprocess')) {
            $info['preprocess functions'][] = $prefix . '_preprocess';
          }
          if (function_exists($prefix . '_preprocess_' . $hook)) {
            $info['preprocess functions'][] = $prefix . '_preprocess_' . $hook;
          }
        }
      }
      // Check for the override flag and prevent the cached variable
      // preprocessors from being used. This allows themes or theme engines
      // to remove variable preprocessors set earlier in the registry build.
      if (!empty($info['override preprocess functions'])) {
        // Flag not needed inside the registry.
        unset($result[$hook]['override preprocess functions']);
      }
      elseif (isset($cache[$hook]['preprocess functions']) && is_array($cache[$hook]['preprocess functions'])) {
        $info['preprocess functions'] = array_merge($cache[$hook]['preprocess functions'], $info['preprocess functions']);
      }
      $result[$hook]['preprocess functions'] = $info['preprocess functions'];
    }

    // Merge the newly created theme hooks into the existing cache.
    $cache = $result + $cache;
  }

  // Let themes have variable preprocessors even if they didn't register a
  // template.
  if ($type == 'theme' || $type == 'base_theme') {
    foreach ($cache as $hook => $info) {
      // Check only if not registered by the theme or engine.
      if (empty($result[$hook])) {
        if (!isset($info['preprocess functions'])) {
          $cache[$hook]['preprocess functions'] = array();
        }
        // Only use non-hook-specific variable preprocessors for theme hooks
        // implemented as templates. See theme().
        if (isset($info['template']) && function_exists($name . '_preprocess')) {
          $cache[$hook]['preprocess functions'][] = $name . '_preprocess';
        }
        if (function_exists($name . '_preprocess_' . $hook)) {
          $cache[$hook]['preprocess functions'][] = $name . '_preprocess_' . $hook;
          $cache[$hook]['theme path'] = $path;
        }
        // Ensure uniqueness.
        $cache[$hook]['preprocess functions'] = array_unique($cache[$hook]['preprocess functions']);
      }
    }
  }
}

/**
 * Builds the theme registry cache.
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
  // First, preprocess the theme hooks advertised by modules. This will
  // serve as the basic registry. Since the list of enabled modules is the same
  // regardless of the theme used, this is cached in its own entry to save
  // building it for every theme.
  if ($cached = cache()->get('theme_registry:build:modules')) {
    $cache = $cached->data;
  }
  else {
    foreach (\Drupal::moduleHandler()->getImplementations('theme') as $module) {
      _theme_process_registry($cache, $module, 'module', $module, drupal_get_path('module', $module));
    }
    // Only cache this registry if all modules are loaded.
    if (\Drupal::moduleHandler()->isLoaded()) {
      cache()->set("theme_registry:build:modules", $cache, CacheBackendInterface::CACHE_PERMANENT, array('theme_registry' => TRUE));
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
    if (empty($info['preprocess functions'])) {
      unset($cache[$hook]['preprocess functions']);
    }
  }
  return $cache;
}

/**
 * Returns a list of all currently available themes.
 *
 * Retrieved from the database, if available and the site is not in maintenance
 * mode; otherwise compiled freshly from the filesystem.
 *
 * @param $refresh
 *   Whether to reload the list of themes from the database. Defaults to FALSE.
 *
 * @return
 *   An associative array of the currently available themes. The keys are the
 *   themes' machine names and the values are objects having the following
 *   properties:
 *   - filename: The filepath and name of the .info.yml file.
 *   - name: The machine name of the theme.
 *   - status: 1 for enabled, 0 for disabled themes.
 *   - info: The contents of the .info.yml file.
 *   - stylesheets: A two dimensional array, using the first key for the
 *     media attribute (e.g. 'all'), the second for the name of the file
 *     (e.g. style.css). The value is a complete filepath (e.g.
 *     themes/bartik/style.css). Not set if no stylesheets are defined in the
 *     .info.yml file.
 *   - scripts: An associative array of JavaScripts, using the filename as key
 *     and the complete filepath as value. Not set if no scripts are defined in
 *     the .info.yml file.
 *   - prefix: The base theme engine prefix.
 *   - engine: The machine name of the theme engine.
 *   - base_theme: If this is a sub-theme, the machine name of the base theme
 *     defined in the .info.yml file. Otherwise, the element is not set.
 *   - base_themes: If this is a sub-theme, an associative array of the
 *     base-theme ancestors of this theme, starting with this theme's base
 *     theme, then the base theme's own base theme, etc. Each entry has an
 *     array key equal to the theme's machine name, and a value equal to the
 *     human-readable theme name; if a theme with matching machine name does
 *     not exist in the system, the value will instead be NULL (and since the
 *     system would not know whether that theme itself has a base theme, that
 *     will end the array of base themes). This is not set if the theme is not
 *     a sub-theme.
 *   - sub_themes: An associative array of themes on the system that are
 *     either direct sub-themes (that is, they declare this theme to be
 *     their base theme), direct sub-themes of sub-themes, etc. The keys are
 *     the themes' machine names, and the values are the themes' human-readable
 *     names. This element is not set if there are no themes on the system that
 *     declare this theme as their base theme.
 */
function list_themes($refresh = FALSE) {
  $list = &drupal_static(__FUNCTION__, array());

  if ($refresh) {
    $list = array();
    system_list_reset();
  }

  if (empty($list)) {
    $list = array();
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
 * Finds all the base themes for the specified theme.
 *
 * Themes can inherit templates and function implementations from earlier
 * themes.
 *
 * @param $themes
 *   An array of available themes.
 * @param $key
 *   The name of the theme whose base we are looking for.
 * @param $used_keys
 *   (optional) A recursion parameter preventing endless loops. Defaults to
 *   NULL.
 *
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
 * All requests for themed output must go through this function (however,
 * calling the theme() function directly is strongly discouraged - see next
 * paragraph). It examines the request and routes it to the appropriate
 * @link themeable theme function or template @endlink, by checking the theme
 * registry.
 *
 * Avoid calling this function directly. It is preferable to replace direct
 * calls to the theme() function with calls to drupal_render() by passing a
 * render array with a #theme key to drupal_render(), which in turn calls
 * theme().
 *
 * @section sec_theme_hooks Theme Hooks
 * Most commonly, the first argument to this function is the name of the theme
 * hook. For instance, to theme a taxonomy term, the theme hook name is
 * 'taxonomy_term'. Modules register theme hooks within a hook_theme()
 * implementation and provide a default implementation via a function named
 * theme_HOOK() (e.g., theme_taxonomy_term()) or via a template file named
 * according to the value of the 'template' key registered with the theme hook
 * (see hook_theme() for details). Default templates are implemented with the
 * Twig rendering engine and are named the same as the theme hook, with
 * underscores changed to hyphens, so for the 'taxonomy_term' theme hook, the
 * default template is 'taxonomy-term.html.twig'.
 *
 * @subsection sub_overriding_theme_hooks Overriding Theme Hooks
 * Themes may also register new theme hooks within a hook_theme()
 * implementation, but it is more common for themes to override default
 * implementations provided by modules than to register entirely new theme
 * hooks. Themes can override a default implementation by implementing a
 * function named THEME_HOOK() (for example, the 'bartik' theme overrides the
 * default implementation of the 'menu_tree' theme hook by implementing a
 * bartik_menu_tree() function), or by adding a template file within its folder
 * structure that follows the template naming structure used by the theme's
 * rendering engine (for example, since the Bartik theme uses the Twig rendering
 * engine, it overrides the default implementation of the 'page' theme hook by
 * containing a 'page.html.twig' file within its folder structure).
 *
 * @subsection sub_preprocess_templates Preprocessing for Template Files
 * If the implementation is a template file, several functions are called before
 * the template file is invoked to modify the $variables array. These make up
 * the "preprocessing" phase, and are executed (if they exist), in the following
 * order (note that in the following list, HOOK indicates the theme hook name,
 * MODULE indicates a module name, THEME indicates a theme name, and ENGINE
 * indicates a theme engine name):
 * - template_preprocess(&$variables, $hook): Creates a default set of variables
 *   for all theme hooks with template implementations.
 * - template_preprocess_HOOK(&$variables): Should be implemented by the module
 *   that registers the theme hook, to set up default variables.
 * - MODULE_preprocess(&$variables, $hook): hook_preprocess() is invoked on all
 *   implementing modules.
 * - MODULE_preprocess_HOOK(&$variables): hook_preprocess_HOOK() is invoked on
 *   all implementing modules, so that modules that didn't define the theme hook
 *   can alter the variables.
 * - ENGINE_engine_preprocess(&$variables, $hook): Allows the theme engine to
 *   set necessary variables for all theme hooks with template implementations.
 * - ENGINE_engine_preprocess_HOOK(&$variables): Allows the theme engine to set
 *   necessary variables for the particular theme hook.
 * - THEME_preprocess(&$variables, $hook): Allows the theme to set necessary
 *   variables for all theme hooks with template implementations.
 * - THEME_preprocess_HOOK(&$variables): Allows the theme to set necessary
 *   variables specific to the particular theme hook.
 *
 * @subsection sub_preprocess_theme_funcs Preprocessing for Theme Functions
 * If the implementation is a function, only the theme-hook-specific preprocess
 * functions (the ones ending in _HOOK) are called from the list above. This is
 * because theme hooks with function implementations need to be fast, and
 * calling the non-theme-hook-specific preprocess functions for them would incur
 * a noticeable performance penalty.
 *
 * @subsection sub_alternate_suggestions Suggesting Alternate Hooks
 * Alternate hooks can be suggested by implementing the hook-specific
 * hook_theme_suggestions_HOOK_alter() or the generic
 * hook_theme_suggestions_alter(). These alter hooks are used to manipulate an
 * array of suggested alternate theme hooks to use, in reverse order of
 * priority. theme() will use the highest priority implementation that exists.
 * If none exists, theme() will use the implementation for the theme hook it was
 * called with. These suggestions are similar to and are used for similar
 * reasons as calling theme() with an array as the $hook parameter (see below).
 * The difference is whether the suggestions are determined by the code that
 * calls theme() or by altering the suggestions via the suggestion alter hooks.
 *
 * @param $hook
 *   The name of the theme hook to call. If the name contains a
 *   double-underscore ('__') and there isn't an implementation for the full
 *   name, the part before the '__' is checked. This allows a fallback to a
 *   more generic implementation. For example, if theme('links__node', ...) is
 *   called, but there is no implementation of that theme hook, then the
 *   'links' implementation is used. This process is iterative, so if
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
 *   registry, pass to preprocess functions for modification, and finally, pass
 *   to the function or template implementing the theme hook. Alternatively,
 *   this can be a renderable array, in which case, its properties are mapped to
 *   variables expected by the theme hook implementations.
 *
 * @return string|false
 *   An HTML string representing the themed output or FALSE if the passed $hook
 *   is not implemented.
 *
 * @see drupal_render()
 * @see themeable
 * @see hook_theme()
 * @see template_preprocess()
 */
function theme($hook, $variables = array()) {
  static $default_attributes;
  // If called before all modules are loaded, we do not necessarily have a full
  // theme registry to work with, and therefore cannot process the theme
  // request properly. See also _theme_load_registry().
  if (!\Drupal::moduleHandler()->isLoaded() && !defined('MAINTENANCE_MODE')) {
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
  // Save the original theme hook, so it can be supplied to theme variable
  // preprocess callbacks.
  $original_hook = $hook;

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
        watchdog('theme', 'Theme hook %hook not found.', array('%hook' => $hook), WATCHDOG_WARNING);
      }
      // There is no theme implementation for the hook passed. Return FALSE so
      // the function calling theme() can differentiate between a hook that
      // exists and renders an empty string and a hook that is not implemented.
      return FALSE;
    }
  }

  $info = $hooks[$hook];
  global $theme_path;
  $temp = $theme_path;
  // point path_to_theme() to the currently used theme path:
  $theme_path = $info['theme path'];

  // Include a file if the theme function or variable preprocessor is held
  // elsewhere.
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
        if (isset($element["#$name"]) || array_key_exists("#$name", $element)) {
          $variables[$name] = $element["#$name"];
        }
      }
    }
    else {
      $variables[$info['render element']] = $element;
      // Give a hint to render engines to prevent infinite recursion.
      $variables[$info['render element']]['#render_children'] = TRUE;
    }
  }

  // Merge in argument defaults.
  if (!empty($info['variables'])) {
    $variables += $info['variables'];
  }
  elseif (!empty($info['render element'])) {
    $variables += array($info['render element'] => array());
  }
  // Supply original caller info.
  $variables += array(
    'theme_hook_original' => $original_hook,
  );

  // Set base hook for later use. For example if '#theme' => 'node__article'
  // is called, we run hook_theme_suggestions_node_alter() rather than
  // hook_theme_suggestions_node__article_alter(), and also pass in the base
  // hook as the last parameter to the suggestions alter hooks.
  if (isset($info['base hook'])) {
    $base_theme_hook = $info['base hook'];
  }
  else {
    $base_theme_hook = $hook;
  }

  // Invoke hook_theme_suggestions_HOOK().
  $suggestions = Drupal::moduleHandler()->invokeAll('theme_suggestions_' . $base_theme_hook, array($variables));
  // If theme() was invoked with a direct theme suggestion like
  // '#theme' => 'node__article', add it to the suggestions array before
  // invoking suggestion alter hooks.
  if (isset($info['base hook'])) {
    $suggestions[] = $hook;
  }
  // Allow suggestions to be altered via hook_theme_suggestions_HOOK_alter().
  Drupal::moduleHandler()->alter('theme_suggestions_' . $base_theme_hook, $suggestions, $variables);

  // Check if each suggestion exists in the theme registry, and if so,
  // use it instead of the hook that theme() was called with. For example, a
  // function may call theme('node', ...), but a module can add
  // 'node__article' as a suggestion via hook_theme_suggestions_HOOK_alter(),
  // enabling a theme to have an alternate template file for article nodes.
  foreach (array_reverse($suggestions) as $suggestion) {
    if (isset($hooks[$suggestion])) {
      $info = $hooks[$suggestion];
      break;
    }
  }

  // Invoke the variable preprocessors, if any.
  if (isset($info['base hook'])) {
    $base_hook = $info['base hook'];
    $base_hook_info = $hooks[$base_hook];
    // Include files required by the base hook, since its variable preprocessors
    // might reside there.
    if (!empty($base_hook_info['includes'])) {
      foreach ($base_hook_info['includes'] as $include_file) {
        include_once DRUPAL_ROOT . '/' . $include_file;
      }
    }
    // Replace the preprocess functions with those from the base hook.
    if (isset($base_hook_info['preprocess functions'])) {
      // Set a variable for the 'theme_hook_suggestion'. This is used to
      // maintain backwards compatibility with template engines.
      $theme_hook_suggestion = $hook;
      $info['preprocess functions'] = $base_hook_info['preprocess functions'];
    }
  }
  if (isset($info['preprocess functions'])) {
    foreach ($info['preprocess functions'] as $preprocessor_function) {
      if (function_exists($preprocessor_function)) {
        $preprocessor_function($variables, $hook, $info);
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
    $render_function = 'twig_render_template';
    $extension = '.html.twig';

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
    // they don't already exist. We don't want the overhead of running
    // template_preprocess() twice, so we use the 'directory' variable to
    // determine if it has already run, which while not completely intuitive,
    // is reasonably safe, and allows us to save on the overhead of adding some
    // new variable to track that.
    if (!isset($variables['directory'])) {
      $default_template_variables = array();
      template_preprocess($default_template_variables, $hook, $info);
      $variables += $default_template_variables;
    }
    if (!isset($default_attributes)) {
      $default_attributes = new Attribute();
    }
    foreach (array('attributes', 'title_attributes', 'content_attributes') as $key) {
      if (isset($variables[$key]) && !($variables[$key] instanceof Attribute)) {
        if ($variables[$key]) {
          $variables[$key] = new Attribute($variables[$key]);
        }
        else {
          // Create empty attributes.
          $variables[$key] = clone $default_attributes;
        }
      }
    }

    // Render the output using the template file.
    $template_file = $info['template'] . $extension;
    if (isset($info['path'])) {
      $template_file = $info['path'] . '/' . $template_file;
    }
    // Add the theme suggestions to the variables array just before rendering
    // the template for backwards compatibility with template engines.
    $variables['theme_hook_suggestions'] = $suggestions;
    // For backwards compatibility, pass 'theme_hook_suggestion' on to the
    // template engine. This is only set when calling a direct suggestion like
    // '#theme' => 'menu_tree__shortcut_default' when the template exists in the
    // current theme.
    if (isset($theme_hook_suggestion)) {
      $variables['theme_hook_suggestion'] = $theme_hook_suggestion;
    }
    $output = $render_function($template_file, $variables);
  }

  // restore path_to_theme()
  $theme_path = $temp;
  return (string) $output;
}

/**
 * Returns the path to the current themed element.
 *
 * It can point to the active theme or the module handling a themed
 * implementation. For example, when invoked within the scope of a theming call
 * it will depend on where the theming function is handled. If implemented from
 * a module, it will point to the module. If implemented from the active theme,
 * it will point to the active theme. When called outside the scope of a
 * theming call, it will always point to the active theme.
 */
function path_to_theme() {
  global $theme_path;

  if (!isset($theme_path)) {
    drupal_theme_initialize();
  }

  return $theme_path;
}

/**
 * Allows themes and/or theme engines to discover overridden theme functions.
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
 * Allows themes and/or theme engines to easily discover overridden templates.
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
  $files = file_scan_directory($path, $regex, array('key' => 'filename'));

  // Find templates that implement registered theme hooks and include that in
  // what is returned so that the registry knows that the theme has this
  // implementation.
  foreach ($files as $template => $file) {
    // Ignore sub-theme templates for the current theme.
    if (strpos($file->uri, str_replace($subtheme_paths, '', $file->uri)) !== 0) {
      continue;
    }
    // Remove the extension from the filename.
    $template = str_replace($extension, '', $template);
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
        $template_candidates = array($info['template'], str_replace($info['theme path'] . '/templates/', '', $info['template']));
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
          $file = $match;
          // Remove the extension from the filename.
          $file = str_replace($extension, '', $file);
          // Put the underscores back in for the hook name and register this
          // pattern.
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
 * Retrieves a setting for the current theme or for a given theme.
 *
 * The final setting is obtained from the last value found in the following
 * sources:
 * - the default theme-specific settings defined in any base theme's .info.yml
 *   file
 * - the default theme-specific settings defined in the theme's .info.yml file
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
    // Create a theme settings object.
    $cache[$theme] = new ThemeSettings($theme);

    // Get the values for the theme-specific settings from the .info.yml files
    // of the theme and all its base themes.
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
          $cache[$theme]->mergeData($themes[$theme_key]->info['settings']);
        }
      }
    }

    // Get the global settings from configuration.
    $cache[$theme]->mergeData(\Drupal::config('system.theme.global')->get());

    if ($theme) {
      // Get the saved theme-specific settings from the configuration system.
      $cache[$theme]->mergeData(\Drupal::config($theme . '.settings')->get());

      // If the theme does not support a particular feature, override the global
      // setting and set the value to NULL.
      //$supports = $cache[$theme]->get('supports');
      if (!empty($theme_object->info['features'])) {
        foreach (_system_default_theme_features() as $feature) {
          if (!in_array($feature, $theme_object->info['features'])) {
            $cache[$theme]->set('features.' . $feature, NULL);
          }
        }
      }

      // Generate the path to the logo image.
      if ($cache[$theme]->get('features.logo')) {
        $logo_path = $cache[$theme]->get('logo.path');
        if ($cache[$theme]->get('logo.use_default')) {
          $cache[$theme]->set('logo.url', file_create_url(dirname($theme_object->filename) . '/logo.png'));
        }
        elseif ($logo_path) {
          $cache[$theme]->set('logo.url', file_create_url($logo_path));
        }
      }

      // Generate the path to the favicon.
      if ($cache[$theme]->get('features.favicon')) {
        $favicon_path = $cache[$theme]->get('favicon.path');
        if ($cache[$theme]->get('favicon.use_default')) {
          if (file_exists($favicon = dirname($theme_object->filename) . '/favicon.ico')) {
            $cache[$theme]->set('favicon.url', file_create_url($favicon));
          }
          else {
            $cache[$theme]->set('favicon.url', file_create_url('core/misc/favicon.ico'));
          }
        }
        elseif ($favicon_path) {
          $cache[$theme]->set('favicon.url', file_create_url($favicon_path));
        }
        else {
          $cache[$theme]->set('features.favicon', FALSE);
        }
      }
    }
  }

  return $cache[$theme]->get($setting_name);
}

/**
 * Converts theme settings to configuration.
 *
 * @see system_theme_settings_submit()
 * @see system_update_8054()
 *
 * @param array $theme_settings
 *   An array of theme settings from system setting form or a Drupal 7 variable.
 * @param Config $config
 *   The configuration object to update.
 *
 * @return
 *   The Config object with updated data.
 */
function theme_settings_convert_to_config(array $theme_settings, Config $config) {
  foreach ($theme_settings as $key => $value) {
    if ($key == 'default_logo') {
      $config->set('logo.use_default', $value);
    }
    else if ($key == 'logo_path') {
      $config->set('logo.path', $value);
    }
    else if ($key == 'default_favicon') {
      $config->set('favicon.use_default', $value);
    }
    else if ($key == 'favicon_path') {
      $config->set('favicon.path', $value);
    }
    else if ($key == 'favicon_mimetype') {
      $config->set('favicon.mimetype', $value);
    }
    else if (substr($key, 0, 7) == 'toggle_') {
      $config->set('features.' . drupal_substr($key, 7), $value);
    }
    else if (!in_array($key, array('theme', 'logo_upload'))) {
      $config->set($key, $value);
    }
  }
  return $config;
}

/**
 * Enables a given list of themes.
 *
 * @param $theme_list
 *   An array of theme names.
 */
function theme_enable($theme_list) {
  drupal_clear_css_cache();
  $theme_config = \Drupal::config('system.theme');
  $disabled_themes = \Drupal::config('system.theme.disabled');
  foreach ($theme_list as $key) {
    // Throw an exception if the theme name is too long.
    if (strlen($key) > DRUPAL_EXTENSION_NAME_MAX_LENGTH) {
      throw new ExtensionNameLengthException(format_string('Theme name %name is over the maximum allowed length of @max characters.', array(
        '%name' => $key,
        '@max' => DRUPAL_EXTENSION_NAME_MAX_LENGTH,
      )));
    }

    // The value is not used; the weight is ignored for themes currently.
    $theme_config->set("enabled.$key", 0)->save();
    $disabled_themes->clear($key)->save();

    // Refresh the theme list as config_install_default_config() needs an
    // updated list to work.
    list_themes(TRUE);
    // Install default configuration of the theme.
    config_install_default_config('theme', $key);
  }

  \Drupal::service('router.builder')->rebuild();
  menu_router_rebuild();
  \Drupal::cache('cache')->deleteTags(array('local_task' => 1));
  drupal_theme_rebuild();

  // Invoke hook_themes_enabled() after the themes have been enabled.
  \Drupal::moduleHandler()->invokeAll('themes_enabled', array($theme_list));
}

/**
 * Disables a given list of themes.
 *
 * @param $theme_list
 *   An array of theme names.
 */
function theme_disable($theme_list) {
  // Don't disable the default theme.
  if ($pos = array_search(\Drupal::config('system.theme')->get('default'), $theme_list) !== FALSE) {
    unset($theme_list[$pos]);
    if (empty($theme_list)) {
      return;
    }
  }

  drupal_clear_css_cache();

  $theme_config = \Drupal::config('system.theme');
  $disabled_themes = \Drupal::config('system.theme.disabled');
  foreach ($theme_list as $key) {
    // The value is not used; the weight is ignored for themes currently.
    $theme_config->clear("enabled.$key");
    $disabled_themes->set($key, 0);
  }
  $theme_config->save();
  $disabled_themes->save();

  list_themes(TRUE);
  \Drupal::service('router.builder')->rebuild();
  menu_router_rebuild();
  \Drupal::cache('cache')->deleteTags(array('local_task' => 1));
  drupal_theme_rebuild();

  // Invoke hook_themes_disabled after the themes have been disabled.
  \Drupal::moduleHandler()->invokeAll('themes_disabled', array($theme_list));
}

/**
 * @addtogroup themeable
 * @{
 */

/**
 * Preprocess variables for theme_datetime().
 *
 * @param array $variables
 *   An associative array possibly containing:
 *   - attributes['timestamp']:
 *   - timestamp:
 *   - text:
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

  // Add a 'datetime' class.
  $variables['attributes']['class'][] = 'datetime';

  $variables['attributes'] = new Attribute($variables['attributes']);
}

/**
 * Prepares variables for status message templates.
 *
 * Default template: status-messages.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - display: (optional) May have a value of 'status' or 'error' when only
 *     displaying messages of that specific type.
 */
function template_preprocess_status_messages(&$variables) {
  $variables['message_list'] = drupal_get_messages($variables['display']);
  $variables['status_headings'] = array(
    'status' => t('Status message'),
    'error' => t('Error message'),
    'warning' => t('Warning message'),
  );
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
 *       will not be passed through
 *       \Drupal\Component\Utility\String::checkPlain().
 *     - attributes: (optional) Attributes for the anchor, or for the <span>
 *       tag used in its place if no 'href' is supplied. If element 'class' is
 *       included, it must be an array of one or more class names.
 *     If the 'href' element is supplied, the entire link array is passed to
 *     l() as its $options parameter.
 *   - attributes: A keyed array of attributes for the UL containing the
 *     list of links.
 *   - heading: (optional) A heading to precede the links. May be an
 *     associative array or a string. If it's an array, it can have the
 *     following elements:
 *     - text: The heading text.
 *     - level: The heading level (e.g. 'h2', 'h3').
 *     - class: (optional) An array of the CSS classes for the heading.
 *     When using a string it will be used as the text of the heading and the
 *     level will default to 'h2'. Headings should be used on navigation menus
 *     and any list of links that consistently appears on multiple pages. To
 *     make the heading invisible use the 'visually-hidden' CSS class. Do not
 *     use 'display:none', which removes it from screen-readers and assistive
 *     technology. Headings allow screen-reader and keyboard only users to
 *     navigate to or skip the links. See
 *     http://juicystudio.com/article/screen-readers-display-none.php and
 *     http://www.w3.org/TR/WCAG-TECHS/H42.html for more information.
 */
function theme_links($variables) {
  $language_url = language(Language::TYPE_URL);

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

      $output .= '<' . $heading['level'] . new Attribute($heading['attributes']) . '>';
      $output .= String::checkPlain($heading['text']);
      $output .= '</' . $heading['level'] . '>';
    }

    $output .= '<ul' . new Attribute($attributes) . '>';

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

      $link += array(
        'href' => NULL,
      );

      // Handle links.
      if (isset($link['href']) || isset($link['route_name'])) {
        $is_current_path = ($link['href'] == current_path() || ($link['href'] == '<front>' && drupal_is_front_page()));
        $is_current_language = (empty($link['language']) || $link['language']->id == $language_url->id);
        if ($is_current_path && $is_current_language) {
          $class[] = 'active';
        }
        // @todo Reconcile Views usage of 'ajax' as a boolean with the rest of
        //   core's usage of it as an array.
        if (isset($link['ajax']) && is_array($link['ajax'])) {
          // To attach Ajax behavior, render a link element, rather than just
          // call l().
          $link_element = array(
            '#type' => 'link',
            '#title' => $link['title'],
            '#href' => $link['href'],
            '#ajax' => $link['ajax'],
            '#options' => array_diff_key($link, drupal_map_assoc(array('title', 'href', 'ajax'))),
          );
          $item = drupal_render($link_element);
        }
        else {
          // @todo theme_links() should *really* use the same parameters as l(),
          //   and just take an array of '#type' => 'link' elements, see
          //   https://drupal.org/node/2102777.
          // Pass in $link as $options, as they share the same keys.
          if (isset($link['href'])) {
            $item = l($link['title'], $link['href'], $link);
          }
          else {
            $link += array('route_parameters' => array());
            $item = \Drupal::l($link['title'], $link['route_name'], $link['route_parameters'], $link);
          }
        }
      }
      // Handle title-only text items.
      else {
        // Merge in default array properties into $link.
        $link += array(
          'html' => FALSE,
        );
        $item = ($link['html'] ? $link['title'] : String::checkPlain($link['title']));
        if (isset($link['attributes'])) {
          $item = '<span' . new Attribute($link['attributes']) . '>' . $item . '</span>';
        }
      }

      $output .= '<li' . new Attribute(array('class' => $class)) . '>';
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
 *     accessibility requirements, so it is strongly encouraged for code
 *     calling theme('image') to pass a meaningful value for this variable.
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

  return '<img' . new Attribute($attributes) . ' />';
}

/**
 * #pre_render callback to transform children of an element into #rows suitable for theme_table().
 *
 * This function converts sub-elements of an element of #type 'table' to be
 * suitable for theme_table():
 * - The first level of sub-elements are table rows. Only the #attributes
 *   property is taken into account.
 * - The second level of sub-elements is converted into columns for the
 *   corresponding first-level table row.
 *
 * Simple example usage:
 * @code
 * $form['table'] = array(
 *   '#type' => 'table',
 *   '#header' => array(t('Title'), array('data' => t('Operations'), 'colspan' => '1')),
 *   // Optionally, to add tableDrag support:
 *   '#tabledrag' => array(
 *     array('order', 'sibling', 'thing-weight'),
 *   ),
 * );
 * foreach ($things as $row => $thing) {
 *   $form['table'][$row]['#weight'] = $thing['weight'];
 *
 *   $form['table'][$row]['title'] = array(
 *     '#type' => 'textfield',
 *     '#default_value' => $thing['title'],
 *   );
 *
 *   // Optionally, to add tableDrag support:
 *   $form['table'][$row]['#attributes']['class'][] = 'draggable';
 *   $form['table'][$row]['weight'] = array(
 *     '#type' => 'textfield',
 *     '#title' => t('Weight for @title', array('@title' => $thing['title'])),
 *     '#title_display' => 'invisible',
 *     '#size' => 4,
 *     '#default_value' => $thing['weight'],
 *     '#attributes' => array('class' => array('thing-weight')),
 *   );
 *
 *   // The amount of link columns should be identical to the 'colspan'
 *   // attribute in #header above.
 *   $form['table'][$row]['edit'] = array(
 *     '#type' => 'link',
 *     '#title' => t('Edit'),
 *     '#href' => 'thing/' . $row . '/edit',
 *   );
 * }
 * @endcode
 *
 * @param array $element
 *   A structured array containing two sub-levels of elements. Properties used:
 *   - #tabledrag: The value is a list of arrays that are passed to
 *     drupal_add_tabledrag(). The HTML ID of the table is prepended to each set
 *     of arguments.
 *
 * @see system_element_info()
 * @see theme_table()
 * @see drupal_process_attached()
 * @see drupal_add_tabledrag()
 */
function drupal_pre_render_table(array $element) {
  foreach (element_children($element) as $first) {
    $row = array('data' => array());
    // Apply attributes of first-level elements as table row attributes.
    if (isset($element[$first]['#attributes'])) {
      $row += $element[$first]['#attributes'];
    }
    // Turn second-level elements into table row columns.
    // @todo Do not render a cell for children of #type 'value'.
    // @see http://drupal.org/node/1248940
    foreach (element_children($element[$first]) as $second) {
      // Assign the element by reference, so any potential changes to the
      // original element are taken over.
      $column = array('data' => &$element[$first][$second]);

      // Apply wrapper attributes of second-level elements as table cell
      // attributes.
      if (isset($element[$first][$second]['#wrapper_attributes'])) {
        $column += $element[$first][$second]['#wrapper_attributes'];
      }

      $row['data'][] = $column;
    }
    $element['#rows'][] = $row;
  }

  // Take over $element['#id'] as HTML ID attribute, if not already set.
  element_set_attributes($element, array('id'));

  // If the custom #tabledrag is set and there is a HTML ID, inject the table's
  // HTML ID as first callback argument and attach the behavior.
  if (!empty($element['#tabledrag']) && isset($element['#attributes']['id'])) {
    foreach ($element['#tabledrag'] as &$args) {
      array_unshift($args, $element['#attributes']['id']);
    }
    $element['#attached']['drupal_add_tabledrag'] = $element['#tabledrag'];
  }

  return $element;
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
 *     - "sort": A default sort order for this column ("asc" or "desc"). Only
 *        one column should be given a default sort order because table sorting
 *        only applies to one column at a time.
 *     - "class": An array of values for the 'class' attribute. In particular,
 *        the least important columns that can be hidden on narrow and medium
 *        width screens should have a 'priority-low' class, referenced with the
 *        RESPONSIVE_PRIORITY_LOW constant. Columns that should be shown on
 *        medium+ wide screens should be marked up with a class of
 *        'priority-medium', referenced by with the RESPONSIVE_PRIORITY_MEDIUM
 *        constant. Themes may hide columns with one of these two classes on
 *        narrow viewports to save horizontal space.
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
  $responsive = $variables['responsive'];
  $empty = $variables['empty'];

  // Add sticky headers, if applicable.
  if (count($header) && $sticky) {
    drupal_add_library('system', 'drupal.tableheader');
    // Add 'sticky-enabled' class to the table to identify it for JS.
    // This is needed to target tables constructed by this function.
    $attributes['class'][] = 'sticky-enabled';
  }
  // If the table has headers and it should react responsively to columns hidden
  // with the classes represented by the constants RESPONSIVE_PRIORITY_MEDIUM
  // and RESPONSIVE_PRIORITY_LOW, add the tableresponsive behaviors.
  if (count($header) && $responsive) {
    drupal_add_library('system', 'drupal.tableresponsive');
    // Add 'responsive-enabled' class to the table to identify it for JS.
    // This is needed to target tables constructed by this function.
    $attributes['class'][] = 'responsive-enabled';
  }

  $output = '<table' . new Attribute($attributes) . ">\n";

  if (isset($caption)) {
    $output .= '<caption>' . $caption . "</caption>\n";
  }

  // Format the table columns:
  if (count($colgroups)) {
    foreach ($colgroups as $colgroup) {
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
        $output .= ' <colgroup' . new Attribute($attributes) . '>';
        foreach ($cols as $col) {
          $output .= ' <col' . new Attribute($col) . ' />';
        }
        $output .= " </colgroup>\n";
      }
      else {
        $output .= ' <colgroup' . new Attribute($attributes) . " />\n";
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

  $responsive = array();
  // Format the table header:
  if (count($header)) {
    $ts = tablesort_init($header);
    // HTML requires that the thead tag has tr tags in it followed by tbody
    // tags. Using ternary operator to check and see if we have any rows.
    $output .= (count($rows) ? ' <thead><tr>' : ' <tr>');
    $i = 0;
    foreach ($header as $cell) {
      $i++;
      // Track responsive classes for each column as needed. Only the header
      // cells for a column are marked up with the responsive classes by a
      // module developer or themer. The responsive classes on the header cells
      // must be transferred to the content cells.
      if (!empty($cell['class']) && is_array($cell['class'])) {
        if (in_array(RESPONSIVE_PRIORITY_MEDIUM, $cell['class'])) {
          $responsive[$i] =  RESPONSIVE_PRIORITY_MEDIUM;
        }
        elseif (in_array(RESPONSIVE_PRIORITY_LOW, $cell['class'])) {
          $responsive[$i] =  RESPONSIVE_PRIORITY_LOW;
        }
      }
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
    foreach ($rows as $row) {
      // Check if we're dealing with a simple or complex row
      if (isset($row['data'])) {
        $cells = $row['data'];
        $no_striping = isset($row['no_striping']) ? $row['no_striping'] : FALSE;

        // Set the attributes array and exclude 'data' and 'no_striping'.
        $attributes = $row;
        unset($attributes['data']);
        unset($attributes['no_striping']);
      }
      else {
        $cells = $row;
        $attributes = array();
        $no_striping = FALSE;
      }
      if (count($cells)) {
        // Add odd/even class
        if (!$no_striping) {
          $class = $flip[$class];
          $attributes['class'][] = $class;
        }

        // Build row
        $output .= ' <tr' . new Attribute($attributes) . '>';
        $i = 0;
        foreach ($cells as $cell) {
          $i++;
          // Add active class if needed for sortable tables.
          $cell = tablesort_cell($cell, $header, $ts, $i);
          // Copy RESPONSIVE_PRIORITY_LOW/RESPONSIVE_PRIORITY_MEDIUM
          // class from header to cell as needed.
          if (isset($responsive[$i])) {
            if (is_array($cell)) {
              $cell['class'][] = $responsive[$i];
            }
            else {
              $cell = array('data' => $cell, 'class' => $responsive[$i]);
            }
          }
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
 * Prepares variables for tablesort indicator templates.
 *
 * Default template: tablesort-indicator.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - style: Set to either 'asc' or 'desc'. This determines which icon to show.
 */
function template_preprocess_tablesort_indicator(&$variables) {
  // Provide the image attributes for an ascending or descending image.
  if ($variables['style'] == 'asc') {
    $variables['uri'] = 'core/misc/arrow-asc.png';
    $alt_title = t('sort ascending');
  }
  else {
    $variables['uri'] = 'core/misc/arrow-desc.png';
    $alt_title = t('sort descending');
  }

  // Add the image element.
  $variables['image'] = array(
    '#theme' => 'image',
    '#uri' => $variables['uri'],
    '#width' => 13,
    '#height' => 13,
    '#alt' => $alt_title,
    '#title' => $alt_title,
  );
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
  $type = $variables['status'];
  global $user;
  if ($user->isAuthenticated()) {
    if ($type == MARK_NEW) {
      return ' <span class="marker">' . t('new') . '</span>';
    }
    elseif ($type == MARK_UPDATED) {
      return ' <span class="marker">' . t('updated') . '</span>';
    }
  }
}

/**
 * Preprocesses variables for theme_item_list().
 *
 * @param array $variables
 *   An associative array containing theme variables for theme_item_list().
 *   'items' in variables will be preprocessed to automatically inherit the
 *   variables of this list to any possibly contained nested lists that do not
 *   specify custom render properties. This allows callers to specify larger
 *   nested lists, without having to explicitly specify and repeat the render
 *   properties for all nested child lists.
 */
function template_preprocess_item_list(&$variables) {
  foreach ($variables['items'] as &$item) {
    // If the item value is an array, then it is a render array.
    if (is_array($item)) {
      // Determine whether there are any child elements in the item that are not
      // fully-specified render arrays. If there are any, then the child
      // elements present nested lists and we automatically inherit the render
      // array properties of the current list to them.
      foreach (element_children($item) as $key) {
        $child = &$item[$key];
        // If this child element does not specify how it can be rendered, then
        // we need to inherit the render properties of the current list.
        if (!isset($child['#type']) && !isset($child['#theme']) && !isset($child['#markup'])) {
          // Since theme_item_list() supports both strings and render arrays as
          // items, the items of the nested list may have been specified as the
          // child elements of the nested list, instead of #items. For
          // convenience, we automatically move them into #items.
          if (!isset($child['#items'])) {
            // This is the same condition as in element_children(), which cannot
            // be used here, since it triggers an error on string values.
            foreach ($child as $child_key => $child_value) {
              if ($child_key[0] !== '#') {
                $child['#items'][$child_key] = $child_value;
                unset($child[$child_key]);
              }
            }
          }
          // Lastly, inherit the original theme variables of the current list.
          $child['#theme'] = $variables['theme_hook_original'];
          $child['#list_type'] = $variables['list_type'];
        }
      }
    }
  }
}

/**
 * Returns HTML for a list or nested list of items.
 *
 * @param $variables
 *   An associative array containing:
 *   - items: A list of items to render. Allowed values are strings or
 *     render arrays. Render arrays can specify list item attributes in the
 *     #wrapper_attributes property.
 *   - title: The title of the list.
 *   - list_type: The type of HTML list (e.g. "ul", "ol").
 *   - attributes: The attributes applied to the list element.
 *   - empty: A message to display when there are no items. Allowed value is a
 *     string or render array.
 */
function theme_item_list($variables) {
  $items = $variables['items'];
  $title = (string) $variables['title'];
  $list_type = $variables['list_type'];
  $list_attributes = $variables['attributes'];

  $output = '';
  if ($items) {
    $output .= '<' . $list_type . new Attribute($list_attributes) . '>';

    $num_items = count($items);
    $i = 0;
    foreach ($items as &$item) {
      $i++;
      $attributes = array();
      if (is_array($item)) {
        if (isset($item['#wrapper_attributes'])) {
          $attributes = $item['#wrapper_attributes'];
        }
        $item = drupal_render($item);
      }
      $attributes['class'][] = ($i % 2 ? 'odd' : 'even');
      if ($i == 1) {
        $attributes['class'][] = 'first';
      }
      if ($i == $num_items) {
        $attributes['class'][] = 'last';
      }
      $output .= '<li' . new Attribute($attributes) . '>' . $item . '</li>';
    }
    $output .= "</$list_type>";
  }
  elseif (!empty($variables['empty'])) {
    $output .= render($variables['empty']);
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
 * Prepares variables for feed icon templates.
 *
 * Default template: feed-icon.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - url: An internal system path or a fully qualified external URL of the
 *     feed.
 *   - title: A descriptive title of the feed.
 */
function template_preprocess_feed_icon(&$variables) {
  $text = t('Subscribe to !feed-title', array('!feed-title' => $variables['title']));
  $variables['icon'] = array(
    '#theme' => 'image__feed_icon',
    '#uri' => 'core/misc/feed.png',
    '#width' => 16,
    '#height' => 16,
    '#alt' => $text,
  );
  $variables['attributes']['class'] = array('feed-icon');
  // Stripping tags because that's what l() used to do.
  $variables['attributes']['title'] = strip_tags($text);
}

/**
 * Returns HTML for a "more" link, like those used in blocks.
 *
 * @param $variables
 *   An associative array containing:
 *   - url: The URL of the main page.
 *   - title: A descriptive verb for the link, like 'Read more'.
 */
function theme_more_link($variables) {
  return '<div class="more-link">' . l(t('More'), $variables['url'], array('attributes' => array('title' => $variables['title']))) . '</div>';
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
 * Returns HTML to wrap child elements in a container.
 *
 * Used for grouped form items. Can also be used as a #theme_wrapper for any
 * renderable element, to surround it with a <div> and add attributes such as
 * classes or an HTML id.
 *
 * @param $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties of the element.
 *     Properties used: #id, #attributes, #children.
 *
 * @ingroup themeable
 */
function theme_container($variables) {
  $element = $variables['element'];
  // Ensure #attributes is set.
  $element += array('#attributes' => array());

  // Special handling for form elements.
  if (isset($element['#array_parents'])) {
    // Assign an html ID.
    if (!isset($element['#attributes']['id'])) {
      $element['#attributes']['id'] = $element['#id'];
    }
    // Add the 'form-wrapper' class.
    $element['#attributes']['class'][] = 'form-wrapper';
  }

  return '<div' . new Attribute($element['#attributes']) . '>' . $element['#children'] . '</div>';
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
    $attributes = new Attribute($cell);
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
 * Adds a default set of helper variables for preprocessors and templates.
 *
 * This function is called for theme hooks implemented as templates only, not
 * for theme hooks implemented as functions. This preprocess function is the
 * first in the sequence of preprocessing functions that are called when
 * preparing variables for a template. See theme() for more details about the
 * full sequence.
 *
 * @see theme()
 */
function template_preprocess(&$variables, $hook, $info) {
  // Tell all templates where they are located.
  $variables['directory'] = path_to_theme();

  // Merge in variables that don't depend on hook and don't change during a
  // single page request.
  // Use the advanced drupal_static() pattern, since this is called very often.
  static $drupal_static_fast;
  if (!isset($drupal_static_fast)) {
    $drupal_static_fast['default_variables'] = &drupal_static(__FUNCTION__);
  }
  $default_variables = &$drupal_static_fast['default_variables'];
  if (!isset($default_variables)) {
    $default_variables = _template_preprocess_default_variables();
  }
  $variables += $default_variables;

  // When theming a render element, merge its #attributes into
  // $variables['attributes'].
  if (isset($info['render element'])) {
    $key = $info['render element'];
    if (isset($variables[$key]['#attributes'])) {
      $variables['attributes'] = NestedArray::mergeDeep($variables['attributes'], $variables[$key]['#attributes']);
    }
  }
}

/**
 * Returns hook-independent variables to template_preprocess().
 */
function _template_preprocess_default_variables() {
  // Variables that don't depend on a database connection.
  $variables = array(
    'attributes' => array(),
    'title_attributes' => array(),
    'content_attributes' => array(),
    'title_prefix' => array(),
    'title_suffix' => array(),
    'db_is_active' => !defined('MAINTENANCE_MODE'),
    'is_admin' => FALSE,
    'logged_in' => FALSE,
  );

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

  // Give modules a chance to alter the default template variables.
  drupal_alter('template_preprocess_default_variables', $variables);

  return $variables;
}

/**
 * Prepares variables for HTML document templates.
 *
 * Default template: html.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - page: A render element representing the page.
 *
 * @see system_elements()
 */
function template_preprocess_html(&$variables) {
  $language_interface = language(Language::TYPE_INTERFACE);

  // Compile a list of classes that are going to be applied to the body element.
  // This allows advanced theming based on context (home page, node of certain type, etc.).
  $variables['attributes']['class'][] = 'html';
  // Add a class that tells us whether we're on the front page or not.
  $variables['attributes']['class'][] = $variables['is_front'] ? 'front' : 'not-front';
  // Add a class that tells us whether the page is viewed by an authenticated user or not.
  $variables['attributes']['class'][] = $variables['logged_in'] ? 'logged-in' : 'not-logged-in';

  // Populate the body classes.
  if ($suggestions = theme_get_suggestions(arg(), 'page', '-')) {
    foreach ($suggestions as $suggestion) {
      if ($suggestion != 'page-front') {
        // Add current suggestion to page classes to make it possible to theme
        // the page depending on the current page type (e.g. node, admin, user,
        // etc.) as well as more specific data like node-12 or node-edit.
        $variables['attributes']['class'][] = drupal_html_class($suggestion);
      }
    }
  }

  // Initializes attributes which are specific to the html and body elements.
  $variables['html_attributes'] = new Attribute;

  // HTML element attributes.
  $variables['html_attributes']['lang'] = $language_interface->id;
  $variables['html_attributes']['dir'] = $language_interface->direction ? 'rtl' : 'ltr';

  // Add favicon.
  if (theme_get_setting('features.favicon')) {
    $favicon = theme_get_setting('favicon.url');
    $type = theme_get_setting('favicon.mimetype');
    drupal_add_html_head_link(array('rel' => 'shortcut icon', 'href' => Url::stripDangerousProtocols($favicon), 'type' => $type));
  }

  $site_config = \Drupal::config('system.site');
  // Construct page title.
  if (isset($variables['page']['#title'])) {
    $head_title = array(
      'title' => strip_tags($variables['page']['#title']),
      'name' => String::checkPlain($site_config->get('name')),
    );
  }
  elseif (drupal_get_title()) {
    $head_title = array(
      'title' => strip_tags(drupal_get_title()),
      'name' => String::checkPlain($site_config->get('name')),
    );
  }
  else {
    $head_title = array('name' => String::checkPlain($site_config->get('name')));
    if ($site_config->get('slogan')) {
      $head_title['slogan'] = strip_tags(filter_xss_admin($site_config->get('slogan')));
    }
  }

  $variables['head_title_array'] = $head_title;
  $variables['head_title'] = implode(' | ', $head_title);

  // Display the html.html.twig's default mobile metatags for responsive design.
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
  );
  foreach ($elements as $name => $element) {
    drupal_add_html_head($element, $name);
  }

  drupal_add_library('system', 'html5shiv', TRUE);

  // Render page_top and page_bottom into top level variables.
  $variables['page_top'] = array();
  if (isset($variables['page']['page_top'])) {
    $variables['page_top'] = drupal_render($variables['page']['page_top']);
  }
  $variables['page_bottom'] = array();
  if (isset($variables['page']['page_bottom'])) {
    $variables['page_bottom'][]['#markup'] = drupal_render($variables['page']['page_bottom']);
  }

  // Add footer scripts as '#markup' so they can be rendered with other
  // elements in page_bottom.
  $footer_scripts = new RenderWrapper('drupal_get_js', array('footer'));
  $variables['page_bottom'][] = array('#markup' => $footer_scripts);

  // Wrap function calls in an object so they can be called when printed.
  $variables['head'] = new RenderWrapper('drupal_get_html_head');
  $variables['styles'] = new RenderWrapper('drupal_get_css');
  $variables['scripts'] = new RenderWrapper('drupal_get_js');
}

/**
 * Prepares variables for the page template.
 *
 * Default template: page.html.twig.
 *
 * Most themes utilize their own copy of page.html.twig. The default is located
 * inside "modules/system/page.html.twig". Look in there for the full list of
 * variables.
 *
 * Uses the arg() function to generate a series of page template suggestions
 * based on the current path.
 *
 * Any changes to variables in this preprocessor should also be changed inside
 * template_preprocess_maintenance_page() to keep all of them consistent.
 *
 * @see drupal_render_page()
 */
function template_preprocess_page(&$variables) {
  $language_interface = language(Language::TYPE_INTERFACE);
  $site_config = \Drupal::config('system.site');

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
  $variables['logo']              = theme_get_setting('logo.url');
  $variables['main_menu']         = theme_get_setting('features.main_menu') ? menu_main_menu() : array();
  $variables['secondary_menu']    = theme_get_setting('features.secondary_menu') ? menu_secondary_menu() : array();
  $variables['action_links']      = menu_get_local_actions();
  $variables['site_name']         = (theme_get_setting('features.name') ? String::checkPlain($site_config->get('name')) : '');
  $variables['site_slogan']       = (theme_get_setting('features.slogan') ? filter_xss_admin($site_config->get('slogan')) : '');
  $variables['tabs']              = menu_local_tabs();

  if (isset($variables['page']['#title'])) {
    $variables['title'] = $variables['page']['#title'];
  }
  else {
    $variables['title'] = new RenderWrapper('drupal_get_title');
  }

  // Pass the main menu and secondary menu to the template as render arrays.
  if (!empty($variables['main_menu'])) {
    $variables['main_menu'] = array(
      '#theme' =>'links__system_main_menu',
      '#links' => $variables['main_menu'],
      '#heading' => array(
        'text' => t('Main menu'),
        'class' => array('visually-hidden'),
      )
    );
  }
  if (!empty($variables['secondary_menu'])) {
    $variables['secondary_menu'] = array(
      '#theme' =>'links__system_secondary_menu',
      '#links' => $variables['secondary_menu'],
      '#heading' => array(
        'text' => t('Secondary menu'),
        'class' => array('visually-hidden'),
      )
    );
  }

  if ($node = menu_get_object()) {
    $variables['node'] = $node;
  }

  // Prepare render array for messages. drupal_get_messages() is called later,
  // when this variable is rendered in a theme function or template file.
  $variables['messages'] = array(
    '#theme' => 'status_messages',
    '#access' => $variables['show_messages'],
  );

  // Set the breadcrumb last, so as to increase the chance of being able to
  // re-use the cache of an already retrieved menu containing the active link
  // for the current page.
  // @see menu_tree_page_data()
  $variables['breadcrumb'] = array(
    '#theme' => 'breadcrumb',
    '#breadcrumb' => \Drupal::service('breadcrumb')->build(\Drupal::request()->attributes->all()),
  );
}

/**
 * Generate an array of suggestions from path arguments.
 *
 * This is typically called for adding to the suggestions in
 * hook_theme_suggestions_HOOK_alter() or adding to 'attributes' class key
 * variables from within preprocess functions, when wanting to base the
 * additional suggestions or classes on the path of the current page.
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
 *   hook_theme_suggestions_HOOK_alter() or to $variables['attributes']['class']
 *   if the suggestions represent extra CSS classes.
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
 * Prepare variables for maintenance page templates.
 *
 * Default template: maintenance-page.html.twig.
 *
 * The variables array generated here is a mirror of
 * template_preprocess_page(). This preprocessor will run its course when
 * theme_maintenance_page() is invoked. An alternate template file of
 * maintenance-page--offline.html.twig can be used when the database is offline
 * to hide errors and completely replace the content.
 *
 * @param array $variables
 *   An associative array containing:
 *   - content - An array of page content.
 */
function template_preprocess_maintenance_page(&$variables) {
  global $theme;
  $language_interface = language(Language::TYPE_INTERFACE);
  // Retrieve the theme data to list all available regions.
  $theme_data = list_themes();
  $regions = $theme_data[$theme]->info['regions'];

  // Initializes attributes which are specific to the html element.
  $variables['html_attributes'] = new Attribute;

  // HTML element attributes.
  $variables['html_attributes']['lang'] = $language_interface->id;
  $variables['html_attributes']['dir'] = $language_interface->direction ? 'rtl' : 'ltr';

  // Add favicon
  if (theme_get_setting('features.favicon')) {
    $favicon = theme_get_setting('favicon.url');
    $type = theme_get_setting('favicon.mimetype');
    drupal_add_html_head_link(array('rel' => 'shortcut icon', 'href' => Url::stripDangerousProtocols($favicon), 'type' => $type));
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

  $site_config = \Drupal::config('system.site');
  $site_name = $site_config->get('name');
  $site_slogan = $site_config->get('slogan');

   // Construct the page title.
  if (isset($variables['page']['#title'])) {
    $head_title = array(
      'title' => strip_tags($variables['page']['#title']),
      'name' => String::checkPlain($site_config->get('name')),
    );
  }
  elseif (drupal_get_title()) {
    $head_title = array(
      'title' => strip_tags(drupal_get_title()),
      'name' => String::checkPlain($site_config->get('name')),
    );
  }
  else {
    $head_title = array('name' => String::checkPlain($site_name));
    if ($site_slogan) {
      $head_title['slogan'] = strip_tags(filter_xss_admin($site_slogan));
    }
  }
  $path = drupal_get_path('module', 'system');

  // These are usually added from system_page_build() except maintenance.css.
  // When the database is inactive it's not called so we add it here.
  $default_css = array(
    '#attached' => array(
      'css' => array(
        $path . '/css/system.module.css',
        $path . '/css/system.theme.css',
        $path . '/css/system.admin.css',
        $path . '/css/system.maintenance.css',
      ),
    ),
  );
  drupal_render($default_css);

  $variables['head_title_array']  = $head_title;
  $variables['head_title']        = implode(' | ', $head_title);
  $variables['base_path']         = base_path();
  $variables['front_page']        = url();
  $variables['breadcrumb']        = '';
  $variables['feed_icons']        = '';
  $variables['help']              = '';
  $variables['language']          = $language_interface;
  $variables['language']->dir     = $language_interface->direction ? 'rtl' : 'ltr';
  $variables['logo']              = theme_get_setting('logo.url');
  $variables['messages']          = $variables['show_messages'] ? theme('status_messages') : '';
  $variables['main_menu']         = array();
  $variables['secondary_menu']    = array();
  $variables['site_name']         = (theme_get_setting('features.name') ? String::checkPlain($site_name) : '');
  $variables['site_slogan']       = (theme_get_setting('features.slogan') ? filter_xss_admin($site_slogan) : '');
  $variables['tabs']              = '';

  // Compile a list of classes that are going to be applied to the body element.
  $variables['attributes']['class'][] = 'maintenance-page';
  $variables['attributes']['class'][] = 'in-maintenance';
  if (isset($variables['db_is_active']) && !$variables['db_is_active']) {
    $variables['attributes']['class'][] = 'db-offline';
  }
  if ($variables['layout'] == 'both') {
    $variables['attributes']['class'][] = 'two-sidebars';
  }
  elseif ($variables['layout'] == 'none') {
    $variables['attributes']['class'][] = 'no-sidebars';
  }
  else {
    $variables['attributes']['class'][] = 'one-sidebar';
    $variables['attributes']['class'][] = 'sidebar-' . $variables['layout'];
  }

  $variables['head'] = drupal_get_html_head();

  // While this code is used in the installer, the language module may not be
  // enabled yet (even maybe no database set up yet), but an RTL language
  // selected should result in RTL stylesheets loaded properly already.
  $css = drupal_add_css();
  include_once DRUPAL_ROOT . '/core/modules/language/language.module';
  // Wrapping drupal_get_css() and drupal_get_js() in an object so they can
  // be called when printed.
  $variables['styles'] = new RenderWrapper('drupal_get_css', array($css));
  $variables['scripts'] = new RenderWrapper('drupal_get_js');

  // Allow the page to define a title.
  if (isset($variables['page']['#title'])) {
    $variables['title'] = $variables['page']['#title'];
  }
  if (!isset($variables['title'])) {
    $variables['title'] = drupal_get_title();
  }
}

/**
 * Prepares variables for install page templates.
 *
 * Default template: install-page.html.twig.
 *
 * The variables array generated here is a mirror of
 * template_preprocess_page(). This preprocessor will run its course when
 * theme_install_page() is invoked.
 *
 * @param array $variables
 *   An associative array containing:
 *   - content - An array of page content.
 *
 * @see template_preprocess_maintenance_page()
 *
 */
function template_preprocess_install_page(&$variables) {
  template_preprocess_maintenance_page($variables);
  // Override the site name that is displayed on the page, since Drupal is
  // still in the process of being installed.
  $variables['site_name'] = drupal_install_profile_distribution_name();
}

/**
 * Prepares variables for region templates.
 *
 * Default template: region.html.twig.
 *
 * Prepares the values passed to the theme_region function to be passed into a
 * pluggable template engine. Uses the region name to generate a template file
 * suggestions.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing properties of the region.
 */
function template_preprocess_region(&$variables) {
  // Create the $content variable that templates expect.
  $variables['content'] = $variables['elements']['#children'];
  $variables['region'] = $variables['elements']['#region'];

  $variables['attributes']['class'][] = 'region';
  $variables['attributes']['class'][] = drupal_html_class('region-' . $variables['region']);
}

/**
 * Provides theme registration for themes across .inc files.
 */
function drupal_common_theme() {
  return array(
    // From theme.inc.
    'html' => array(
      'render element' => 'page',
      'template' => 'html',
    ),
    'page' => array(
      'render element' => 'page',
      'template' => 'page',
    ),
    'region' => array(
      'render element' => 'elements',
      'template' => 'region',
    ),
    'datetime' => array(
      'variables' => array('timestamp' => NULL, 'text' => NULL, 'attributes' => array(), 'html' => FALSE),
      'template' => 'datetime',
    ),
    'status_messages' => array(
      'variables' => array('display' => NULL),
      'template' => 'status-messages',
    ),
    'links' => array(
      'variables' => array('links' => array(), 'attributes' => array('class' => array('links')), 'heading' => array()),
    ),
    'dropbutton_wrapper' => array(
      'variables' => array('children' => NULL),
      'template' => 'dropbutton-wrapper',
    ),
    'image' => array(
      // HTML 4 and XHTML 1.0 always require an alt attribute. The HTML 5 draft
      // allows the alt attribute to be omitted in some cases. Therefore,
      // default the alt attribute to an empty string, but allow code calling
      // theme('image') to pass explicit NULL for it to be omitted. Usually,
      // neither omission nor an empty string satisfies accessibility
      // requirements, so it is strongly encouraged for code calling
      // theme('image') to pass a meaningful value for the alt variable.
      // - http://www.w3.org/TR/REC-html40/struct/objects.html#h-13.8
      // - http://www.w3.org/TR/xhtml1/dtds.html
      // - http://dev.w3.org/html5/spec/Overview.html#alt
      // The title attribute is optional in all cases, so it is omitted by
      // default.
      'variables' => array('uri' => NULL, 'width' => NULL, 'height' => NULL, 'alt' => '', 'title' => NULL, 'attributes' => array()),
    ),
    'breadcrumb' => array(
      'variables' => array('breadcrumb' => NULL),
      'template' => 'breadcrumb',
    ),
    'table' => array(
      'variables' => array('header' => NULL, 'rows' => NULL, 'attributes' => array(), 'caption' => NULL, 'colgroups' => array(), 'sticky' => FALSE, 'responsive' => TRUE, 'empty' => ''),
    ),
    'tablesort_indicator' => array(
      'variables' => array('style' => NULL),
      'template' => 'tablesort-indicator',
    ),
    'mark' => array(
      'variables' => array('status' => MARK_NEW),
    ),
    'item_list' => array(
      'variables' => array('items' => array(), 'title' => '', 'list_type' => 'ul', 'attributes' => array(), 'empty' => NULL),
    ),
    'feed_icon' => array(
      'variables' => array('url' => NULL, 'title' => NULL),
      'template' => 'feed-icon',
    ),
    'more_link' => array(
      'variables' => array('url' => NULL, 'title' => NULL)
    ),
    'progress_bar' => array(
      'variables' => array('label' => NULL, 'percent' => NULL, 'message' => NULL),
      'template' => 'progress-bar',
    ),
    'indentation' => array(
      'variables' => array('size' => 1),
    ),
    // From theme.maintenance.inc.
    'maintenance_page' => array(
      'variables' => array('content' => NULL, 'show_messages' => TRUE),
      'template' => 'maintenance-page',
    ),
    'install_page' => array(
      'variables' => array('content' => NULL, 'show_messages' => TRUE),
      'template' => 'install-page',
    ),
    'task_list' => array(
      'variables' => array('items' => NULL, 'active' => NULL,  'variant' => NULL),
    ),
    'authorize_message' => array(
      'variables' => array('message' => NULL, 'success' => TRUE),
    ),
    'authorize_report' => array(
      'variables' => array('messages' => array()),
    ),
    // From pager.inc.
    'pager' => array(
      'variables' => array('tags' => array(), 'element' => 0, 'parameters' => array(), 'quantity' => 9),
      'template' => 'pager',
    ),
    // From menu.inc.
    'menu_link' => array(
      'render element' => 'element',
    ),
    'menu_tree' => array(
      'render element' => 'tree',
    ),
    'menu_local_task' => array(
      'render element' => 'element',
    ),
    'menu_local_action' => array(
      'render element' => 'element',
    ),
    'menu_local_tasks' => array(
      'variables' => array('primary' => array(), 'secondary' => array()),
    ),
    // From form.inc.
    'input' => array(
      'render element' => 'element',
    ),
    'select' => array(
      'render element' => 'element',
    ),
    'fieldset' => array(
      'render element' => 'element',
    ),
    'details' => array(
      'render element' => 'element',
    ),
    'radios' => array(
      'render element' => 'element',
    ),
    'date' => array(
      'render element' => 'element',
    ),
    'exposed_filters' => array(
      'render element' => 'form',
    ),
    'checkboxes' => array(
      'render element' => 'element',
    ),
    'form' => array(
      'render element' => 'element',
    ),
    'textarea' => array(
      'render element' => 'element',
    ),
    'tableselect' => array(
      'render element' => 'element',
    ),
    'form_element' => array(
      'render element' => 'element',
    ),
    'form_required_marker' => array(
      'render element' => 'element',
    ),
    'form_element_label' => array(
      'render element' => 'element',
    ),
    'vertical_tabs' => array(
      'render element' => 'element',
    ),
    'container' => array(
      'render element' => 'element',
    ),
  );
}
