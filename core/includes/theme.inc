<?php

/**
 * @file
 * The theme system, which controls the output of Drupal.
 *
 * The theme system allows for nearly all output of the Drupal system to be
 * customized by user themes.
 */

use Drupal\Core\Datetime\DatePreprocess;
use Drupal\Core\Extension\ThemeSettingsProvider;
use Drupal\Core\Field\FieldPreprocess;
use Drupal\Core\Pager\PagerPreprocess;
use Drupal\Core\Breadcrumb\BreadcrumbPreprocess;
use Drupal\Core\Menu\MenuPreprocess;
use Drupal\Core\Theme\ImagePreprocess;
use Drupal\Core\Theme\ThemePreprocess;
use Drupal\Core\Config\Config;
use Drupal\Core\Template\AttributeHelper;
use Drupal\Core\Theme\ThemeCommonElements;

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
 * Prepares variables for time templates.
 *
 * Default template: time.html.twig.
 *
 * @param array $variables
 *   An associative array possibly containing:
 *   - "attributes['timestamp']:".
 *   - "timestamp:".
 *   - "text:".
 *
 * @deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Initial
 *  template_preprocess functions are registered directly in hook_theme().
 *
 * @see https://www.drupal.org/node/3504125
 */
function template_preprocess_time(&$variables): void {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Initial template_preprocess functions are registered directly in hook_theme(). See https://www.drupal.org/node/3504125', E_USER_DEPRECATED);
  \Drupal::service(DatePreprocess::class)->preprocessTime($variables);
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
 * @deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Initial
 *  template_preprocess functions are registered directly in hook_theme().
 *
 * @see https://www.drupal.org/node/3504125
 * @see form_process_datetime()
 */
function template_preprocess_datetime_form(&$variables): void {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Initial template_preprocess functions are registered directly in hook_theme(). See https://www.drupal.org/node/3504125', E_USER_DEPRECATED);
  \Drupal::service(DatePreprocess::class)->preprocessDatetimeForm($variables);
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
 *
 * @deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Initial
 *  template_preprocess functions are registered directly in hook_theme().
 *
 * @see https://www.drupal.org/node/3504125
 */
function template_preprocess_datetime_wrapper(&$variables): void {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Initial template_preprocess functions are registered directly in hook_theme(). See https://www.drupal.org/node/3504125', E_USER_DEPRECATED);
  \Drupal::service(DatePreprocess::class)->preprocessDatetimeWrapper($variables);
}

/**
 * Prepares variables for links templates.
 *
 * Default template: links.html.twig.
 *
 * Unfortunately links templates duplicate the "active" class handling of l()
 * and LinkGenerator::generate() because it needs to be able to set the "active"
 * class not on the links themselves (<a> tags), but on the list items (<li>
 * tags) that contain the links. This is necessary for CSS to be able to style
 * list items differently when the link is active, since CSS does not yet allow
 * one to style list items only if it contains a certain element with a certain
 * class. I.e. we cannot yet convert this jQuery selector to a CSS selector:
 * jQuery('li:has("a.is-active")')
 *
 * @param array $variables
 *   An associative array containing:
 *   - links: An array of links to be themed. Each link itself is an array, with
 *     the following elements:
 *     - title: The link text.
 *     - url: (optional) The \Drupal\Core\Url object to link to. If the 'url'
 *       element is supplied, the 'title' and 'url' are used to generate a link
 *       through \Drupal::linkGenerator()->generate(). All data from the link
 *       array other than 'title' and 'url' are added as #options on
 *       the URL object. See \Drupal\Core\Url::fromUri() for details on the
 *       options. If no 'url' is supplied, the 'title' is printed as plain text.
 *     - attributes: (optional) Attributes for the anchor, or for the <span>
 *       tag used in its place if no 'url' is supplied. If element 'class' is
 *       included, it must be an array of one or more class names.
 *   - attributes: A keyed array of attributes for the <ul> containing the list
 *     of links.
 *   - set_active_class: (optional) Whether each link should compare the
 *     route_name + route_parameters or URL (path), language, and query options
 *     to the current URL, to determine whether the link is "active". If so,
 *     attributes will be added to the HTML elements for both the link and the
 *     list item that contains it, which will result in an "is-active" class
 *     being added to both. The class is added via JavaScript for authenticated
 *     users (in the active-link library), and via PHP for anonymous users (in
 *     the \Drupal\Core\EventSubscriber\ActiveLinkResponseFilter class).
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
 *     https://www.w3.org/TR/WCAG-TECHS/H42.html for more information.
 *
 * @deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Initial
 *  template_preprocess functions are registered directly in hook_theme().
 *
 * @see https://www.drupal.org/node/3504125
 * @see \Drupal\Core\Utility\LinkGenerator
 * @see \Drupal\Core\Utility\LinkGenerator::generate()
 * @see system_page_attachments()
 */
function template_preprocess_links(&$variables): void {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Initial template_preprocess functions are registered directly in hook_theme(). See https://www.drupal.org/node/3504125', E_USER_DEPRECATED);
  \Drupal::service(ThemePreprocess::class)->preprocessLinks($variables);
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
 *     building variables for image.html.twig templates to pass a meaningful
 *     value for this variable.
 *     - https://www.w3.org/TR/REC-html40/struct/objects.html#h-13.8
 *     - https://www.w3.org/TR/xhtml1/dtds.html
 *     - http://dev.w3.org/html5/spec/Overview.html#alt
 *   - title: The title text is displayed when the image is hovered in some
 *     popular browsers.
 *   - attributes: Associative array of attributes to be placed in the img tag.
 *   - srcset: Array of multiple URIs and sizes/multipliers.
 *   - sizes: The sizes attribute for viewport-based selection of images.
 *     phpcs:ignore
 *     - http://www.whatwg.org/specs/web-apps/current-work/multipage/embedded-content.html#introduction-3:viewport-based-selection-2
 *
 * @deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. Initial
 *   template_preprocess functions are registered directly in hook_theme().
 *
 * @see https://www.drupal.org/node/3504125
 */
function template_preprocess_image(&$variables): void {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. Initial template_preprocess functions are registered directly in hook_theme(). See https://www.drupal.org/node/3504125', E_USER_DEPRECATED);
  \Drupal::service(ImagePreprocess::class)->preprocessImage($variables);
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
 *     - data: The localized title of the table column, as a string or render
 *       array.
 *     - field: The database field represented in the table column (required
 *       if user is to be able to sort on this column).
 *     - sort: A default sort order for this column ("asc" or "desc"). Only
 *       one column should be given a default sort order because table sorting
 *       only applies to one column at a time.
 *     - initial_click_sort: Set the initial sort of the column when clicked.
 *       Defaults to "asc".
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
 *     - data: The string or render array to display in the table cell.
 *     - header: Indicates this cell is a header.
 *     - Any HTML attributes, such as "colspan", to apply to the table cell.
 *     Here's an example for $rows:
 *     @code
 *     $rows = [
 *       // Simple row
 *       [
 *         'Cell 1', 'Cell 2', 'Cell 3'
 *       ],
 *       // Row with attributes on the row and some of its cells.
 *       [
 *         'data' => ['Cell 1', ['data' => 'Cell 2', 'colspan' => 2]], 'class' => ['funky']
 *       ],
 *     ];
 *     @endcode
 *   - footer: An array of table rows which will be printed within a <tfoot>
 *     tag, in the same format as the rows element (see above).
 *   - attributes: An array of HTML attributes to apply to the table tag.
 *   - caption: A localized string to use for the <caption> tag.
 *   - colgroups: An array of column groups. Each element of the array can be
 *     either:
 *     - An array of columns, each of which is an associative array of HTML
 *       attributes applied to the <col> element.
 *     - An array of attributes applied to the <colgroup> element, which must
 *       include a "data" attribute. To add attributes to <col> elements,
 *       set the "data" attribute with an array of columns, each of which is an
 *       associative array of HTML attributes.
 *     Here's an example for $colgroup:
 *     @code
 *     $colgroup = [
 *       // <colgroup> with one <col> element.
 *       [
 *         [
 *           'class' => ['funky'], // Attribute for the <col> element.
 *         ],
 *       ],
 *       // <colgroup> with attributes and inner <col> elements.
 *       [
 *         'data' => [
 *           [
 *             'class' => ['funky'], // Attribute for the <col> element.
 *           ],
 *         ],
 *         'class' => ['jazzy'], // Attribute for the <colgroup> element.
 *       ],
 *     ];
 *     @endcode
 *     These optional tags are used to group and set properties on columns
 *     within a table. For example, one may easily group three columns and
 *     apply same background style to all.
 *   - sticky: Use a "sticky" table header.
 *   - empty: The message to display in an extra row if table does not have any
 *     rows.
 *
 * @deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. Initial
 *   template_preprocess functions are registered directly in hook_theme().
 *
 * @see https://www.drupal.org/node/3504125
 */
function template_preprocess_table(&$variables): void {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. Initial template_preprocess functions are registered directly in hook_theme(). See https://www.drupal.org/node/3504125', E_USER_DEPRECATED);
  \Drupal::service(ThemePreprocess::class)->preprocessTable($variables);
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
 *   - wrapper_attributes: HTML attributes to be applied to the list wrapper.
 *
 * @deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. Initial
 *  template_preprocess functions are registered directly in hook_theme().
 *
 * @see https://www.drupal.org/node/3504125
 */
function template_preprocess_item_list(&$variables): void {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. Initial template_preprocess functions are registered directly in hook_theme(). See https://www.drupal.org/node/3504125', E_USER_DEPRECATED);
  \Drupal::service(ThemePreprocess::class)->preprocessItemList($variables);
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
 *
 * @deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Initial
 *  template_preprocess functions are registered directly in hook_theme().
 *
 * @see https://www.drupal.org/node/3504125
 */
function template_preprocess_container(&$variables): void {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Initial template_preprocess functions are registered directly in hook_theme(). See https://www.drupal.org/node/3504125', E_USER_DEPRECATED);
  \Drupal::service(ThemePreprocess::class)->preprocessContainer($variables);
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
 *     dangerous HTML such as <script> tags.
 *   - active: The key for the currently active maintenance task.
 *
 * @deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. Initial
 *  template_preprocess functions are registered directly in hook_theme().
 *
 * @see https://www.drupal.org/node/3504125
 */
function template_preprocess_maintenance_task_list(&$variables): void {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. Initial template_preprocess functions are registered directly in hook_theme(). See https://www.drupal.org/node/3504125', E_USER_DEPRECATED);
  \Drupal::service(ThemePreprocess::class)->preprocessMaintenanceTaskList($variables);
}

/**
 * Adds a default set of helper variables for preprocessors and templates.
 *
 * This function is called for every theme hook. It is the first in the
 * sequence of preprocessing functions called when preparing variables for a
 * template.
 *
 * See the @link themeable Default theme implementations topic @endlink for
 * details.
 *
 * @deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. There is
 *   no replacement, default preprocess variables are added for all theme hooks
 *   directly in \Drupal\Core\Theme\ThemeManager.
 *
 * @see https://www.drupal.org/node/2340341
 */
function template_preprocess(&$variables, $hook, $info): void {
  @trigger_error('template_preprocess() is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. There is no replacement, default preprocess variables are added for all theme hooks directly in \Drupal\Core\Theme\ThemeManager. See https://www.drupal.org/node/2340341', E_USER_DEPRECATED);

  $themeManager = \Drupal::service('theme.manager');
  // ThemeManager::getDefaultTemplateVariables() is not on the interface, it is
  // internal and only public for backwards compatibility, verify it really
  // exists.
  if (method_exists($themeManager, 'getDefaultTemplateVariables')) {
    $variables += $themeManager->getDefaultTemplateVariables();
  }

  // When theming a render element, merge its #attributes into
  // $variables['attributes'].
  if (isset($info['render element'])) {
    $key = $info['render element'];
    if (isset($variables[$key]['#attributes'])) {
      $variables['attributes'] = AttributeHelper::mergeCollections($variables['attributes'], $variables[$key]['#attributes']);
    }
  }
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
 * @deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Initial
 *  template_preprocess functions are registered directly in hook_theme().
 *
 * @see https://www.drupal.org/node/3504125
 */
function template_preprocess_html(&$variables): void {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Initial template_preprocess functions are registered directly in hook_theme(). See https://www.drupal.org/node/3504125', E_USER_DEPRECATED);
  \Drupal::service(ThemePreprocess::class)->preprocessHtml($variables);
}

/**
 * Prepares variables for the page template.
 *
 * Default template: page.html.twig.
 *
 * See the page.html.twig template for the list of variables.
 *
 * @deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Initial
 * template_preprocess functions are registered directly in hook_theme().
 *
 * @see https://www.drupal.org/node/3504125
 */
function template_preprocess_page(&$variables): void {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Initial template_preprocess functions are registered directly in hook_theme(). See https://www.drupal.org/node/3504125', E_USER_DEPRECATED);
  \Drupal::service(ThemePreprocess::class)->preprocessPage($variables);
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

/**
 * Prepares variables for tablesort indicators.
 *
 * Default template: tablesort-indicator.html.twig.
 *
 * @deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. Initial
 *    template_preprocess functions are registered directly in hook_theme().
 *
 * @see https://www.drupal.org/node/3504125
 */
function template_preprocess_tablesort_indicator(&$variables): void {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. Initial template_preprocess functions are registered directly in hook_theme(). See https://www.drupal.org/node/3504125', E_USER_DEPRECATED);
  \Drupal::service(ThemePreprocess::class)->preprocessTablesortIndicator($variables);
}

/**
 * Prepares variables for maintenance page templates.
 *
 * Default template: maintenance-page.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - content - An array of page content.
 *
 * @deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. Initial
 *  template_preprocess functions are registered directly in hook_theme().
 *
 * @see https://www.drupal.org/node/3504125
 */
function template_preprocess_maintenance_page(&$variables): void {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. Initial template_preprocess functions are registered directly in hook_theme(). See https://www.drupal.org/node/3504125', E_USER_DEPRECATED);
  \Drupal::service(ThemePreprocess::class)->preprocessMaintenancePage($variables);
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
 * @deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. Initial
 *   template_preprocess functions are registered directly in hook_theme().
 *
 * @see https://www.drupal.org/node/3504125
 */
function template_preprocess_install_page(&$variables): void {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. Initial template_preprocess functions are registered directly in hook_theme(). See https://www.drupal.org/node/3504125', E_USER_DEPRECATED);
  \Drupal::service(ThemePreprocess::class)->preprocessInstallPage($variables);
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
 *
 * @deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. Initial
 *   template_preprocess functions are registered directly in hook_theme().
 *
 * @see https://www.drupal.org/node/3504125
 */
function template_preprocess_region(&$variables): void {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. Initial template_preprocess functions are registered directly in hook_theme(). See https://www.drupal.org/node/3504125', E_USER_DEPRECATED);
  \Drupal::service(ThemePreprocess::class)->preprocessRegion($variables);
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
 *
 * @deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. Initial
 *    template_preprocess functions are registered directly in hook_theme().
 *
 * @see https://www.drupal.org/node/3504125
 */
function template_preprocess_field(&$variables, $hook): void {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. Initial template_preprocess functions are registered directly in hook_theme(). See https://www.drupal.org/node/3504125', E_USER_DEPRECATED);
  \Drupal::service(FieldPreprocess::class)->preprocessField($variables);
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
 *
 * @deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. Initial
 *    template_preprocess functions are registered directly in hook_theme().
 *
 * @see https://www.drupal.org/node/3504125
 */
function template_preprocess_field_multiple_value_form(&$variables): void {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. Initial template_preprocess functions are registered directly in hook_theme(). See https://www.drupal.org/node/3504125', E_USER_DEPRECATED);
  \Drupal::service(FieldPreprocess::class)->preprocessFieldMultipleValueForm($variables);
}

/**
 * Prepares variables for breadcrumb templates.
 *
 * Default template: breadcrumb.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - links: A list of \Drupal\Core\Link objects which should be rendered.
 *
 * @deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. Initial
 *   template_preprocess functions are registered directly in hook_theme().
 *
 * @see https://www.drupal.org/node/3504125
 */
function template_preprocess_breadcrumb(&$variables): void {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. Initial template_preprocess functions are registered directly in hook_theme(). See https://www.drupal.org/node/3504125', E_USER_DEPRECATED);
  \Drupal::service(BreadcrumbPreprocess::class)->preprocessBreadcrumb($variables);
}

/**
 * Prepares variables for pager templates.
 *
 * Default template: pager.html.twig.
 *
 * Menu callbacks that display paged query results should use #type => pager
 * to retrieve a pager control so that users can view other results. Format a
 * list of nearby pages with additional query results.
 *
 * @param array $variables
 *   An associative array containing:
 *   - pager: A render element containing:
 *     - #tags: An array of labels for the controls in the pager.
 *     - #element: An optional integer to distinguish between multiple pagers on
 *       one page.
 *     - #pagination_heading_level: An optional heading level for the pager.
 *     - #parameters: An associative array of query string parameters to append
 *       to the pager links.
 *     - #route_parameters: An associative array of the route parameters.
 *     - #quantity: The number of pages in the list.
 *
 * @deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. Initial
 *   template_preprocess functions are registered directly in hook_theme().
 *
 * @see https://www.drupal.org/node/3504125
 */
function template_preprocess_pager(&$variables): void {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. Initial template_preprocess functions are registered directly in hook_theme(). See https://www.drupal.org/node/3504125', E_USER_DEPRECATED);
  \Drupal::service(PagerPreprocess::class)->preprocessPager($variables);
}

/**
 * Prepares variables for single local task link templates.
 *
 * Default template: menu-local-task.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: A render element containing:
 *     - #link: A menu link array with 'title', 'url', and (optionally)
 *       'localized_options' keys.
 *     - #active: A boolean indicating whether the local task is active.
 *
 * @deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. Initial
 *   template_preprocess functions are registered directly in hook_theme().
 *
 * @see https://www.drupal.org/node/3504125
 */
function template_preprocess_menu_local_task(&$variables): void {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. Initial template_preprocess functions are registered directly in hook_theme(). See https://www.drupal.org/node/3504125', E_USER_DEPRECATED);
  \Drupal::service(MenuPreprocess::class)->preprocessMenuLocalTask($variables);
}

/**
 * Prepares variables for single local action link templates.
 *
 * Default template: menu-local-action.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: A render element containing:
 *     - #link: A menu link array with 'title', 'url', and (optionally)
 *       'localized_options' keys.
 *
 * @deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. Initial
 *   template_preprocess functions are registered directly in hook_theme().
 *
 * @see https://www.drupal.org/node/3504125
 */
function template_preprocess_menu_local_action(&$variables): void {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. Initial template_preprocess functions are registered directly in hook_theme(). See https://www.drupal.org/node/3504125', E_USER_DEPRECATED);
  \Drupal::service(MenuPreprocess::class)->preprocessMenuLocalAction($variables);
}

/**
 * Callback for usort() of field item form elements.
 *
 * Sorts using ['_weight']['#value']
 *
 * @deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. There is no
 *   replacement.
 *
 * @see https://www.drupal.org/node/3504125
 */
function _field_multiple_value_form_sort_helper($a, $b) {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. Initial template_preprocess functions are registered directly in hook_theme(). See https://www.drupal.org/node/3504125', E_USER_DEPRECATED);
  $a_weight = (is_array($a) && isset($a['_weight']['#value']) ? $a['_weight']['#value'] : 0);
  $b_weight = (is_array($b) && isset($b['_weight']['#value']) ? $b['_weight']['#value'] : 0);
  return $a_weight - $b_weight;
}

/**
 * Provides theme registration for themes across .inc files.
 *
 * @deprecated in drupal:11.1.0 and is removed from drupal:12.0.0. Use
 *   \Drupal\Core\Theme\ThemeCommonElements::commonElements() instead,
 *
 * @see https://www.drupal.org/node/3488470
 */
function drupal_common_theme(): array {
  @trigger_error('drupal_common_theme() is deprecated in drupal:11.1.0 and is removed from drupal:12.0.0. Use \Drupal\Core\Theme\ThemeCommonElements::commonElements() instead. See https://www.drupal.org/node/3488470', E_USER_DEPRECATED);
  return ThemeCommonElements::commonElements();
}

/**
 * Prepares variables for authorize.php operation report templates.
 *
 * This report displays the results of an operation run via authorize.php.
 *
 * Default template: authorize-report.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - messages: An array of result messages.
 *
 * @deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. There is no
 *   replacement. Use composer to manage the code for your site.
 *
 * @see https://www.drupal.org/node/3522119
 */
function template_preprocess_authorize_report(&$variables): void {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. There is no replacement. Use composer to manage the code for your site. See https://www.drupal.org/node/3522119', E_USER_DEPRECATED);
  $messages = [];
  if (!empty($variables['messages'])) {
    foreach ($variables['messages'] as $heading => $logs) {
      $items = [];
      foreach ($logs as $number => $log_message) {
        if ($number === '#abort') {
          continue;
        }
        $class = 'authorize-results__' . ($log_message['success'] ? 'success' : 'failure');
        $items[] = [
          '#wrapper_attributes' => ['class' => [$class]],
          '#markup' => $log_message['message'],
        ];
      }
      $messages[] = [
        '#theme' => 'item_list',
        '#items' => $items,
        '#title' => $heading,
      ];
    }
  }
  $variables['messages'] = $messages;
}
