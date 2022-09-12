<?php

namespace Drupal\Tests\responsive_image\Functional;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\responsive_image\Entity\ResponsiveImageStyle;

/**
 * Tests order multipliers numerically upgrade path.
 *
 * @coversDefaultClass \Drupal\responsive_image\ResponsiveImageConfigUpdater
 *
 * @group responsive_image
 * @group legacy
 */
class ResponsiveImageOrderMultipliersNumericallyUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles(): void {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../system/tests/fixtures/update/drupal-9.4.0.filled.standard.php.gz',
      __DIR__ . '/../../fixtures/update/responsive_image.php',
      __DIR__ . '/../../fixtures/update/responsive_image-order-multipliers-numerically.php',
    ];
  }

  /**
   * Test order multipliers numerically upgrade path.
   *
   * @see responsive_image_post_update_order_multiplier_numerically()
   *
   * @legacy
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

  /**
   * Test ResponsiveImageStyle::preSave correctly orders by multiplier weight.
   *
   * @covers ::orderMultipliersNumerically
   */
  public function testEntitySave(): void {
    $this->expectDeprecation('The responsive image style multiplier re-ordering update for "responsive_image_style" is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. Profile, module and theme provided Responsive Image configuration should be updated to accommodate the changes described at https://www.drupal.org/node/3274803.');
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
