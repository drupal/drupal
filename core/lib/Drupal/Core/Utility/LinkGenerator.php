<?php

namespace Drupal\Core\Utility;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\GeneratedLink;
use Drupal\Core\GeneratedButton;
use Drupal\Core\GeneratedNoLink;
use Drupal\Core\Link;
use Drupal\Core\Render\RendererInterface;
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
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a LinkGenerator instance.
   *
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The url generator.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(UrlGeneratorInterface $url_generator, ModuleHandlerInterface $module_handler, RendererInterface $renderer) {
    $this->urlGenerator = $url_generator;
    $this->moduleHandler = $module_handler;
    $this->renderer = $renderer;
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
   * system_page_attachments().
   *
   * @see system_page_attachments()
   */
  public function generate($text, Url $url) {
    // The link generator should not modify the original URL object, this
    // ensures consistent rendering.
    // @see https://www.drupal.org/node/2842399
    $url = clone $url;

    // Performance: avoid Url::toString() needing to retrieve the URL generator
    // service from the container.
    $url->setUrlGenerator($this->urlGenerator);

    if (is_array($text)) {
      $text = $this->renderer->render($text);
    }

    // Start building a structured representation of our link to be altered later.
    $variables = [
      'text' => $text,
      'url' => $url,
      'options' => $url->getOptions(),
    ];

    // Merge in default options.
    $variables['options'] += [
      'attributes' => [],
      'query' => [],
      'language' => NULL,
      'set_active_class' => FALSE,
      'absolute' => FALSE,
    ];

    // Add a hreflang attribute if we know the language of this link's url and
    // hreflang has not already been set.
    if (!empty($variables['options']['language']) && !isset($variables['options']['attributes']['hreflang'])) {
      $variables['options']['attributes']['hreflang'] = $variables['options']['language']->getId();
    }

    // Ensure that query values are strings.
    array_walk($variables['options']['query'], function (&$value) {
      if ($value instanceof MarkupInterface) {
        $value = (string) $value;
      }
    });

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
        if ($url->getRouteName() === '<front>') {
          $system_path = '<front>';
        }

        if (!empty($system_path)) {
          $variables['options']['attributes']['data-drupal-link-system-path'] = $system_path;
        }
      }
    }

    // Remove all HTML and PHP tags from a tooltip, calling expensive strip_tags()
    // only when a quick strpos() gives suspicion tags are present.
    if (isset($variables['options']['attributes']['title']) && strpos($variables['options']['attributes']['title'], '<') !== FALSE) {
      $variables['options']['attributes']['title'] = strip_tags($variables['options']['attributes']['title']);
    }

    // Allow other modules to modify the structure of the link.
    $this->moduleHandler->alter('link', $variables);
    $url = $variables['url'];

    // Move attributes out of options since generateFromRoute() doesn't need
    // them. Make sure the "href" comes first for testing purposes.
    $attributes = ['href' => ''] + $variables['options']['attributes'];
    unset($variables['options']['attributes']);
    $url->setOptions($variables['options']);

    // External URLs can not have cacheable metadata.
    if ($url->isExternal()) {
      $generated_link = new GeneratedLink();
      $attributes['href'] = $url->toString(FALSE);
      return $this->doGenerate($generated_link, $attributes, $variables);
    }
    if ($url->isRouted() && $url->getRouteName() === '<nolink>') {
      $generated_link = new GeneratedNoLink();
      unset($attributes['href'], $attributes['hreflang']);
      return $this->doGenerate($generated_link, $attributes, $variables);
    }
    if ($url->isRouted() && $url->getRouteName() === '<button>') {
      $generated_link = new GeneratedButton();
      $attributes['type'] = 'button';
      unset($attributes['href'], $attributes['hreflang']);
      return $this->doGenerate($generated_link, $attributes, $variables);
    }
    $generated_url = $url->toString(TRUE);
    $generated_link = GeneratedLink::createFromObject($generated_url);
    // The result of the URL generator is a plain-text URL to use as the href
    // attribute, and it is escaped by \Drupal\Core\Template\Attribute.
    $attributes['href'] = $generated_url->getGeneratedUrl();
    return $this->doGenerate($generated_link, $attributes, $variables);
  }

  /**
   * Generates the link.
   *
   * @param Drupal\Core\GeneratedLink $generated_link
   *   The generated link, along with its associated cacheability metadata.
   * @param array $attributes
   *   The attributes of the generated link.
   * @param array $variables
   *   The link text, url, and other options.
   *
   * @return Drupal\Core\GeneratedLink
   *   The generated link, along with its associated cacheability metadata.
   */
  protected function doGenerate($generated_link, $attributes, $variables) {
    if (!($variables['text'] instanceof MarkupInterface)) {
      $variables['text'] = Html::escape($variables['text']);
    }
    $attributes = new Attribute($attributes);
    // This is safe because Attribute does escaping and $variables['text'] is
    // either rendered or escaped.
    return $generated_link->setGeneratedLink('<' . $generated_link::TAG . $attributes . '>' . $variables['text'] . '</' . $generated_link::TAG . '>');
  }

}
