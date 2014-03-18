<?php

/**
 * @file
 * The theme system, which controls the output of Drupal.
 *
 * The theme system allows for nearly all output of the Drupal system to be
 * customized by user themes.
 */

use Drupal\Component\Utility\String;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\Config;
use Drupal\Core\Language\Language;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ExtensionNameLengthException;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Template\RenderWrapper;
use Drupal\Core\Theme\ThemeSettings;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Render\Element;

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
 * @param string|\Drupal\Core\Extension\Extension $theme
 *   Either the name of a theme or a full theme object.
 *
 * @return bool
 *   Boolean TRUE if the theme is enabled or is the site administration theme;
 *   FALSE otherwise.
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal::service('access_check.theme')->checkAccess().
 *
 * @see \Drupal\Core\Theme\ThemeAccessCheck::checkAccess().
 */
function drupal_theme_access($theme) {
  if ($theme instanceof Extension) {
    $theme = $theme->getName();
  }
  return \Drupal::service('access_check.theme')->checkAccess($theme);
}

/**
 * Initializes the theme system by loading the theme.
 */
function drupal_theme_initialize() {
  global $theme, $theme_key;

  // If $theme is already set, assume the others are set, too, and do nothing
  if (isset($theme)) {
    return;
  }

  $themes = list_themes();

  // @todo Let the theme.negotiator listen to the kernel request event.
  // Determine the active theme for the theme negotiator service. This includes
  // the default theme as well as really specific ones like the ajax base theme.
  $request = \Drupal::request();
  $theme = \Drupal::service('theme.negotiator')->determineActiveTheme($request) ?: 'stark';

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
}

/**
 * Initializes the theme system given already loaded information.
 *
 * This function is useful to initialize a theme when no database is present.
 *
 * @param \Drupal\Core\Extension\Extension $theme
 *   The theme extension object.
 * @param \Drupal\Core\Extension\Extension[] $base_theme
 *    An optional array of objects that represent the 'base theme' if the
 *    theme is meant to be derivative of another theme. It requires
 *    the same information as the $theme object. It should be in
 *    'oldest first' order, meaning the top level of the chain will
 *    be first.
 */
function _drupal_theme_initialize($theme, $base_theme = array()) {
  global $theme_info, $base_theme_info, $theme_engine, $theme_path;
  $theme_info = $theme;
  $base_theme_info = $base_theme;

  $theme_path = $theme->getPath();

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
    $base_theme_path = $base->getPath();
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

  // And now add the stylesheets properly.
  $css = array();
  foreach ($final_stylesheets as $media => $stylesheets) {
    foreach ($stylesheets as $stylesheet) {
      $css['#attached']['css'][$stylesheet] = array(
        'group' => CSS_AGGREGATE_THEME,
        'every_page' => TRUE,
        'media' => $media
      );
    }
  }
  drupal_render($css);

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
  $js = array();
  foreach ($final_scripts as $script) {
    $js['#attached']['js'][] = array(
      'data' => $script,
      'group' => JS_THEME,
      'every_page' => TRUE,
    );
  }
  drupal_render($js);

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
  $theme_registry = \Drupal::service('theme.registry');
  if ($complete) {
    return $theme_registry->get();
  }
  else {
    return $theme_registry->getRuntime();
  }
}

/**
 * Forces the system to rebuild the theme registry.
 *
 * This function should be called when modules are added to the system, or when
 * a dynamic system needs to add more theme hooks.
 */
function drupal_theme_rebuild() {
  \Drupal::service('theme.registry')->reset();
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
 * @return array
 *   An associative array of the currently available themes.
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal::service('theme_handler')->listInfo().
 *
 * @see \Drupal\Core\Extension\ThemeHandler::listInfo().
 */
function list_themes($refresh = FALSE) {
  /** @var \Drupal\Core\Extension\ThemeHandler $theme_handler */
  $theme_handler = \Drupal::service('theme_handler');

  if ($refresh) {
    $theme_handler->reset();
    system_list_reset();
  }

  return $theme_handler->listInfo();
}

/**
 * Generates themed output (internal use only).
 *
 * _theme() is an internal function. Do not call this function directly as it
 * will prevent the following items from working correctly:
 * - Render caching.
 * - JavaScript and CSS asset attachment.
 * - Pre / post render hooks.
 * - Defaults provided by hook_element_info(), including attached assets.
 * Instead, build a render array with a #theme key, and either return the
 * array (where possible) or call drupal_render() to convert it to HTML.
 *
 * All requests for themed output must go through this function, which is
 * invoked as part of the @link theme_render drupal_render() process @endlink.
 * The appropriate theme function is indicated by the #theme property
 * of a renderable array. _theme() examines the request and routes it to the
 * appropriate @link themeable theme function or template @endlink, by checking
 * the theme registry.
 *
 * @param $hook
 *   The name of the theme hook to call. If the name contains a
 *   double-underscore ('__') and there isn't an implementation for the full
 *   name, the part before the '__' is checked. This allows a fallback to a
 *   more generic implementation. For example, if _theme('links__node', ...) is
 *   called, but there is no implementation of that theme hook, then the
 *   'links' implementation is used. This process is iterative, so if
 *   _theme('links__contextual__node', ...) is called, _theme() checks for the
 *   following implementations, and uses the first one that exists:
 *   - links__contextual__node
 *   - links__contextual
 *   - links
 *   This allows themes to create specific theme implementations for named
 *   objects and contexts of otherwise generic theme hooks. The $hook parameter
 *   may also be an array, in which case the first theme hook that has an
 *   implementation is used. This allows for the code that calls _theme() to
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
function _theme($hook, $variables = array()) {
  static $default_attributes;
  // If called before all modules are loaded, we do not necessarily have a full
  // theme registry to work with, and therefore cannot process the theme
  // request properly. See also \Drupal\Core\Theme\Registry::get().
  if (!\Drupal::moduleHandler()->isLoaded() && !defined('MAINTENANCE_MODE')) {
    throw new Exception(t('_theme() may not be called until all modules are loaded.'));
  }

  /** @var \Drupal\Core\Utility\ThemeRegistry $theme_registry */
  $theme_registry = \Drupal::service('theme.registry')->getRuntime();

  // If an array of hook candidates were passed, use the first one that has an
  // implementation.
  if (is_array($hook)) {
    foreach ($hook as $candidate) {
      if ($theme_registry->has($candidate)) {
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
  if (!$theme_registry->has($hook)) {
    // Iteratively strip everything after the last '__' delimiter, until an
    // implementation is found.
    while ($pos = strrpos($hook, '__')) {
      $hook = substr($hook, 0, $pos);
      if ($theme_registry->has($hook)) {
        break;
      }
    }
    if (!$theme_registry->has($hook)) {
      // Only log a message when not trying theme suggestions ($hook being an
      // array).
      if (!isset($candidate)) {
        watchdog('theme', 'Theme hook %hook not found.', array('%hook' => $hook), WATCHDOG_WARNING);
      }
      // There is no theme implementation for the hook passed. Return FALSE so
      // the function calling _theme() can differentiate between a hook that
      // exists and renders an empty string and a hook that is not implemented.
      return FALSE;
    }
  }

  $info = $theme_registry->get($hook);
  global $theme_path;
  $temp = $theme_path;
  // point path_to_theme() to the currently used theme path:
  $theme_path = $info['theme path'];


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
  // If _theme() was invoked with a direct theme suggestion like
  // '#theme' => 'node__article', add it to the suggestions array before
  // invoking suggestion alter hooks.
  if (isset($info['base hook'])) {
    $suggestions[] = $hook;
  }

  // Invoke hook_theme_suggestions_alter() and
  // hook_theme_suggestions_HOOK_alter().
  $hooks = array(
    'theme_suggestions',
    'theme_suggestions_' . $base_theme_hook,
  );
  \Drupal::moduleHandler()->alter($hooks, $suggestions, $variables, $base_theme_hook);

  // Check if each suggestion exists in the theme registry, and if so,
  // use it instead of the hook that _theme() was called with. For example, a
  // function may call _theme('node', ...), but a module can add
  // 'node__article' as a suggestion via hook_theme_suggestions_HOOK_alter(),
  // enabling a theme to have an alternate template file for article nodes.
  foreach (array_reverse($suggestions) as $suggestion) {
    if ($theme_registry->has($suggestion)) {
      $info = $theme_registry->get($suggestion);
      break;
    }
  }

  // Include a file if the theme function or variable preprocessor is held
  // elsewhere.
  if (!empty($info['includes'])) {
    foreach ($info['includes'] as $include_file) {
      include_once DRUPAL_ROOT . '/' . $include_file;
    }
  }

  // Invoke the variable preprocessors, if any.
  if (isset($info['base hook'])) {
    $base_hook = $info['base hook'];
    $base_hook_info = $theme_registry->get($base_hook);
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
      $theme_paths[$theme_info->base_theme][$theme_info->getName()] = $theme_info->getPath();
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
          $cache[$theme]->merge($themes[$theme_key]->info['settings']);
        }
      }
    }

    // Get the global settings from configuration.
    $cache[$theme]->merge(\Drupal::config('system.theme.global')->get());

    if ($theme) {
      // Get the saved theme-specific settings from the configuration system.
      $cache[$theme]->merge(\Drupal::config($theme . '.settings')->get());

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
          $cache[$theme]->set('logo.url', file_create_url($theme_object->getPath() . '/logo.png'));
        }
        elseif ($logo_path) {
          $cache[$theme]->set('logo.url', file_create_url($logo_path));
        }
      }

      // Generate the path to the favicon.
      if ($cache[$theme]->get('features.favicon')) {
        $favicon_path = $cache[$theme]->get('favicon.path');
        if ($cache[$theme]->get('favicon.use_default')) {
          if (file_exists($favicon = $theme_object->getPath() . '/favicon.ico')) {
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
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal::service('theme_handler')->enable().
 *
 * @see \Drupal\Core\Extension\ThemeHandler::enable().
 */
function theme_enable($theme_list) {
  \Drupal::service('theme_handler')->enable($theme_list);
}

/**
 * Disables a given list of themes.
 *
 * @param $theme_list
 *   An array of theme names.
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal::service('theme_handler')->disable().
 *
 * @see \Drupal\Core\Extension\ThemeHandler::disable().
 */
function theme_disable($theme_list) {
  \Drupal::service('theme_handler')->disable($theme_list);
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
 * Prepares variables for links templates.
 *
 * Default template: links.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - links: An associative array of links to be themed. The key for each link
 *     is used as its CSS class. Each link should be itself an array, with the
 *     following elements:
 *     - title: The link text.
 *     - route_name: (optional) The name of the route to link to. If omitted
 *       (and if 'href' is omitted as well), the 'title' is shown as
 *       a plain text item in the links list.
 *     - route_parameters: (optional) An array of route parameters for the link.
 *     - href: (optional) The link URL. It is preferred to use 'route_name' and
 *       'route parameters' for internal links. Use 'href' for links to external
 *       URLs. If omitted (and if 'route_name' is omitted as well), the 'title'
 *       is shown as a plain text item in the links list.
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
 *   - set_active_class: (optional) Whether each link should compare the
 *     route_name + route_parameters or href (path), language and query options
 *     to the current URL, to determine whether the link is "active". If so, an
 *     "active" class will be applied to the list item containing the link, as
 *     well as the link itself. It is important to use this sparingly since it
 *     is usually unnecessary and requires extra processing.
 *     For anonymous users, the "active" class will be calculated on the server,
 *     because most sites serve each anonymous user the same cached page anyway.
 *     For authenticated users, the "active" class will be calculated on the
 *     client (through JavaScript), only data- attributes are added to list
 *     items and contained links, to prevent breaking the render cache. The
 *     JavaScript is added in system_page_build().
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
 *
 * Unfortunately links templates duplicate the "active" class handling of l()
 * and LinkGenerator::generate() because it needs to be able to set the "active"
 * class not on the links themselves ("a" tags), but on the list items ("li"
 * tags) that contain the links. This is necessary for CSS to be able to style
 * list items differently when the link is active, since CSS does not yet allow
 * one to style list items only if it contains a certain element with a certain
 * class. I.e. we cannot yet convert this jQuery selector to a CSS selector:
 *   jQuery('li:has("a.active")')
 *
 * @see l()
 * @see \Drupal\Core\Utility\LinkGenerator::generate()
 * @see system_page_build()
 */
function template_preprocess_links(&$variables) {
  $links = $variables['links'];
  $heading = &$variables['heading'];

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
      // Convert the attributes array into an Attribute object.
      $heading['attributes'] = new Attribute($heading['attributes']);
      $heading['text'] = String::checkPlain($heading['text']);
    }

    $variables['links'] = array();
    foreach ($links as $key => $link) {
      $item = array();
      $link += array(
        'href' => NULL,
        'route_name' => NULL,
        'route_parameters' => NULL,
        'ajax' => NULL,
      );

      $li_attributes = array('class' => array());
      // Use the array key as class name.
      $li_attributes['class'][] = drupal_html_class($key);

      $keys = array('title', 'href', 'route_name', 'route_parameters');
      $link_element = array(
        '#type' => 'link',
        '#title' => $link['title'],
        '#options' => array_diff_key($link, array_combine($keys, $keys)),
        '#href' => $link['href'],
        '#route_name' => $link['route_name'],
        '#route_parameters' => $link['route_parameters'],
        '#ajax' => $link['ajax'],
      );

      // Handle links and ensure that the active class is added on the LIs, but
      // only if the 'set_active_class' option is not empty.
      if (isset($link['href']) || isset($link['route_name'])) {
        if (!empty($variables['set_active_class'])) {

          // Also enable set_active_class for the contained link.
          $link_element['#options']['set_active_class'] = TRUE;

          if (!empty($link['language'])) {
            $li_attributes['hreflang'] = $link['language']->id;
          }

          // Add a "data-drupal-link-query" attribute to let the
          // drupal.active-link library know the query in a standardized manner.
          if (!empty($link['query'])) {
            $query = $link['query'];
            ksort($query);
            $li_attributes['data-drupal-link-query'] = Json::encode($query);
          }

          if (isset($link['route_name'])) {
            $path = \Drupal::service('url_generator')->getPathFromRoute($link['route_name'], $link['route_parameters']);
          }
          else {
            $path = $link['href'];
          }

          // Add a "data-drupal-link-system-path" attribute to let the
          // drupal.active-link library know the path in a standardized manner.
          $li_attributes['data-drupal-link-system-path'] = \Drupal::service('path.alias_manager.cached')->getSystemPath($path);
        }

        $item['link'] = $link_element;
      }

      // Handle title-only text items.
      $text = (!empty($link['html']) ? $link['title'] : String::checkPlain($link['title']));
      $item['text'] = $text;
      if (isset($link['attributes'])) {
        $item['text_attributes'] = new Attribute($link['attributes']);
      }

      // Handle list item attributes.
      $item['attributes'] = new Attribute($li_attributes);

      // Add the item to the list of links.
      $variables['links'][] = $item;
    }
  }
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
 *     calling _theme('image') to pass a meaningful value for this variable.
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
 *     array(
 *       'action' => 'order',
 *       'relationship' => 'sibling',
 *       'group' => 'thing-weight',
 *     ),
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
 *   - #tabledrag: The value is a list of $options arrays that are passed to
 *     drupal_attach_tabledrag(). The HTML ID of the table is added to each
 *     $options array.
 *
 * @see system_element_info()
 * @see theme_table()
 * @see drupal_process_attached()
 * @see drupal_attach_tabledrag()
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


  // Add sticky headers, if applicable.
  if (count($element['#header']) && $element['#sticky']) {
    $element['#attached']['library'][] = 'core/drupal.tableheader';
    // Add 'sticky-enabled' class to the table to identify it for JS.
    // This is needed to target tables constructed by this function.
    $element['#attributes']['class'][] = 'sticky-enabled';
  }
  // If the table has headers and it should react responsively to columns hidden
  // with the classes represented by the constants RESPONSIVE_PRIORITY_MEDIUM
  // and RESPONSIVE_PRIORITY_LOW, add the tableresponsive behaviors.
  if (count($element['#header']) && $element['#responsive']) {
    $element['#attached']['library'][] = 'core/drupal.tableresponsive';
    // Add 'responsive-enabled' class to the table to identify it for JS.
    // This is needed to target tables constructed by this function.
    $element['#attributes']['class'][] = 'responsive-enabled';
  }

  // If the custom #tabledrag is set and there is a HTML ID, add the table's
  // HTML ID to the options and attach the behavior.
  if (!empty($element['#tabledrag']) && isset($element['#attributes']['id'])) {
    foreach ($element['#tabledrag'] as $options) {
      $options['table_id'] = $element['#attributes']['id'];
      drupal_attach_tabledrag($element, $options);
    }
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
 * Returns HTML for a marker for new or updated content.
 *
 * @param $variables
 *   An associative array containing:
 *   - type: Number representing the marker type to display. See MARK_NEW,
 *     MARK_UPDATED, MARK_READ.
 */
function theme_mark($variables) {
  $type = $variables['status'];
  if (\Drupal::currentUser()->isAuthenticated()) {
    if ($type == MARK_NEW) {
      return ' <span class="marker">' . t('new') . '</span>';
    }
    elseif ($type == MARK_UPDATED) {
      return ' <span class="marker">' . t('updated') . '</span>';
    }
  }
}

/**
 * Prepares variables for item list templates.
 *
 * Default template: item-list.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - items: An array of items to be displayed in the list. Each item can be
 *     either a string or a render array. If #type, #theme, or #markup
 *     properties are not specified for child render arrays, they will be
 *     inherited from the parent list, allowing callers to specify larger
 *     nested lists without having to explicitly specify and repeat the
 *     render properties for all nested child lists.
 *   - title: A title to be prepended to the list.
 *   - list_type: The type of list to return (e.g. "ul", "ol").
 *
 * @see http://drupal.org/node/1842756
 */
function template_preprocess_item_list(&$variables) {
  $variables['title'] = (string) $variables['title'];

  foreach ($variables['items'] as &$item) {
    $attributes = array();
    // If the item value is an array, then it is a render array.
    if (is_array($item)) {
      // List items support attributes via the '#wrapper_attributes' property.
      if (isset($item['#wrapper_attributes'])) {
        $attributes = $item['#wrapper_attributes'];
      }
      // Determine whether there are any child elements in the item that are not
      // fully-specified render arrays. If there are any, then the child
      // elements present nested lists and we automatically inherit the render
      // array properties of the current list to them.
      foreach (Element::children($item) as $key) {
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

    // Set the item's value and attributes for the template.
    $item = array(
      'value' => $item,
      'attributes' => new Attribute($attributes),
    );
  }
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
 * Prepares variables for container templates.
 *
 * Default template: container.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties of the element.
 *     Properties used: #id, #attributes, #children.
 */
function template_preprocess_container(&$variables) {
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

  $variables['children'] = $element['#children'];
  $variables['attributes'] = $element['#attributes'];
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
 * preparing variables for a template. See _theme() for more details about the
 * full sequence.
 *
 * @see _theme()
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
  \Drupal::moduleHandler()->alter('template_preprocess_default_variables', $variables);

  return $variables;
}

/**
 * #pre_render callback for the html element type.
 *
 * @param array $element
 *   A structured array containing the html element type build properties.
 *
 * @see system_element_info()
 */
function drupal_pre_render_html(array $element) {
  // Add favicon.
  if (theme_get_setting('features.favicon')) {
    $favicon = theme_get_setting('favicon.url');
    $type = theme_get_setting('favicon.mimetype');
    $element['#attached']['drupal_add_html_head_link'][][] = array(
      'rel' => 'shortcut icon',
      'href' => UrlHelper::stripDangerousProtocols($favicon),
      'type' => $type,
    );
  }

  return $element;
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
  /** @var $page \Drupal\Core\Page\HtmlPage */
  $page = $variables['page_object'];

  $variables['html_attributes'] = $page->getHtmlAttributes();
  $variables['attributes'] = $page->getBodyAttributes();
  $variables['page'] = $page->getContent();

  // Compile a list of classes that are going to be applied to the body element.
  // This allows advanced theming based on context (home page, node of certain type, etc.).
  $body_classes = $variables['attributes']['class'];
  $body_classes[] = 'html';
  // Add a class that tells us whether we're on the front page or not.
  $body_classes[] = $variables['is_front'] ? 'front' : 'not-front';
  // Add a class that tells us whether the page is viewed by an authenticated user or not.
  $body_classes[] = $variables['logged_in'] ? 'logged-in' : 'not-logged-in';
  $variables['attributes']['class'] = $body_classes;

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

  $site_config = \Drupal::config('system.site');
  // Construct page title.
  if ($page->hasTitle()) {
    $head_title = array(
      'title' => strip_tags($page->getTitle()),
      'name' => String::checkPlain($site_config->get('name')),
    );
  }
  // @todo Remove once views is not bypassing the view subscriber anymore.
  //   @see http://drupal.org/node/2068471
  elseif (drupal_is_front_page()) {
    $head_title = array(
      'title' => t('Home'),
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

  $variables['page_top'][] = array('#markup' => $page->getBodyTop());
  $variables['page_bottom'][] = array('#markup' => $page->getBodyBottom());

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
 * @see drupal_render_page()
 */
function template_preprocess_page(&$variables) {
  $language_interface = \Drupal::languageManager()->getCurrentLanguage();
  $site_config = \Drupal::config('system.site');

  // Move some variables to the top level for themer convenience and template cleanliness.
  $variables['show_messages'] = $variables['page']['#show_messages'];
  $variables['title'] = $variables['page']['#title'];

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

  // Pass the main menu and secondary menu to the template as render arrays.
  if (!empty($variables['main_menu'])) {
    $variables['main_menu'] = array(
      '#theme' =>'links__system_main_menu',
      '#links' => $variables['main_menu'],
      '#heading' => array(
        'text' => t('Main menu'),
        'class' => array('visually-hidden'),
      ),
      '#set_active_class' => TRUE,
    );
  }
  if (!empty($variables['secondary_menu'])) {
    $variables['secondary_menu'] = array(
      '#theme' =>'links__system_secondary_menu',
      '#links' => $variables['secondary_menu'],
      '#heading' => array(
        'text' => t('Secondary menu'),
        'class' => array('visually-hidden'),
      ),
      '#set_active_class' => TRUE,
    );
  }

  if ($node = \Drupal::request()->attributes->get('node')) {
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
 *
 * @see system_page_build()
 */
function template_preprocess_maintenance_page(&$variables) {
  $language_interface = \Drupal::languageManager()->getCurrentLanguage();

  // Initializes attributes which are specific to the html element.
  $variables['html_attributes'] = new Attribute;

  // HTML element attributes.
  $variables['html_attributes']['lang'] = $language_interface->id;
  $variables['html_attributes']['dir'] = $language_interface->direction ? 'rtl' : 'ltr';

  // Add favicon
  if (theme_get_setting('features.favicon')) {
    $favicon = theme_get_setting('favicon.url');
    $type = theme_get_setting('favicon.mimetype');
    $build['#attached']['drupal_add_html_head_link'][][] = array(
      'rel' => 'shortcut icon',
      'href' => UrlHelper::stripDangerousProtocols($favicon),
      'type' => $type,
    );
    drupal_render($build);
  }

  foreach (system_region_list($GLOBALS['theme']) as $region_key => $region_name) {
    if (!isset($variables[$region_key])) {
      $variables[$region_key] = array();
    }
    // Append region content set with drupal_add_region_content() as markup.
    if ($region_content = drupal_get_region_content($region_key)) {
      $variables[$region_key][]['#markup'] = $region_content;
    }
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
  else {
    $head_title = array('name' => String::checkPlain($site_name));
    if ($site_slogan) {
      $head_title['slogan'] = strip_tags(filter_xss_admin($site_slogan));
    }
  }

  // These are usually added from system_page_build() except maintenance.css.
  // When the database is inactive it's not called so we add it here.
  $default_css['library'][] = 'core/normalize';
  $default_css['library'][] = 'system/maintenance';
  $attached = array('#attached' => $default_css);
  drupal_render($attached);
  $variables['messages'] = array(
    '#theme' => 'status_messages',
    '#access' => $variables['show_messages'],
  );

  $variables['head_title_array']  = $head_title;
  $variables['head_title']        = implode(' | ', $head_title);
  $variables['front_page']        = url();
  $variables['help']              = '';
  $variables['language']          = $language_interface;
  $variables['logo']              = theme_get_setting('logo.url');
  $variables['site_name']         = (theme_get_setting('features.name') ? String::checkPlain($site_name) : '');
  $variables['site_slogan']       = (theme_get_setting('features.slogan') ? filter_xss_admin($site_slogan) : '');

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
  $css = _drupal_add_css();
  include_once DRUPAL_ROOT . '/core/modules/language/language.module';
  // Wrapping drupal_get_css() and drupal_get_js() in an object so they can
  // be called when printed.
  $variables['styles'] = new RenderWrapper('drupal_get_css', array($css));
  $variables['scripts'] = new RenderWrapper('drupal_get_js');

  // Allow the page to define a title.
  if (isset($variables['page']['#title'])) {
    $variables['title'] = $variables['page']['#title'];
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
  $variables['attributes']['class'][] = 'install-page';
  // Override the site name that is displayed on the page, since Drupal is
  // still in the process of being installed.
  $distribution_name = String::checkPlain(drupal_install_profile_distribution_name());
  $variables['site_name'] = $distribution_name;
  $variables['head_title_array']['name'] = $distribution_name;

  $variables['head_title'] = implode(' | ', $variables['head_title_array']);
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
      'variables' => array('page_object' => NULL),
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
      'variables' => array('links' => array(), 'attributes' => array('class' => array('links')), 'heading' => array(), 'set_active_class' => FALSE),
      'template' => 'links',
    ),
    'dropbutton_wrapper' => array(
      'variables' => array('children' => NULL),
      'template' => 'dropbutton-wrapper',
    ),
    'image' => array(
      // HTML 4 and XHTML 1.0 always require an alt attribute. The HTML 5 draft
      // allows the alt attribute to be omitted in some cases. Therefore,
      // default the alt attribute to an empty string, but allow code calling
      // _theme('image') to pass explicit NULL for it to be omitted. Usually,
      // neither omission nor an empty string satisfies accessibility
      // requirements, so it is strongly encouraged for code calling
      // _theme('image') to pass a meaningful value for the alt variable.
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
      'template' => 'item-list',
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
      'variables' => array('content' => NULL, 'show_messages' => TRUE, 'page' => array()),
      'template' => 'maintenance-page',
    ),
    'install_page' => array(
      'variables' => array('content' => NULL, 'show_messages' => TRUE, 'page' => array()),
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
      'template' => 'select',
    ),
    'fieldset' => array(
      'render element' => 'element',
      'template' => 'fieldset',
    ),
    'details' => array(
      'render element' => 'element',
      'template' => 'details',
    ),
    'radios' => array(
      'render element' => 'element',
    ),
    'date' => array(
      'render element' => 'element',
    ),
    'checkboxes' => array(
      'render element' => 'element',
    ),
    'form' => array(
      'render element' => 'element',
      'template' => 'form',
    ),
    'textarea' => array(
      'render element' => 'element',
      'template' => 'textarea',
    ),
    'tableselect' => array(
      'render element' => 'element',
    ),
    'form_element' => array(
      'render element' => 'element',
      'template' => 'form-element',
    ),
    'form_required_marker' => array(
      'render element' => 'element',
    ),
    'form_element_label' => array(
      'render element' => 'element',
    ),
    'vertical_tabs' => array(
      'render element' => 'element',
      'template' => 'vertical-tabs',
    ),
    'container' => array(
      'render element' => 'element',
      'template' => 'container',
    ),
  );
}
