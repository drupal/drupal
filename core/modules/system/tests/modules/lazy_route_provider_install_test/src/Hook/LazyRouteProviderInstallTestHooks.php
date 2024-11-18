<?php

declare(strict_types=1);

namespace Drupal\lazy_route_provider_install_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for lazy_route_provider_install_test.
 */
class LazyRouteProviderInstallTestHooks {

  /**
   * Implements hook_menu_links_discovered_alter().
   */
  #[Hook('menu_links_discovered_alter')]
  public function menuLinksDiscoveredAlter(&$links): void {
    $message = \Drupal::state()->get('lazy_route_provider_install_test_menu_links_discovered_alter', 'success');
    try {
      // Ensure that calling this does not cause a recursive rebuild.
      \Drupal::service('router.route_provider')->getAllRoutes();
    }
    catch (\RuntimeException) {
      $message = 'failed';
    }
    \Drupal::state()->set('lazy_route_provider_install_test_menu_links_discovered_alter', $message);
  }

}
