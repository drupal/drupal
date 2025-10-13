<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Extension;

use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Test whether unimplemented deprecated hook invocations trigger errors.
 */
#[CoversClass(ModuleHandler::class)]
#[Group('Extension')]
#[RunTestsInSeparateProcesses]
class ModuleHandlerDeprecatedHookUnimplementedTest extends KernelTestBase {

  /**
   * Tests unimplemented hooks.
   *
   * @legacy-covers ::alterDeprecated
   * @legacy-covers ::invokeAllDeprecated
   * @legacy-covers ::invokeDeprecated
   */
  public function testUnimplementedHooks(): void {
    $unimplemented_hook_name = 'unimplemented_hook_name';

    /** @var \Drupal\Core\Extension\ModuleHandlerInterface $module_handler */
    $module_handler = $this->container->get('module_handler');
    $this->assertInstanceOf(ModuleHandlerInterface::class, $module_handler);

    $module_handler->invokeDeprecated('Use something else.', 'deprecation_test', $unimplemented_hook_name);
    $module_handler->invokeAllDeprecated('Use something else.', $unimplemented_hook_name);
    $data = [];
    $module_handler->alterDeprecated('Alter something else.', $unimplemented_hook_name, $data);
  }

}
