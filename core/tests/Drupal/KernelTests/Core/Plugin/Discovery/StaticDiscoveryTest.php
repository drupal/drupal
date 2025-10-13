<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Plugin\Discovery;

use Drupal\Component\Plugin\Discovery\StaticDiscovery;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests that plugins using static discovery are correctly discovered.
 */
#[Group('Plugin')]
#[RunTestsInSeparateProcesses]
class StaticDiscoveryTest extends DiscoveryTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->expectedDefinitions = [
      'apple' => [
        'label' => 'Apple',
        'color' => 'green',
      ],
      'cherry' => [
        'label' => 'Cherry',
        'color' => 'red',
      ],
      'orange' => [
        'label' => 'Orange',
        'color' => 'orange',
      ],
    ];
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
