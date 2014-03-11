<?php

/**
 * @file
 * Definition of Drupal\file\Tests\ValidatorTest.
 */

namespace Drupal\file\Tests;

/**
 *  This will run tests against the file validation functions (file_validate_*).
 */
class ValidatorTest extends FileManagedUnitTestBase {
  public static function getInfo() {
    return array(
      'name' => 'File validator tests',
      'description' => 'Tests the functions used to validate uploaded files.',
      'group' => 'File Managed API',
    );
  }

  function setUp() {
    parent::setUp();

    $this->image = entity_create('file');
    $this->image->setFileUri('core/misc/druplicon.png');
    $this->image->setFilename(drupal_basename($this->image->getFileUri()));

    $this->non_image = entity_create('file');
    $this->non_image->setFileUri('core/assets/vendor/jquery/jquery.js');
    $this->non_image->setFilename(drupal_basename($this->non_image->getFileUri()));
  }

  /**
   * Test the file_validate_extensions() function.
   */
  function testFileValidateExtensions() {
    $file = entity_create('file', array('filename' => 'asdf.txt'));
    $errors = file_validate_extensions($file, 'asdf txt pork');
    $this->assertEqual(count($errors), 0, 'Valid extension accepted.', 'File');

    $file->setFilename('asdf.txt');
    $errors = file_validate_extensions($file, 'exe png');
    $this->assertEqual(count($errors), 1, 'Invalid extension blocked.', 'File');
  }

  /**
   *  This ensures a specific file is actually an image.
   */
  function testFileValidateIsImage() {
    $this->assertTrue(file_exists($this->image->getFileUri()), 'The image being tested exists.', 'File');
    $errors = file_validate_is_image($this->image);
    $this->assertEqual(count($errors), 0, 'No error reported for our image file.', 'File');

    $this->assertTrue(file_exists($this->non_image->getFileUri()), 'The non-image being tested exists.', 'File');
    $errors = file_validate_is_image($this->non_image);
    $this->assertEqual(count($errors), 1, 'An error reported for our non-image file.', 'File');
  }

  /**
   *  This ensures the resolution of a specific file is within bounds.
   *  The image will be resized if it's too large.
   */
  function testFileValidateImageResolution() {
    // Non-images.
    $errors = file_validate_image_resolution($this->non_image);
    $this->assertEqual(count($errors), 0, 'Should not get any errors for a non-image file.', 'File');
    $errors = file_validate_image_resolution($this->non_image, '50x50', '100x100');
    $this->assertEqual(count($errors), 0, 'Do not check the resolution on non files.', 'File');

    // Minimum size.
    $errors = file_validate_image_resolution($this->image);
    $this->assertEqual(count($errors), 0, 'No errors for an image when there is no minimum or maximum resolution.', 'File');
    $errors = file_validate_image_resolution($this->image, 0, '200x1');
    $this->assertEqual(count($errors), 1, 'Got an error for an image that was not wide enough.', 'File');
    $errors = file_validate_image_resolution($this->image, 0, '1x200');
    $this->assertEqual(count($errors), 1, 'Got an error for an image that was not tall enough.', 'File');
    $errors = file_validate_image_resolution($this->image, 0, '200x200');
    $this->assertEqual(count($errors), 1, 'Small images report an error.', 'File');

    // Maximum size.
    if ($this->container->has('image.toolkit.manager')) {
      // Copy the image so that the original doesn't get resized.
      copy('core/misc/druplicon.png', 'temporary://druplicon.png');
      $this->image->setFileUri('temporary://druplicon.png');

      $errors = file_validate_image_resolution($this->image, '10x5');
      $this->assertEqual(count($errors), 0, 'No errors should be reported when an oversized image can be scaled down.', 'File');

      $image = $this->container->get('image.factory')->get($this->image->getFileUri());
      $this->assertTrue($image->getWidth() <= 10, 'Image scaled to correct width.', 'File');
      $this->assertTrue($image->getHeight() <= 5, 'Image scaled to correct height.', 'File');

      drupal_unlink('temporary://druplicon.png');
    }
    else {
      // TODO: should check that the error is returned if no toolkit is available.
      $errors = file_validate_image_resolution($this->image, '5x10');
      $this->assertEqual(count($errors), 1, 'Oversize images that cannot be scaled get an error.', 'File');
    }
  }

  /**
   *  This will ensure the filename length is valid.
   */
  function testFileValidateNameLength() {
    // Create a new file entity.
    $file = entity_create('file');

    // Add a filename with an allowed length and test it.
    $file->setFilename(str_repeat('x', 240));
    $this->assertEqual(strlen($file->getFilename()), 240);
    $errors = file_validate_name_length($file);
    $this->assertEqual(count($errors), 0, 'No errors reported for 240 length filename.', 'File');

    // Add a filename with a length too long and test it.
    $file->setFilename(str_repeat('x', 241));
    $errors = file_validate_name_length($file);
    $this->assertEqual(count($errors), 1, 'An error reported for 241 length filename.', 'File');

    // Add a filename with an empty string and test it.
    $file->setFilename('');
    $errors = file_validate_name_length($file);
    $this->assertEqual(count($errors), 1, 'An error reported for 0 length filename.', 'File');
  }


  /**
   * Test file_validate_size().
   */
  function testFileValidateSize() {
    // Run these tests as a regular user.
    $user = entity_create('user', array('uid' => 2, 'name' => $this->randomName()));
    $user->enforceIsNew();
    $user->save();
    $this->container->set('current_user', $user);

    // Create a file with a size of 1000 bytes, and quotas of only 1 byte.
    $file = entity_create('file', array('filesize' => 1000));
    $errors = file_validate_size($file, 0, 0);
    $this->assertEqual(count($errors), 0, 'No limits means no errors.', 'File');
    $errors = file_validate_size($file, 1, 0);
    $this->assertEqual(count($errors), 1, 'Error for the file being over the limit.', 'File');
    $errors = file_validate_size($file, 0, 1);
    $this->assertEqual(count($errors), 1, 'Error for the user being over their limit.', 'File');
    $errors = file_validate_size($file, 1, 1);
    $this->assertEqual(count($errors), 2, 'Errors for both the file and their limit.', 'File');
  }
}
