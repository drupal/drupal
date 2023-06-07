<?php

namespace Drupal\Tests\Core\Plugin;

use Drupal\Component\Plugin\Definition\PluginDefinitionInterface;
use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Plugin\Definition\DependentPluginDefinitionInterface;
use Drupal\Core\Plugin\PluginDependencyTrait;
use Drupal\Tests\UnitTestCase;
use Prophecy\Prophecy\ProphecyInterface;
use Prophecy\Prophet;

/**
 * @coversDefaultClass \Drupal\Core\Plugin\PluginDependencyTrait
 * @group Plugin
 */
class PluginDependencyTraitTest extends UnitTestCase {

  /**
   * @covers ::getPluginDependencies
   *
   * @dataProvider providerTestPluginDependencies
   */
  public function testGetPluginDependencies(ProphecyInterface $plugin, $definition, array $expected) {
    $test_class = new TestPluginDependency();

    $module_handler = $this->prophesize(ModuleHandlerInterface::class);
    $module_handler->moduleExists('test_module1')->willReturn(TRUE);
    $module_handler->moduleExists('test_theme1')->willReturn(FALSE);
    $test_class->setModuleHandler($module_handler->reveal());

    $theme_handler = $this->prophesize(ThemeHandlerInterface::class);
    $theme_handler->themeExists('test_module1')->willReturn(FALSE);
    $theme_handler->themeExists('test_theme1')->willReturn(TRUE);
    $test_class->setThemeHandler($theme_handler->reveal());

    $plugin->getPluginDefinition()->willReturn($definition);

    $actual = $test_class->getPluginDependencies($plugin->reveal());
    $this->assertEquals($expected, $actual);
    $this->assertEmpty($test_class->getDependencies());
  }

  /**
   * @covers ::calculatePluginDependencies
   *
   * @dataProvider providerTestPluginDependencies
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

    $module_handler = $this->prophesize(ModuleHandlerInterface::class);
    $module_handler->moduleExists('test_module1')->willReturn(TRUE);
    $module_handler->moduleExists('test_theme1')->willReturn(FALSE);
    $test_class->setModuleHandler($module_handler->reveal());

    $theme_handler = $this->prophesize(ThemeHandlerInterface::class);
    $theme_handler->themeExists('test_module1')->willReturn(FALSE);
    $theme_handler->themeExists('test_theme1')->willReturn(TRUE);
    $test_class->setThemeHandler($theme_handler->reveal());

    $plugin->getPluginDefinition()->willReturn($definition);

    $test_class->calculatePluginDependencies($plugin->reveal());
    $this->assertEquals($expected, $test_class->getDependencies());
  }

  /**
   * Provides test data for plugin dependencies.
   */
  public static function providerTestPluginDependencies() {
    $prophet = new Prophet();
    $data = [];

    $plugin = $prophet->prophesize(PluginInspectionInterface::class);

    $dependent_plugin = $prophet->prophesize(PluginInspectionInterface::class)->willImplement(DependentPluginInterface::class);
    $dependent_plugin->calculateDependencies()->willReturn([
      'module' => ['test_module2'],
    ]);

    $data['dependent_plugin_from_module'] = [
      $dependent_plugin,
      ['provider' => 'test_module1'],
      [
        'module' => [
          'test_module1',
          'test_module2',
        ],
      ],
    ];
    $data['dependent_plugin_from_core'] = [
      $dependent_plugin,
      ['provider' => 'core'],
      [
        'module' => [
          'core',
          'test_module2',
        ],
      ],
    ];
    $data['dependent_plugin_from_theme'] = [
      $dependent_plugin,
      ['provider' => 'test_theme1'],
      [
        'module' => [
          'test_module2',
        ],
        'theme' => [
          'test_theme1',
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

    $definition = $prophet->prophesize(PluginDefinitionInterface::class);
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

    $dependent_definition = $prophet->prophesize(PluginDefinitionInterface::class)->willImplement(DependentPluginDefinitionInterface::class);
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
    getPluginDependencies as public;
  }

  protected $moduleHandler;

  protected $themeHandler;

  public function setModuleHandler(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  public function setThemeHandler(ThemeHandlerInterface $theme_handler) {
    $this->themeHandler = $theme_handler;
  }

  protected function moduleHandler() {
    return $this->moduleHandler;
  }

  protected function themeHandler() {
    return $this->themeHandler;
  }

  /**
   * @return array[]
   */
  public function getDependencies() {
    return $this->dependencies;
  }

}
