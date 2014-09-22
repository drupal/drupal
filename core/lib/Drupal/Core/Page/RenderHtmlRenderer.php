<?php

/**
 * @file
 * Contains \Drupal\Core\Page\RenderHtmlRenderer.
 */

namespace Drupal\Core\Page;

use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Utility\Title;

/**
 * Provides an implementation for an render array to HTML fragment renderer.
 *
 * This renderer takes into account the cache information, the attached assets
 * as well as the title and HTML HEAD elements.
 */
class RenderHtmlRenderer implements RenderHtmlRendererInterface {

  /**
   * The URL generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * Constructs a new RenderHtmlRenderer.
   *
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The URL generator.
   */
  public function __construct(UrlGeneratorInterface $url_generator) {
    $this->urlGenerator = $url_generator;
  }

  /**
   * {@inheritdoc}
   */
  public function render(array $render_array) {
    $content = $this->drupalRender($render_array);
    if (!empty($render_array)) {
      drupal_process_attached($render_array);
    }
    $cache = !empty($render_array['#cache']['tags']) ? ['tags' => $render_array['#cache']['tags']] : [];
    $fragment = new HtmlFragment($content, $cache);

    if (isset($render_array['#title'])) {
      $fragment->setTitle($render_array['#title'], Title::FILTER_XSS_ADMIN);
    }

    $attached = isset($render_array['#attached']) ? $render_array['#attached'] : [];
    $attached += [
      'drupal_add_feed' => [],
      'drupal_add_html_head' => [],
      'drupal_add_html_head_link' => [],
    ];


    // Add feed links from the page content.
    foreach ($attached['drupal_add_feed'] as $feed) {
      $fragment->addLinkElement(new FeedLinkElement($feed[1], $this->urlGenerator->generateFromPath($feed[0])));
    }

    // Add generic links from the page content.
    foreach ($attached['drupal_add_html_head_link'] as $link) {
      $fragment->addLinkElement(new LinkElement($this->urlGenerator->generateFromPath($link[0]['href']), $link[0]['rel']));
    }

    // @todo Also transfer the contents of "drupal_add_html_head" once
    // https://www.drupal.org/node/2296951 lands.

    // @todo Transfer CSS and JS over to the fragment once those are supported
    // on the fragment object.

    return $fragment;
  }

  /**
   * Wraps drupal_render().
   *
   * @todo: Convert drupal_render into a proper injectable service.
   */
  protected function drupalRender(&$elements, $is_recursive_call = FALSE) {
    return drupal_render($elements, $is_recursive_call);
  }
}
