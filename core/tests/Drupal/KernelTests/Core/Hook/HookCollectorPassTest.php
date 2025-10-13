<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Hook;

use Drupal\Core\Cache\NullBackend;
use Drupal\Core\Hook\HookCollectorKeyValueWritePass;
use Drupal\Core\Hook\HookCollectorPass;
use Drupal\Core\KeyValueStore\KeyValueMemoryFactory;
use Drupal\KernelTests\KernelTestBase;
use Drupal\module_handler_test_all1\Hook\ModuleHandlerTestAll1Hooks;
use Drupal\user_hooks_test\Hook\UserHooksTest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Tests Drupal\Core\Hook\HookCollectorPass.
 */
#[CoversClass(HookCollectorPass::class)]
#[Group('Hook')]
#[RunTestsInSeparateProcesses]
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
    $keyvalue = new KeyValueMemoryFactory();
    $container->set('keyvalue', $keyvalue);
    $container->set('cache.bootstrap', new NullBackend('bootstrap'));
    (new HookCollectorPass())->process($container);
    (new HookCollectorKeyValueWritePass())->process($container);
    $this->assertRegisteredHooks([
      'user_format_name_alter' => [
        UserHooksTest::class . '::userFormatNameAlter' => 'user_hooks_test',
      ],
    ], $container);
  }

  /**
   * Test that ordering works.
   */
  #[IgnoreDeprecations]
  public function testOrdering(): void {
    $container = new ContainerBuilder();
    $module_filenames = [
      'module_handler_test_all1' => ['pathname' => "core/tests/Drupal/Tests/Core/Extension/modules/module_handler_test_all1/module_handler_test_all1.info.yml"],
      'module_handler_test_all2' => ['pathname' => "core/tests/Drupal/Tests/Core/Extension/modules/module_handler_test_all2/module_handler_test_all2.info.yml"],
    ];
    include_once 'core/tests/Drupal/Tests/Core/Extension/modules/module_handler_test_all1/src/Hook/ModuleHandlerTestAll1Hooks.php';
    $container->setParameter('container.modules', $module_filenames);
    $keyvalue = new KeyValueMemoryFactory();
    $container->set('keyvalue', $keyvalue);
    $container->set('cache.bootstrap', new NullBackend('bootstrap'));
    (new HookCollectorPass())->process($container);
    (new HookCollectorKeyValueWritePass())->process($container);
    $this->assertRegisteredHooks([
      'hook' => [
        'module_handler_test_all1_hook' => 'module_handler_test_all1',
        'module_handler_test_all2_hook' => 'module_handler_test_all2',
      ],
      'module_implements_alter' => [
        'module_handler_test_all1_module_implements_alter' => 'module_handler_test_all1',
      ],
      'order1' => [
        'module_handler_test_all2_order1' => 'module_handler_test_all2',
        ModuleHandlerTestAll1Hooks::class . '::order' => 'module_handler_test_all1',
      ],
      'order2' => [
        ModuleHandlerTestAll1Hooks::class . '::order' => 'module_handler_test_all1',
        'module_handler_test_all2_order2' => 'module_handler_test_all2',
      ],
    ], $container);
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
    $keyvalue = new KeyValueMemoryFactory();
    $container->set('keyvalue', $keyvalue);
    $container->set('cache.bootstrap', new NullBackend('bootstrap'));
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
   * Test procedural hooks for a module are skipped when skip is set.
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
    $this->assertFalse($this->container->hasParameter('hook_collector_skip_procedural.skip_procedural_hook_scan'));
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
   * Tests that legacy install/update hooks are ignored.
   */
  public function testHookIgnoreNonOop(): void {
    $module_installer = $this->container->get('module_installer');
    $this->assertTrue($module_installer->install(['system_module_test', 'update', 'update_test_2']));

    $map = $this->container->get('keyvalue')->get('hook_data')->get('hook_list');
    $this->assertArrayHasKey('help', $map);
    // Ensure that no install or update hooks are registered in the
    // implementations map, including update functions incorrectly mapped to
    // the wrong module.
    $this->assertArrayNotHasKey('install', $map);
    $this->assertArrayNotHasKey('update_dependencies', $map);
    $this->assertArrayNotHasKey('test_2_update_8001', $map);
    $this->assertArrayNotHasKey('update_8001', $map);
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

  /**
   * Asserts that given hook implementations are registered in the container.
   *
   * @param array<string, array<string, string>> $implementationsByHook
   *   Expected implementations, as module names keyed by hook name and
   *   "$class::$method" identifier.
   * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
   *   The container builder.
   */
  protected function assertRegisteredHooks(array $implementationsByHook, ContainerBuilder $container): void {
    foreach ($implementationsByHook as $hook => $implementations) {
      $this->assertEquals($implementations, $container->get('keyvalue')->get('hook_data')->get('hook_list')[$hook]);
    }
  }

}
