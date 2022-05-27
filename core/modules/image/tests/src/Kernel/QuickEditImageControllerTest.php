<?php

namespace Drupal\Tests\image\Kernel;

use Drupal\image\Controller\QuickEditImageController;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests class \Drupal\image\Controller\QuickEditImageController deprecation.
 *
 * @group image
 * @group legacy
 */
class QuickEditImageControllerTest extends KernelTestBase {

  /**
   * Tests class \Drupal\image\Controller\QuickEditImageController deprecation.
   */
  public function testQuickEditImageControllerDeprecation(): void {
    $this->expectDeprecation('Drupal\image\Controller\QuickEditImageController is deprecated in drupal:9.4.0 and is removed from drupal:10.0.0. Instead, use Drupal\quickedit\QuickEditImageController. See https://www.drupal.org/node/3271848');
    new QuickEditImageController(
      $this->container->get('renderer'),
      $this->container->get('image.factory'),
      $this->container->get('tempstore.private'),
      $this->container->get('entity_display.repository'),
      $this->container->get('file_system')
    );
  }

}
