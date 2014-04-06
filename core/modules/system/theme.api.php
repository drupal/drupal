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
 * The theme layer is utilized by specifying theme implementations in a
 * renderable array. The names of the keys in a renderable array
 * determine how the information in the array is converted to themed output.
 *
 * To invoke a theme implementation on a renderable array, define a key named
 * '#theme' and assign it a string value that is the name of a theme hook. Any
 * variables that the theme hook requires may be supplied as additional keys --
 * prepended with a '#' character -- in the renderable array.
 *
 * @code
 * $item_list = array(
 *   '#theme' => 'item_list',
 *   '#items' => $links,
 *   '#title' => t('Next steps'),
 * );
 *
 * return $item_list;
 * @endcode
 *
 * Do not call _theme() directly; instead, build and return a renderable array.
 * If necessary, the array may be rendered to a string in-place
 * by calling drupal_render().
 *
 * @code
 * $output = drupal_render($item_list);
 * @endcode
 *
 * @section sec_theme_hooks Theme Hooks
 * Modules register theme hooks within a hook_theme() implementation and provide
 * a default implementation via a function named theme_HOOK(). For instance, to
 * theme a taxonomy term, the theme hook name is 'taxonomy_term'. If theming is
 * handled via a function then the corresponding function name is
 * theme_taxonomy_term(). If theming is handled via a template then the file
 * should be named according to the value of the 'template' key registered with
 * the theme hook (see hook_theme() for details). Default templates are
 * implemented with the Twig rendering engine and are named the same as the
 * theme hook, with underscores changed to hyphens, so for the 'taxonomy_term'
 * theme hook, the default template is 'taxonomy-term.html.twig'.
 *
 * @subsection sub_overriding_theme_hooks Overriding Theme Hooks
 * Themes may register new theme hooks within a hook_theme()
 * implementation, but it is more common for themes to override default
 * implementations provided by modules than to register entirely new theme
 * hooks. Themes can override a default implementation by implementing a
 * function named THEME_HOOK() (for example, the 'bartik' theme overrides the
 * default implementation of the 'menu_tree' theme hook by implementing a
 * bartik_menu_tree() function), or by adding a template file within its folder
 * structure that follows the template naming structure used by the theme's
 * rendering engine. For example, since the Bartik theme uses the Twig rendering
 * engine, it overrides the default implementation of the 'page' theme hook by
 * containing a 'page.html.twig' file within its folder structure.
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
 * If the theming implementation is a function, only the theme-hook-specific
 * preprocess functions (the ones ending in _HOOK) are called from the list
 * above. This is because theme hooks with function implementations need to be
 * fast, and calling the non-theme-hook-specific preprocess functions for them
 * would incur a noticeable performance penalty.
 *
 * @subsection sub_alternate_suggestions Suggesting Alternate Hooks
 * Alternate hooks can be suggested by implementing the hook-specific
 * hook_theme_suggestions_HOOK_alter() or the generic
 * hook_theme_suggestions_alter(). These alter hooks are used to manipulate an
 * array of suggested alternate theme hooks to use, in reverse order of
 * priority. _theme() will use the highest priority implementation that exists.
 * If none exists, _theme() will use the implementation for the theme hook it
 * was called with. These suggestions are similar to and are used for similar
 * reasons as assigning an array of theme hooks to the #theme property of a
 * renderable array. The difference is whether the suggestions are determined
 * when _theme() is called or through altering the suggestions via the
 * suggestion alter hooks.
 *
 * @see drupal_render()
 * @see _theme()
 * @see hook_theme()
 * @see hooks
 * @see callbacks
 * @see system_element_info()
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
 * For more detailed information, see _theme().
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
 * For more detailed information, see _theme().
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
  $suggestions[] = $hook . '__' . \Drupal::languageManager()->getCurrentLanguage()->id;
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
