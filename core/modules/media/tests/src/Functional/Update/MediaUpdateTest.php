<?php

namespace Drupal\Tests\media\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\media\Entity\Media;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\user\Entity\Role;

/**
 * Tests that media settings are properly updated during database updates.
 *
 * @group media
 * @group legacy
 */
class MediaUpdateTest extends UpdatePathTestBase {

  use MediaTypeCreationTrait;

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

  /**
   * Tests that the media entity type has an 'owner' entity key.
   *
   * @see media_update_8700()
   */
  public function testOwnerEntityKey() {
    // Check that the 'owner' entity key does not exist prior to the update.
    $entity_type = \Drupal::entityDefinitionUpdateManager()->getEntityType('media');
    $this->assertFalse($entity_type->getKey('owner'));

    // Run updates.
    $this->runUpdates();

    // Check that the entity key exists and it has the correct value.
    $entity_type = \Drupal::entityDefinitionUpdateManager()->getEntityType('media');
    $this->assertEquals('uid', $entity_type->getKey('owner'));
  }

  /**
   * Tests that the standalone URL is still accessible.
   *
   * @see media_post_update_enable_standalone_url()
   */
  public function testEnableStandaloneUrl() {
    $this->container->get('module_installer')->install(['media_test_source']);

    // Create a media type.
    $media_type = $this->createMediaType('test');

    // Run updates.
    $this->runUpdates();

    // Create a media item.
    $media = Media::create([
      'bundle' => $media_type->id(),
      'name' => 'Unnamed',
    ]);
    $media->save();

    $user = $this->drupalCreateUser([
      'administer media',
    ]);
    $this->drupalLogin($user);

    $this->drupalGet('media/' . $media->id());
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests that the status extra filter is added to the media view.
   *
   * @see media_post_update_add_status_extra_filter()
   */
  public function testMediaViewStatusExtraFilter() {
    $config = $this->config('views.view.media');
    $this->assertNull($config->get('display.default.display_options.filters.status_extra'));

    $this->runUpdates();

    $config = $this->config('views.view.media');
    $filter = $config->get('display.default.display_options.filters.status_extra');
    $this->assertInternalType('array', $filter);
    $this->assertSame('status_extra', $filter['field']);
    $this->assertSame('media', $filter['entity_type']);
    $this->assertSame('media_status', $filter['plugin_id']);
    $this->assertSame('status_extra', $filter['id']);
    $this->assertFalse($filter['exposed']);
  }

}
