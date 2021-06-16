<?php

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
  public function testInvalidGetName() {
    $this->expectException(UnknownExtensionException::class);
    $this->expectExceptionMessage('The module module_nonsense does not exist.');
    $module_handler = $this->container->get('module_handler');
    $module_handler->getName('module_nonsense');
  }

}
