<?php

namespace Drupal\Tests\media\Kernel;

use Drupal\media\Entity\Media;
use Drupal\media\Entity\MediaType;
use Drupal\media\MediaInterface;
use Drupal\media\MediaTypeInterface;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Tests creation of media types and media items.
 *
 * @group media
 */
class MediaCreationTest extends MediaKernelTestBase {

  /**
   * Tests creating a media type programmatically.
   */
  public function testMediaTypeCreation() {
    $media_type_storage = $this->container->get('entity_type.manager')->getStorage('media_type');

    $this->assertInstanceOf(MediaTypeInterface::class, MediaType::load($this->testMediaType->id()));

    // Test a media type created from default configuration.
    $this->container->get('module_installer')->install(['media_test_type']);
    $test_media_type = $media_type_storage->load('test');
    $this->assertInstanceOf(MediaTypeInterface::class, $test_media_type);
    $this->assertSame('Test type', $test_media_type->get('label'), 'Could not assure the correct type name.');
    $this->assertSame('Test type.', $test_media_type->get('description'), 'Could not assure the correct type description.');
    $this->assertSame('test', $test_media_type->get('source'), 'Could not assure the correct media source.');
    // Source field is not set on the media source, but it should never
    // be created automatically when a config is being imported.
    $this->assertSame(['source_field' => '', 'test_config_value' => 'Foo'], $test_media_type->get('source_configuration'), 'Could not assure the correct media source configuration.');
    $this->assertSame(['metadata_attribute' => 'field_attribute_config_test'], $test_media_type->get('field_map'), 'Could not assure the correct field map.');
    // Check the Media Type access handler behavior.
    // We grant access to the 'view label' operation to all users having
    // permission to 'view media'.
    $user1 = User::create([
      'name' => 'username1',
      'status' => 1,
    ]);
    $user1->save();
    $user2 = User::create([
      'name' => 'username2',
      'status' => 1,
    ]);
    $user2->save();
    $role = Role::create([
      'id' => 'role1',
      'label' => 'role1',
    ]);
    $role->grantPermission('view media')->trustData()->save();
    $user2->addRole($role->id());
    $this->assertFalse($test_media_type->access('view label', $user1));
    $this->assertTrue($test_media_type->access('view label', $user2));
  }

  /**
   * Tests creating a media item programmatically.
   */
  public function testMediaEntityCreation() {
    $media = Media::create([
      'bundle' => $this->testMediaType->id(),
      'name' => 'Unnamed',
      'field_media_test' => 'Nation of sheep, ruled by wolves, owned by pigs.',
    ]);
    $media->save();

    $this->assertNotInstanceOf(MediaInterface::class, Media::load(rand(1000, 9999)));

    $this->assertInstanceOf(MediaInterface::class, Media::load($media->id()));
    $this->assertSame($this->testMediaType->id(), $media->bundle(), 'The media item was not created with the correct type.');
    $this->assertSame('Unnamed', $media->getName(), 'The media item was not created with the correct name.');
    $source_field_name = $media->bundle->entity->getSource()->getSourceFieldDefinition($media->bundle->entity)->getName();
    $this->assertSame('Nation of sheep, ruled by wolves, owned by pigs.', $media->get($source_field_name)->value, 'Source returns incorrect source field value.');
  }

}
