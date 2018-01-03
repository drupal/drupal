<?php

namespace Drupal\KernelTests\Core\Extension;

use Drupal\KernelTests\KernelTestBase;

/**
 * Test whether deprecated hook invocations trigger errors.
 *
 * @group Extension
 * @group legacy
 *
 * @coversDefaultClass Drupal\Core\Extension\ModuleHandler
 */
class ModuleHandlerDeprecatedHookTest extends KernelTestBase {

  protected static $modules = ['deprecation_test'];

  /**
   * @covers ::invokeDeprecated
   * @expectedDeprecation The deprecated hook hook_deprecated_hook() is implemented in these functions: deprecation_test_deprecated_hook(). Use something else.
   */
  public function testInvokeDeprecated() {
    /* @var $module_handler \Drupal\Core\Extension\ModuleHandlerInterface */
    $module_handler = $this->container->get('module_handler');
    $arg = 'an_arg';
    $this->assertEqual(
      $arg,
      $module_handler->invokeDeprecated('Use something else.', 'deprecation_test', 'deprecated_hook', [$arg])
    );
  }

  /**
   * @covers ::invokeAllDeprecated
   * @expectedDeprecation The deprecated hook hook_deprecated_hook() is implemented in these functions: deprecation_test_deprecated_hook(). Use something else.
   */
  public function testInvokeAllDeprecated() {
    /* @var $module_handler \Drupal\Core\Extension\ModuleHandlerInterface */
    $module_handler = $this->container->get('module_handler');
    $arg = 'an_arg';
    $this->assertEqual(
      [$arg],
      $module_handler->invokeAllDeprecated('Use something else.', 'deprecated_hook', [$arg])
    );
  }

  /**
   * @covers ::alterDeprecated
   * @expectedDeprecation The deprecated alter hook hook_deprecated_alter_alter() is implemented in these functions: deprecation_test_deprecated_alter_alter. Alter something else.
   */
  public function testAlterDeprecated() {
    /* @var $module_handler \Drupal\Core\Extension\ModuleHandlerInterface */
    $module_handler = $this->container->get('module_handler');
    $data = [];
    $context1 = 'test1';
    $context2 = 'test2';
    $module_handler->alterDeprecated('Alter something else.', 'deprecated_alter', $data, $context1, $context2);
    $this->assertEqual([$context1, $context2], $data);
  }

}
