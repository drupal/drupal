<?php

declare(strict_types=1);

namespace Drupal\common_test\Render\MainContent;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\TitleResolverInterface;
use Drupal\Core\Render\MainContent\MainContentRendererInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Default main content renderer for JSON requests.
 */
class JsonRenderer implements MainContentRendererInterface {

  /**
   * Constructs a new JsonRenderer.
   *
   * @param \Drupal\Core\Controller\TitleResolverInterface $titleResolver
   *   The title resolver.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(
    protected TitleResolverInterface $titleResolver,
    protected RendererInterface $renderer,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function renderResponse(array $main_content, Request $request, RouteMatchInterface $route_match) {
    $json = [];

    $json['content'] = (string) $this->renderer->renderRoot($main_content);
    if (!empty($main_content['#title'])) {
      $json['title'] = (string) $main_content['#title'];
    }
    else {
      $json['title'] = (string) $this->titleResolver->getTitle($request, $route_match->getRouteObject());
    }

    $response = new CacheableJsonResponse($json, 200);
    $response->addCacheableDependency(CacheableMetadata::createFromRenderArray($main_content));
    return $response;
  }

}
