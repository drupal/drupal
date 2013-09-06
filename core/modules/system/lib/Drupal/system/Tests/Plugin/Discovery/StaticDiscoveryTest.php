<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Plugin\Discovery\StaticDiscoveryTest.
 */

namespace Drupal\system\Tests\Plugin\Discovery;

use Drupal\Component\Plugin\Discovery\StaticDiscovery;

/**
 * Tests that plugins are correctly discovered.
 */
class StaticDiscoveryTest extends DiscoveryTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Static discovery',
      'description' => 'Tests that plugins using static discovery are correctly discovered.',
      'group' => 'Plugin API',
    );
  }

  public function setUp() {
    parent::setUp();
    $this->expectedDefinitions = array(
      'apple' => array(
        'label' => 'Apple',
        'color' => 'green',
      ),
      'cherry' => array(
        'label' => 'Cherry',
        'color' => 'red',
      ),
      'orange' => array(
        'label' => 'Orange',
        'color' => 'orange',
      ),
    );
    // Instead of registering the empty discovery component first and then
    // setting the plugin definitions, we set them first and then delete them
    // again. This implicitly tests StaticDiscovery::deleteDefinition() (in
    // addition to StaticDiscovery::setDefinition() which we need to use
    // anyway).
    $discovery = new StaticDiscovery();
    foreach ($this->expectedDefinitions as $plugin_id => $definition) {
      $discovery->setDefinition($plugin_id, $definition);
    }
    $this->discovery = clone $discovery;
    foreach ($this->expectedDefinitions as $plugin_id => $definition) {
      $discovery->deleteDefinition($plugin_id);
    }
    $this->emptyDiscovery = $discovery;
  }
}

