<?php

namespace Drupal\Tests\responsive_image\Functional;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests lazy-load upgrade path.
 *
 * @coversDefaultClass \Drupal\responsive_image\ResponsiveImageConfigUpdater
 *
 * @group responsive_image
 * @group legacy
 */
class ResponsiveImageLazyLoadUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles(): void {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../system/tests/fixtures/update/drupal-9.4.0.filled.standard.php.gz',
      __DIR__ . '/../../fixtures/update/responsive_image.php',
      __DIR__ . '/../../fixtures/update/responsive_image-loading-attribute.php',
    ];
  }

  /**
   * Test new lazy-load setting upgrade path.
   *
   * @see responsive_image_post_update_image_loading_attribute
   */
  public function testUpdate(): void {
    $data = EntityViewDisplay::load('node.article.default')->toArray();
    $this->assertArrayNotHasKey('image_loading', $data['content']['field_image']['settings']);

    $this->runUpdates();

    $data = EntityViewDisplay::load('node.article.default')->toArray();
    $this->assertArrayHasKey('image_loading', $data['content']['field_image']['settings']);
    $this->assertEquals('eager', $data['content']['field_image']['settings']['image_loading']['attribute']);
  }

  /**
   * Test responsive_image_entity_view_display_presave invokes deprecations.
   *
   * @covers ::processResponsiveImageField
   */
  public function testEntitySave(): void {
    $this->expectDeprecation('The responsive image loading attribute update for "node.article.default" is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. Configuration should be updated. See https://www.drupal.org/node/3279032');
    $view_display = EntityViewDisplay::load('node.article.default');
    $this->assertArrayNotHasKey('image_loading', $view_display->toArray()['content']['field_image']['settings']);

    $view_display->save();

    $view_display = EntityViewDisplay::load('node.article.default');
    $this->assertArrayHasKey('image_loading', $view_display->toArray()['content']['field_image']['settings']);
    $this->assertEquals('eager', $view_display->toArray()['content']['field_image']['settings']['image_loading']['attribute']);
  }

}
