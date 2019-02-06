<?php

namespace Drupal\Tests\media_library\Kernel;

use Drupal\image\Entity\ImageStyle;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests the media library access.
 *
 * @group media_library
 */
class MediaLibraryAccessTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'media',
    'media_library',
    'file',
    'field',
    'image',
    'system',
    'views',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('file');
    $this->installSchema('file', 'file_usage');
    $this->installSchema('system', ['sequences', 'key_value_expire']);
    $this->installEntitySchema('media');
    $this->installConfig([
      'field',
      'system',
      'file',
      'image',
      'media',
      'media_library',
    ]);

    // Create an account with special UID 1.
    $this->createUser([]);
  }

  /**
   * Tests that users can't delete the 'media_library' image style.
   */
  public function testMediaLibraryImageStyleAccess() {
    // Create a user who can manage the image styles.
    $user = $this->createUser([
      'access administration pages',
      'administer image styles',
    ]);

    // The user should be able to delete the 'medium' image style, but not the
    // 'media_library' image style.
    $this->assertTrue(ImageStyle::load('medium')->access('delete', $user));
    $this->assertFalse(ImageStyle::load('media_library')->access('delete', $user));
  }

}
