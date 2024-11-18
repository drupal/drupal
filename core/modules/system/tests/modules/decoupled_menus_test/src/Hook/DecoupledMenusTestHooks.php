<?php

declare(strict_types=1);

namespace Drupal\decoupled_menus_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for decoupled_menus_test.
 */
class DecoupledMenusTestHooks {

  /**
   * Implements hook_menu_links_discovered_alter().
   */
  #[Hook('menu_links_discovered_alter')]
  public function menuLinksDiscoveredAlter(&$links): void {
    // Sets a custom link relation type on a menu item.
    // @see https://tools.ietf.org/id/draft-pot-authentication-link-01.html
    $links['user.page']['options']['attributes']['rel'] = 'authenticated-as';
  }

}
