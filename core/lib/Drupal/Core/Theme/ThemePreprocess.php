<?php

namespace Drupal\Core\Theme;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ThemeSettingsProvider;
use Drupal\Core\Installer\InstallerKernel;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Markup;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;
use Drupal\Core\Utility\TableSort;

/**
 * Preprocess for common/core theme templates.
 *
 * @internal
 */
class ThemePreprocess {

  use StringTranslationTrait;

  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected PathMatcherInterface $pathMatcher,
    protected CurrentPathStack $currentPathStack,
    protected LanguageManagerInterface $languageManager,
    protected RendererInterface $renderer,
    protected RouteMatchInterface $routeMatch,
    protected ThemeManagerInterface $themeManager,
    protected ThemeSettingsProvider $themeSettingsProvider,
  ) {
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
  public function preprocessContainer(array &$variables): void {
    $variables['has_parent'] = FALSE;
    $element = $variables['element'];
    // Ensure #attributes is set.
    $element += ['#attributes' => []];

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
   * Prepares variables for links templates.
   *
   * Default template: links.html.twig.
   *
   * Unfortunately links templates duplicate the "active" class handling of l()
   * and LinkGenerator::generate() because it needs to be able to set the
   * "active" class not on the links themselves (<a> tags), but on the list
   * items (<li> tags) that contain the links. This is necessary for CSS to be
   * able to style list items differently when the link is active, since CSS
   * does not yet allow one to style list items only if it contains a certain
   * element with a certain class. I.e. we cannot yet convert this jQuery
   * selector to a CSS selector: jQuery('li:has("a.is-active")')
   *
   * @param array $variables
   *   An associative array containing:
   *   - links: An array of links to be themed. Each link itself is an array,
   *     with the following elements:
   *     - title: The link text.
   *     - url: (optional) The \Drupal\Core\Url object to link to. If the 'url'
   *       element is supplied, the 'title' and 'url' are used to generate a
   *       link through \Drupal::linkGenerator()->generate(). All data from the
   *       link array other than 'title' and 'url' are added as #options on
   *       the URL object. See \Drupal\Core\Url::fromUri() for details on the
   *       options. If no 'url' is supplied, the 'title' is printed as plain
   *       text.
   *     - attributes: (optional) Attributes for the anchor, or for the <span>
   *       tag used in its place if no 'url' is supplied. If element 'class' is
   *       included, it must be an array of one or more class names.
   *   - attributes: A keyed array of attributes for the <ul> containing the
   *     list of links.
   *   - set_active_class: (optional) Whether each link should compare the
   *     route_name + route_parameters or URL (path), language, and query
   *     options to the current URL, to determine whether the link is "active".
   *     If so, attributes will be added to the HTML elements for both the link
   *     and the list item that contains it, which will result in an "is-active"
   *     class being added to both. The class is added via JavaScript for
   *     authenticated users (in the active-link library), and via PHP for
   *     anonymous users (in the
   *     \Drupal\Core\EventSubscriber\ActiveLinkResponseFilter class).
   *   - heading: (optional) A heading to precede the links. May be an
   *     associative array or a string. If it's an array, it can have the
   *     following elements:
   *     - text: The heading text.
   *     - level: The heading level (e.g. 'h2', 'h3').
   *     - attributes: (optional) An array of the CSS attributes for the
   *       heading.
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
   * @see \Drupal\Core\Utility\LinkGenerator
   * @see \Drupal\Core\Utility\LinkGenerator::generate()
   * @see system_page_attachments()
   */
  public function preprocessLinks(array &$variables): void {
    $links = $variables['links'];
    $heading = &$variables['heading'];

    if (!empty($links)) {
      // Prepend the heading to the list, if any.
      if (!empty($heading)) {
        // Convert a string heading into an array, using a <h2> tag by default.
        if (is_string($heading)) {
          $heading = ['text' => $heading];
        }
        // Merge in default array properties into $heading.
        $heading += [
          'level' => 'h2',
          'attributes' => [],
        ];
        // Convert the attributes array into an Attribute object.
        $heading['attributes'] = new Attribute($heading['attributes']);
      }

      $variables['links'] = [];
      foreach ($links as $key => $link) {
        $item = [];
        $link += [
          'ajax' => NULL,
          'url' => NULL,
        ];

        $li_attributes = [];
        $keys = ['title', 'url'];
        $link_element = [
          '#type' => 'link',
          '#title' => $link['title'],
          '#options' => array_diff_key($link, array_combine($keys, $keys)),
          '#url' => $link['url'],
          '#ajax' => $link['ajax'],
        ];

        // Handle links and ensure that the active class is added on the LIs,
        // but only if the 'set_active_class' option is not empty. Links
        // templates duplicate the "is-active" class handling of l() and
        // LinkGenerator::generate() because they need to be able to set the
        // "is-active" class not on the links themselves (<a> tags), but on the
        // list items (<li> tags) that contain the links. This is necessary for
        // CSS to be able to style list items differently when the link is
        // active, since CSS does not yet allow one to style list items only if
        // they contain a certain element with a certain class. That is, we
        // cannot yet convert this jQuery selector to a CSS selector:
        // jQuery('li:has("a.is-active")')
        if (isset($link['url'])) {
          if (!empty($variables['set_active_class'])) {

            // Also enable set_active_class for the contained link.
            $link_element['#options']['set_active_class'] = TRUE;

            if (!empty($link['language'])) {
              $li_attributes['data-drupal-language'] = $link['language']->getId();
            }

            // Add a "data-drupal-link-query" attribute to let the
            // drupal.active-link library know the query in a standardized
            // manner. Only add the data- attribute. The "is-active" class will
            // be calculated using JavaScript, to prevent breaking the render
            // cache.
            if (!empty($link['query'])) {
              $query = $link['query'];
              ksort($query);
              $li_attributes['data-drupal-link-query'] = Json::encode($query);
            }

            /** @var \Drupal\Core\Url $url */
            $url = $link['url'];
            if ($url->isRouted()) {
              // Add a "data-drupal-link-system-path" attribute to let the
              // drupal.active-link library know the path in a standardized
              // manner. Only add the data- attribute. The "is-active" class
              // will be calculated using JavaScript, to prevent breaking the
              // render cache.
              $system_path = $url->getInternalPath();
              // @todo System path is deprecated - use the route name and parameters.
              // Special case for the front page.
              $li_attributes['data-drupal-link-system-path'] = $system_path == '' ? '<front>' : $system_path;
            }
          }

          $item['link'] = $link_element;
        }

        // Handle title-only text items.
        $item['text'] = $link['title'];
        if (isset($link['attributes'])) {
          $item['text_attributes'] = new Attribute($link['attributes']);
        }

        // Handle list item attributes.
        $item['attributes'] = new Attribute($li_attributes);

        // Add the item to the list of links.
        $variables['links'][$key] = $item;
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
   */
  public function preprocessHtml(array &$variables): void {
    $variables['page'] = $variables['html']['page'];
    unset($variables['html']['page']);
    $variables['page_top'] = NULL;
    if (isset($variables['html']['page_top'])) {
      $variables['page_top'] = $variables['html']['page_top'];
      unset($variables['html']['page_top']);
    }
    $variables['page_bottom'] = NULL;
    if (isset($variables['html']['page_bottom'])) {
      $variables['page_bottom'] = $variables['html']['page_bottom'];
      unset($variables['html']['page_bottom']);
    }

    $variables['html_attributes'] = new Attribute();

    // <html> element attributes.
    $language_interface = $this->languageManager->getCurrentLanguage();
    $variables['html_attributes']['lang'] = $language_interface->getId();
    $variables['html_attributes']['dir'] = $language_interface->getDirection();

    if (isset($variables['db_is_active']) && !$variables['db_is_active']) {
      $variables['db_offline'] = TRUE;
    }

    // Add a variable for the root path. This can be used to create a class and
    // theme the page depending on the current path (e.g. node, admin, user) as
    // well as more specific data like path-frontpage.
    $is_front_page = $this->pathMatcher->isFrontPage();

    if ($is_front_page) {
      $variables['root_path'] = FALSE;
    }
    else {
      $system_path = $this->currentPathStack->getPath();
      $variables['root_path'] = explode('/', $system_path)[1];
    }

    $site_config = $this->configFactory->get('system.site');
    // Construct page title.
    if (isset($variables['page']['#title']) && is_array($variables['page']['#title'])) {
      // Do an early render if the title is a render array.
      $variables['page']['#title'] = (string) $this->renderer->render($variables['page']['#title']);
    }
    if (!empty($variables['page']['#title'])) {
      $head_title = [
        // Marking the title as safe since it has had the tags stripped.
        'title' => Markup::create(trim(strip_tags($variables['page']['#title']))),
        'name' => $site_config->get('name'),
      ];
    }
    // @todo Remove once views is not bypassing the view subscriber anymore.
    //   @see https://www.drupal.org/node/2068471
    elseif ($is_front_page) {
      $head_title = [
        'title' => $this->t('Home'),
        'name' => $site_config->get('name'),
      ];
    }
    else {
      $head_title = ['name' => $site_config->get('name')];
      if ($site_config->get('slogan')) {
        $head_title['slogan'] = strip_tags($site_config->get('slogan'));
      }
    }

    $variables['head_title'] = $head_title;

    // Create placeholder strings for these keys.
    // @see \Drupal\Core\Render\HtmlResponseSubscriber
    $types = [
      'styles' => 'css',
      'scripts' => 'js',
      'scripts_bottom' => 'js-bottom',
      'head' => 'head',
    ];
    $variables['placeholder_token'] = Crypt::randomBytesBase64(55);
    foreach ($types as $type => $placeholder_name) {
      $placeholder = '<' . $placeholder_name . '-placeholder token="' . $variables['placeholder_token'] . '">';
      $variables['#attached']['html_response_attachment_placeholders'][$type] = $placeholder;
    }
  }

  /**
   * Prepares variables for the page template.
   *
   * Default template: page.html.twig.
   *
   * See the page.html.twig template for the list of variables.
   */
  public function preprocessPage(array &$variables): void {
    $language_interface = $this->languageManager->getCurrentLanguage();

    foreach ($this->themeManager->getActiveTheme()->getRegions() as $region) {
      if (!isset($variables['page'][$region])) {
        $variables['page'][$region] = [];
      }
    }

    $variables['base_path'] = base_path();
    $variables['front_page'] = Url::fromRoute('<front>')->toString();
    $variables['language'] = $language_interface;

    // An exception might be thrown.
    try {
      $variables['is_front'] = $this->pathMatcher->isFrontPage();
    }
    catch (\Exception) {
      // If the database is not yet available, set default values for these
      // variables.
      $variables['is_front'] = FALSE;
      $variables['db_is_active'] = FALSE;
    }

    if (($node = $this->routeMatch->getParameter('node')) || ($node = $this->routeMatch->getParameter('node_preview'))) {
      $variables['node'] = $node;
    }
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
   * @see system_page_attachments()
   */
  public function preprocessMaintenancePage(array &$variables): void {
    // @todo Rename the templates to page--maintenance + page--install.
    $this->preprocessPage($variables);

    // @see system_page_attachments()
    $variables['#attached']['library'][] = 'system/maintenance';

    // Maintenance page and install page need branding info in variables because
    // there is no blocks.
    $site_config = $this->configFactory->get('system.site');
    $variables['logo'] = $this->themeSettingsProvider->getSetting('logo.url');
    $variables['site_name'] = $site_config->get('name');
    $variables['site_slogan'] = $site_config->get('slogan');

    // Maintenance page and install page need page title in variable because
    // there are no blocks.
    $variables['title'] = $variables['page']['#title'];
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
   * @see \Drupal\Core\Theme\ThemePreprocess::preprocessMaintenancePage()
   */
  public function preprocessInstallPage(array &$variables): void {
    $installer_active_task = NULL;
    if (defined('MAINTENANCE_MODE') && MAINTENANCE_MODE === 'install' && InstallerKernel::installationAttempted()) {
      $installer_active_task = $GLOBALS['install_state']['active_task'];
    }

    $this->preprocessMaintenancePage($variables);

    // Override the site name that is displayed on the page, since Drupal is
    // still in the process of being installed.
    $distribution_name = drupal_install_profile_distribution_name();
    $variables['site_name'] = $distribution_name;
    $variables['site_version'] = $installer_active_task ? drupal_install_profile_distribution_version() : '';
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
  public function preprocessRegion(array &$variables): void {
    // Create the $content variable that templates expect.
    $variables['content'] = $variables['elements']['#children'];
    $variables['region'] = $variables['elements']['#region'];
  }

  /**
   * Prepares variables for table templates.
   *
   * Default template: table.html.twig.
   *
   * @param array $variables
   *   An associative array containing:
   *   - header: An array containing the table headers. Each element of the
   *     array can be either a localized string or an associative array with the
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
   *       set the "data" attribute with an array of columns, each of which is
   *       an associative array of HTML attributes.
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
   *   - empty: The message to display in an extra row if table does not have
   *     any rows.
   */
  public function preprocessTable(array &$variables): void {
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
          $colgroup_attributes = [];
        }
        $colgroup = [];
        $colgroup['attributes'] = new Attribute($colgroup_attributes);
        $colgroup['cols'] = [];

        // Build columns.
        if (is_array($cols) && !empty($cols)) {
          foreach ($cols as $col_key => $col) {
            $colgroup['cols'][$col_key]['attributes'] = new Attribute($col);
          }
        }
      }
    }

    // Build an associative array of responsive classes keyed by column.
    $responsive_classes = [];

    // Format the table header:
    $ts = [];
    $header_columns = 0;
    if (!empty($variables['header'])) {
      $ts = TableSort::getContextFromRequest($variables['header'], \Drupal::request());

      // Use a separate index with responsive classes as headers
      // may be associative.
      $responsive_index = -1;
      foreach ($variables['header'] as $col_key => $cell) {
        // Increase the responsive index.
        $responsive_index++;

        if (!is_array($cell)) {
          $header_columns++;
          $cell_content = $cell;
          $cell_attributes = new Attribute();
          $is_header = TRUE;
        }
        else {
          if (isset($cell['colspan'])) {
            $header_columns += $cell['colspan'];
          }
          else {
            $header_columns++;
          }
          $cell_content = '';
          if (isset($cell['data'])) {
            $cell_content = $cell['data'];
            unset($cell['data']);
          }
          // Flag the cell as a header or not and remove the flag.
          $is_header = $cell['header'] ?? TRUE;
          unset($cell['header']);

          // Track responsive classes for each column as needed. Only the header
          // cells for a column are marked up with the responsive classes by a
          // module developer or themer. The responsive classes on the header
          // cells must be transferred to the content cells.
          if (!empty($cell['class']) && is_array($cell['class'])) {
            if (in_array(RESPONSIVE_PRIORITY_MEDIUM, $cell['class'])) {
              $responsive_classes[$responsive_index] = RESPONSIVE_PRIORITY_MEDIUM;
            }
            elseif (in_array(RESPONSIVE_PRIORITY_LOW, $cell['class'])) {
              $responsive_classes[$responsive_index] = RESPONSIVE_PRIORITY_LOW;
            }
          }

          TableSort::header($cell_content, $cell, $variables['header'], $ts);

          // TableSort::header() removes the 'sort', 'initial_click_sort' and
          // 'field' keys.
          $cell_attributes = new Attribute($cell);
        }
        $variables['header'][$col_key] = [];
        $variables['header'][$col_key]['tag'] = $is_header ? 'th' : 'td';
        $variables['header'][$col_key]['attributes'] = $cell_attributes;
        $variables['header'][$col_key]['content'] = $cell_content;
      }
    }
    $variables['header_columns'] = $header_columns;

    // Rows and footer have the same structure.
    $sections = ['rows' , 'footer'];
    foreach ($sections as $section) {
      if (!empty($variables[$section])) {
        foreach ($variables[$section] as $row_key => $row) {
          $cells = $row;
          $row_attributes = [];

          // Check if we're dealing with a simple or complex row
          if (isset($row['data'])) {
            $cells = $row['data'];
            $variables['no_striping'] = $row['no_striping'] ?? FALSE;

            // Set the attributes array and exclude 'data' and 'no_striping'.
            $row_attributes = $row;
            unset($row_attributes['data']);
            unset($row_attributes['no_striping']);
          }

          // Build row.
          $variables[$section][$row_key] = [];
          $variables[$section][$row_key]['attributes'] = new Attribute($row_attributes);
          $variables[$section][$row_key]['cells'] = [];
          if (!empty($cells)) {
            // Reset the responsive index.
            $responsive_index = -1;
            foreach ($cells as $col_key => $cell) {
              // Increase the responsive index.
              $responsive_index++;

              if (!is_array($cell)) {
                $cell_content = $cell;
                $cell_attributes = [];
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
              }
              // Active table sort information.
              if (isset($variables['header'][$col_key]['data']) && $variables['header'][$col_key]['data'] == $ts['name'] && !empty($variables['header'][$col_key]['field'])) {
                $variables[$section][$row_key]['cells'][$col_key]['active_table_sort'] = TRUE;
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
    if (empty($variables['no_striping'])) {
      $variables['attributes']['data-striping'] = 1;
    }
  }

  /**
   * Prepares variables for tablesort indicators.
   *
   * Default template: tablesort-indicator.html.twig.
   */
  public function preprocessTablesortIndicator(array &$variables): void {
    $variables['#attached']['library'][] = 'core/drupal.tablesort';
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
   * @see https://www.drupal.org/node/1842756
   */
  public function preprocessItemList(array &$variables): void {
    $variables['wrapper_attributes'] = new Attribute($variables['wrapper_attributes']);
    $variables['#attached']['library'][] = 'core/drupal.item-list';
    foreach ($variables['items'] as &$item) {
      $attributes = [];
      // If the item value is an array, then it is a render array.
      if (is_array($item)) {
        // List items support attributes via the '#wrapper_attributes' property.
        if (isset($item['#wrapper_attributes'])) {
          $attributes = $item['#wrapper_attributes'];
        }
        // Determine whether there are any child elements in the item that are
        // not fully-specified render arrays. If there are any, then the child
        // elements present nested lists and we automatically inherit the render
        // array properties of the current list to them.
        foreach (Element::children($item) as $key) {
          $child = &$item[$key];
          // If this child element does not specify how it can be rendered, then
          // we need to inherit the render properties of the current list.
          if (!isset($child['#type']) && !isset($child['#theme']) && !isset($child['#markup'])) {
            // Since item-list.html.twig supports both strings and render arrays
            // as items, the items of the nested list may have been specified as
            // the child elements of the nested list, instead of #items. For
            // convenience, we automatically move them into #items.
            if (!isset($child['#items'])) {
              // This is the same condition as in
              // \Drupal\Core\Render\Element::children(), which cannot be used
              // here, since it triggers an error on string values.
              foreach ($child as $child_key => $child_value) {
                if (is_int($child_key) || $child_key === '' || $child_key[0] !== '#') {
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
      $item = [
        'value' => $item,
        'attributes' => new Attribute($attributes),
      ];
    }
  }

  /**
   * Prepares variables for maintenance task list templates.
   *
   * Default template: maintenance-task-list.html.twig.
   *
   * @param array $variables
   *   An associative array containing:
   *   - items: An associative array of maintenance tasks.
   *     It's the caller's responsibility to ensure this array's items contain
   *     no dangerous HTML such as <script> tags.
   *   - active: The key for the currently active maintenance task.
   */
  public function preprocessMaintenanceTaskList(array &$variables): void {
    $items = $variables['items'];
    $active = $variables['active'];

    $done = isset($items[$active]) || $active == NULL;
    foreach ($items as $k => $item) {
      $variables['tasks'][$k]['item'] = $item;
      $variables['tasks'][$k]['attributes'] = new Attribute();
      if ($active == $k) {
        $variables['tasks'][$k]['attributes']->addClass('is-active');
        $variables['tasks'][$k]['status'] = $this->t('active');
        $done = FALSE;
      }
      else {
        if ($done) {
          $variables['tasks'][$k]['attributes']->addClass('done');
          $variables['tasks'][$k]['status'] = $this->t('done');
        }
      }
    }
  }

}
