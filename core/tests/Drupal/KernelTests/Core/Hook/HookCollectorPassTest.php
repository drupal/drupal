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

}
