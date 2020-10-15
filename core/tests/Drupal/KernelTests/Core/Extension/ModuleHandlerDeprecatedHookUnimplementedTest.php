<?php

namespace Drupal\KernelTests\Core\Extension;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\KernelTests\KernelTestBase;

/**
 * Test whether unimplemented deprecated hook invocations trigger errors.
 *
 * @group Extension
 *
 * @coversDefaultClass Drupal\Core\Extension\ModuleHandler
 */
class ModuleHandlerDeprecatedHookUnimplementedTest extends KernelTestBase {

  /**
   * @covers ::alterDeprecated
   * @covers ::invokeAllDeprecated
   * @covers ::invokeDeprecated
   */
  public function testUnimplementedHooks() {
    $unimplemented_hook_name = 'unimplemented_hook_name';

    /* @var $module_handler \Drupal\Core\Extension\ModuleHandlerInterface */
    $module_handler = $this->container->get('module_handler');
    $this->assertInstanceOf(ModuleHandlerInterface::class, $module_handler);

    $module_handler->invokeDeprecated('Use something else.', 'deprecation_test', $unimplemented_hook_name);
    $module_handler->invokeAllDeprecated('Use something else.', $unimplemented_hook_name);
    $data = [];
    $module_handler->alterDeprecated('Alter something else.', $unimplemented_hook_name, $data);
  }

}
