<?php

/**
 * @file
 * Contains \Drupal\Core\Controller\HtmlControllerBase.
 */

namespace Drupal\Core\Controller;

use Drupal\Core\Page\FeedLinkElement;
use Drupal\Core\Page\HtmlFragment;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Utility\Title;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Base class for HTML page-generating controllers.
 */
class HtmlControllerBase {

  /**
   * The title resolver.
   *
   * @var \Drupal\Core\Controller\TitleResolver
   */
  protected $titleResolver;

  /**
   * The url generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * Constructs a new HtmlControllerBase object.
   *
   * @param \Drupal\Core\Controller\TitleResolverInterface $title_resolver
   *   The title resolver.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The url generator.
   */
  public function __construct(TitleResolverInterface $title_resolver, UrlGeneratorInterface $url_generator) {
    $this->titleResolver = $title_resolver;
    $this->urlGenerator = $url_generator;
  }

  /**
   * Converts a render array into an HtmlFragment object.
   *
   * @param array|string $page_content
   *   The page content area to display.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Drupal\Core\Page\HtmlPage
   *   A page object.
   */
  protected function createHtmlFragment($page_content, Request $request) {
    // Allow controllers to return a HtmlFragment or a Response object directly.
    if ($page_content instanceof HtmlFragment || $page_content instanceof Response) {
      return $page_content;
    }

    if (!is_array($page_content)) {
      $page_content = array(
        'main' => array(
          '#markup' => $page_content,
        ),
      );
    }

    $content = $this->drupalRender($page_content);
    $cache = !empty($page_content['#cache']['tags']) ? array('tags' => $page_content['#cache']['tags']) : array();
    $fragment = new HtmlFragment($content, $cache);

    // A title defined in the return always wins.
    if (isset($page_content['#title'])) {
      $fragment->setTitle($page_content['#title'], Title::FILTER_XSS_ADMIN);
    }
    else if ($route = $request->attributes->get(RouteObjectInterface::ROUTE_OBJECT)) {
      $fragment->setTitle($this->titleResolver->getTitle($request, $route), Title::PASS_THROUGH);
    }

    // Add feed links from the page content.
    $attached = drupal_render_collect_attached($page_content, TRUE);
    if (!empty($attached['drupal_add_feed'])) {
      foreach ($attached['drupal_add_feed'] as $feed) {
        $feed_link = new FeedLinkElement($feed[1], $this->urlGenerator->generateFromPath($feed[0]));
        $fragment->addLinkElement($feed_link);
      }
    }

    return $fragment;
  }

  /**
   * Wraps drupal_render().
   *
   * @todo: Remove as part of https://drupal.org/node/2182149
   */
  protected function drupalRender(&$elements, $is_recursive_call = FALSE) {
    return drupal_render($elements, $is_recursive_call);
  }

}
