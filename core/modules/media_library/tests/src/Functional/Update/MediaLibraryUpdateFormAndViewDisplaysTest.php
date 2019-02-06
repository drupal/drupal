<?php

namespace Drupal\Tests\media_library\Functional\Update;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\image\Entity\ImageStyle;

/**
 * Tests the media library module updates for form and view displays.
 *
 * @group media_library
 * @group legacy
 */
class MediaLibraryUpdateFormAndViewDisplaysTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  public function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.4.0.bare.standard.php.gz',
      __DIR__ . '/../../../../../media/tests/fixtures/update/drupal-8.4.0-media_installed.php',
      __DIR__ . '/../../../fixtures/update/drupal-8.media_library-update-form-view-displays-2988433.php',
    ];
  }

  /**
   * Tests the media library module updates for form and view displays.
   *
   * @see media_library_update_8701()
   * @see media_library_post_update_display_modes()
   */
  public function testPostUpdateDisplayModes() {
    $this->assertNull(ImageStyle::load('media_library'));
    $this->assertNull(EntityFormDisplay::load('media.file.media_library'));
    $this->assertNull(EntityViewDisplay::load('media.file.media_library'));
    $this->assertNull(EntityFormDisplay::load('media.image.media_library'));
    $this->assertNull(EntityViewDisplay::load('media.image.media_library'));
    $this->runUpdates();
    $this->assertInstanceOf(ImageStyle::class, ImageStyle::load('media_library'));
    $this->assertInstanceOf(EntityFormDisplay::class, EntityFormDisplay::load('media.file.media_library'));
    $this->assertInstanceOf(EntityViewDisplay::class, EntityViewDisplay::load('media.file.media_library'));
    $this->assertInstanceOf(EntityFormDisplay::class, EntityFormDisplay::load('media.image.media_library'));
    $this->assertInstanceOf(EntityViewDisplay::class, EntityViewDisplay::load('media.image.media_library'));
    $this->assertSession()->pageTextContains('Media Library form and view displays have been created for the following media types: File, Image.');
  }

}
