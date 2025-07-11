<?php

declare(strict_types=1);

namespace Drupal\router_test\Hook;

use Drupal\Core\Url;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for router_test.
 */
class RouterTestThemeHooks {

  /**
   * Implements hook_preprocess_HOOK().
   *
   * Performs an operation that calls the RouteProvider's collection method
   * during an exception page view. (which is rendered during a subrequest.)
   *
   * @see \Drupal\FunctionalTests\Routing\RouteCachingQueryAlteredTest
   */
  #[Hook('preprocess_page')]
  public function preprocessPage(&$variables): void {
    $request = \Drupal::request();
    if ($request->getPathInfo() === '/router-test/rejects-query-strings') {
      // Create a URL from the request, e.g. for a breadcrumb or other contextual
      // information.
      Url::createFromRequest($request);
    }
  }

}
