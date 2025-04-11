<?php

namespace Drupal\Core\Theme;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;

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
              $li_attributes['hreflang'] = $link['language']->getId();
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

}
