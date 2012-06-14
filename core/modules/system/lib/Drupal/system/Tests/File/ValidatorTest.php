<?php

/**
 * @file
 * Definition of Drupal\system\Tests\File\ValidatorTest.
 */

namespace Drupal\system\Tests\File;

use Drupal\simpletest\WebTestBase;

/**
 *  This will run tests against the file validation functions (file_validate_*).
 */
class ValidatorTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'File validator tests',
      'description' => 'Tests the functions used to validate uploaded files.',
      'group' => 'File API',
    );
  }

  function setUp() {
    parent::setUp();

    $this->image = entity_create('file', array());
    $this->image->uri = 'core/misc/druplicon.png';
    $this->image->filename = drupal_basename($this->image->uri);

    $this->non_image = entity_create('file', array());
    $this->non_image->uri = 'core/misc/jquery.js';
    $this->non_image->filename = drupal_basename($this->non_image->uri);
  }

  /**
   * Test the file_validate_extensions() function.
   */
  function testFileValidateExtensions() {
    $file = entity_create('file', array('filename' => 'asdf.txt'));
    $errors = file_validate_extensions($file, 'asdf txt pork');
    $this->assertEqual(count($errors), 0, t('Valid extension accepted.'), 'File');

    $file->filename = 'asdf.txt';
    $errors = file_validate_extensions($file, 'exe png');
    $this->assertEqual(count($errors), 1, t('Invalid extension blocked.'), 'File');
  }

  /**
   *  This ensures a specific file is actually an image.
   */
  function testFileValidateIsImage() {
    $this->assertTrue(file_exists($this->image->uri), t('The image being tested exists.'), 'File');
    $errors = file_validate_is_image($this->image);
    $this->assertEqual(count($errors), 0, t('No error reported for our image file.'), 'File');

    $this->assertTrue(file_exists($this->non_image->uri), t('The non-image being tested exists.'), 'File');
    $errors = file_validate_is_image($this->non_image);
    $this->assertEqual(count($errors), 1, t('An error reported for our non-image file.'), 'File');
  }

  /**
   *  This ensures the resolution of a specific file is within bounds.
   *  The image will be resized if it's too large.
   */
  function testFileValidateImageResolution() {
    // Non-images.
    $errors = file_validate_image_resolution($this->non_image);
    $this->assertEqual(count($errors), 0, t("Shouldn't get any errors for a non-image file."), 'File');
    $errors = file_validate_image_resolution($this->non_image, '50x50', '100x100');
    $this->assertEqual(count($errors), 0, t("Don't check the resolution on non files."), 'File');

    // Minimum size.
    $errors = file_validate_image_resolution($this->image);
    $this->assertEqual(count($errors), 0, t('No errors for an image when there is no minimum or maximum resolution.'), 'File');
    $errors = file_validate_image_resolution($this->image, 0, '200x1');
    $this->assertEqual(count($errors), 1, t("Got an error for an image that wasn't wide enough."), 'File');
    $errors = file_validate_image_resolution($this->image, 0, '1x200');
    $this->assertEqual(count($errors), 1, t("Got an error for an image that wasn't tall enough."), 'File');
    $errors = file_validate_image_resolution($this->image, 0, '200x200');
    $this->assertEqual(count($errors), 1, t('Small images report an error.'), 'File');

    // Maximum size.
    if (image_get_toolkit()) {
      // Copy the image so that the original doesn't get resized.
      copy('core/misc/druplicon.png', 'temporary://druplicon.png');
      $this->image->uri = 'temporary://druplicon.png';

      $errors = file_validate_image_resolution($this->image, '10x5');
      $this->assertEqual(count($errors), 0, t('No errors should be reported when an oversized image can be scaled down.'), 'File');

      $info = image_get_info($this->image->uri);
      $this->assertTrue($info['width'] <= 10, t('Image scaled to correct width.'), 'File');
      $this->assertTrue($info['height'] <= 5, t('Image scaled to correct height.'), 'File');

      drupal_unlink('temporary://druplicon.png');
    }
    else {
      // TODO: should check that the error is returned if no toolkit is available.
      $errors = file_validate_image_resolution($this->image, '5x10');
      $this->assertEqual(count($errors), 1, t("Oversize images that can't be scaled get an error."), 'File');
    }
  }

  /**
   *  This will ensure the filename length is valid.
   */
  function testFileValidateNameLength() {
    // Create a new file entity.
    $file = entity_create('file', array());

    // Add a filename with an allowed length and test it.
    $file->filename = str_repeat('x', 240);
    $this->assertEqual(strlen($file->filename), 240);
    $errors = file_validate_name_length($file);
    $this->assertEqual(count($errors), 0, t('No errors reported for 240 length filename.'), 'File');

    // Add a filename with a length too long and test it.
    $file->filename = str_repeat('x', 241);
    $errors = file_validate_name_length($file);
    $this->assertEqual(count($errors), 1, t('An error reported for 241 length filename.'), 'File');

    // Add a filename with an empty string and test it.
    $file->filename = '';
    $errors = file_validate_name_length($file);
    $this->assertEqual(count($errors), 1, t('An error reported for 0 length filename.'), 'File');
  }


  /**
   * Test file_validate_size().
   */
  function testFileValidateSize() {
    global $user;
    $original_user = $user;
    drupal_save_session(FALSE);

    // Run these test as uid = 1.
    $user = user_load(1);

    $file = entity_create('file', array('filesize' => 999999));
    $errors = file_validate_size($file, 1, 1);
    $this->assertEqual(count($errors), 0, t('No size limits enforced on uid=1.'), 'File');

    // Run these tests as a regular user.
    $user = $this->drupalCreateUser();

    // Create a file with a size of 1000 bytes, and quotas of only 1 byte.
    $file = entity_create('file', array('filesize' => 1000));
    $errors = file_validate_size($file, 0, 0);
    $this->assertEqual(count($errors), 0, t('No limits means no errors.'), 'File');
    $errors = file_validate_size($file, 1, 0);
    $this->assertEqual(count($errors), 1, t('Error for the file being over the limit.'), 'File');
    $errors = file_validate_size($file, 0, 1);
    $this->assertEqual(count($errors), 1, t('Error for the user being over their limit.'), 'File');
    $errors = file_validate_size($file, 1, 1);
    $this->assertEqual(count($errors), 2, t('Errors for both the file and their limit.'), 'File');

    $user = $original_user;
    drupal_save_session(TRUE);
  }
}
