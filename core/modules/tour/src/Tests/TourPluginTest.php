<?php

/**
 * @file
 * Contains \Drupal\tour\Tests\TourPluginTest.
 */

namespace Drupal\tour\Tests;

use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Tests the functionality of tour plugins.
 *
 * @group tour
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
   * @var \Drupal\tour\TipPluginManager
   */
  protected $pluginManager;

  protected function setUp() {
    parent::setUp();

    $this->installConfig(array('tour'));
    $this->pluginManager = $this->container->get('plugin.manager.tour.tip');
  }

  /**
   * Test tour plugins.
   */
  public function testTourPlugins() {
    $this->assertIdentical(count($this->pluginManager->getDefinitions()), 1, 'Only tour plugins for the enabled modules were returned.');
  }

}
