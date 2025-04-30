<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Hook;

use Drupal\Core\Hook\HookCollectorPass;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * @coversDefaultClass \Drupal\Core\Hook\HookCollectorPass
 * @group Hook
 */
class HookCollectorPassTest extends KernelTestBase {

  /**
   * VFS does not and can not support symlinks.
   */
  protected function setUpFilesystem(): void {}

  /**
   * Test that symlinks are properly followed.
   */
  public function testSymlink(): void {
    mkdir($this->siteDirectory);

    foreach (scandir("core/modules/user/tests/modules/user_hooks_test") as $item) {
      $target = "$this->siteDirectory/$item";
      if (!file_exists($target)) {
        symlink(realpath("core/modules/user/tests/modules/user_hooks_test/$item"), $target);
      }
    }
    $container = new ContainerBuilder();
    $module_filenames = [
      'user_hooks_test' => ['pathname' => "$this->siteDirectory/user_hooks_test.info.yml"],
    ];
    $container->setParameter('container.modules', $module_filenames);
    $container->setDefinition('module_handler', new Definition());
    (new HookCollectorPass())->process($container);
    $implementations = [
      'user_format_name_alter' => [
        'Drupal\user_hooks_test\Hook\UserHooksTest' => [
          'userFormatNameAlter' => 'user_hooks_test',
        ],
      ],
    ];

    $this->assertSame($implementations, $container->getParameter('hook_implementations_map'));
  }

  /**
   * Test that ordering works.
   *
   * @group legacy
   */
  public function testOrdering(): void {
    $container = new ContainerBuilder();
    $module_filenames = [
      'module_handler_test_all1' => ['pathname' => "core/tests/Drupal/Tests/Core/Extension/modules/module_handler_test_all1/module_handler_test_all1.info.yml"],
      'module_handler_test_all2' => ['pathname' => "core/tests/Drupal/Tests/Core/Extension/modules/module_handler_test_all2/module_handler_test_all2.info.yml"],
    ];
    include_once 'core/tests/Drupal/Tests/Core/Extension/modules/module_handler_test_all1/src/Hook/ModuleHandlerTestAll1Hooks.php';
    $container->setParameter('container.modules', $module_filenames);
    $container->setDefinition('module_handler', new Definition());
    (new HookCollectorPass())->process($container);
    $priorities = [];
    foreach ($container->findTaggedServiceIds('kernel.event_listener') as $tags) {
      foreach ($tags as $attributes) {
        if (str_starts_with($attributes['event'], 'drupal_hook.order')) {
          $priorities[$attributes['event']][$attributes['method']] = $attributes['priority'];
        }
      }
    }
    // For the order1 hook, module_handler_test_all2_order1() fires first
    // despite all1 coming before all2 in the module list, because
    // module_handler_test_all1_module_implements_alter() moved all1 to the
    // end. The array key 'order' comes from
    // ModuleHandlerTestAll1Hooks::order().
    $this->assertGreaterThan($priorities['drupal_hook.order1']['order'], $priorities['drupal_hook.order1']['module_handler_test_all2_order1']);
    // For the hook order2 or any hook but order1, however, all1 fires first
    // and all2 second.
    $this->assertLessThan($priorities['drupal_hook.order2']['order'], $priorities['drupal_hook.order2']['module_handler_test_all2_order2']);
  }

  /**
   * Test LegacyModuleImplementsAlter.
   */
  public function testLegacyModuleImplementsAlter(): void {
    $container = new ContainerBuilder();
    $module_filenames = [
      'module_implements_alter_test_legacy' => ['pathname' => "core/tests/Drupal/Tests/Core/Extension/modules/module_implements_alter_test_legacy/module_implements_alter_test_legacy.info.yml"],
    ];
    include_once 'core/tests/Drupal/Tests/Core/Extension/modules/module_implements_alter_test_legacy/module_implements_alter_test_legacy.module';
    $container->setParameter('container.modules', $module_filenames);
    $container->setDefinition('module_handler', new Definition());
    (new HookCollectorPass())->process($container);

    // This test will also fail if the deprecation notice shows up.
    $this->assertFalse(isset($GLOBALS['ShouldNotRunLegacyModuleImplementsAlter']));
  }

  /**
   * Test hooks implemented on behalf of an uninstalled module.
   *
   * They should be picked up but only executed when the other
   * module is installed.
   */
  public function testHooksImplementedOnBehalfFileCache(): void {
    $module_installer = $this->container->get('module_installer');
    $this->assertTrue($module_installer->install(['hook_collector_on_behalf']));
    $this->assertTrue($module_installer->install(['hook_collector_on_behalf_procedural']));
    drupal_flush_all_caches();
    $this->assertFalse(isset($GLOBALS['on_behalf_oop']));
    $this->assertFalse(isset($GLOBALS['on_behalf_procedural']));
    $this->assertTrue($module_installer->install(['respond_install_uninstall_hook_test']));
    drupal_flush_all_caches();
    $this->assertTrue(isset($GLOBALS['on_behalf_oop']));
    $this->assertTrue(isset($GLOBALS['on_behalf_procedural']));
  }

  /**
   * Test procedural hooks for a module are skipped when skip is set..
   */
  public function testProceduralHooksSkippedWhenConfigured(): void {
    $module_installer = $this->container->get('module_installer');
    $this->assertTrue($module_installer->install(['hook_collector_skip_procedural']));
    $this->assertTrue($module_installer->install(['hook_collector_on_behalf_procedural']));
    $this->assertTrue($module_installer->install(['hook_collector_skip_procedural_attribute']));
    $this->assertTrue($module_installer->install(['hook_collector_on_behalf']));
    $this->assertFalse(isset($GLOBALS['skip_procedural_all']));
    $this->assertFalse(isset($GLOBALS['procedural_attribute_skip_has_attribute']));
    $this->assertFalse(isset($GLOBALS['procedural_attribute_skip_after_attribute']));
    $this->assertFalse(isset($GLOBALS['procedural_attribute_skip_find']));
    $this->assertFalse(isset($GLOBALS['skipped_procedural_oop_cache_flush']));
    drupal_flush_all_caches();
    $this->assertFalse(isset($GLOBALS['skip_procedural_all']));
    $this->assertFalse(isset($GLOBALS['procedural_attribute_skip_has_attribute']));
    $this->assertFalse(isset($GLOBALS['procedural_attribute_skip_after_attribute']));
    $this->assertTrue(isset($GLOBALS['procedural_attribute_skip_find']));
    $this->assertTrue(isset($GLOBALS['skipped_procedural_oop_cache_flush']));
  }

  /**
   * Tests hook ordering with attributes.
   */
  public function testHookFirst(): void {
    $module_installer = $this->container->get('module_installer');
    $module_installer->install(['aaa_hook_collector_test']);
    $module_installer->install(['bbb_hook_collector_test']);
    $module_handler = $this->container->get('module_handler');
    // Last alphabetically uses the Order::First enum to place it before
    // the implementation it would naturally come after.
    $expected_calls = [
      'Drupal\bbb_hook_collector_test\Hook\TestHookFirst::hookFirst',
      'Drupal\aaa_hook_collector_test\Hook\TestHookFirst::hookFirst',
    ];
    $calls = $module_handler->invokeAll('custom_hook_test_hook_first');
    $this->assertEquals($expected_calls, $calls);
  }

  /**
   * Tests hook ordering with attributes.
   */
  public function testHookAfter(): void {
    $module_installer = $this->container->get('module_installer');
    $module_installer->install(['aaa_hook_collector_test']);
    $module_installer->install(['bbb_hook_collector_test']);
    $module_handler = $this->container->get('module_handler');
    // First alphabetically uses the OrderAfter to place it after
    // the implementation it would naturally come before.
    $expected_calls = [
      'Drupal\bbb_hook_collector_test\Hook\TestHookAfter::hookAfter',
      'Drupal\aaa_hook_collector_test\Hook\TestHookAfter::hookAfter',
    ];
    $calls = $module_handler->invokeAll('custom_hook_test_hook_after');
    $this->assertEquals($expected_calls, $calls);
  }

  /**
   * Tests hook ordering with attributes.
   */
  public function testHookAfterClassMethod(): void {
    $module_installer = $this->container->get('module_installer');
    $module_installer->install(['aaa_hook_collector_test']);
    $module_installer->install(['bbb_hook_collector_test']);
    $module_handler = $this->container->get('module_handler');
    // First alphabetically uses the OrderAfter to place it after
    // the implementation it would naturally come before using call and method.
    $expected_calls = [
      'Drupal\bbb_hook_collector_test\Hook\TestHookAfterClassMethod::hookAfterClassMethod',
      'Drupal\aaa_hook_collector_test\Hook\TestHookAfterClassMethod::hookAfterClassMethod',
    ];
    $calls = $module_handler->invokeAll('custom_hook_test_hook_after_class_method');
    $this->assertEquals($expected_calls, $calls);
  }

  /**
   * Tests hook ordering with attributes.
   */
  public function testHookBefore(): void {
    $module_installer = $this->container->get('module_installer');
    $module_installer->install(['aaa_hook_collector_test']);
    $module_installer->install(['bbb_hook_collector_test']);
    $module_handler = $this->container->get('module_handler');
    // First alphabetically uses the OrderBefore to place it before
    // the implementation it would naturally come after.
    $expected_calls = [
      'Drupal\bbb_hook_collector_test\Hook\TestHookBefore::hookBefore',
      'Drupal\aaa_hook_collector_test\Hook\TestHookBefore::hookBefore',
    ];
    $calls = $module_handler->invokeAll('custom_hook_test_hook_before');
    $this->assertEquals($expected_calls, $calls);
  }

  /**
   * Tests hook ordering with attributes.
   */
  public function testHookOrderExtraTypes(): void {
    $module_installer = $this->container->get('module_installer');
    $module_installer->install(['aaa_hook_collector_test']);
    $module_installer->install(['bbb_hook_collector_test']);
    $module_handler = $this->container->get('module_handler');
    // First alphabetically uses the OrderAfter to place it after
    // the implementation it would naturally come before.
    $expected_calls = [
      'Drupal\bbb_hook_collector_test\Hook\TestHookOrderExtraTypes::customHookExtraTypes',
      'Drupal\aaa_hook_collector_test\Hook\TestHookOrderExtraTypes::customHookExtraTypes',
    ];
    $hooks = [
      'custom_hook',
      'custom_hook_extra_types1',
      'custom_hook_extra_types2',
    ];
    $calls = [];
    $module_handler->alter($hooks, $calls);
    $this->assertEquals($expected_calls, $calls);
  }

  /**
   * Tests hook ordering with attributes.
   */
  public function testHookLast(): void {
    $module_installer = $this->container->get('module_installer');
    $module_installer->install(['aaa_hook_collector_test']);
    $module_installer->install(['bbb_hook_collector_test']);
    $module_handler = $this->container->get('module_handler');
    // First alphabetically uses the OrderBefore to place it before
    // the implementation it would naturally come after.
    $expected_calls = [
      'Drupal\bbb_hook_collector_test\Hook\TestHookLast::hookLast',
      'Drupal\aaa_hook_collector_test\Hook\TestHookLast::hookLast',
    ];
    $calls = $module_handler->invokeAll('custom_hook_test_hook_last');
    $this->assertEquals($expected_calls, $calls);
  }

  /**
   * Tests hook remove.
   */
  public function testHookRemove(): void {
    $module_installer = $this->container->get('module_installer');
    $this->assertTrue($module_installer->install(['hook_test_remove']));
    $module_handler = $this->container->get('module_handler');
    // There are two hooks implementing custom_hook1.
    // One is removed with RemoveHook so it should not run.
    $expected_calls = [
      'Drupal\hook_test_remove\Hook\TestHookRemove::hookDoRun',
    ];
    $calls = $module_handler->invokeAll('custom_hook1');
    $this->assertEquals($expected_calls, $calls);
  }

  /**
   * Tests hook override.
   */
  public function testHookOverride(): void {
    $module_installer = $this->container->get('module_installer');
    $module_installer->install(['aaa_hook_collector_test']);
    $module_installer->install(['bbb_hook_collector_test']);
    $module_handler = $this->container->get('module_handler');
    $expected_calls = [
      'Drupal\aaa_hook_collector_test\Hook\TestHookReorderHookFirst::customHookOverride',
      'Drupal\bbb_hook_collector_test\Hook\TestHookReorderHookLast::customHookOverride',
    ];
    $calls = $module_handler->invokeAll('custom_hook_override');
    $this->assertEquals($expected_calls, $calls);
  }

}
