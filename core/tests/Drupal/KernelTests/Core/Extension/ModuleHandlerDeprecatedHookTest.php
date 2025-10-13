<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Extension;

use Drupal\Core\Extension\ModuleHandler;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Test whether deprecated hook invocations trigger errors.
 */
#[CoversClass(ModuleHandler::class)]
#[Group('Extension')]
#[IgnoreDeprecations]
#[RunTestsInSeparateProcesses]
class ModuleHandlerDeprecatedHookTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'deprecation_test',
    'deprecation_hook_attribute_test',
  ];

  /**
   * Tests invoke deprecated.
   *
   * @legacy-covers ::invokeDeprecated
   */
  public function testInvokeDeprecated(): void {
    $this->expectDeprecation('The deprecated hook hook_deprecated_hook() is implemented in these modules: deprecation_test, deprecation_hook_attribute_test. Use something else.');
    /** @var \Drupal\Core\Extension\ModuleHandlerInterface $module_handler */
    $module_handler = $this->container->get('module_handler');
    $arg = 'an_arg';
    $this->assertEquals(
      $arg,
      $module_handler->invokeDeprecated('Use something else.', 'deprecation_test', 'deprecated_hook', [$arg])
    );
  }

  /**
   * Tests invoke all deprecated.
   *
   * @legacy-covers ::invokeAllDeprecated
   */
  public function testInvokeAllDeprecated(): void {
    $this->expectDeprecation('The deprecated hook hook_deprecated_hook() is implemented in these modules: deprecation_test, deprecation_hook_attribute_test. Use something else.');
    /** @var \Drupal\Core\Extension\ModuleHandlerInterface $module_handler */
    $module_handler = $this->container->get('module_handler');
    $arg = 'an_arg';
    $this->assertEquals(
      [
        $arg,
        $arg,
      ],
      $module_handler->invokeAllDeprecated('Use something else.', 'deprecated_hook', [$arg])
    );
  }

  /**
   * Tests alter deprecated.
   *
   * @legacy-covers ::alterDeprecated
   */
  public function testAlterDeprecated(): void {
    $this->expectDeprecation('The deprecated alter hook hook_deprecated_alter_alter() is implemented in these locations: deprecation_test_deprecated_alter_alter, Drupal\deprecation_hook_attribute_test\Hook\DeprecationHookAttributeTestHooks::deprecatedAlterAlterFirst. Alter something else.');
    /** @var \Drupal\Core\Extension\ModuleHandlerInterface $module_handler */
    $module_handler = $this->container->get('module_handler');
    $data = [];
    $context1 = 'test1';
    $context2 = 'test2';
    $module_handler->alterDeprecated('Alter something else.', 'deprecated_alter', $data, $context1, $context2);
    $this->assertEquals([$context1, $context2], $data);
  }

}
