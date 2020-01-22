<?php

namespace Drupal\Tests\media_library\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests update path to create the media_library.settings config object.
 *
 * @group media_library
 * @group legacy
 *
 * @covers media_library_update_8704
 */
class MediaLibraryUpdate8704Test extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  public function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.4.0.bare.standard.php.gz',
      __DIR__ . '/../../../../../media/tests/fixtures/update/drupal-8.4.0-media_installed.php',
      __DIR__ . '/../../../fixtures/update/drupal-8.7.2-media_library_installed.php',
    ];
  }

  /**
   * Tests that the update creates the media_library.settings config object.
   */
  public function testUpdate() {
    $this->assertNull($this->config('media_library.settings')->get('advanced_ui'));
    $this->runUpdates();
    $this->assertTrue($this->config('media_library.settings')->get('advanced_ui'));
  }

}
