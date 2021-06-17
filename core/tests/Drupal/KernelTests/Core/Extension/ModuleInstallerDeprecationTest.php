<?php

namespace Drupal\KernelTests\Core\Extension;

use Drupal\Core\DrupalKernelInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ModuleInstaller;
use Drupal\KernelTests\KernelTestBase;

/**
 * @group legacy
 * @group extension
 * @coversDefaultClass \Drupal\Core\Extension\ModuleInstaller
 */
class ModuleInstallerDeprecationTest extends KernelTestBase {

  /**
   * @covers ::__construct
   */
  public function testConstructorDeprecation() {
    $this->expectDeprecation('Calling ' . ModuleInstaller::class . '::__construct() without the $update_registry argument is deprecated in drupal:9.3.0 and $update_registry argument will be required in drupal:10.0.0. See https://www.drupal.org/node/2124069');
    $root = '';
    $module_handler = $this->prophesize(ModuleHandlerInterface::class);
    $kernel = $this->prophesize(DrupalKernelInterface::class);
    $this->assertNotNull(new ModuleInstaller($root, $module_handler->reveal(), $kernel->reveal()));
  }

}
