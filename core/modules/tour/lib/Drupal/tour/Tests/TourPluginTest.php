<?php

/**
 * @file
 * Contains \Drupal\tour\Tests\TourPluginTest.
 */

namespace Drupal\tour\Tests;

use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Tests tour plugin functionality.
 */
class TourPluginTest extends DrupalUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('tour');

  /**
   * Stores the tour plugin manager.
   *
   * @var \Drupal\tour\TourManager
   */
  protected $pluginManager;

  /**
   * Defines test info.
   */
  public static function getInfo() {
    return array(
      'name' => 'Tour plugin tests',
      'description' => 'Test the functionality of tour plugins.',
      'group' => 'Tour',
    );
  }

  /**
   * Sets up the test.
   */
  protected function setUp() {
    parent::setUp();

    config_install_default_config('module', 'tour');
    $this->pluginManager = $this->container->get('plugin.manager.tour');
  }

  /**
   * Test tour plugins.
   */
  public function testTourPlugins() {
    $this->assertIdentical(count($this->pluginManager->getDefinitions()), 1, 'Only tour plugins for the enabled modules were returned.');
  }

}
