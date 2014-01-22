<?php

/**
 * @defgroup themeable Default theme implementations
 * @{
 * Functions and templates for the user interface to be implemented by themes.
 *
 * Drupal's presentation layer is a pluggable system known as the theme
 * layer. Each theme can take control over most of Drupal's output, and
 * has complete control over the CSS.
 *
 * Inside Drupal, the theme layer is utilized by the use of the theme()
 * function, which is passed the name of a component (the theme hook)
 * and an array of variables. For example,
 * theme('table', array('header' => $header, 'rows' => $rows));
 * Additionally, the theme() function can take an array of theme
 * hooks, which can be used to provide 'fallback' implementations to
 * allow for more specific control of output. For example, the function:
 * theme(array('table__foo', 'table'), $variables) would look to see if
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
 * Drupal's default template renderer is Twig. Drupal's theme engines can
 * provide alternate template engines, such as XTemplate, Smarty and PHPTal.
 *
 * In order to create theme-specific implementations of these hooks, themes can
 * implement their own version of theme hooks, either as functions or templates.
 * These implementations will be used instead of the default implementation. If
 * using a pure .theme without an engine, the .theme is required to implement
 * its own version of hook_theme() to tell Drupal what it is implementing;
 * themes utilizing an engine will have their well-named theming functions
 * automatically registered for them. While this can vary based upon the theme
 * engine, the standard is that theme functions should be named THEMENAME_HOOK.
 * For example, for Drupal's default theme (Bartik) to implement the 'table'
 * hook, the theme function should be called bartik_table().
 *
 * The theme system is described and defined in theme.inc.
 *
 * @see theme()
 * @see hook_theme()
 * @see hooks
 * @see callbacks
 *
 * @} End of "defgroup themeable".
 */

/**
 * Allow themes to alter the theme-specific settings form.
 *
 * With this hook, themes can alter the theme-specific settings form in any way
 * allowable by Drupal's Form API, such as adding form elements, changing
 * default values and removing form elements. See the Form API documentation on
 * api.drupal.org for detailed information.
 *
 * Note that the base theme's form alterations will be run before any sub-theme
 * alterations.
 *
 * @param $form
 *   Nested array of form elements that comprise the form.
 * @param $form_state
 *   A keyed array containing the current state of the form.
 */
function hook_form_system_theme_settings_alter(&$form, &$form_state) {
  // Add a checkbox to toggle the breadcrumb trail.
  $form['toggle_breadcrumb'] = array(
    '#type' => 'checkbox',
    '#title' => t('Display the breadcrumb'),
    '#default_value' => theme_get_setting('features.breadcrumb'),
    '#description'   => t('Show a trail of links from the homepage to the current page.'),
  );
}

/**
 * Preprocess theme variables for templates.
 *
 * This hook allows modules to preprocess theme variables for theme templates.
 * It is called for all theme hooks implemented as templates, but not for theme
 * hooks implemented as functions. hook_preprocess_HOOK() can be used to
 * preprocess variables for a specific theme hook, whether implemented as a
 * template or function.
 *
 * For more detailed information, see theme().
 *
 * @param $variables
 *   The variables array (modify in place).
 * @param $hook
 *   The name of the theme hook.
 */
function hook_preprocess(&$variables, $hook) {
 static $hooks;

  // Add contextual links to the variables, if the user has permission.

  if (!user_access('access contextual links')) {
    return;
  }

  if (!isset($hooks)) {
    $hooks = theme_get_registry();
  }

  // Determine the primary theme function argument.
  if (isset($hooks[$hook]['variables'])) {
    $keys = array_keys($hooks[$hook]['variables']);
    $key = $keys[0];
  }
  else {
    $key = $hooks[$hook]['render element'];
  }

  if (isset($variables[$key])) {
    $element = $variables[$key];
  }

  if (isset($element) && is_array($element) && !empty($element['#contextual_links'])) {
    $variables['title_suffix']['contextual_links'] = contextual_links_view($element);
    if (!empty($variables['title_suffix']['contextual_links'])) {
      $variables['attributes']['class'][] = 'contextual-links-region';
    }
  }
}

/**
 * Preprocess theme variables for a specific theme hook.
 *
 * This hook allows modules to preprocess theme variables for a specific theme
 * hook. It should only be used if a module needs to override or add to the
 * theme preprocessing for a theme hook it didn't define.
 *
 * For more detailed information, see theme().
 *
 * @param $variables
 *   The variables array (modify in place).
 */
function hook_preprocess_HOOK(&$variables) {
  // This example is from rdf_preprocess_image(). It adds an RDF attribute
  // to the image hook's variables.
  $variables['attributes']['typeof'] = array('foaf:Image');
}

/**
 * Provides alternate named suggestions for a specific theme hook.
 *
 * This hook allows the module implementing hook_theme() for a theme hook to
 * provide alternative theme function or template name suggestions. This hook is
 * only invoked for the first module implementing hook_theme() for a theme hook.
 *
 * HOOK is the least-specific version of the hook being called. For example, if
 * '#theme' => 'node__article' is called, then node_theme_suggestions_node()
 * will be invoked, not node_theme_suggestions_node__article(). The specific
 * hook called (in this case 'node__article') is available in
 * $variables['theme_hook_original'].
 *
 * @todo Add @code sample.
 *
 * @param array $variables
 *   An array of variables passed to the theme hook. Note that this hook is
 *   invoked before any preprocessing.
 *
 * @return array
 *   An array of theme suggestions.
 *
 * @see hook_theme_suggestions_HOOK_alter()
 */
function hook_theme_suggestions_HOOK(array $variables) {
  $suggestions = array();

  $suggestions[] = 'node__' . $variables['elements']['#langcode'];

  return $suggestions;
}

/**
 * Alters named suggestions for all theme hooks.
 *
 * This hook is invoked for all theme hooks, if you are targeting a specific
 * theme hook it's best to use hook_theme_suggestions_HOOK_alter().
 *
 * The call order is as follows: all existing suggestion alter functions are
 * called for module A, then all for module B, etc., followed by all for any
 * base theme(s), and finally for the active theme. The order is
 * determined by system weight, then by extension (module or theme) name.
 *
 * Within each module or theme, suggestion alter hooks are called in the
 * following order: first, hook_theme_suggestions_alter(); second,
 * hook_theme_suggestions_HOOK_alter(). So, for each module or theme, the more
 * general hooks are called first followed by the more specific.
 *
 * In the following example, we provide an alternative template suggestion to
 * node and taxonomy term templates based on the user being logged in.
 * @code
 * function MYMODULE_theme_suggestions_alter(array &$suggestions, array $variables, $hook) {
 *   if (\Drupal::currentUser()->isAuthenticated() && in_array($hook, array('node', 'taxonomy_term'))) {
 *     $suggestions[] = $hook . '__' . 'logged_in';
 *   }
 * }
 *
 * @endcode
 *
 * @param array $suggestions
 *   An array of alternate, more specific names for template files or theme
 *   functions.
 * @param array $variables
 *   An array of variables passed to the theme hook. Note that this hook is
 *   invoked before any variable preprocessing.
 * @param string $hook
 *   The base hook name. For example, if '#theme' => 'node__article' is called,
 *   then $hook will be 'node', not 'node__article'. The specific hook called
 *   (in this case 'node__article') is available in
 *   $variables['theme_hook_original'].
 *
 * @return array
 *   An array of theme suggestions.
 *
 * @see hook_theme_suggestions_HOOK_alter()
 */
function hook_theme_suggestions_alter(array &$suggestions, array $variables, $hook) {
  // Add an interface-language specific suggestion to all theme hooks.
  $suggestions[] = $hook . '__' . \Drupal::languageManager()->getLanguage()->id;
}

/**
 * Alters named suggestions for a specific theme hook.
 *
 * This hook allows any module or theme to provide altenative theme function or
 * template name suggestions and reorder or remove suggestions provided by
 * hook_theme_suggestions_HOOK() or by earlier invocations of this hook.
 *
 * HOOK is the least-specific version of the hook being called. For example, if
 * '#theme' => 'node__article' is called, then node_theme_suggestions_node()
 * will be invoked, not node_theme_suggestions_node__article(). The specific
 * hook called (in this case 'node__article') is available in
 * $variables['theme_hook_original'].
 *
 * @todo Add @code sample.
 *
 * @param array $suggestions
 *   An array of theme suggestions.
 * @param array $variables
 *   An array of variables passed to the theme hook. Note that this hook is
 *   invoked before any preprocessing.
 *
 * @see hook_theme_suggestions_alter()
 * @see hook_theme_suggestions_HOOK()
 */
function hook_theme_suggestions_HOOK_alter(array &$suggestions, array $variables) {
  if (empty($variables['header'])) {
    $suggestions[] = 'hookname__' . 'no_header';
  }
}

/**
 * Respond to themes being enabled.
 *
 * @param array $theme_list
 *   Array containing the names of the themes being enabled.
 *
 * @see theme_enable()
 */
function hook_themes_enabled($theme_list) {
  foreach ($theme_list as $theme) {
    block_theme_initialize($theme);
  }
}

/**
 * Respond to themes being disabled.
 *
 * @param array $theme_list
 *   Array containing the names of the themes being disabled.
 *
 * @see theme_disable()
 */
function hook_themes_disabled($theme_list) {
 // Clear all update module caches.
  update_storage_clear();
}
