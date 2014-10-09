<?php

/**
 * @file
 * The theme system, which controls the output of Drupal.
 *
 * The theme system allows for nearly all output of the Drupal system to be
 * customized by user themes.
 */

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\String;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\StorageException;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ExtensionNameLengthException;
use Drupal\Core\Page\FeedLinkElement;
use Drupal\Core\Page\LinkElement;
use Drupal\Core\Page\MetaElement;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Theme\ThemeSettings;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Render\Element;
use Symfony\Component\HttpFoundation\Request;

/**
 * @defgroup content_flags Content markers
 * @{
 * Markers used by mark.html.twig and node_mark() to designate content.
 *
 * @see mark.html.twig
 * @see node_mark()
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
 *   Boolean TRUE if the theme is installed or is the site administration theme;
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

  $module_handler = \Drupal::moduleHandler();
  $active_theme = \Drupal::theme()->getActiveTheme();

  // If called before all modules are loaded, we do not necessarily have a full
  // theme registry to work with, and therefore cannot process the theme
  // request properly. See also \Drupal\Core\Theme\Registry::get().
  if (!$module_handler->isLoaded() && !defined('MAINTENANCE_MODE')) {
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
        \Drupal::logger('theme')->warning('Theme hook %hook not found.', array('%hook' => $hook));
      }
      // There is no theme implementation for the hook passed. Return FALSE so
      // the function calling _theme() can differentiate between a hook that
      // exists and renders an empty string and a hook that is not implemented.
      return FALSE;
    }
  }

  $info = $theme_registry->get($hook);

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
  $suggestions = $module_handler->invokeAll('theme_suggestions_' . $base_theme_hook, array($variables));
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
  $module_handler->alter($hooks, $suggestions, $variables, $base_theme_hook);
  \Drupal::theme()->alter($hooks, $suggestions, $variables, $base_theme_hook);

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
    // Allow theme preprocess functions to set $variables['#attached'] and use
    // it like the #attached property on render arrays. In Drupal 8, this is the
    // (only) officially supported method of attaching assets from preprocess
    // functions. Assets attached here should be associated with the template
    // that we're preprocessing variables for.
    if (isset($variables['#attached'])) {
      $preprocess_attached = ['#attached' => $variables['#attached']];
      drupal_render($preprocess_attached, TRUE);
    }
  }

  // Generate the output using either a function or a template.
  $output = '';
  if (isset($info['function'])) {
    if (function_exists($info['function'])) {
      $output = SafeMarkup::set($info['function']($variables));
    }
  }
  else {
    $render_function = 'twig_render_template';
    $extension = '.html.twig';

    // The theme engine may use a different extension and a different renderer.
    $theme_engine = $active_theme->getEngine();
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
    // '#theme' => 'menu__shortcut_default' when the template exists in the
    // current theme.
    if (isset($theme_hook_suggestion)) {
      $variables['theme_hook_suggestion'] = $theme_hook_suggestion;
    }
    $output = $render_function($template_file, $variables);
  }

  return (string) $output;
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
  $theme = \Drupal::theme()->getActiveTheme()->getName();
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
    $theme = \Drupal::theme()->getActiveTheme()->getName();
  }

  if (empty($cache[$theme])) {
    // Create a theme settings object.
    $cache[$theme] = new ThemeSettings($theme);

    // Get the values for the theme-specific settings from the .info.yml files
    // of the theme and all its base themes.
    $themes = list_themes();
    if ($theme && isset($themes[$theme])) {
      $theme_object = $themes[$theme];

      // Create a list which includes the current theme and all its base themes.
      if (isset($theme_object->base_themes)) {
        $theme_keys = array_keys($theme_object->base_themes);
        $theme_keys[] = $theme;
      }
      else {
        $theme_keys = array($theme);
      }
      // Read hard-coded default settings from the theme info files.
      foreach ($theme_keys as $theme_key) {
        if (!empty($themes[$theme_key]->info['settings'])) {
          $cache[$theme]->merge($themes[$theme_key]->info['settings']);
        }
      }
    }

    // Get the global settings from configuration.
    $cache[$theme]->merge(\Drupal::config('system.theme.global')->get());

    if ($theme && isset($themes[$theme])) {
      // Retrieve configured theme-specific settings, if any.
      try {
        if ($theme_settings = \Drupal::config($theme . '.settings')->get()) {
          $cache[$theme]->merge($theme_settings);
        }
      }
      catch (StorageException $e) {
      }

      // If the theme does not support a particular feature, override the global
      // setting and set the value to NULL.
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
 * @addtogroup themeable
 * @{
 */

/**
 * Prepares variables for time templates.
 *
 * Default template: time.html.twig.
 *
 * @param array $variables
 *   An associative array possibly containing:
 *   - attributes['timestamp']:
 *   - timestamp:
 *   - text:
 */
function template_preprocess_time(&$variables) {
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
 * Prepares variables for datetime form element templates.
 *
 * The datetime form element serves as a wrapper around the date element type,
 * which creates a date and a time component for a date.
 *
 * Default template: datetime-form.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties of the element.
 *     Properties used: #title, #value, #options, #description, #required,
 *     #attributes.
 *
 * @see form_process_datetime()
 */
function template_preprocess_datetime_form(&$variables) {
  $element = $variables['element'];

  $variables['attributes'] = array();
  if (isset($element['#id'])) {
    $variables['attributes']['id'] = $element['#id'];
  }
  if (!empty($element['#attributes']['class'])) {
    $variables['attributes']['class'] = (array) $element['#attributes']['class'];
  }

  $variables['content'] = $element;
}

/**
 * Prepares variables for datetime form wrapper templates.
 *
 * Default template: datetime-wrapper.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties of the element.
 *     Properties used: #title, #children, #required, #attributes.
 */
function template_preprocess_datetime_wrapper(&$variables) {
  $element = $variables['element'];

  if (!empty($element['#title'])) {
    $variables['title'] = $element['#title'];
  }

  if (!empty($element['#description'])) {
    $variables['description'] = $element['#description'];
  }

  $variables['required'] = FALSE;
  // For required datetime fields a 'form-required' class is appended to the
  // label attributes.
  if (!empty($element['#required'])) {
    $variables['required'] = TRUE;
  }
  $variables['content'] = $element['#children'];
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
 *     - url: (optional) The url object to link to. If omitted, no a tag is
 *       printed out.
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
 *     - attributes: (optional) An array of the CSS attributes for the heading.
 *     When using a string it will be used as the text of the heading and the
 *     level will default to 'h2'. Headings should be used on navigation menus
 *     and any list of links that consistently appears on multiple pages. To
 *     make the heading invisible use the 'visually-hidden' CSS class. Do not
 *     use 'display:none', which removes it from screen readers and assistive
 *     technology. Headings allow screen reader and keyboard only users to
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
 * @see \Drupal\Core\Utility\LinkGenerator
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
      // Convert the attributes array into an Attribute object.
      $heading['attributes'] = new Attribute($heading['attributes']);
      $heading['text'] = String::checkPlain($heading['text']);
    }

    $variables['links'] = array();
    foreach ($links as $key => $link) {
      $item = array();
      $link += array(
        'ajax' => NULL,
        'url' => NULL,
      );

      $li_attributes = array('class' => array());
      // Use the array key as class name.
      $li_attributes['class'][] = drupal_html_class($key);

      $keys = ['title', 'url'];
      $link_element = array(
        '#type' => 'link',
        '#title' => $link['title'],
        '#options' => array_diff_key($link, array_combine($keys, $keys)),
        '#url' => $link['url'],
        '#ajax' => $link['ajax'],
      );

      // Handle links and ensure that the active class is added on the LIs, but
      // only if the 'set_active_class' option is not empty.
      if (isset($link['url'])) {
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

          /** @var \Drupal\Core\Url $url */
          $url = $link['url'];
          if ($url->isRouted()) {
            // Add a "data-drupal-link-system-path" attribute to let the
            // drupal.active-link library know the path in a standardized manner.
            $system_path = $url->getInternalPath();
            // @todo System path is deprecated - use the route name and parameters.
            // Special case for the front page.
            $li_attributes['data-drupal-link-system-path'] = $system_path == '' ? '<front>' : $system_path;
          }
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
 * Prepares variables for image templates.
 *
 * Default template: image.html.twig.
 *
 * @param array $variables
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
 *   - srcset: Array of multiple URIs and sizes/multipliers.
 *   - sizes: The sizes attribute for viewport-based selection of images.
 *     - http://www.whatwg.org/specs/web-apps/current-work/multipage/embedded-content.html#introduction-3:viewport-based-selection-2
 */
function template_preprocess_image(&$variables) {
  if (!empty($variables['uri'])) {
    $variables['attributes']['src'] = file_create_url($variables['uri']);
  }
  // Generate a srcset attribute conforming to the spec at
  // http://www.w3.org/html/wg/drafts/html/master/embedded-content.html#attr-img-srcset
  if (!empty($variables['srcset'])) {
    $srcset = array();
    foreach ($variables['srcset'] as $src) {
      // URI is mandatory.
      $source = file_create_url($src['uri']);
      if (isset($src['width']) && !empty($src['width'])) {
        $source .= ' ' . $src['width'];
      }
      elseif (isset($src['multiplier']) && !empty($src['multiplier'])) {
        $source .= ' ' . $src['multiplier'];
      }
      $srcset[] = $source;
    }
    $variables['attributes']['srcset'] = implode(', ', $srcset);
  }

  foreach (array('width', 'height', 'alt', 'title', 'sizes') as $key) {
    if (isset($variables[$key])) {
      // If the property has already been defined in the attributes,
      // do not override, including NULL.
      if (array_key_exists($key, $variables['attributes'])) {
        continue;
      }
      $variables['attributes'][$key] = $variables[$key];
    }
  }
}

/**
 * Prepares variables for table templates.
 *
 * Default template: table.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - header: An array containing the table headers. Each element of the array
 *     can be either a localized string or an associative array with the
 *     following keys:
 *     - data: The localized title of the table column.
 *     - field: The database field represented in the table column (required
 *       if user is to be able to sort on this column).
 *     - sort: A default sort order for this column ("asc" or "desc"). Only
 *       one column should be given a default sort order because table sorting
 *       only applies to one column at a time.
 *     - class: An array of values for the 'class' attribute. In particular,
 *       the least important columns that can be hidden on narrow and medium
 *       width screens should have a 'priority-low' class, referenced with the
 *       RESPONSIVE_PRIORITY_LOW constant. Columns that should be shown on
 *       medium+ wide screens should be marked up with a class of
 *       'priority-medium', referenced by with the RESPONSIVE_PRIORITY_MEDIUM
 *       constant. Themes may hide columns with one of these two classes on
 *       narrow viewports to save horizontal space.
 *     - Any HTML attributes, such as "colspan", to apply to the column header
 *       cell.
 *   - rows: An array of table rows. Every row is an array of cells, or an
 *     associative array with the following keys:
 *     - data: An array of cells.
 *     - Any HTML attributes, such as "class", to apply to the table row.
 *     - no_striping: A Boolean indicating that the row should receive no
 *       'even / odd' styling. Defaults to FALSE.
 *     Each cell can be either a string or an associative array with the
 *     following keys:
 *     - data: The string to display in the table cell.
 *     - header: Indicates this cell is a header.
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
 *       ),
 *     );
 *     @endcode
 *   - footer: An array of table rows which will be printed within a <tfoot>
 *     tag, in the same format as the rows element (see above).
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
function template_preprocess_table(&$variables) {
  $is_sticky = !empty($variables['sticky']);
  $is_responsive = !empty($variables['responsive']);

  // Format the table columns:
  if (!empty($variables['colgroups'])) {
    foreach ($variables['colgroups'] as &$colgroup) {
      // Check if we're dealing with a simple or complex column
      if (isset($colgroup['data'])) {
        $cols = $colgroup['data'];
        unset($colgroup['data']);
        $colgroup_attributes = $colgroup;
      }
      else {
        $cols = $colgroup;
        $colgroup_attributes = array();
      }
      $colgroup = array();
      $colgroup['attributes'] = new Attribute($colgroup_attributes);
      $colgroup['cols'] = array();

      // Build columns.
      if (is_array($cols) && !empty($cols)) {
        foreach ($cols as $col_key => $col) {
          $colgroup['cols'][$col_key]['attributes'] = new Attribute($col);
        }
      }
    }
  }

  // Add the 'empty' row message if available.
  if (empty($variables['rows']) && isset($variables['empty'])) {
    $header_count = 0;
    foreach ($variables['header'] as $header_cell) {
      if (is_array($header_cell) && isset($header_cell['colspan'])) {
        $header_count += $header_cell['colspan'];
      }
      else {
        $header_count++;
      }
    }
    $variables['rows'][] = array(array(
      'data' => $variables['empty'],
      'colspan' => $header_count,
      'class' => array('empty', 'message'),
    ));
  }

  // Build an associative array of responsive classes keyed by column.
  $responsive_classes = array();

  // Format the table header:
  $ts = array();
  if (!empty($variables['header'])) {
    $ts = tablesort_init($variables['header']);

    // Use a separate index with responsive classes as headers
    // may be associative.
    $responsive_index = -1;
    foreach ($variables['header'] as $col_key => $cell) {
      // Increase the responsive index.
      $responsive_index++;

      if (!is_array($cell)) {
        $cell_content = $cell;
        $cell_attributes = new Attribute();
        $is_header = TRUE;
      }
      else {
        $cell_content = '';
        if (isset($cell['data'])) {
          $cell_content = $cell['data'];
          unset($cell['data']);
        }
        // Flag the cell as a header or not and remove the flag.
        $is_header = isset($cell['header']) ? $cell['header'] : TRUE;
        unset($cell['header']);

        // Track responsive classes for each column as needed. Only the header
        // cells for a column are marked up with the responsive classes by a
        // module developer or themer. The responsive classes on the header cells
        // must be transferred to the content cells.
        if (!empty($cell['class']) && is_array($cell['class'])) {
          if (in_array(RESPONSIVE_PRIORITY_MEDIUM, $cell['class'])) {
            $responsive_classes[$responsive_index] = RESPONSIVE_PRIORITY_MEDIUM;
          }
          elseif (in_array(RESPONSIVE_PRIORITY_LOW, $cell['class'])) {
            $responsive_classes[$responsive_index] = RESPONSIVE_PRIORITY_LOW;
          }
        }

        if (is_array($cell_content)) {
          $cell_content = drupal_render($cell_content);
        }

        tablesort_header($cell_content, $cell, $variables['header'], $ts);

        // tablesort_header() removes the 'sort' and 'field' keys.
        $cell_attributes = new Attribute($cell);
      }
      $variables['header'][$col_key] = array();
      $variables['header'][$col_key]['tag'] = $is_header ? 'th' : 'td';
      $variables['header'][$col_key]['attributes'] = $cell_attributes;
      $variables['header'][$col_key]['content'] = $cell_content;
    }
  }

  // Rows and footer have the same structure.
  $sections = array('rows' , 'footer');
  foreach ($sections as $section) {
    if (!empty($variables[$section])) {
      $flip = array('even' => 'odd', 'odd' => 'even');
      $class = 'even';
      foreach ($variables[$section] as $row_key => $row) {
        $cells = $row;
        $row_attributes = array();
        $no_striping = $section === 'footer';

        // Check if we're dealing with a simple or complex row
        if (isset($row['data'])) {
          $cells = $row['data'];
          $no_striping = isset($row['no_striping']) ? $row['no_striping'] : $no_striping;

          // Set the attributes array and exclude 'data' and 'no_striping'.
          $row_attributes = $row;
          unset($row_attributes['data']);
          unset($row_attributes['no_striping']);
        }

        // Add odd/even class.
        if (!$no_striping) {
          $class = $flip[$class];
          $row_attributes['class'][] = $class;
        }


        // Build row.
        $variables[$section][$row_key] = array();
        $variables[$section][$row_key]['attributes'] = new Attribute($row_attributes);
        $variables[$section][$row_key]['cells'] = array();
        if (!empty($cells)) {
          // Reset the responsive index.
          $responsive_index = -1;
          foreach ($cells as $col_key => $cell) {
            // Increase the responsive index.
            $responsive_index++;

            if (!is_array($cell)) {
              $cell_content = $cell;
              $cell_attributes = array();
              $is_header = FALSE;
            }
            else {
              $cell_content = '';
              if (isset($cell['data'])) {
                $cell_content = $cell['data'];
                unset($cell['data']);
              }

              // Flag the cell as a header or not and remove the flag.
              $is_header = !empty($cell['header']);
              unset($cell['header']);

              $cell_attributes = $cell;

              if (is_array($cell_content)) {
                $cell_content = drupal_render($cell_content);
              }
            }
            // Add active class if needed for sortable tables.
            if (isset($variables['header'][$col_key]['data']) && $variables['header'][$col_key]['data'] == $ts['name'] && !empty($variables['header'][$col_key]['field'])) {
              $cell_attributes['class'][] = 'active';
            }
            // Copy RESPONSIVE_PRIORITY_LOW/RESPONSIVE_PRIORITY_MEDIUM
            // class from header to cell as needed.
            if (isset($responsive_classes[$responsive_index])) {
              $cell_attributes['class'][] = $responsive_classes[$responsive_index];
            }
            $variables[$section][$row_key]['cells'][$col_key]['tag'] = $is_header ? 'th' : 'td';
            $variables[$section][$row_key]['cells'][$col_key]['attributes'] = new Attribute($cell_attributes);
            $variables[$section][$row_key]['cells'][$col_key]['content'] = $cell_content;
          }
        }
      }
    }
  }
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
    $variables['arrow_asc'] = file_create_url('core/misc/arrow-asc.png');
  }
  else {
    $variables['arrow_desc'] = file_create_url('core/misc/arrow-desc.png');
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
            // This is the same condition as in
            // \Drupal\Core\Render\Element::children(), which cannot be used
            // here, since it triggers an error on string values.
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
  // Stripping tags because that's what l() used to do.
  $variables['attributes']['title'] = strip_tags($text);
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
  $variables['has_parent'] = FALSE;
  $element = $variables['element'];
  // Ensure #attributes is set.
  $element += array('#attributes' => array());

  // Special handling for form elements.
  if (isset($element['#array_parents'])) {
    // Assign an html ID.
    if (!isset($element['#attributes']['id'])) {
      $element['#attributes']['id'] = $element['#id'];
    }
    $variables['has_parent'] = TRUE;
  }

  $variables['children'] = $element['#children'];
  $variables['attributes'] = $element['#attributes'];
}

/**
 * Prepares variables for maintenance task list templates.
 *
 * Default template: maintenance-task-list.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - items: An associative array of maintenance tasks.
 *     It's the caller's responsibility to ensure this array's items contain no
 *     dangerous HTML such as SCRIPT tags.
 *   - active: The key for the currently active maintenance task.
 */
function template_preprocess_maintenance_task_list(&$variables) {
  $items = $variables['items'];
  $active = $variables['active'];

  $done = isset($items[$active]) || $active == NULL;
  foreach ($items as $k => $item) {
    $variables['tasks'][$k]['item'] = $item;
    $variables['tasks'][$k]['attributes'] = new Attribute();
    if ($active == $k) {
      $variables['tasks'][$k]['attributes']->addClass('active');
      $variables['tasks'][$k]['status'] = t('active');
      $done = FALSE;
    }
    else {
      if ($done) {
        $variables['tasks'][$k]['attributes']->addClass('done');
        $variables['tasks'][$k]['status'] = t('done');
      }
    }
  }
}

/**
 * @} End of "addtogroup themeable".
 */

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
  $variables['directory'] = \Drupal::theme()->getActiveTheme()->getPath();

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
 * @deprecated Use \Drupal\Core\Render\Element\Html::preRenderHtml().
 */
function drupal_pre_render_html(array $element) {
  return Element\Html::preRenderHtml($element);
}

/**
 * Prepares variables for HTML document templates.
 *
 * Default template: html.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - page: A render element representing the page.
 */
function template_preprocess_html(&$variables) {
  /** @var $page \Drupal\Core\Page\HtmlPage */
  $page = $variables['page_object'];

  $variables['html_attributes'] = $page->getHtmlAttributes();
  $variables['attributes'] = $page->getBodyAttributes();
  $variables['page'] = $page;

  // Compile a list of classes that are going to be applied to the body element.
  // This allows advanced theming based on context (home page, node of certain type, etc.).
  $body_classes = $variables['attributes']['class'];
  $body_classes[] = 'html';
  // Add a class that tells us whether we're on the front page or not.
  $body_classes[] = $variables['is_front'] ? 'front' : 'not-front';
  // Add a class that tells us whether the page is viewed by an authenticated user or not.
  $body_classes[] = $variables['logged_in'] ? 'logged-in' : 'not-logged-in';
  $variables['attributes']['class'] = $body_classes;

  $path_args = explode('/', current_path());
  // Populate the body classes.
  if ($suggestions = theme_get_suggestions($path_args, 'page', '-')) {
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
      'title' => SafeMarkup::set(trim(strip_tags($page->getTitle()))),
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
      $head_title['slogan'] = strip_tags(Xss::filterAdmin($site_config->get('slogan')));
    }
  }

  $variables['head_title_array'] = $head_title;
  $output = '';
  $separator = '';
  foreach ($head_title as $item) {
    $output .= $separator . SafeMarkup::escape($item);
    $separator = ' | ';
  }
  $variables['head_title'] = SafeMarkup::set($output);

  // @todo Remove drupal_*_html_head() and refactor accordingly.
  $html_heads = drupal_get_html_head(FALSE);
  uasort($html_heads, 'Drupal\Component\Utility\SortArray::sortByWeightElement');
  foreach ($html_heads as $name => $tag) {
    if ($tag['#tag'] == 'link') {
      $link = new LinkElement($name, isset($tag['#attributes']['content']) ? $tag['#attributes']['content'] : NULL, $tag['#attributes']);
      if (!empty($tag['#noscript'])) {
        $link->setNoScript();
      }
      $page->addLinkElement($link);
    }
    elseif ($tag['#tag'] == 'meta') {
      $metatag = new MetaElement(NULL, $tag['#attributes']);
      if (!empty($tag['#noscript'])) {
        $metatag->setNoScript();
      }
      $page->addMetaElement($metatag);
    }
  }

  // Add favicon.
  if (theme_get_setting('features.favicon')) {
    $url = UrlHelper::stripDangerousProtocols(theme_get_setting('favicon.url'));
    $link = new LinkElement($url, 'shortcut icon', ['type' => theme_get_setting('favicon.mimetype')]);
    $page->addLinkElement($link);
  }

  $variables['page_top'][] = array('#markup' => $page->getBodyTop());
  $variables['page_bottom'][] = array('#markup' => $page->getBodyBottom());
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
 * @see DefaultHtmlFragmentRenderer::render()
 */
function template_preprocess_page(&$variables) {
  $language_interface = \Drupal::languageManager()->getCurrentLanguage();
  $site_config = \Drupal::config('system.site');

  // Move some variables to the top level for themer convenience and template cleanliness.
  $variables['show_messages'] = $variables['page']['#show_messages'];
  $variables['title'] = $variables['page']['#title'];

  foreach (system_region_list(\Drupal::theme()->getActiveTheme()->getName()) as $region_key => $region_name) {
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
  $variables['front_page']        = \Drupal::url('<front>');
  $variables['language']          = $language_interface;
  $variables['logo']              = theme_get_setting('logo.url');
  $variables['site_name']         = (theme_get_setting('features.name') ? String::checkPlain($site_config->get('name')) : '');
  $variables['site_slogan']       = (theme_get_setting('features.slogan') ? Xss::filterAdmin($site_config->get('slogan')) : '');

  if (!defined('MAINTENANCE_MODE')) {
    $variables['action_links']   = menu_get_local_actions();
    $variables['tabs']           = menu_local_tabs();

    // Convert drupal_get_feeds to feed links on the page object.
    /** @var \Drupal\Core\Page\HtmlPage $page */
    $page = $variables['page']['#page'];
    // Render the feed icons.
    $variables['feed_icons'] = array();
    foreach ($page->getFeedLinkElements() as $link) {
      $variables['feed_icons'][] = array(
        '#theme' => 'feed_icon',
        '#url' => $link->getAttributes()['href'],
        '#title' => $link->getAttributes()['title'],
      );
    }
  }
  else {
    $variables['action_links']   = array();
    $variables['tabs']           = array();
    $variables['feed_icons']     = '';
  }

  if ($node = \Drupal::routeMatch()->getParameter('node')) {
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
  if (!defined('MAINTENANCE_MODE')) {
    $variables['breadcrumb'] = array(
      '#theme' => 'breadcrumb',
      '#links' => \Drupal::service('breadcrumb')->build(\Drupal::routeMatch()),
    );
  }
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
 *   An array of path arguments.
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
 * @param array $variables
 *   An associative array containing:
 *   - content - An array of page content.
 *
 * @see system_page_build()
 */
function template_preprocess_maintenance_page(&$variables) {
  // @todo Rename the templates to page--maintenance + page--install.
  template_preprocess_page($variables);

  $page_object = $variables['page']['#page'];
  $attributes = $page_object->getBodyAttributes();
  $classes = $attributes['class'];
  $classes[] = 'maintenance-page';
  $classes[] = 'in-maintenance';
  if (isset($variables['db_is_active']) && !$variables['db_is_active']) {
    $classes[] = 'db-offline';
  }
  $attributes['class'] = $classes;

  // @see system_page_build()
  $variables['#attached']['library'][] = 'core/normalize';
  $variables['#attached']['library'][] = 'system/maintenance';
}

/**
 * Prepares variables for install page templates.
 *
 * Default template: install-page.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - content - An array of page content.
 *
 * @see template_preprocess_maintenance_page()
 */
function template_preprocess_install_page(&$variables) {
  template_preprocess_maintenance_page($variables);

  $page_object = $variables['page']['#page'];
  $attributes = $page_object->getBodyAttributes();
  $classes = $attributes['class'];
  $classes[] = 'install-page';
  $attributes['class'] = $classes;

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
}

/**
 * Prepares variables for field templates.
 *
 * Default template: field.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: A render element representing the field.
 *   - attributes: A string containing the attributes for the wrapping div.
 *   - title_attributes: A string containing the attributes for the title.
 *   - content_attributes: A string containing the attributes for the content's
 *     div.
 */
function template_preprocess_field(&$variables, $hook) {
  $element = $variables['element'];

  $variables['label_hidden'] = ($element['#label_display'] == 'hidden');
  // Always set the field label - allow themes to decide whether to display it.
  // In addition the label should be rendered but hidden to support screen
  // readers.
  $variables['label'] = String::checkPlain($element['#title']);

  // Add default CSS classes. Since there can be many fields rendered on a page,
  // save some overhead by calling strtr() directly instead of
  // drupal_html_class().
  $variables['entity_type_css'] = strtr($element['#entity_type'], '_', '-');
  $variables['field_name_css'] = strtr($element['#field_name'], '_', '-');
  $variables['field_type_css'] = strtr($element['#field_type'], '_', '-');
  $variables['attributes']['class'] = array(
    'field',
    'field-' . $variables['entity_type_css'] . '--' . $variables['field_name_css'],
    'field-name-' . $variables['field_name_css'],
    'field-type-' . $variables['field_type_css'],
    'field-label-' . $element['#label_display'],
  );

  // Add a "clearfix" class to the wrapper since we float the label and the
  // field items in field.module.css if the label is inline.
  if ($element['#label_display'] == 'inline') {
    $variables['attributes']['class'][] = 'clearfix';
  }

  // Hide labels visually, but display them to screenreaders if applicable.
  if ($element['#label_display'] == 'visually_hidden') {
    $variables['title_attributes']['class'][] = 'visually-hidden';
  }

  static $default_attributes;
  if (!isset($default_attributes)) {
    $default_attributes = new Attribute;
  }

  // We want other preprocess functions and the theme implementation to have
  // fast access to the field item render arrays. The item render array keys
  // (deltas) should always be numerically indexed starting from 0, and looping
  // on those keys is faster than calling element_children() or looping on all
  // keys within $element, since that requires traversal of all element
  // properties.
  $variables['items'] = array();
  $delta = 0;
  while (!empty($element[$delta])) {
    $variables['items'][$delta]['content'] = $element[$delta];

    // Modules (e.g., rdf.module) can add field item attributes (to
    // $item->_attributes) within hook_entity_prepare_view(). Some field
    // formatters move those attributes into some nested formatter-specific
    // element in order have them rendered on the desired HTML element (e.g., on
    // the <a> element of a field item being rendered as a link). Other field
    // formatters leave them within $element['#items'][$delta]['_attributes'] to
    // be rendered on the item wrappers provided by field.html.twig.
    $variables['items'][$delta]['attributes'] = !empty($element['#items'][$delta]->_attributes) ? new Attribute($element['#items'][$delta]->_attributes) : clone($default_attributes);
    $delta++;
  }
}

/**
 * Prepares variables for individual form element templates.
 *
 * Default template: field-multiple-value-form.html.twig.
 *
 * Combines multiple values into a table with drag-n-drop reordering.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: A render element representing the form element.
 */
function template_preprocess_field_multiple_value_form(&$variables) {
  $element = $variables['element'];
  $variables['multiple'] = $element['#cardinality_multiple'];

  if ($variables['multiple']) {
    $table_id = drupal_html_id($element['#field_name'] . '_values');
    $order_class = $element['#field_name'] . '-delta-order';
    $header_attributes = new Attribute(array('class' => array('label')));
    if (!empty($element['#required'])) {
      $header_attributes['class'][] = 'form-required';
    }
    $header = array(
      array(
        'data' => array(
          '#prefix' => '<h4' . $header_attributes . '>',
          'title' => array(
            '#markup' => t($element['#title']),
          ),
          '#suffix' => '</h4>',
        ),
        'colspan' => 2,
        'class' => array('field-label'),
      ),
      t('Order', array(), array('context' => 'Sort order')),
    );
    $rows = array();

    // Sort items according to '_weight' (needed when the form comes back after
    // preview or failed validation).
    $items = array();
    $variables['button'] = array();
    foreach (Element::children($element) as $key) {
      if ($key === 'add_more') {
        $variables['button'] = &$element[$key];
      }
      else {
        $items[] = &$element[$key];
      }
    }
    usort($items, '_field_multiple_value_form_sort_helper');

    // Add the items as table rows.
    foreach ($items as $item) {
      $item['_weight']['#attributes']['class'] = array($order_class);

      // Remove weight form element from item render array so it can be rendered
      // in a separate table column.
      $delta_element = $item['_weight'];
      unset($item['_weight']);

      $cells = array(
        array('data' => '', 'class' => array('field-multiple-drag')),
        array('data' => $item),
        array('data' => $delta_element, 'class' => array('delta-order')),
      );
      $rows[] = array(
        'data' => $cells,
        'class' => array('draggable'),
      );
    }

    $variables['table'] = array(
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => array(
        'id' => $table_id,
        'class' => array('field-multiple-table'),
      ),
      '#tabledrag' => array(
        array(
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => $order_class,
        ),
      ),
    );

    $variables['description'] = $element['#description'];
  }
  else {
    $variables['elements'] = array();
    foreach (Element::children($element) as $key) {
      $variables['elements'][] = $element[$key];
    }
  }
}

/**
 * Prepares variables for breadcrumb templates.
 *
 * Default template: breadcrumb.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - links: A list of \Drupal\Core\Link objects which should be rendered.
 */
function template_preprocess_breadcrumb(&$variables) {
  $variables['breadcrumb'] = array();
  /** @var \Drupal\Core\Link $link */
  foreach ($variables['links'] as $key => $link) {
    $variables['breadcrumb'][$key] = array('text' => $link->getText(), 'url' => $link->getUrl()->toString());
  }
}

/**
 * Callback for usort() within template_preprocess_field_multiple_value_form().
 *
 * Sorts using ['_weight']['#value']
 */
function _field_multiple_value_form_sort_helper($a, $b) {
  $a_weight = (is_array($a) && isset($a['_weight']['#value']) ? $a['_weight']['#value'] : 0);
  $b_weight = (is_array($b) && isset($b['_weight']['#value']) ? $b['_weight']['#value'] : 0);
  return $a_weight - $b_weight;
}

/**
 * Provides theme registration for themes across .inc files.
 */
function drupal_common_theme() {
  return array(
    // From theme.inc.
    'html' => array(
      'variables' => array('page_object' => NULL),
    ),
    'page' => array(
      'render element' => 'page',
    ),
    'region' => array(
      'render element' => 'elements',
    ),
    'time' => array(
      'variables' => array('timestamp' => NULL, 'text' => NULL, 'attributes' => array(), 'html' => FALSE),
    ),
    'datetime_form' => array(
      'render element' => 'element',
    ),
    'datetime_wrapper' => array(
      'render element' => 'element',
    ),
    'status_messages' => array(
      'variables' => array('display' => NULL),
    ),
    'links' => array(
      'variables' => array('links' => array(), 'attributes' => array('class' => array('links')), 'heading' => array(), 'set_active_class' => FALSE),
    ),
    'dropbutton_wrapper' => array(
      'variables' => array('children' => NULL),
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
      'variables' => array('uri' => NULL, 'width' => NULL, 'height' => NULL, 'alt' => '', 'title' => NULL, 'attributes' => array(), 'sizes' => NULL, 'srcset' => array(), 'style_name' => NULL),
    ),
    'breadcrumb' => array(
      'variables' => array('links' => array()),
    ),
    'table' => array(
      'variables' => array('header' => NULL, 'rows' => NULL, 'footer' => NULL, 'attributes' => array(), 'caption' => NULL, 'colgroups' => array(), 'sticky' => FALSE, 'responsive' => TRUE, 'empty' => ''),
    ),
    'tablesort_indicator' => array(
      'variables' => array('style' => NULL),
    ),
    'mark' => array(
      'variables' => array('status' => MARK_NEW),
    ),
    'item_list' => array(
      'variables' => array('items' => array(), 'title' => '', 'list_type' => 'ul', 'attributes' => array(), 'empty' => NULL),
    ),
    'feed_icon' => array(
      'variables' => array('url' => NULL, 'title' => NULL),
    ),
    'progress_bar' => array(
      'variables' => array('label' => NULL, 'percent' => NULL, 'message' => NULL),
    ),
    'indentation' => array(
      'variables' => array('size' => 1),
      'function' => 'theme_indentation',
    ),
    // From theme.maintenance.inc.
    'maintenance_page' => array(
      'render element' => 'page',
    ),
    'install_page' => array(
      'render element' => 'page',
    ),
    'maintenance_task_list' => array(
      'variables' => array('items' => NULL, 'active' => NULL,  'variant' => NULL),
    ),
    'authorize_message' => array(
      'variables' => array('message' => NULL, 'success' => TRUE),
      'function' => 'theme_authorize_message',
      'path' => 'core/includes',
      'file' => 'theme.maintenance.inc',
    ),
    'authorize_report' => array(
      'variables' => array('messages' => array()),
      'function' => 'theme_authorize_report',
      'path' => 'core/includes',
      'file' => 'theme.maintenance.inc',
    ),
    // From pager.inc.
    'pager' => array(
      'variables' => array('tags' => array(), 'element' => 0, 'parameters' => array(), 'quantity' => 9),
    ),
    // From menu.inc.
    'menu' => array(
      'variables' => array('items' => array(), 'attributes' => array()),
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
    'checkboxes' => array(
      'render element' => 'element',
    ),
    'form' => array(
      'render element' => 'element',
    ),
    'textarea' => array(
      'render element' => 'element',
    ),
    'form_element' => array(
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
    // From field system.
    'field' => array(
      'render element' => 'element',
    ),
    'field_multiple_value_form' => array(
      'render element' => 'element',
    ),
  );
}
