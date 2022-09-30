<?php

namespace Drupal\Tests\media\Functional\FieldFormatter;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests eager-load upgrade path.
 *
 * @group media
 * @group legacy
 */
class OembedUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Because the test manually installs media module, the entity type config
    // must be manually installed similar to kernel tests.
    $entity_type_manager = \Drupal::entityTypeManager();
    $media = $entity_type_manager->getDefinition('media');
    \Drupal::service('entity_type.listener')->onEntityTypeCreate($media);
    $media_type = $entity_type_manager->getDefinition('media_type');
    \Drupal::service('entity_type.listener')->onEntityTypeCreate($media_type);
  }

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles(): void {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-9.4.0.filled.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/media.php',
      __DIR__ . '/../../../fixtures/update/media-oembed-iframe.php',
    ];
  }

  /**
   * Test eager-load setting upgrade path.
   *
   * @see media_post_update_oembed_loading_attribute
   *
   * @legacy
   */
  public function testUpdate(): void {
    $this->expectDeprecation('The oEmbed loading attribute update for view display "media.remote_video.default" is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. Profile, module and theme provided configuration should be updated to accommodate the changes described at https://www.drupal.org/node/3275103.');
    $data = EntityViewDisplay::load('media.remote_video.default')->toArray();
    $this->assertArrayNotHasKey('loading', $data['content']['field_media_oembed_video']['settings']);

    $this->runUpdates();

    $data = EntityViewDisplay::load('media.remote_video.default')->toArray();
    $this->assertArrayHasKey('loading', $data['content']['field_media_oembed_video']['settings']);
    $this->assertEquals('eager', $data['content']['field_media_oembed_video']['settings']['loading']['attribute']);
  }

}
