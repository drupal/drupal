<?php

namespace Drupal\Tests\Core\Plugin;

use Drupal\Component\Plugin\Definition\PluginDefinitionInterface;
use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\Definition\DependentPluginDefinitionInterface;
use Drupal\Core\Plugin\PluginDependencyTrait;
use Drupal\Tests\UnitTestCase;
use Prophecy\Prophecy\ProphecyInterface;

/**
 * @coversDefaultClass \Drupal\Core\Plugin\PluginDependencyTrait
 * @group Plugin
 */
class PluginDependencyTraitTest extends UnitTestCase {

  /**
   * @covers ::calculatePluginDependencies
   *
   * @dataProvider providerTestCalculatePluginDependencies
   *
   * @param \Prophecy\Prophecy\ProphecyInterface $plugin
   *   A prophecy of a plugin instance.
   * @param mixed $definition
   *   A plugin definition.
   * @param array $expected
   *   The expected dependencies.
   */
  public function testCalculatePluginDependencies(ProphecyInterface $plugin, $definition, array $expected) {
    $test_class = new TestPluginDependency();

    $plugin->getPluginDefinition()->willReturn($definition);

    $test_class->calculatePluginDependencies($plugin->reveal());
    $this->assertEquals($expected, $test_class->getDependencies());
  }

  /**
   * Provides test data for ::testCalculatePluginDependencies().
   */
  public function providerTestCalculatePluginDependencies() {
    $data = [];

    $plugin = $this->prophesize(PluginInspectionInterface::class);

    $dependent_plugin = $this->prophesize(PluginInspectionInterface::class)->willImplement(DependentPluginInterface::class);
    $dependent_plugin->calculateDependencies()->willReturn([
      'module' => ['test_module2'],
    ]);

    $data['dependent_plugin'] = [
      $dependent_plugin,
      ['provider' => 'test_module1'],
      [
        'module' => [
          'test_module1',
          'test_module2',
        ],
      ],
    ];

    $data['array_with_config_dependencies'] = [
      $plugin,
      [
        'provider' => 'test_module1',
        'config_dependencies' => [
          'module' => ['test_module2'],
        ],
      ],
      [
        'module' => [
          'test_module1',
          'test_module2',
        ],
      ],
    ];

    $definition = $this->prophesize(PluginDefinitionInterface::class);
    $definition->getProvider()->willReturn('test_module1');
    $data['object_definition'] = [
      $plugin,
      $definition->reveal(),
      [
        'module' => [
          'test_module1',
        ],
      ],
    ];

    $dependent_definition = $this->prophesize(PluginDefinitionInterface::class)->willImplement(DependentPluginDefinitionInterface::class);
    $dependent_definition->getProvider()->willReturn('test_module1');
    $dependent_definition->getConfigDependencies()->willReturn(['module' => ['test_module2']]);
    $data['dependent_object_definition'] = [
      $plugin,
      $dependent_definition->reveal(),
      [
        'module' => [
          'test_module1',
          'test_module2',
        ],
      ],
    ];
    return $data;
  }

}

class TestPluginDependency {

  use PluginDependencyTrait {
    calculatePluginDependencies as public;
  }

  /**
   * @return array[]
   */
  public function getDependencies() {
    return $this->dependencies;
  }

}
