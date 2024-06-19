<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Extension;

use Drupal\KernelTests\KernelTestBase;

/**
 * @coversDefaultClass \Drupal\Core\Extension\ModuleExtensionList
 * @group Extension
 */
class ModuleExtensionListTest extends KernelTestBase {

  /**
   * @covers ::getList
   */
  public function testGetList(): void {
    \Drupal::configFactory()->getEditable('core.extension')
      ->set('module.testing', 1000)
      ->set('profile', 'testing')
      ->save();

    // The installation profile is provided by a container parameter.
    // Saving the configuration doesn't automatically trigger invalidation
    $this->container->get('kernel')->rebuildContainer();

    /** @var \Drupal\Core\Extension\ModuleExtensionList $module_extension_list */
    $module_extension_list = \Drupal::service('extension.list.module');
    $extensions = $module_extension_list->getList();

    $this->assertArrayHasKey('testing', $extensions);
    $this->assertEquals(1000, $extensions['testing']->weight);
  }

}
