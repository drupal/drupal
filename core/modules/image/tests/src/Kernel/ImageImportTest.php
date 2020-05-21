<?php

namespace Drupal\Tests\image\Kernel;

use Drupal\image\Entity\ImageStyle;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests config import for Image styles.
 *
 * @group image
 */
class ImageImportTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system', 'image', 'image_module_test'];

  /**
   * Tests importing image styles.
   */
  public function testImport() {
    $style = ImageStyle::create([
      'name' => 'test',
    ]);

    $style->addImageEffect(['id' => 'image_module_test_null']);
    $style->addImageEffect(['id' => 'image_module_test_null']);
    $style->save();

    $this->assertCount(2, $style->getEffects());

    $uuid = \Drupal::service('uuid')->generate();
    $style->set('effects', [
      $uuid => [
        'id' => 'image_module_test_null',
      ],
    ]);
    $style->save();

    $style = ImageStyle::load('test');
    $this->assertCount(1, $style->getEffects());
  }

}
