<?php

/**
 * @file
 * Definition of Drupal\views_ui_listing\Tests\ConfigEntityListingTest.
 */

namespace Drupal\views_ui_listing\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\config\ConfigEntityBase;

/**
 * Tests configuration entities.
 */
class ConfigEntityListingTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('views_ui_listing_test');

  public static function getInfo() {
    return array(
      'name' => 'Views configuration entity listing',
      'description' => 'Tests configuration entity listing plugins.',
      'group' => 'Views',
    );
  }

  /**
   * Tests basic listing plugin functionilty.
   */
  function testListingPlugin() {
    $controller = views_ui_listing_get_list_controller('config_test');

    // Get a list of Config entities.
    $list = $controller->getList();
    $this->assertEqual(count($list), 1, 'Correct number of plugins found.');
    $this->assertTrue(!empty($list['default']), '"Default" config entity key found in list.');
    $this->assertTrue($list['default'] instanceof ConfigEntityBase, '"Default" config entity is an instance of ConfigEntityBase');
  }

  /**
   * Tests the listing UI.
   */
  function testListingUI() {
    $page = $this->drupalGet('config-listing-test');

    // Test that the page exists.
    $this->assertText('Config test', 'Config test listing page title found.');

    // Check we have the default id and label on the page too.
    $this->assertText('default', '"default" ID found.');
    $this->assertText('Default', '"Default" label found');

    // Check each link.
    foreach (array('edit', 'add', 'delete') as $link) {
      $this->drupalSetContent($page);
      $this->assertLink($link);
      $this->clickLink($link);
      $this->assertResponse(200);
    }

    // @todo Test AJAX links.
  }

}
