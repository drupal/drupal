<?php

namespace Drupal\Tests\responsive_image\Functional;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\responsive_image\Entity\ResponsiveImageStyle;

/**
 * Tests order multipliers numerically upgrade path.
 *
 * @group responsive_image
 */
class ResponsiveImageOrderMultipliersNumericallyUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles(): void {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../system/tests/fixtures/update/drupal-9.0.0.filled.standard.php.gz',
      __DIR__ . '/../../fixtures/update/responsive_image.php',
      __DIR__ . '/../../fixtures/update/responsive_image-order-multipliers-numerically.php',
    ];
  }

  /**
   * Test order multipliers numerically upgrade path.
   *
   * @see responsive_image_post_update_order_multiplier_numerically()
   */
  public function testUpdate(): void {
    $mappings = ResponsiveImageStyle::load('responsive_image_style')->getImageStyleMappings();
    $this->assertEquals('1.5x', $mappings[0]['multiplier']);
    $this->assertEquals('2x', $mappings[1]['multiplier']);
    $this->assertEquals('1x', $mappings[2]['multiplier']);

    $this->runUpdates();

    $mappings = ResponsiveImageStyle::load('responsive_image_style')->getImageStyleMappings();
    $this->assertEquals('1x', $mappings[0]['multiplier']);
    $this->assertEquals('1.5x', $mappings[1]['multiplier']);
    $this->assertEquals('2x', $mappings[2]['multiplier']);
  }

  public function testEntitySave(): void {
    $image_style = ResponsiveImageStyle::load('responsive_image_style');
    $mappings = $image_style->getImageStyleMappings();
    $this->assertEquals('1.5x', $mappings[0]['multiplier']);
    $this->assertEquals('2x', $mappings[1]['multiplier']);
    $this->assertEquals('1x', $mappings[2]['multiplier']);

    $image_style->save();

    $mappings = ResponsiveImageStyle::load('responsive_image_style')->getImageStyleMappings();
    $this->assertEquals('1x', $mappings[0]['multiplier']);
    $this->assertEquals('1.5x', $mappings[1]['multiplier']);
    $this->assertEquals('2x', $mappings[2]['multiplier']);
  }

}
