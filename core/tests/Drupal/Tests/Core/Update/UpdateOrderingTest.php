<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Update;

use Drupal\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Update\UpdateHookRegistry;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Prophecy\Argument;

/**
 * Tests update ordering.
 *
 * Note code is loaded and mock the container, so isolate the tests.
 */
#[Group('Update')]
#[PreserveGlobalState(FALSE)]
#[RunTestsInSeparateProcesses]
class UpdateOrderingTest extends UnitTestCase {

  /**
   * The return value of hook_update_dependencies().
   *
   * @see hook_update_dependencies()
   */
  public static array $updateDependenciesHookReturn = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    require_once $this->root . '/core/includes/update.inc';
    // Load a hook_update_dependencies() implementation that allows this test
    // to control the update ordering.
    require_once $this->root . '/core/tests/fixtures/test_update_ordering/test_update_ordering.php';

    $registry = $this->prophesize(UpdateHookRegistry::class);
    $registry->getAllInstalledVersions()->willReturn(['a_module' => 8000, 'system' => 8000, 'z_module' => 8000]);
    $registry->getAvailableUpdates('system')->willReturn([9000, 9001]);
    $registry->getInstalledVersion('system')->willReturn(8000);
    $registry->getAvailableUpdates('a_module')->willReturn([9000]);
    $registry->getInstalledVersion('a_module')->willReturn(8000);
    $registry->getAvailableUpdates('z_module')->willReturn([9000, 9001]);
    $registry->getInstalledVersion('z_module')->willReturn(8000);
    $extension_list = $this->prophesize(ModuleExtensionList::class);
    $extension_list->exists(Argument::any())->willReturn(TRUE);
    $module_handler = $this->prophesize(ModuleHandlerInterface::class);
    $container = $this->prophesize(ContainerInterface::class);
    $container->get('extension.list.module')->willReturn($extension_list->reveal());
    $container->get('update.update_hook_registry')->willReturn($registry->reveal());
    $container->get('module_handler')->willReturn($module_handler->reveal());
    \Drupal::setContainer($container->reveal());
  }

  /**
   * Tests updates to ensure without dependencies system updates come first.
   */
  public function testUpdateOrdering(): void {
    $updates = update_resolve_dependencies(['a_module' => 9000, 'system' => '9000', 'z_module' => 9000]);
    $this->assertSame([
      'system_update_9000',
      'system_update_9001',
      'z_module_update_9000',
      'z_module_update_9001',
      'a_module_update_9000',
    ], array_keys($updates));
  }

  /**
   * Tests update ordering with a dependency.
   */
  public function testUpdateOrderingWithDependency(): void {
    // Indicate that the a_module_update_9000() function must run before the
    // system_update_9000() function.
    static::$updateDependenciesHookReturn['system'][9000] = [
      'a_module' => 9000,
    ];
    $updates = update_resolve_dependencies(['a_module' => 9000, 'system' => '9000', 'z_module' => 9000]);
    $this->assertSame([
      'a_module_update_9000',
      'system_update_9000',
      'system_update_9001',
      'z_module_update_9000',
      'z_module_update_9001',
    ], array_keys($updates));
  }

  /**
   * Tests update ordering with a dependency chain.
   */
  public function testUpdateOrderingWithDependencyChain(): void {
    // Indicate that the z_module_update_9000() function must run before the
    // a_module_update_9000() function.
    static::$updateDependenciesHookReturn['a_module'][9000] = [
      'z_module' => 9000,
    ];
    // Indicate that the a_module_update_9000() function must run before the
    // system_update_9000() function.
    static::$updateDependenciesHookReturn['system'][9000] = [
      'a_module' => 9000,
    ];
    $updates = update_resolve_dependencies(['a_module' => 9000, 'system' => '9000', 'z_module' => 9000]);
    $this->assertSame([
      'z_module_update_9000',
      'a_module_update_9000',
      'system_update_9000',
      'system_update_9001',
      'z_module_update_9001',
    ], array_keys($updates));
  }

  /**
   * Tests update ordering with dependencies not on system updates.
   */
  public function testUpdateOrderingWithNonSystemDependency(): void {
    // Indicate that the a_module_update_9000() function must run before the
    // z_module_update_9000() function.
    static::$updateDependenciesHookReturn['z_module'][9000] = [
      'a_module' => 9000,
    ];
    $updates = update_resolve_dependencies(['a_module' => 9000, 'system' => '9000', 'z_module' => 9000]);
    $this->assertSame([
      'system_update_9000',
      'system_update_9001',
      'a_module_update_9000',
      'z_module_update_9000',
      'z_module_update_9001',
    ], array_keys($updates));
  }

  /**
   * Tests update ordering with a dependency in between system updates.
   */
  public function testUpdateOrderingWithInBetweenDependency(): void {
    // Indicate that the z_module_update_9000() function must run before the
    // system_update_9001() function.
    static::$updateDependenciesHookReturn['system'][9001] = [
      'z_module' => 9000,
    ];
    $updates = update_resolve_dependencies(['a_module' => 9000, 'system' => '9000', 'z_module' => 9000]);
    $this->assertSame([
      'system_update_9000',
      'z_module_update_9000',
      'system_update_9001',
      'z_module_update_9001',
      'a_module_update_9000',
    ], array_keys($updates));
  }

  /**
   * Tests update ordering with an impossible dependency.
   */
  public function testUpdateOrderingAlreadyRunUpdate(): void {
    // Indicate that the a_module_update_9000() function must run before the
    // system_update_8999() function. Note, this is not impossible as the update
    // has already run.
    static::$updateDependenciesHookReturn['system'][8999] = [
      'a_module' => 9000,
    ];
    // Indicate that the a_module_update_9000() function must run before the
    // z_module_update_9000() function.
    static::$updateDependenciesHookReturn['z_module'][9000] = [
      'a_module' => 9000,
    ];
    $updates = update_resolve_dependencies(['a_module' => 9000, 'system' => '9000', 'z_module' => 9000]);
    $this->assertSame([
      'system_update_9000',
      'system_update_9001',
      'a_module_update_9000',
      'z_module_update_9000',
      'z_module_update_9001',
    ], array_keys($updates));
  }

  /**
   * Tests update ordering with multiple dependencies to system updates.
   */
  public function testUpdateOrderingComplexSystemDependencies(): void {
    // Indicate that the z_module_update_9001() function must run before the
    // a_module_update_9000() function.
    static::$updateDependenciesHookReturn['a_module'][9000] = [
      'z_module' => 9001,
    ];
    // Indicate that the z_module_update_9000() function must run before the
    // system_update_9000() function.
    static::$updateDependenciesHookReturn['system'][9000] = [
      'z_module' => 9000,
    ];
    // Indicate that the a_module_update_9000() function must run before the
    // system_update_9001() function.
    static::$updateDependenciesHookReturn['system'][9001] = [
      'a_module' => 9000,
    ];
    $updates = update_resolve_dependencies(['a_module' => 9000, 'system' => '9000', 'z_module' => 9000]);
    $this->assertSame([
      'z_module_update_9000',
      'system_update_9000',
      'z_module_update_9001',
      'a_module_update_9000',
      'system_update_9001',
    ], array_keys($updates));
  }

}
