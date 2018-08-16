<?php

namespace Drupal\Tests\responsive_image\Kernel;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\responsive_image\Entity\ResponsiveImageStyle;

/**
 * Tests the integration of responsive image with other components.
 *
 * @group responsive_image
 */
class ResponsiveImageIntegrationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['responsive_image', 'field', 'image', 'file', 'entity_test', 'breakpoint', 'responsive_image_test_module'];

  /**
   * Tests integration with entity view display.
   */
  public function testEntityViewDisplayDependency() {
    // Create a responsive image style.
    ResponsiveImageStyle::create([
      'id' => 'foo',
      'label' => 'Foo',
      'breakpoint_group' => 'responsive_image_test_module',
    ])->save();
    // Create an image field to be used with a responsive image formatter.
    FieldStorageConfig::create([
      'type' => 'image',
      'entity_type' => 'entity_test',
      'field_name' => 'bar',
    ])->save();
    FieldConfig::create([
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
      'field_name' => 'bar',
    ])->save();
    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display */
    $display = EntityViewDisplay::create([
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
    ]);
    $display->setComponent('bar', [
      'type' => 'responsive_image',
      'label' => 'hidden',
      'settings' => ['responsive_image_style' => 'foo', 'image_link' => ''],
      'third_party_settings' => [],
    ])->save();

    // Check that the 'foo' field is on the display.
    $this->assertNotNull($display = EntityViewDisplay::load('entity_test.entity_test.default'));
    $this->assertTrue($display->getComponent('bar'));
    $this->assertArrayNotHasKey('bar', $display->get('hidden'));

    // Delete the responsive image style.
    ResponsiveImageStyle::load('foo')->delete();

    // Check that the view display was not deleted.
    $this->assertNotNull($display = EntityViewDisplay::load('entity_test.entity_test.default'));
    // Check that the 'foo' field was disabled.
    $this->assertNull($display->getComponent('bar'));
    $this->assertArrayHasKey('bar', $display->get('hidden'));
  }

}
