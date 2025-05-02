<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Extension;

use Drupal\Core\Extension\Exception\UnknownExtensionException;
use Drupal\KernelTests\KernelTestBase;

/**
 * @coversDefaultClass \Drupal\Core\Extension\ModuleHandler
 *
 * @group Extension
 */
class ModuleHandlerTest extends KernelTestBase {

  /**
   * Tests requesting the name of an invalid module.
   *
   * @covers ::getName
   */
  public function testInvalidGetName(): void {
    $this->expectException(UnknownExtensionException::class);
    $this->expectExceptionMessage('The module module_nonsense does not exist.');
    $module_handler = $this->container->get('module_handler');
    $module_handler->getModule('module_nonsense');
  }

  /**
   * Tests deprecation of getName() function.
   *
   * @group legacy
   */
  public function testGetNameDeprecation(): void {
    $this->expectDeprecation('Drupal\Core\Extension\ModuleHandler::getName() is deprecated in drupal:10.3.0 and is removed from drupal:12.0.0. Use \Drupal\Core\Extension\ModuleExtensionList::getName($module) instead. See https://www.drupal.org/node/3310017');
    $this->assertNotNull(\Drupal::service('module_handler')->getName('module_test'));
  }

  /**
   * Tests that resetImplementations clears the invokeMap memory cache.
   *
   * @covers ::resetImplementations
   */
  public function testResetImplementationsClearsInvokeMap(): void {
    /** @var \Drupal\Core\Extension\ModuleInstallerInterface $moduleInstaller */
    $moduleInstaller = \Drupal::service('module_installer');
    $moduleInstaller->install(['module_test']);
    /** @var \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler */
    $moduleHandler = \Drupal::service('module_handler');
    $this->assertTrue($moduleHandler->hasImplementations('system_info_alter'));
    $moduleInstaller->uninstall(['module_test']);
    $this->assertFalse($moduleHandler->hasImplementations('system_info_alter'));
  }

}
