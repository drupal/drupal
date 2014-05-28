<?php

/**
 * @file
 * Definition of Drupal\user\Tests\UserPictureTest.
 */

namespace Drupal\user\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests user picture functionality.
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

  protected $user;
  protected $_directory_test;

  public static function getInfo() {
    return array(
      'name' => 'User pictures',
      'description' => 'Tests user picture functionality.',
      'group' => 'User',
    );
  }

  function setUp() {
    parent::setUp();

    $this->web_user = $this->drupalCreateUser(array(
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
    $this->drupalLogin($this->web_user);

    // Save a new picture.
    $image = current($this->drupalGetTestFiles('image'));
    $file = $this->saveUserPicture($image);

    // Verify that the image is displayed on the user account page.
    $this->drupalGet('user');
    $this->assertRaw(file_uri_target($file->getFileUri()), 'User picture found on user account page.');

    // Delete the picture.
    $edit = array();
    $this->drupalPostForm('user/' . $this->web_user->id() . '/edit', $edit, t('Remove'));
    $this->drupalPostForm(NULL, array(), t('Save'));

    // Call file_cron() to clean up the file. Make sure the timestamp
    // of the file is older than the system.file.temporary_maximum_age
    // configuration value.
    db_update('file_managed')
      ->fields(array(
        'changed' => REQUEST_TIME - ($this->container->get('config.factory')->get('system.file')->get('temporary_maximum_age') + 1),
      ))
      ->condition('fid', $file->id())
      ->execute();
    \Drupal::service('cron')->run();

    // Verify that the image has been deleted.
    $this->assertFalse(file_load($file->id(), TRUE), 'File was removed from the database.');
    // Clear out PHP's file stat cache so we see the current value.
    clearstatcache(TRUE, $file->getFileUri());
    $this->assertFalse(is_file($file->getFileUri()), 'File was removed from the file system.');
  }

  /**
   * Tests embedded users on node pages.
   */
  function testPictureOnNodeComment() {
    $this->drupalLogin($this->web_user);

    // Save a new picture.
    $image = current($this->drupalGetTestFiles('image'));
    $file = $this->saveUserPicture($image);

    $node = $this->drupalCreateNode(array('type' => 'article'));

    // Enable user pictures on nodes.
    $this->container->get('config.factory')->get('system.theme.global')->set('features.node_user_picture', TRUE)->save();

    // Verify that the image is displayed on the user account page.
    $this->drupalGet('node/' . $node->id());
    $this->assertRaw(file_uri_target($file->getFileUri()), 'User picture found on node page.');

    // Enable user pictures on comments, instead of nodes.
    $this->container->get('config.factory')->get('system.theme.global')
      ->set('features.node_user_picture', FALSE)
      ->set('features.comment_user_picture', TRUE)
      ->save();

    $edit = array(
      'comment_body[0][value]' => $this->randomString(),
    );
    $this->drupalPostForm('comment/reply/node/' . $node->id() . '/comment', $edit, t('Save'));
    $this->assertRaw(file_uri_target($file->getFileUri()), 'User picture found on comment.');

    // Disable user pictures on comments and nodes.
    $this->container->get('config.factory')->get('system.theme.global')
      ->set('features.node_user_picture', FALSE)
      ->set('features.comment_user_picture', FALSE)
      ->save();
    \Drupal::entityManager()->getViewBuilder('comment')->resetCache();

    $this->drupalGet('node/' . $node->id());
    $this->assertNoRaw(file_uri_target($file->getFileUri()), 'User picture not found on node and comment.');
  }

  /**
   * Edits the user picture for the test user.
   */
  function saveUserPicture($image) {
    $edit = array('files[user_picture_0]' => drupal_realpath($image->uri));
    $this->drupalPostForm('user/' . $this->web_user->id() . '/edit', $edit, t('Save'));

    // Load actual user data from database.
    $account = user_load($this->web_user->id(), TRUE);
    return file_load($account->user_picture->target_id, TRUE);
  }
}
