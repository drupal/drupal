<?php

/**
 * @file
 * Contains \Drupal\Core\Utility\LinkGenerator.
 */

namespace Drupal\Core\Utility;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\String;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Link;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;

/**
 * Provides a class which generates a link with route names and parameters.
 */
class LinkGenerator implements LinkGeneratorInterface {

  /**
   * The url generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * The module handler firing the route_link alter hook.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a LinkGenerator instance.
   *
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The url generator.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(UrlGeneratorInterface $url_generator, ModuleHandlerInterface $module_handler) {
    $this->urlGenerator = $url_generator;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function generateFromLink(Link $link) {
    return $this->generate($link->getText(), $link->getUrl());
  }

  /**
   * {@inheritdoc}
   *
   * For anonymous users, the "active" class will be calculated on the server,
   * because most sites serve each anonymous user the same cached page anyway.
   * For authenticated users, the "active" class will be calculated on the
   * client (through JavaScript), only data- attributes are added to links to
   * prevent breaking the render cache. The JavaScript is added in
   * system_page_build().
   *
   * @see system_page_build()
   */
  public function generate($text, Url $url) {
    // Performance: avoid Url::toString() needing to retrieve the URL generator
    // service from the container.
    $url->setUrlGenerator($this->urlGenerator);

    // Start building a structured representation of our link to be altered later.
    $variables = array(
      // @todo Inject the service when drupal_render() is converted to one.
      'text' => is_array($text) ? drupal_render($text) : $text,
      'url' => $url,
      'options' => $url->getOptions(),
    );

    // Merge in default options.
    $variables['options'] += array(
      'attributes' => array(),
      'query' => array(),
      'html' => FALSE,
      'language' => NULL,
      'set_active_class' => FALSE,
      'absolute' => FALSE,
    );

    // Add a hreflang attribute if we know the language of this link's url and
    // hreflang has not already been set.
    if (!empty($variables['options']['language']) && !isset($variables['options']['attributes']['hreflang'])) {
      $variables['options']['attributes']['hreflang'] = $variables['options']['language']->getId();
    }

    // Set the "active" class if the 'set_active_class' option is not empty.
    if (!empty($variables['options']['set_active_class']) && !$url->isExternal()) {
      // Add a "data-drupal-link-query" attribute to let the
      // drupal.active-link library know the query in a standardized manner.
      if (!empty($variables['options']['query'])) {
        $query = $variables['options']['query'];
        ksort($query);
        $variables['options']['attributes']['data-drupal-link-query'] = Json::encode($query);
      }

      // Add a "data-drupal-link-system-path" attribute to let the
      // drupal.active-link library know the path in a standardized manner.
      if ($url->isRouted() && !isset($variables['options']['attributes']['data-drupal-link-system-path'])) {
        // @todo System path is deprecated - use the route name and parameters.
        $system_path = $url->getInternalPath();
        // Special case for the front page.
        $variables['options']['attributes']['data-drupal-link-system-path'] = $system_path == '' ? '<front>' : $system_path;
      }
    }

    // Remove all HTML and PHP tags from a tooltip, calling expensive strip_tags()
    // only when a quick strpos() gives suspicion tags are present.
    if (isset($variables['options']['attributes']['title']) && strpos($variables['options']['attributes']['title'], '<') !== FALSE) {
      $variables['options']['attributes']['title'] = strip_tags($variables['options']['attributes']['title']);
    }

    // Allow other modules to modify the structure of the link.
    $this->moduleHandler->alter('link', $variables);

    // Move attributes out of options. generateFromRoute(() doesn't need them.
    $attributes = new Attribute($variables['options']['attributes']);
    unset($variables['options']['attributes']);
    $url->setOptions($variables['options']);

    // The result of the url generator is a plain-text URL. Because we are using
    // it here in an HTML argument context, we need to encode it properly.
    $url = String::checkPlain($url->toString());

    // Sanitize the link text if necessary.
    $text = $variables['options']['html'] ? $variables['text'] : String::checkPlain($variables['text']);
    return SafeMarkup::set('<a href="' . $url . '"' . $attributes . '>' . $text . '</a>');
  }

}
