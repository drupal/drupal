<?php

namespace Drupal\Tests\media_library\Functional;

use Drupal\image\Entity\ImageStyle;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests access to the Media library image style.
 *
 * @group media_library
 */
class MediaLibraryImageStyleAccessTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['media_library'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that users can't delete the 'media_library' image style.
   */
  public function testMediaLibraryImageStyleAccess(): void {
    // Create a user who can manage the image styles.
    $user = $this->createUser([
      'access administration pages',
      'administer image styles',
    ]);

    // The user should be able to delete the 'medium' image style, but not the
    // 'media_library' image style.
    $medium = ImageStyle::load('medium');
    $this->assertTrue($medium->access('delete', $user));
    $mediaLibrary = ImageStyle::load('media_library');
    $this->assertFalse($mediaLibrary->access('delete', $user));

    $this->drupalLogin($user);
    $this->drupalGet($medium->toUrl('delete-form'));
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet($mediaLibrary->toUrl('delete-form'));
    $this->assertSession()->statusCodeEquals(403);
  }

}
