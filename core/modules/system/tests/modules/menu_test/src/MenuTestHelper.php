<?php

declare(strict_types=1);

namespace Drupal\menu_test;

/**
 * Helper class for the menu API tests.
 */
final class MenuTestHelper {

  /**
   * Sets a static variable for the testMenuName() test.
   *
   * Used to change the menu_name parameter of a menu.
   *
   * @param string $new_name
   *   (optional) If set, will change the $menu_name value.
   *
   * @return string
   *   The $menu_name value to use.
   */
  public static function menuName(string $new_name = ''): string {
    static $menu_name = 'original';
    if ($new_name) {
      $menu_name = $new_name;
    }
    return $menu_name;
  }

}
