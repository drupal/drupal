<?php

namespace Drupal\system\Tests\Routing;

use Drupal\Core\Database\Database;
use Drupal\simpletest\WebTestBase;

/**
 * Functional class for the full integrated routing system.
 */
class RouterTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('block', 'router_test');

  public static function getInfo() {
    return array(
      'name' => 'Integrated Router tests',
      'description' => 'Function Tests for the fully integrated routing system.',
      'group' => 'Routing',
    );
  }

  public function testCanRoute() {
    $this->drupalGet('router_test/test1');
    $this->assertRaw('test1', 'The correct string was returned because the route was successful.');
  }

}
