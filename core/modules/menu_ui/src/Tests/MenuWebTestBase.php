<?php

/**
 * @file
 * Contains \Drupal\menu_ui\Tests\MenuWebTestBase.
 */

namespace Drupal\menu_ui\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Base class for menu web tests.
 */
abstract class MenuWebTestBase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('menu_ui');

  /**
   * Fetchs the menu item from the database and compares it to expected item.
   *
   * @param int $mlid
   *   Menu item id.
   * @param array $item
   *   Array containing properties to verify.
   */
  function assertMenuLink($mlid, array $expected_item) {
    // Retrieve menu link.
    $item = entity_load('menu_link', $mlid);
    $options = $item->options;
    if (!empty($options['query'])) {
      $item['link_path'] .= '?' . \Drupal::urlGenerator()->httpBuildQuery($options['query']);
    }
    if (!empty($options['fragment'])) {
      $item['link_path'] .= '#' . $options['fragment'];
    }
    foreach ($expected_item as $key => $value) {
      $this->assertEqual($item[$key], $value);
    }
  }

}
