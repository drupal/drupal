<?php
/**
 * @file
 * Contains \Drupal\menu_test\Access\AccessCheck.
 */

namespace Drupal\menu_test\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;

/**
 * Checks access based on the 'menu_test' key in session.
 */
class AccessCheck implements AccessInterface {

  /**
   * Check to see if user accessed this page.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access() {
    if (!isset($_SESSION['menu_test'])) {
      $result = AccessResult::allowed();
    }
    else {
      $result = AccessResult::allowedIf($_SESSION['menu_test'] < 2);
    }
    return $result->setCacheMaxAge(0);
  }

}
