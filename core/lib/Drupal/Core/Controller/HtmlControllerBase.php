<?php

/**
 * @file
 * Contains \Drupal\Core\Controller\HtmlControllerBase.
 */

namespace Drupal\Core\Controller;

use Drupal\Core\Page\HtmlFragment;
use Drupal\Core\Page\RenderHtmlRendererInterface;
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
   * @var \Drupal\Core\Controller\TitleResolverInterface
   */
  protected $titleResolver;

  /**
   * The render array to HTML fragment renderer.
   *
   * @var \Drupal\Core\Page\RenderHtmlRendererInterface
   */
  protected $renderHtmlRenderer;

  /**
   * Constructs a new HtmlControllerBase object.
   *
   * @param \Drupal\Core\Controller\TitleResolverInterface $title_resolver
   *   The title resolver.
   * @param \Drupal\Core\Page\RenderHtmlRendererInterface $render_html_renderer
   *   The render array to HTML fragment renderer.
   */
  public function __construct(TitleResolverInterface $title_resolver, RenderHtmlRendererInterface $render_html_renderer) {
    $this->titleResolver = $title_resolver;
    $this->renderHtmlRenderer = $render_html_renderer;
  }

  /**
   * Converts a render array into an HtmlFragment object.
   *
   * @param array|\Drupal\Core\Page\HtmlFragmentInterface|\Symfony\Component\HttpFoundation\Response $page_content
   *   The page content area to display.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Drupal\Core\Page\HtmlPage
   *   A page object.
   *
   * @throws \InvalidArgumentException
   *   Thrown if the controller returns a string.
   */
  protected function createHtmlFragment($page_content, Request $request) {
    // Allow controllers to return a HtmlFragment or a Response object directly.
    if ($page_content instanceof HtmlFragment || $page_content instanceof Response) {
      return $page_content;
    }

    if (is_string($page_content)) {
      throw new \InvalidArgumentException('_content controllers are not allowed to return strings. You can return a render array, a html fragment or a response object.');
    }

    $fragment = $this->renderHtmlRenderer->render($page_content);

    if (!$fragment->getTitle() && $route = $request->attributes->get(RouteObjectInterface::ROUTE_OBJECT)) {
      $fragment->setTitle($this->titleResolver->getTitle($request, $route), Title::PASS_THROUGH);
    }

    return $fragment;
  }

}
