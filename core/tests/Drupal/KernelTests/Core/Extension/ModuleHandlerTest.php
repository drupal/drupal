<?php

namespace Drupal\KernelTests\Core\Extension;

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
   * @group legacy
   * @expectedDeprecation Calling ModuleHandler::getName() with an unknown module is deprecated in Drupal 8.7.0 and support for this will be removed in Drupal 9.0.0, check that the module exists before calling this method. See https://www.drupal.org/node/3024541.
   */
  public function testInvalidGetName() {
    $module_handler = $this->container->get('module_handler');
    $this->assertSame('module_nonsense', $module_handler->getName('module_nonsense'));
  }

}
