<?php

/**
 * @file
 * Contains \Drupal\user\Tests\UserPictureTest.
 */

namespace Drupal\user\Tests;

use Drupal\image\Entity\ImageStyle;
use Drupal\simpletest\WebTestBase;
use Drupal\file\Entity\File;

/**
 * Tests user picture functionality.
 *
 * @group user
 */
class UserPictureTest extends WebTestBase {

  /**
   * The profile to install as a basis for testing.
   *
   * Using the standard profile to test user picture config provided by the
   * standard profile.
   *
   * @var string
   */
  protected $profile = 'standard';

  /**
   * A regular user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  protected function setUp() {
    parent::setUp();

    $this->webUser = $this->drupalCreateUser(array(
      'access content',
      'access comments',
      'post comments',
      'skip comment approval',
    ));
  }

  /**
   * Tests creation, display, and deletion of user pictures.
   */
  function testCreateDeletePicture() {
    $this->drupalLogin($this->webUser);

    // Save a new picture.
    $image = current($this->drupalGetTestFiles('image'));
    $file = $this->saveUserPicture($image);

    // Verify that the image is displayed on the user account page.
    $this->drupalGet('user');
    $this->assertRaw(file_uri_target($file->getFileUri()), 'User picture found on user account page.');

    // Delete the picture.
    $edit = array();
    $this->drupalPostForm('user/' . $this->webUser->id() . '/edit', $edit, t('Remove'));
    $this->drupalPostForm(NULL, array(), t('Save'));

    // Call file_cron() to clean up the file. Make sure the timestamp
    // of the file is older than the system.file.temporary_maximum_age
    // configuration value.
    db_update('file_managed')
      ->fields(array(
        'changed' => REQUEST_TIME - ($this->config('system.file')->get('temporary_maximum_age') + 1),
      ))
      ->condition('fid', $file->id())
      ->execute();
    \Drupal::service('cron')->run();

    // Verify that the image has been deleted.
    $this->assertFalse(File::load($file->id()), 'File was removed from the database.');
    // Clear out PHP's file stat cache so we see the current value.
    clearstatcache(TRUE, $file->getFileUri());
    $this->assertFalse(is_file($file->getFileUri()), 'File was removed from the file system.');
  }

  /**
   * Tests embedded users on node pages.
   */
  function testPictureOnNodeComment() {
    $this->drupalLogin($this->webUser);

    // Save a new picture.
    $image = current($this->drupalGetTestFiles('image'));
    $file = $this->saveUserPicture($image);

    $node = $this->drupalCreateNode(array('type' => 'article'));

    // Enable user pictures on nodes.
    $this->config('system.theme.global')->set('features.node_user_picture', TRUE)->save();

    $image_style_id = $this->config('core.entity_view_display.user.user.compact')->get('content.user_picture.settings.image_style');
    $style = ImageStyle::load($image_style_id);
    $image_url = file_url_transform_relative($style->buildUrl($file->getfileUri()));
    $alt_text = 'Profile picture for user ' . $this->webUser->getUsername();

    // Verify that the image is displayed on the node page.
    $this->drupalGet('node/' . $node->id());
    $elements = $this->cssSelect('.node__meta .field--name-user-picture img[alt="' . $alt_text . '"][src="' . $image_url . '"]');
    $this->assertEqual(count($elements), 1, 'User picture with alt text found on node page.');

    // Enable user pictures on comments, instead of nodes.
    $this->config('system.theme.global')
      ->set('features.node_user_picture', FALSE)
      ->set('features.comment_user_picture', TRUE)
      ->save();

    $edit = array(
      'comment_body[0][value]' => $this->randomString(),
    );
    $this->drupalPostForm('comment/reply/node/' . $node->id() . '/comment', $edit, t('Save'));
    $elements = $this->cssSelect('.comment__meta .field--name-user-picture img[alt="' . $alt_text . '"][src="' . $image_url . '"]');
    $this->assertEqual(count($elements), 1, 'User picture with alt text found on the comment.');

    // Disable user pictures on comments and nodes.
    $this->config('system.theme.global')
      ->set('features.node_user_picture', FALSE)
      ->set('features.comment_user_picture', FALSE)
      ->save();

    $this->drupalGet('node/' . $node->id());
    $this->assertNoRaw(file_uri_target($file->getFileUri()), 'User picture not found on node and comment.');
  }

  /**
   * Edits the user picture for the test user.
   */
  function saveUserPicture($image) {
    $edit = array('files[user_picture_0]' => drupal_realpath($image->uri));
    $this->drupalPostForm('user/' . $this->webUser->id() . '/edit', $edit, t('Save'));

    // Load actual user data from database.
    $user_storage = $this->container->get('entity.manager')->getStorage('user');
    $user_storage->resetCache(array($this->webUser->id()));
    $account = $user_storage->load($this->webUser->id());
    return File::load($account->user_picture->target_id);
  }
}
