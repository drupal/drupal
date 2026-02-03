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
 * Tests Drupal\Core\Extension\ModuleHandler.
 */
#[CoversClass(ModuleHandler::class)]
#[Group('Extension')]
#[RunTestsInSeparateProcesses]
class ModuleHandlerTest extends KernelTestBase {

  /**
   * Tests that resetImplementations() clears the hook memory cache.
   */
  public function testResetImplementationsClearsHooks(): void {
    $oldModuleHandler = \Drupal::moduleHandler();
    $this->assertHasResetHookImplementations(FALSE, $oldModuleHandler);

    // Installing a module does not trigger ->resetImplementations().
    /** @var \Drupal\Core\Extension\ModuleInstallerInterface $moduleInstaller */
    $moduleInstaller = \Drupal::service('module_installer');
    $moduleInstaller->install(['module_test']);
    $this->assertHasResetHookImplementations(FALSE, $oldModuleHandler);
    // Only the new ModuleHandler instance has the updated implementations.
    $moduleHandler = \Drupal::moduleHandler();
    $this->assertHasResetHookImplementations(TRUE, $moduleHandler);
    $backupModuleList = $moduleHandler->getModuleList();
    $moduleListWithout = array_diff_key($backupModuleList, ['module_test' => TRUE]);
    $this->assertArrayHasKey('module_test', $backupModuleList);

    // Silently setting the property does not clear the hooks cache.
    $moduleListProperty = (new \ReflectionProperty($moduleHandler, 'moduleList'));
    $this->assertSame($backupModuleList, $moduleListProperty->getValue($moduleHandler));
    $moduleListProperty->setValue($moduleHandler, $moduleListWithout);
    $this->assertHasResetHookImplementations(TRUE, $moduleHandler);

    // Directly calling ->resetImplementations() clears the hook caches.
    $moduleHandler->resetImplementations();
    $this->assertHasResetHookImplementations(FALSE, $moduleHandler);
    $moduleListProperty->setValue($moduleHandler, $backupModuleList);
    $this->assertHasResetHookImplementations(FALSE, $moduleHandler);
    $moduleHandler->resetImplementations();
    $this->assertHasResetHookImplementations(TRUE, $moduleHandler);

    // Calling ->setModuleList() triggers ->resetImplementations().
    $moduleHandler->setModuleList(['system']);
    $this->assertHasResetHookImplementations(FALSE, $moduleHandler);
    $moduleHandler->setModuleList($backupModuleList);
    $this->assertHasResetHookImplementations(TRUE, $moduleHandler);

    // Uninstalling a module triggers ->resetImplementations().
    /** @var \Drupal\Core\Extension\ModuleInstallerInterface $moduleInstaller */
    $moduleInstaller = \Drupal::service('module_installer');
    $moduleInstaller->uninstall(['module_test']);
    $this->assertSame($moduleListWithout, $moduleHandler->getModuleList());
    $this->assertHasResetHookImplementations(FALSE, $moduleHandler);
  }

  /**
   * Asserts whether certain hook implementations exist.
   *
   * This is used to verify that all internal hook cache properties have been
   * reset and updated.
   *
   * @param bool $exists
   *   TRUE if the implementations are expected to exist, FALSE if not.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   *
   * @see \module_test_test_reset_implementations_hook()
   * @see \module_test_test_reset_implementations_alter()
   */
  protected function assertHasResetHookImplementations(bool $exists, ModuleHandlerInterface $moduleHandler): void {
    $this->assertSame($exists, $moduleHandler->hasImplementations('test_reset_implementations_hook'));
    $this->assertSame($exists, $moduleHandler->hasImplementations('test_reset_implementations_alter'));
    $expected_list = $exists ? ['module_test_test_reset_implementations_hook'] : [];
    $this->assertSame($expected_list, $moduleHandler->invokeAll('test_reset_implementations_hook'));
    $expected_alter_list = $exists ? ['module_test_test_reset_implementations_alter'] : [];
    $alter_list = [];
    $moduleHandler->alter('test_reset_implementations', $alter_list);
    $this->assertSame($expected_alter_list, $alter_list);
  }

}
