<?php

/**
 * @file
 * Definition of Drupal\user\Tests\UserPictureTest.
 */

namespace Drupal\user\Tests;

use Drupal\simpletest\WebTestBase;

class UserPictureTest extends WebTestBase {
  protected $user;
  protected $_directory_test;

  public static function getInfo() {
    return array(
      'name' => 'Upload user picture',
      'description' => 'Assure that dimension check, extension check and image scaling work as designed.',
      'group' => 'User'
    );
  }

  function setUp() {
    parent::setUp(array('image'));
    // Enable user pictures.
    variable_set('user_pictures', 1);

    // Configure default user picture settings.
    variable_set('user_picture_dimensions', '1024x1024');
    variable_set('user_picture_file_size', '800');
    variable_set('user_picture_style', 'thumbnail');

    $this->user = $this->drupalCreateUser();

    // Test if directories specified in settings exist in filesystem.
    $file_dir = 'public://';
    $file_check = file_prepare_directory($file_dir, FILE_CREATE_DIRECTORY);
    // TODO: Test public and private methods?

    $picture_dir = variable_get('user_picture_path', 'pictures');
    $picture_path = $file_dir . $picture_dir;

    $pic_check = file_prepare_directory($picture_path, FILE_CREATE_DIRECTORY);
    $this->_directory_test = is_writable($picture_path);
    $this->assertTrue($this->_directory_test, "The directory $picture_path doesn't exist or is not writable. Further tests won't be made.");
  }

  function testNoPicture() {
    $this->drupalLogin($this->user);

    // Try to upload a file that is not an image for the user picture.
    $not_an_image = current($this->drupalGetTestFiles('html'));
    $this->saveUserPicture($not_an_image);
    $this->assertRaw(t('Only JPEG, PNG and GIF images are allowed.'), t('Non-image files are not accepted.'));
  }

  /**
   * Do the test:
   *  GD Toolkit is installed
   *  Picture has invalid dimension
   *
   * results: The image should be uploaded because ImageGDToolkit resizes the picture
   */
  function testWithGDinvalidDimension() {
    if ($this->_directory_test && image_get_toolkit()) {
      $this->drupalLogin($this->user);

      $image = current($this->drupalGetTestFiles('image'));
      $info = image_get_info($image->uri);

      // Set new variables: invalid dimensions, valid filesize (0 = no limit).
      $test_dim = ($info['width'] - 10) . 'x' . ($info['height'] - 10);
      variable_set('user_picture_dimensions', $test_dim);
      variable_set('user_picture_file_size', 0);

      $pic_path = $this->saveUserPicture($image);
      // Check that the image was resized and is being displayed on the
      // user's profile page.
      $text = t('The image was resized to fit within the maximum allowed dimensions of %dimensions pixels.', array('%dimensions' => $test_dim));
      $this->assertRaw($text, t('Image was resized.'));
      $alt = t("@user's picture", array('@user' => user_format_name($this->user)));
      $style = variable_get('user_picture_style', '');
      $this->assertRaw(image_style_url($style, $pic_path), t("Image is displayed in user's edit page"));

      // Check if file is located in proper directory.
      $this->assertTrue(is_file($pic_path), t("File is located in proper directory"));
    }
  }

  /**
   * Do the test:
   *  GD Toolkit is installed
   *  Picture has invalid size
   *
   * results: The image should be uploaded because ImageGDToolkit resizes the picture
   */
  function testWithGDinvalidSize() {
    if ($this->_directory_test && image_get_toolkit()) {
      $this->drupalLogin($this->user);

      // Images are sorted first by size then by name. We need an image
      // bigger than 1 KB so we'll grab the last one.
      $files = $this->drupalGetTestFiles('image');
      $image = end($files);
      $info = image_get_info($image->uri);

      // Set new variables: valid dimensions, invalid filesize.
      $test_dim = ($info['width'] + 10) . 'x' . ($info['height'] + 10);
      $test_size = 1;
      variable_set('user_picture_dimensions', $test_dim);
      variable_set('user_picture_file_size', $test_size);

      $pic_path = $this->saveUserPicture($image);

      // Test that the upload failed and that the correct reason was cited.
      $text = t('The specified file %filename could not be uploaded.', array('%filename' => $image->filename));
      $this->assertRaw($text, t('Upload failed.'));
      $text = t('The file is %filesize exceeding the maximum file size of %maxsize.', array('%filesize' => format_size(filesize($image->uri)), '%maxsize' => format_size($test_size * 1024)));
      $this->assertRaw($text, t('File size cited as reason for failure.'));

      // Check if file is not uploaded.
      $this->assertFalse(is_file($pic_path), t('File was not uploaded.'));
    }
  }

  /**
   * Do the test:
   *  GD Toolkit is not installed
   *  Picture has invalid size
   *
   * results: The image shouldn't be uploaded
   */
  function testWithoutGDinvalidDimension() {
    if ($this->_directory_test && !image_get_toolkit()) {
      $this->drupalLogin($this->user);

      $image = current($this->drupalGetTestFiles('image'));
      $info = image_get_info($image->uri);

      // Set new variables: invalid dimensions, valid filesize (0 = no limit).
      $test_dim = ($info['width'] - 10) . 'x' . ($info['height'] - 10);
      variable_set('user_picture_dimensions', $test_dim);
      variable_set('user_picture_file_size', 0);

      $pic_path = $this->saveUserPicture($image);

      // Test that the upload failed and that the correct reason was cited.
      $text = t('The specified file %filename could not be uploaded.', array('%filename' => $image->filename));
      $this->assertRaw($text, t('Upload failed.'));
      $text = t('The image is too large; the maximum dimensions are %dimensions pixels.', array('%dimensions' => $test_dim));
      $this->assertRaw($text, t('Checking response on invalid image (dimensions).'));

      // Check if file is not uploaded.
      $this->assertFalse(is_file($pic_path), t('File was not uploaded.'));
    }
  }

  /**
   * Do the test:
   *  GD Toolkit is not installed
   *  Picture has invalid size
   *
   * results: The image shouldn't be uploaded
   */
  function testWithoutGDinvalidSize() {
    if ($this->_directory_test && !image_get_toolkit()) {
      $this->drupalLogin($this->user);

      $image = current($this->drupalGetTestFiles('image'));
      $info = image_get_info($image->uri);

      // Set new variables: valid dimensions, invalid filesize.
      $test_dim = ($info['width'] + 10) . 'x' . ($info['height'] + 10);
      $test_size = 1;
      variable_set('user_picture_dimensions', $test_dim);
      variable_set('user_picture_file_size', $test_size);

      $pic_path = $this->saveUserPicture($image);

      // Test that the upload failed and that the correct reason was cited.
      $text = t('The specified file %filename could not be uploaded.', array('%filename' => $image->filename));
      $this->assertRaw($text, t('Upload failed.'));
      $text = t('The file is %filesize exceeding the maximum file size of %maxsize.', array('%filesize' => format_size(filesize($image->uri)), '%maxsize' => format_size($test_size * 1024)));
      $this->assertRaw($text, t('File size cited as reason for failure.'));

      // Check if file is not uploaded.
      $this->assertFalse(is_file($pic_path), t('File was not uploaded.'));
    }
  }

  /**
   * Do the test:
   *  Picture is valid (proper size and dimension)
   *
   * results: The image should be uploaded
   */
  function testPictureIsValid() {
    if ($this->_directory_test) {
      $this->drupalLogin($this->user);

      $image = current($this->drupalGetTestFiles('image'));
      $info = image_get_info($image->uri);

      // Set new variables: valid dimensions, valid filesize (0 = no limit).
      $test_dim = ($info['width'] + 10) . 'x' . ($info['height'] + 10);
      variable_set('user_picture_dimensions', $test_dim);
      variable_set('user_picture_file_size', 0);

      $pic_path = $this->saveUserPicture($image);

      // Check if image is displayed in user's profile page.
      $this->drupalGet('user');
      $this->assertRaw(file_uri_target($pic_path), t("Image is displayed in user's profile page"));

      // Check if file is located in proper directory.
      $this->assertTrue(is_file($pic_path), t('File is located in proper directory'));

      // Set new picture dimensions.
      $test_dim = ($info['width'] + 5) . 'x' . ($info['height'] + 5);
      variable_set('user_picture_dimensions', $test_dim);

      $pic_path2 = $this->saveUserPicture($image);
      $this->assertNotEqual($pic_path, $pic_path2, t('Filename of second picture is different.'));
    }
  }

  /**
   * Test HTTP schema working with user pictures.
   */
  function testExternalPicture() {
    $this->drupalLogin($this->user);
    // Set the default picture to an URI with a HTTP schema.
    $images = $this->drupalGetTestFiles('image');
    $image = $images[0];
    $pic_path = file_create_url($image->uri);
    variable_set('user_picture_default', $pic_path);

    // Check if image is displayed in user's profile page.
    $this->drupalGet('user');

    // Get the user picture image via xpath.
    $elements = $this->xpath('//div[@class="user-picture"]/img');
    $this->assertEqual(count($elements), 1, t("There is exactly one user picture on the user's profile page"));
    $this->assertEqual($pic_path, (string) $elements[0]['src'], t("User picture source is correct: " . $pic_path . " " . print_r($elements, TRUE)));
  }

  /**
   * Tests deletion of user pictures.
   */
  function testDeletePicture() {
    $this->drupalLogin($this->user);

    $image = current($this->drupalGetTestFiles('image'));
    $info = image_get_info($image->uri);

    // Set new variables: valid dimensions, valid filesize (0 = no limit).
    $test_dim = ($info['width'] + 10) . 'x' . ($info['height'] + 10);
    variable_set('user_picture_dimensions', $test_dim);
    variable_set('user_picture_file_size', 0);

    // Save a new picture.
    $edit = array('files[picture_upload]' => drupal_realpath($image->uri));
    $this->drupalPost('user/' . $this->user->uid . '/edit', $edit, t('Save'));

    // Load actual user data from database.
    $account = user_load($this->user->uid, TRUE);
    $pic_path = !empty($account->picture) ? $account->picture->uri : NULL;

    // Check if image is displayed in user's profile page.
    $this->drupalGet('user');
    $this->assertRaw(file_uri_target($pic_path), "Image is displayed in user's profile page");

    // Check if file is located in proper directory.
    $this->assertTrue(is_file($pic_path), 'File is located in proper directory');

    $edit = array('picture_delete' => 1);
    $this->drupalPost('user/' . $this->user->uid . '/edit', $edit, t('Save'));

    // Load actual user data from database.
    $account1 = user_load($this->user->uid, TRUE);
    $this->assertFalse($account1->picture, 'User object has no picture');

    $file = file_load($account->picture->fid);
    $this->assertFalse($file, 'File is removed from database');

    // Clear out PHP's file stat cache so we see the current value.
    clearstatcache();
    $this->assertFalse(is_file($pic_path), 'File is removed from file system');
  }

  function saveUserPicture($image) {
    $edit = array('files[picture_upload]' => drupal_realpath($image->uri));
    $this->drupalPost('user/' . $this->user->uid . '/edit', $edit, t('Save'));

    // Load actual user data from database.
    $account = user_load($this->user->uid, TRUE);
    return !empty($account->picture) ? $account->picture->uri : NULL;
  }

  /**
   * Tests the admin form validates user picture settings.
   */
  function testUserPictureAdminFormValidation() {
    $this->drupalLogin($this->drupalCreateUser(array('administer users')));

    // The default values are valid.
    $this->drupalPost('admin/config/people/accounts', array(), t('Save configuration'));
    $this->assertText(t('The configuration options have been saved.'), 'The default values are valid.');

    // The form does not save with an invalid file size.
    $edit = array(
      'user_picture_file_size' => $this->randomName(),
    );
    $this->drupalPost('admin/config/people/accounts', $edit, t('Save configuration'));
    $this->assertNoText(t('The configuration options have been saved.'), 'The form does not save with an invalid file size.');
  }
}
