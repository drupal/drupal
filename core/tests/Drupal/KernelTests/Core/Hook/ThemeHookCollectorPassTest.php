<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Hook;

use Drupal\Core\Cache\NullBackend;
use Drupal\Core\Hook\ThemeHookCollectorPass;
use Drupal\Core\KeyValueStore\KeyValueMemoryFactory;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Tests Drupal\Core\Hook\ThemeHookCollectorPass.
 */
#[CoversClass(ThemeHookCollectorPass::class)]
#[Group('Hook')]
#[RunTestsInSeparateProcesses]
class ThemeHookCollectorPassTest extends KernelTestBase {

  /**
   * Test collection.
   */
  public function testCollection(): void {
    $container = new ContainerBuilder();
    $theme_filenames = [
      'oop_hook_theme' => [
        'pathname' => 'core/modules/system/tests/themes/HookCollector/oop_hook_theme/oop_hook_theme.info.yml',
      ],
    ];
    $container->setParameter('container.themes', $theme_filenames);
    $keyvalue = new KeyValueMemoryFactory();
    $container->set('keyvalue', $keyvalue);
    $container->set('cache.bootstrap', new NullBackend('bootstrap'));
    (new ThemeHookCollectorPass())->process($container);

    $themeHookData = $container->getParameter('.theme_hook_data');
    $this->assertEquals(
      ['Drupal\oop_hook_theme\Hook\TestHookCollectionHooks::testHookAlter'],
      $themeHookData['theme_hook_list']['oop_hook_theme']['test_hook_alter'],
    );
  }

  /**
   * Test exception with module parameter.
   *
   * The module parameter is not allowed.
   */
  public function testExceptionModule(): void {
    $this->expectException(\LogicException::class);
    $container = new ContainerBuilder();
    $theme_filenames = [
      'oop_hook_theme_with_module' => [
        'pathname' => 'core/modules/system/tests/themes/HookCollector/oop_hook_theme_with_module/oop_hook_theme_with_module.info.yml',
      ],
    ];
    $container->setParameter('container.themes', $theme_filenames);
    $container->setParameter('preprocess_for_suggestions', []);
    $container->setDefinition('theme.manager', new Definition());
    (new ThemeHookCollectorPass())->process($container);
  }

  /**
   * Test exception with order parameter.
   *
   * The order parameter is not allowed.
   */
  public function testExceptionOrder(): void {
    $this->expectException(\LogicException::class);
    $container = new ContainerBuilder();
    $theme_filenames = [
      'oop_hook_theme_with_order' => [
        'pathname' => 'core/modules/system/tests/themes/HookCollector/oop_hook_theme_with_order/oop_hook_theme_with_order.info.yml',
      ],
    ];
    $container->setParameter('container.themes', $theme_filenames);
    $container->setParameter('preprocess_for_suggestions', []);
    $container->setDefinition('theme.manager', new Definition());
    (new ThemeHookCollectorPass())->process($container);
  }

  /**
   * Test exception with reorder attribute.
   *
   * ReorderHook attribute is not allowed.
   */
  public function testExceptionReorder(): void {
    $this->expectException(\LogicException::class);
    $container = new ContainerBuilder();
    $theme_filenames = [
      'oop_hook_theme_with_reorder' => [
        'pathname' => 'core/modules/system/tests/themes/HookCollector/oop_hook_theme_with_reorder/oop_hook_theme_with_reorder.info.yml',
      ],
    ];
    $container->setParameter('container.themes', $theme_filenames);
    $container->setParameter('preprocess_for_suggestions', []);
    $container->setDefinition('theme.manager', new Definition());
    (new ThemeHookCollectorPass())->process($container);
  }

  /**
   * Test exception with remove attribute.
   *
   * RemoveHook attribute is not allowed.
   */
  public function testExceptionRemove(): void {
    $this->expectException(\LogicException::class);
    $container = new ContainerBuilder();
    $theme_filenames = [
      'oop_hook_theme_with_remove' => [
        'pathname' => 'core/modules/system/tests/themes/HookCollector/oop_hook_theme_with_remove/oop_hook_theme_with_remove.info.yml',
      ],
    ];
    $container->setParameter('container.themes', $theme_filenames);
    $container->setParameter('preprocess_for_suggestions', []);
    $container->setDefinition('theme.manager', new Definition());
    (new ThemeHookCollectorPass())->process($container);
  }

  /**
   * Tests hook execution in themes.
   */
  public function testHookExecution(): void {
    $this->container->get('theme_installer')->install(['oop_hook_theme']);
    $theme_manager = $this->container->get('theme.manager');
    $theme_manager->setActiveTheme(\Drupal::service('theme.initialization')->initTheme('oop_hook_theme'));
    $expected_calls = [
      'Drupal\oop_hook_theme\Hook\TestHookCollectionHooks::testHookAlter',
    ];
    $hooks = 'test_hook';
    $calls = [];
    $theme_manager->alter($hooks, $calls);
    $this->assertEquals($expected_calls, $calls);
  }

}
