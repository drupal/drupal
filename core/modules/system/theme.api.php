<?php

/**
 * @defgroup themeable Default theme implementations
 * @{
 * Functions and templates for the user interface that themes can override.
 *
 * Drupal's theme system allows a theme to have nearly complete control over
 * the appearance of the site, which includes both the markup and the CSS used
 * to style the markup. For this system to work, modules, instead of writing
 * HTML markup directly, need to return "render arrays", which are structured
 * hierarchical arrays that include the data to be rendered into HTML (or XML or
 * another output format), and options that affect the markup. Render arrays
 * are ultimately rendered into HTML or other output formats by recursive calls
 * to drupal_render(), traversing the depth of the render array hierarchy. At
 * each level, the theme system is invoked to do the actual rendering. See the
 * documentation of drupal_render() and the
 * @link theme_render Theme system and Render API topic @endlink for more
 * information about render arrays and rendering.
 *
 * @section sec_theme_hooks Theme Hooks The theme system is invoked in
 * drupal_render() by calling the internal _theme() function, which operates on
 * the concept of "theme hooks". Theme hooks define how a particular type of
 * data should be rendered. They are registered by modules by implenting
 * hook_theme(), which specifies the name of the hook, the input "variables"
 * used to provide data and options, and other information. Modules implementing
 * hook_theme() also need to provide a default implementation for each of their
 * theme hooks, normally in a Twig file, and they may also provide preprocessing
 * functions. For example, the core Search module defines a theme hook for a
 * search result item in search_theme():
 * @code
 * return array(
 *   'search_result' => array(
 *     'variables' => array(
 *       'result' => NULL,
 *       'plugin_id' => NULL,
 *     ),
 *    'file' => 'search.pages.inc',
 *    'template' => 'search-result',
 *   ),
 * );
 * @endcode
 * Given this definition, the template file with the default implementation is
 * search-result.html.twig, which can be found in the
 * core/modules/search/templates directory, and the variables for rendering are
 * the search result and the plugin ID. In addition, there is a function
 * template_preprocess_search_result(), located in file search.pages.inc, which
 * preprocesses the information from the input variables so that it can be
 * rendered by the Twig template; the processed variables that the Twig template
 * receives are documented in the header of the default Twig template file.
 *
 * hook_theme() implementations can also specify that a theme hook
 * implementation is a theme function, but that is uncommon. It is only used for
 * special cases, for performance reasons, because rendering using theme
 * functions is somewhat faster than theme templates.
 *
 * @section sec_overriding_theme_hooks Overriding Theme Hooks
 * Themes may register new theme hooks within a hook_theme() implementation, but
 * it is more common for themes to override default implementations provided by
 * modules than to register entirely new theme hooks. Themes can override a
 * default implementation by creating a template file with the same name as the
 * default implementation; for example, to override the display of search
 * results, a theme would add a file called search-result.html.twig to its
 * templates directory. A good starting point for doing this is normally to
 * copy the default implementation template, and then modifying it as desired.
 *
 * In the uncommon case that a theme hook uses a theme function instead of a
 * template file, a module would provide a default implementation function
 * called theme_HOOK, where HOOK is the name of the theme hook (for example,
 * theme_search_result() would be the name of the function for search result
 * theming). In this case, a theme can override the default implentation by
 * defining a function called THEME_HOOK() in its THEME.theme file, where THEME
 * is the machine name of the theme (for example, 'bartik' is the machine name
 * of the core Bartik theme, and it would define a function called
 * bartik_search_result() in the bartik.theme file, if the search_result hook
 * implementation was a function instead of a template). Normally, copying the
 * default function is again a good starting point for overriding its behavior.
 *
 * @section sec_preprocess_templates Preprocessing for Template Files
 * If the theme implementation is a template file, several functions are called
 * before the template file is invoked to modify the variables that are passed
 * to the template. These make up the "preprocessing" phase, and are executed
 * (if they exist), in the following order (note that in the following list,
 * HOOK indicates the theme hook name, MODULE indicates a module name, THEME
 * indicates a theme name, and ENGINE indicates a theme engine name). Modules,
 * themes, and theme engines can provide these functions to modify how the
 * data is preprocessed, before it is passed to the theme template:
 * - template_preprocess(&$variables, $hook): Creates a default set of variables
 *   for all theme hooks with template implementations. Provided by Drupal Core.
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
 * @section sec_preprocess_functions Preprocessing for Theme Functions
 * If the theming implementation is a function, only the theme-hook-specific
 * preprocess functions (the ones ending in _HOOK) are called from the list
 * above. This is because theme hooks with function implementations need to be
 * fast, and calling the non-theme-hook-specific preprocess functions for them
 * would incur a noticeable performance penalty.
 *
 * @section sec_suggestions Theme hook suggestions
 * In some cases, instead of calling the base theme hook implementation (either
 * the default provided by the module that defined the hook, or the override
 * provided by the theme), the theme system will instead look for "suggestions"
 * of other hook names to look for. Suggestions can be specified in several
 * ways:
 * - In a render array, the '#theme' property (which gives the name of the hook
 *   to use) can be an array of theme hook names instead of a single hook name.
 *   In this case, the render system will look first for the highest-priority
 *   hook name, and if no implementation is found, look for the second, and so
 *   on. Note that the highest-priority suggestion is at the end of the array.
 * - In a render array, the '#theme' property can be set to the name of a hook
 *   with a '__SUGGESTION' suffix. For example, in search results theming, the
 *   hook 'item_list__search_results' is given. In this case, the render system
 *   will look for theme templates called item-list--search-results.html.twig,
 *   which would only be used for rendering item lists containing search
 *   results, and if this template is not found, it will fall back to using the
 *   base item-list.html.twig template. This type of suggestion can also be
 *   combined with providing an array of theme hook names as described above.
 * - A module can implement hook_theme_suggestions_HOOK(). This allows the
 *   module that defines the theme template to dynamically return an array
 *   containing specific theme hook names (presumably with '__' suffixes as
 *   defined above) to use as suggestions. For example, the Search module
 *   does this in search_theme_suggestions_search_result() to suggest
 *   search_result__PLUGIN as the theme hook for search result items, where
 *   PLUGIN is the machine name of the particular search plugin type that was
 *   used for the search (such as node_search or user_search).
 *
 * @section sec_alternate_suggestions Altering theme hook suggestions
 * Modules can also alter the theme suggestions provided using the mechanisms
 * of the previous section. There are two hooks for this: the
 * theme-hook-specific hook_theme_suggestions_HOOK_alter() and the generic
 * hook_theme_suggestions_alter(). These hooks get the current list of
 * suggestions as input, and can change this array (adding suggestions and
 * removing them).
 *
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
