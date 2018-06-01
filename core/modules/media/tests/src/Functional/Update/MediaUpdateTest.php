<?php

namespace Drupal\Tests\media\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\user\Entity\Role;

/**
 * Tests that media settings are properly updated during database updates.
 *
 * @group media
 */
class MediaUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.bare.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/drupal-8.media-enabled.php',
    ];
  }

  /**
   * Tests that media permissions are correctly migrated.
   *
   * @see media_update_8500()
   */
  public function testBundlePermission() {
    $role = Role::load(Role::AUTHENTICATED_ID);

    $this->grantPermissions($role, [
      'update media',
      'update any media',
      'delete media',
      'delete any media',
      'create media',
    ]);

    $this->runUpdates();

    /** @var \Drupal\user\RoleInterface $role */
    $role = Role::load(Role::AUTHENTICATED_ID);

    $media_types = \Drupal::entityQuery('media_type')->execute();
    foreach ($media_types as $media_type) {
      $this->assertTrue($role->hasPermission("create $media_type media"));
      $this->assertTrue($role->hasPermission("edit own $media_type media"));
      $this->assertTrue($role->hasPermission("edit any $media_type media"));
      $this->assertTrue($role->hasPermission("delete own $media_type media"));
      $this->assertTrue($role->hasPermission("delete any $media_type media"));
    }
  }

  /**
   * Tests that the oembed_providers key is added to the media.settings config.
   *
   * @see media_update_8600()
   */
  public function testOEmbedProvidersConfig() {
    // The drupal-8.media-enabled.php fixture installs Media and all its config,
    // which includes the oembed_providers key in media.settings. So, in order
    // to prove that the update actually works, delete the value from config
    // before running the update.
    $this->config('media.settings')->clear('oembed_providers')->save(TRUE);

    $this->runUpdates();
    $this->assertSame(
      'https://oembed.com/providers.json',
      $this->config('media.settings')->get('oembed_providers')
    );
  }

}
