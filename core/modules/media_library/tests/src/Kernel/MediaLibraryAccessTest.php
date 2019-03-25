<?php

namespace Drupal\Tests\media_library\Kernel;

use Drupal\image\Entity\ImageStyle;
use Drupal\KernelTests\KernelTestBase;
use Drupal\media_library\MediaLibraryState;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\views\Views;

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

  /**
   * Tests the Media Library access.
   */
  public function testMediaLibraryAccess() {
    /** @var \Drupal\media_library\MediaLibraryUiBuilder $ui_builder */
    $ui_builder = $this->container->get('media_library.ui_builder');

    // Create a media library state to test access.
    $state = MediaLibraryState::create('test', ['file', 'image'], 'file', 2);

    // Create a clone of the view so we can reset the original later.
    $view_original = clone Views::getView('media_library');

    // Create our test users.
    $forbidden_account = $this->createUser([]);
    $allowed_account = $this->createUser(['view media']);

    // Assert the 'view media' permission is needed to access the library and
    // validate the cache dependencies.
    $access_result = $ui_builder->checkAccess($forbidden_account, $state);
    $this->assertFalse($access_result->isAllowed());
    $this->assertSame("The 'view media' permission is required.", $access_result->getReason());
    $this->assertSame($view_original->storage->getCacheTags(), $access_result->getCacheTags());
    $this->assertSame(['user.permissions'], $access_result->getCacheContexts());

    // Assert that the media library access is denied when the view widget
    // display is deleted.
    $view_storage = Views::getView('media_library')->storage;
    $displays = $view_storage->get('display');
    unset($displays['widget']);
    $view_storage->set('display', $displays);
    $view_storage->save();
    $access_result = $ui_builder->checkAccess($allowed_account, $state);
    $this->assertFalse($access_result->isAllowed());
    $this->assertSame('The media library widget display does not exist.', $access_result->getReason());
    $this->assertSame($view_original->storage->getCacheTags(), $access_result->getCacheTags());
    $this->assertSame([], $access_result->getCacheContexts());

    // Restore the original view and assert that the media library controller
    // works again.
    $view_original->storage->save();
    $access_result = $ui_builder->checkAccess($allowed_account, $state);
    $this->assertTrue($access_result->isAllowed());
    $this->assertSame($view_original->storage->getCacheTags(), $access_result->getCacheTags());
    $this->assertSame(['user.permissions'], $access_result->getCacheContexts());

    // Assert that the media library access is denied when the entire media
    // library view is deleted.
    Views::getView('media_library')->storage->delete();
    $access_result = $ui_builder->checkAccess($allowed_account, $state);
    $this->assertFalse($access_result->isAllowed());
    $this->assertSame('The media library view does not exist.', $access_result->getReason());
    $this->assertSame([], $access_result->getCacheTags());
    $this->assertSame([], $access_result->getCacheContexts());
  }

}
