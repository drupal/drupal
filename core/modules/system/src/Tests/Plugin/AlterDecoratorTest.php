<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Plugin\AlterDecoratorTest.
 */

namespace Drupal\system\Tests\Plugin;

use Drupal\plugin_test\Plugin\AlterDecoratorTestPluginManager;
use Drupal\simpletest\WebTestBase;

/**
 * Tests that the AlterDecorator fires and respects the alter hook.
 */
class AlterDecoratorTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('plugin_test');

  /**
   * Stores a plugin manager which uses the AlterDecorator.
   *
   * @var \Drupal\plugin_test\Plugin\AlterDecoratorTestPluginManager;
   */
  protected $alterTestPluginManager;

  public static function getInfo() {
    return array(
      'name' => 'AlterDecorator',
      'description' => 'Tests that the AlterDecorator fires and respects the alter hook.',
      'group' => 'Plugin API',
    );
  }

  public function setUp() {
    parent::setUp();

    // Setup a plugin manager which uses the alter decorator.
    $this->alterTestPluginManager = new AlterDecoratorTestPluginManager();
  }

  /**
   * Tests getDefinitions() and getDefinition() of Drupal\Core\Plugin\Discovery\AlterDecorator.
   */
  public function testAlterDecorator() {
    // Ensure that getDefinitions() fires and changes the actual plugin definitions.
    $definitions = $this->alterTestPluginManager->getDefinitions();
    foreach ($definitions as &$definition) {
      $this->assertTrue($definition['altered']);
    }

    // Ensure that getDefinitions() fires and changes the actual plugin definition.
    $definition = $this->alterTestPluginManager->getDefinition('user_login');
    $this->assertTrue($definition['altered_single']);
  }

}
