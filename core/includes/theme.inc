<?php

/**
 * @file
 * The theme system, which controls the output of Drupal.
 *
 * The theme system allows for nearly all output of the Drupal system to be
 * customized by user themes.
 */

use Drupal\Core\Extension\ThemeSettingsProvider;
use Drupal\Core\Config\Config;

/**
 * @defgroup content_flags Content markers
 * @{
 * Markers used by mark.html.twig to designate content.
 *
 * @see mark.html.twig
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
 * Returns an array of default theme features.
 *
 * @deprecated in drupal:11.3.0 and is removed from drupal:13.0.0. Use
 *   \Drupal\Core\Extension\ThemeSettingsProvider::DEFAULT_THEME_FEATURES
 *   instead.
 *
 * @see https://www.drupal.org/node/3554127
 */
function _system_default_theme_features() {
  @trigger_error('_system_default_theme_features() is deprecated in drupal:11.3.0 and is removed from drupal:13.0.0. Use \Drupal\Core\Extension\ThemeSettingsProvider::DEFAULT_THEME_FEATURES instead. See https://www.drupal.org/node/3554127', E_USER_DEPRECATED);
  return ThemeSettingsProvider::DEFAULT_THEME_FEATURES;
}

/**
 * Allows themes and/or theme engines to easily discover overridden templates.
 *
 * @param array $cache
 *   The existing cache of theme hooks to test against.
 * @param string $extension
 *   The extension that these templates will have.
 * @param string $path
 *   The path to search.
 */
function drupal_find_theme_templates($cache, $extension, $path): array {
  $implementations = [];

  // Collect paths to all sub-themes grouped by base themes. These will be
  // used for filtering. This allows base themes to have sub-themes in its
  // folder hierarchy without affecting the base themes template discovery.
  $theme_paths = [];
  foreach (\Drupal::service('theme_handler')->listInfo() as $theme_info) {
    if (!empty($theme_info->base_theme)) {
      $theme_paths[$theme_info->base_theme][$theme_info->getName()] = $theme_info->getPath();
    }
  }
  foreach ($theme_paths as $base_theme => $subthemes) {
    foreach ($subthemes as $subtheme => $subtheme_path) {
      if (isset($theme_paths[$subtheme])) {
        $theme_paths[$base_theme] = array_merge($theme_paths[$base_theme], $theme_paths[$subtheme]);
      }
    }
  }
  $theme = \Drupal::theme()->getActiveTheme()->getName();
  $subtheme_paths = $theme_paths[$theme] ?? [];

  // Escape the periods in the extension.
  $regex = '/' . str_replace('.', '\.', $extension) . '$/';
  // Get a listing of all template files in the path to search.
  $files = [];
  if (is_dir($path)) {
    $files = \Drupal::service('file_system')->scanDirectory($path, $regex, ['key' => 'filename']);
  }

  // Find templates that implement registered theme hooks and include that in
  // what is returned so that the registry knows that the theme has this
  // implementation.
  foreach ($files as $template => $file) {
    // Ignore sub-theme templates for the current theme.
    if (!str_starts_with($file->uri, str_replace($subtheme_paths, '', $file->uri))) {
      continue;
    }
    // Remove the extension from the filename.
    $template = str_replace($extension, '', $template);
    // Transform - in filenames to _ to match function naming scheme
    // for the purposes of searching.
    $hook = strtr($template, '-', '_');
    if (isset($cache[$hook])) {
      $implementations[$hook] = [
        'template' => $template,
        'path' => dirname($file->uri),
      ];
    }

    // Match templates based on the 'template' filename.
    foreach ($cache as $hook => $info) {
      if (isset($info['template'])) {
        if ($template === $info['template']) {
          $implementations[$hook] = [
            'template' => $template,
            'path' => dirname($file->uri),
          ];
        }
      }
    }
  }

  // Find templates that implement possible "suggestion" variants of registered
  // theme hooks and add those as new registered theme hooks. See
  // hook_theme_suggestions_alter() for more information about suggestions and
  // the use of 'pattern' and 'base hook'.
  $patterns = array_keys($files);
  foreach ($cache as $hook => $info) {
    $pattern = $info['pattern'] ?? ($hook . '__');
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
          $implementations[strtr($file, '-', '_')] = [
            'template' => $file,
            'path' => dirname($files[$match]->uri),
            $arg_name => $info[$arg_name],
            'base hook' => $hook,
          ];
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
 * - the saved values from the global theme settings form
 * - the saved values from the theme's settings form
 * To only retrieve the default global theme setting, an empty string should be
 * given for $theme.
 *
 * @param string $setting_name
 *   The name of the setting to be retrieved.
 * @param string $theme
 *   The name of a given theme; defaults to the current theme.
 *
 * @return mixed
 *   The value of the requested setting, NULL if the setting does not exist.
 *
 * @deprecated in drupal:11.3.0 and is removed from drupal:13.0.0. Use
 *   \Drupal::service('Drupal\Core\Extension\ThemeSettingsProvider')->getSetting()
 *   instead.
 *
 * @see https://www.drupal.org/node/3035289
 */
function theme_get_setting($setting_name, $theme = NULL) {
  @trigger_error('theme_get_setting() is deprecated in drupal:11.3.0 and is removed from drupal:13.0.0. Use \Drupal::service(\'\Drupal\Core\Extension\ThemeSettingsProvider\')->getSetting() instead. See https://www.drupal.org/node/3035289', E_USER_DEPRECATED);
  return \Drupal::service(ThemeSettingsProvider::class)->getSetting($setting_name, $theme);
}

/**
 * Converts theme settings to configuration.
 *
 * @param array $theme_settings
 *   An array of theme settings from system setting form or a Drupal 7 variable.
 * @param \Drupal\Core\Config\Config $config
 *   The configuration object to update.
 *
 * @return \Drupal\Core\Config\Config
 *   The Config object with updated data.
 *
 * @see system_theme_settings_submit()
 */
function theme_settings_convert_to_config(array $theme_settings, Config $config) {
  foreach ($theme_settings as $key => $value) {
    if ($key == 'default_logo') {
      $config->set('logo.use_default', $value);
    }
    elseif ($key == 'logo_path') {
      $config->set('logo.path', $value);
    }
    elseif ($key == 'default_favicon') {
      $config->set('favicon.use_default', $value);
    }
    elseif ($key == 'favicon_path') {
      $config->set('favicon.path', $value);
    }
    elseif ($key == 'favicon_mimetype') {
      $config->set('favicon.mimetype', $value);
    }
    elseif (str_starts_with($key, 'toggle_')) {
      $config->set('features.' . mb_substr($key, 7), $value);
    }
    elseif (!in_array($key, ['theme', 'logo_upload'])) {
      $config->set($key, $value);
    }
  }
  return $config;
}

/**
 * Generate an array of suggestions from path arguments.
 *
 * This is typically called for adding to the suggestions in
 * hook_theme_suggestions_HOOK_alter() or adding to 'attributes' class key
 * variables from within preprocess functions, when wanting to base the
 * additional suggestions or classes on the path of the current page.
 *
 * @param array $args
 *   An array of path arguments.
 * @param string $base
 *   A string identifying the base 'thing' from which more specific suggestions
 *   are derived. For example, 'page' or 'html'.
 * @param string $delimiter
 *   The string used to delimit increasingly specific information. The default
 *   of '__' is appropriate for theme hook suggestions. '-' is appropriate for
 *   extra classes.
 *
 * @return array
 *   An array of suggestions, suitable for adding to
 *   hook_theme_suggestions_HOOK_alter() or to $variables['attributes']['class']
 *   if the suggestions represent extra CSS classes.
 */
function theme_get_suggestions($args, $base, $delimiter = '__'): array {

  // Build a list of suggested theme hooks in order of
  // specificity. One suggestion is made for every element of the current path,
  // though numeric elements are not carried to subsequent suggestions. For
  // example, for $base='page', http://www.example.com/node/1/edit would result
  // in the following suggestions:
  //
  // page__node
  // page__node__%
  // page__node__1
  // page__node__edit

  $suggestions = [];
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
    $arg = str_replace(["/", "\\", "\0", '-'], ['', '', '', '_'], $arg);
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
  if (\Drupal::service('path.matcher')->isFrontPage()) {
    // Front templates should be based on root only, not prefixed arguments.
    $suggestions[] = $base . $delimiter . 'front';
  }

  return $suggestions;
}
