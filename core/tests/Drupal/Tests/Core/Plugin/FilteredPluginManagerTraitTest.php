<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Plugin;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\Plugin\FilteredPluginManagerInterface;
use Drupal\Core\Plugin\FilteredPluginManagerTrait;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Plugin\FilteredPluginManagerTrait
 * @group Plugin
 */
class FilteredPluginManagerTraitTest extends UnitTestCase {

  /**
   * @covers ::getFilteredDefinitions
   * @dataProvider providerTestGetFilteredDefinitions
   */
  public function testGetFilteredDefinitions($contexts, $expected): void {
    // Start with two plugins.
    $definitions = [];
    $definitions['plugin1'] = ['id' => 'plugin1'];
    $definitions['plugin2'] = ['id' => 'plugin2'];

    $type = 'the_type';
    $consumer = 'the_consumer';
    $extra = ['foo' => 'bar'];

    $context_handler = $this->prophesize(ContextHandlerInterface::class);
    // Remove the second plugin when context1 is provided.
    $context_handler->filterPluginDefinitionsByContexts(['context1' => 'fake context'], $definitions)
      ->willReturn(['plugin1' => $definitions['plugin1']]);
    // Remove the first plugin when no contexts are provided.
    $context_handler->filterPluginDefinitionsByContexts([], $definitions)
      ->willReturn(['plugin2' => $definitions['plugin2']]);

    // After context filtering, the alter hook will be invoked.
    $module_handler = $this->prophesize(ModuleHandlerInterface::class);
    $hooks = ["plugin_filter_{$type}", "plugin_filter_{$type}__{$consumer}"];
    $module_handler->alter($hooks, $expected, $extra, $consumer)->shouldBeCalled();

    $theme_manager = $this->prophesize(ThemeManagerInterface::class);
    $theme_manager->alter($hooks, $expected, $extra, $consumer)->shouldBeCalled();

    $plugin_manager = new TestFilteredPluginManager($definitions, $module_handler->reveal(), $theme_manager->reveal(), $context_handler->reveal());
    $result = $plugin_manager->getFilteredDefinitions($consumer, $contexts, $extra);
    $this->assertSame($expected, $result);
  }

  /**
   * Provides test data for ::testGetFilteredDefinitions().
   */
  public static function providerTestGetFilteredDefinitions() {
    $data = [];
    $data['populated context'] = [
      ['context1' => 'fake context'],
      ['plugin1' => ['id' => 'plugin1']],
    ];
    $data['empty context'] = [
      [],
      ['plugin2' => ['id' => 'plugin2']],
    ];
    $data['null context'] = [
      NULL,
      [
        'plugin1' => ['id' => 'plugin1'],
        'plugin2' => ['id' => 'plugin2'],
      ],
    ];
    return $data;
  }

}

/**
 * Class that allows testing the trait.
 */
class TestFilteredPluginManager extends PluginManagerBase implements FilteredPluginManagerInterface {

  use FilteredPluginManagerTrait;

  /**
   * An array of plugin definitions.
   *
   * @var array
   */
  protected $definitions = [];

  /**
   * The module handler.
   *
   * @var Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The theme manager.
   *
   * @var Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * The context handler.
   *
   * @var Drupal\Core\Plugin\Context\ContextHandlerInterface
   */
  protected $contextHandler;

  public function __construct(array $definitions, ModuleHandlerInterface $module_handler, ThemeManagerInterface $theme_manager, ContextHandlerInterface $context_handler) {
    $this->definitions = $definitions;
    $this->moduleHandler = $module_handler;
    $this->themeManager = $theme_manager;
    $this->contextHandler = $context_handler;
  }

  protected function contextHandler() {
    return $this->contextHandler;
  }

  protected function moduleHandler() {
    return $this->moduleHandler;
  }

  protected function themeManager() {
    return $this->themeManager;
  }

  protected function getType(): string {
    return 'the_type';
  }

  public function getDefinitions() {
    return $this->definitions;
  }

}
