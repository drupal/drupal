<?php

declare(strict_types=1);

namespace Drupal\Tests\image\Kernel;

use Drupal\image\Entity\ImageStyle;
use Drupal\KernelTests\Core\Config\ConfigEntityValidationTestBase;

/**
 * Tests validation of image_style entities.
 *
 * @group image
 * @group #slow
 */
class ImageStyleValidationTest extends ConfigEntityValidationTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['image'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entity = ImageStyle::create([
      'name' => 'test',
      'label' => 'Test',
    ]);
    $this->entity->save();
  }

}
