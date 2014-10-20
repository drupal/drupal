<?php

/**
 * @defgroup themeable Theme system overview
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
 * @section sec_twig_theme Twig Templating Engine
 * Drupal 8 uses the templating engine Twig. Twig offers developers a fast,
 * secure, and flexible method for building templates for Drupal 8 sites. Twig
 * also offers substantial usability improvements over PHPTemplate, and does
 * not require front-end developers to know PHP to build and manipulate Drupal
 * 8 themes.
 *
 * For further information on theming in Drupal 8 see
 * https://www.drupal.org/theme-guide/8
 *
 * For further Twig documentation see
 * http://twig.sensiolabs.org/doc/templates.html
 *
 * @section sec_theme_hooks Theme Hooks
 * The theme system is invoked in drupal_render() by calling the internal
 * _theme() function, which operates on the concept of "theme hooks". Theme
 * hooks define how a particular type of data should be rendered. They are
 * registered by modules by implenting hook_theme(), which specifies the name of
 * the hook, the input "variables" used to provide data and options, and other
 * information. Modules implementing hook_theme() also need to provide a default
 * implementation for each of their theme hooks, normally in a Twig file, and
 * they may also provide preprocessing functions. For example, the core Search
 * module defines a theme hook for a search result item in search_theme():
 * @code
 * return array(
 *   'search_result' => array(
 *     'variables' => array(
 *       'result' => NULL,
 *       'plugin_id' => NULL,
 *     ),
 *    'file' => 'search.pages.inc',
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
 * @section Assets
 *
 * We can distinguish between three types of assets:
 * 1. unconditional page-level assets (loaded on all pages where the theme is in
 *    use): these are defined in the theme's *.info.yml file.
 * 2. conditional page-level assets (loaded on all pages where the theme is in
 *    use and a certain condition is met): these are attached in
 *    hook_page_attachments_alter(), e.g.:
 *    @code
 *    function THEME_page_attachments_alter(array &$page) {
 *      if ($some_condition) {
 *        $page['#attached']['library'][] = 'mytheme/something';
 *      }
 *    }
 *    @endcode
 * 3. template-specific assets (loaded on all pages where a specific template is
 *    in use): these can be added by in preprocessing functions, using @code
 *    $variables['#attached'] @endcode, e.g.:
 *    @code
 *    function THEME_preprocess_menu_local_action(array &$variables) {
 *      // We require Modernizr's touch test for button styling.
 *      $variables['#attached']['library'][] = 'core/modernizr';
 *    }
 *    @endcode
 *
 * @see hooks
 * @see callbacks
 * @see theme_render
 *
 * @} End of "defgroup themeable".
 */

/**
 * @defgroup theme_render Render API overview
 * @{
 * Overview of the Theme system and Render API.
 *
 * The main purpose of Drupal's Theme system is to give themes complete control
 * over the appearance of the site, which includes the markup returned from HTTP
 * requests and the CSS files used to style that markup. In order to ensure that
 * a theme can completely customize the markup, module developers should avoid
 * directly writing HTML markup for pages, blocks, and other user-visible output
 * in their modules, and instead return structured "render arrays" (see @ref
 * arrays below). Doing this also increases usability, by ensuring that the
 * markup used for similar functionality on different areas of the site is the
 * same, which gives users fewer user interface patterns to learn.
 *
 * For further information on the Theme and Render APIs, see:
 * - https://drupal.org/documentation/theme
 * - https://drupal.org/node/722174
 * - https://drupal.org/node/933976
 * - https://drupal.org/node/930760
 *
 * @todo Check these links. Some are for Drupal 7, and might need updates for
 *   Drupal 8.
 *
 * @section arrays Render arrays
 * The core structure of the Render API is the render array, which is a
 * hierarchical associative array containing data to be rendered and properties
 * describing how the data should be rendered. A render array that is returned
 * by a function to specify markup to be sent to the web browser or other
 * services will eventually be rendered by a call to drupal_render(), which will
 * recurse through the render array hierarchy if appropriate, making calls into
 * the theme system to do the actual rendering. If a function or method actually
 * needs to return rendered output rather than a render array, the best practice
 * would be to create a render array, render it by calling drupal_render(), and
 * return that result, rather than writing the markup directly. See the
 * documentation of drupal_render() for more details of the rendering process.
 *
 * Each level in the hierarchy of a render array (including the outermost array)
 * has one or more array elements. Array elements whose names start with '#' are
 * known as "properties", and the array elements with other names are "children"
 * (constituting the next level of the hierarchy); the names of children are
 * flexible, while property names are specific to the Render API and the
 * particular type of data being rendered. A special case of render arrays is a
 * form array, which specifies the form elements for an HTML form; see the
 * @link form_api Form generation topic @endlink for more information on forms.
 *
 * Render arrays (at each level in the hierarchy) will usually have one of the
 * following three properties defined:
 * - #type: Specifies that the array contains data and options for a particular
 *   type of "render element" (examples: 'form', for an HTML form; 'textfield',
 *   'submit', and other HTML form element types; 'table', for a table with
 *   rows, columns, and headers). See @ref elements below for more on render
 *   element types.
 * - #theme: Specifies that the array contains data to be themed by a particular
 *   theme hook. Modules define theme hooks by implementing hook_theme(), which
 *   specifies the input "variables" used to provide data and options; if a
 *   hook_theme() implementation specifies variable 'foo', then in a render
 *   array, you would provide this data using property '#foo'. Modules
 *   implementing hook_theme() also need to provide a default implementation for
 *   each of their theme hooks, normally in a Twig file. For more information
 *   and to discover available theme hooks, see the documentation of
 *   hook_theme() and the
 *   @link themeable Default theme implementations topic. @endlink
 * - #markup: Specifies that the array provides HTML markup directly. Unless the
 *   markup is very simple, such as an explanation in a paragraph tag, it is
 *   normally preferable to use #theme or #type instead, so that the theme can
 *   customize the markup.
 *
 * @section elements Render elements
 * Render elements are defined by Drupal core and modules. The primary way to
 * define a render element is to create a render element plugin. There are
 * two types of render element plugins:
 * - Generic elements: Generic render element plugins implement
 *   \Drupal\Core\Render\Element\ElementInterface, are annotated with
 *   \Drupal\Core\Render\Annotation\RenderElement annotation, go in plugin
 *   namespace Element, and generally extend the
 *   \Drupal\Core\Render\Element\RenderElement base class.
 * - Form input elements: Render elements representing form input elements
 *   implement \Drupal\Core\Render\Element\FormElementInterface, are annotated
 *   with \Drupal\Core\Render\Annotation\FormElement annotation, go in plugin
 *   namespace Element, and generally extend the
 *   \Drupal\Core\Render\Element\FormElement base class.
 * See the @link plugin_api Plugin API topic @endlink for general information
 * on plugins, and look for classes with the RenderElement or FormElement
 * annotation to discover what render elements are available.
 *
 * Modules can also currently define render elements by implementing
 * hook_element_info(), although defining a plugin is preferred.
 * properties. Look through implementations of hook_element_info() to discover
 * elements defined this way.
 *
 * @see themeable
 *
 * @}
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
 *   The current state of the form.
 */
function hook_form_system_theme_settings_alter(&$form, \Drupal\Core\Form\FormStateInterface $form_state) {
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

  if (!\Drupal::currentUser()->hasPermission('access contextual links')) {
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
  $suggestions[] = $hook . '__' . \Drupal::languageManager()->getCurrentLanguage()->getId();
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
 * Respond to themes being installed.
 *
 * @param array $theme_list
 *   Array containing the names of the themes being installed.
 *
 * @see \Drupal\Core\Extension\ThemeHandler::install()
 */
function hook_themes_installed($theme_list) {
  foreach ($theme_list as $theme) {
    block_theme_initialize($theme);
  }
}

/**
 * Respond to themes being uninstalled.
 *
 * @param array $theme_list
 *   Array containing the names of the themes being uninstalled.
 *
 * @see \Drupal\Core\Extension\ThemeHandler::uninstall()
 */
function hook_themes_uninstalled(array $themes) {
  // Remove some state entries depending on the theme.
  foreach ($themes as $theme) {
    \Drupal::state()->delete('example.' . $theme);
  }
}

/**
 * Declare a template file extension to be used with a theme engine.
 *
 * This hook is used in a theme engine implementation in the format of
 * ENGINE_extension().
 *
 * @return string
 *   The file extension the theme engine will recognize.
 */
function hook_extension() {
  // Extension for template base names in Twig.
  return '.html.twig';
}

/**
 * Render a template using the theme engine.
 *
 * @param string $template_file
 *   The path (relative to the Drupal root directory) to the template to be
 *   rendered including its extension in the format 'path/to/TEMPLATE_NAME.EXT'.
 * @param array $variables
 *   A keyed array of variables that are available for composing the output. The
 *   theme engine is responsible for passing all the variables to the template.
 *   Depending on the code in the template, all or just a subset of the
 *   variables might be used in the template.
 *
 * @return string
 *   The output generated from the template. In most cases this will be a string
 *   containing HTML markup.
 */
function hook_render_template($template_file, $variables) {
  $twig_service = \Drupal::service('twig');

  return $twig_service->loadTemplate($template_file)->render($variables);
}
