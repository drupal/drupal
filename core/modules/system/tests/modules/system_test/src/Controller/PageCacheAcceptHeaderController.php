<?php

declare(strict_types=1);

namespace Drupal\system_test\Controller;

use Drupal\Core\Cache\CacheableAjaxResponse;
use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableResponse;
use Drupal\Core\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a controller to respond the page cache accept header test.
 */
class PageCacheAcceptHeaderController {

  /**
   * Processes a request that will vary with Accept header.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   *
   * @return mixed
   *   The processed response object.
   */
  #[Route(
    path: '/system-test/page-cache/accept-header',
    name: 'system_test.page_cache_accept_header',
    requirements: ['_access' => 'TRUE'],
  )]
  public function content(Request $request) {
    if ($request->getRequestFormat() === 'json' && $request->query->get('_wrapper_format') === 'drupal_ajax') {
      $response = new CacheableAjaxResponse(['content' => 'oh hai this is ajax']);
    }
    elseif ($request->getRequestFormat() === 'json') {
      $response = new CacheableJsonResponse(['content' => 'oh hai this is json']);
    }
    else {
      $response = new CacheableResponse("<p>oh hai this is html.</p>");
    }
    $response->addCacheableDependency((new CacheableMetadata())->addCacheContexts(['url.query_args:_wrapper_format']));
    return $response;
  }

}
