<?php

namespace Drupal\Tests\media\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\user\Entity\Role;

/**
 * Tests that media settings are properly updated during database updates.
 *
 * @group media
 * @group legacy
 */
class MediaUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.4.0.bare.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/drupal-8.4.0-media_installed.php',
      __DIR__ . '/../../../fixtures/update/drupal-8.media-add-additional-permissions.php',
    ];
  }

  /**
   * Tests that media permissions are correctly migrated.
   *
   * @see media_update_8500()
   */
  public function testBundlePermission() {
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
   * Tests that media.settings config is updated with oEmbed configuration.
   *
   * @see media_update_8600()
   */
  public function testOEmbedConfig() {
    $config = $this->config('media.settings');
    $this->assertNull($config->get('oembed_providers_url'));
    $this->assertNull($config->get('iframe_domain'));

    $this->runUpdates();

    $config = $this->config('media.settings');
    $this->assertSame('https://oembed.com/providers.json', $config->get('oembed_providers_url'));
    $this->assertSame('', $config->get('iframe_domain'));
  }

}
