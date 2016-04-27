<?php

namespace Drupal\system\Tests\Plugin\Discovery;

use Drupal\Component\Plugin\Discovery\StaticDiscovery;

/**
 * Tests that plugins using static discovery are correctly discovered.
 *
 * @group Plugin
 */
class StaticDiscoveryTest extends DiscoveryTestBase {

  protected function setUp() {
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
