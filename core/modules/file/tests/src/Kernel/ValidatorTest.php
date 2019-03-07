<?php

namespace Drupal\Tests\file\Kernel;

use Drupal\file\Entity\File;

/**
 * Tests the functions used to validate uploaded files.
 *
 * @group file
 */
class ValidatorTest extends FileManagedUnitTestBase {

  /**
   * An image file.
   *
   * @var \Drupal\file\FileInterface
   */
  protected $image;

  /**
   * A file which is not an image.
   *
   * @var \Drupal\file\Entity\File
   */
  protected $nonImage;

  protected function setUp() {
    parent::setUp();

    $this->image = File::create();
    $this->image->setFileUri('core/misc/druplicon.png');
    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');
    $this->image->setFilename($file_system->basename($this->image->getFileUri()));

    $this->nonImage = File::create();
    $this->nonImage->setFileUri('core/assets/vendor/jquery/jquery.min.js');
    $this->nonImage->setFilename($file_system->basename($this->nonImage->getFileUri()));
  }

  /**
   * Test the file_validate_extensions() function.
   */
  public function testFileValidateExtensions() {
    $file = File::create(['filename' => 'asdf.txt']);
    $errors = file_validate_extensions($file, 'asdf txt pork');
    $this->assertEqual(count($errors), 0, 'Valid extension accepted.', 'File');

    $file->setFilename('asdf.txt');
    $errors = file_validate_extensions($file, 'exe png');
    $this->assertEqual(count($errors), 1, 'Invalid extension blocked.', 'File');
  }

  /**
   * This ensures a specific file is actually an image.
   */
  public function testFileValidateIsImage() {
    $this->assertTrue(file_exists($this->image->getFileUri()), 'The image being tested exists.', 'File');
    $errors = file_validate_is_image($this->image);
    $this->assertEqual(count($errors), 0, 'No error reported for our image file.', 'File');

    $this->assertTrue(file_exists($this->nonImage->getFileUri()), 'The non-image being tested exists.', 'File');
    $errors = file_validate_is_image($this->nonImage);
    $this->assertEqual(count($errors), 1, 'An error reported for our non-image file.', 'File');
  }

  /**
   * This ensures the resolution of a specific file is within bounds.
   *
   * The image will be resized if it's too large.
   */
  public function testFileValidateImageResolution() {
    // Non-images.
    $errors = file_validate_image_resolution($this->nonImage);
    $this->assertEqual(count($errors), 0, 'Should not get any errors for a non-image file.', 'File');
    $errors = file_validate_image_resolution($this->nonImage, '50x50', '100x100');
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
    if ($this->container->get('image.factory')->getToolkitId()) {
      // Copy the image so that the original doesn't get resized.
      copy('core/misc/druplicon.png', 'temporary://druplicon.png');
      $this->image->setFileUri('temporary://druplicon.png');

      $errors = file_validate_image_resolution($this->image, '10x5');
      $this->assertEqual(count($errors), 0, 'No errors should be reported when an oversized image can be scaled down.', 'File');

      $image = $this->container->get('image.factory')->get($this->image->getFileUri());
      $this->assertTrue($image->getWidth() <= 10, 'Image scaled to correct width.', 'File');
      $this->assertTrue($image->getHeight() <= 5, 'Image scaled to correct height.', 'File');

      // Once again, now with negative width and height to force an error.
      copy('core/misc/druplicon.png', 'temporary://druplicon.png');
      $this->image->setFileUri('temporary://druplicon.png');
      $errors = file_validate_image_resolution($this->image, '-10x-5');
      $this->assertEqual(count($errors), 1, 'An error reported for an oversized image that can not be scaled down.', 'File');

      drupal_unlink('temporary://druplicon.png');
    }
    else {
      // TODO: should check that the error is returned if no toolkit is available.
      $errors = file_validate_image_resolution($this->image, '5x10');
      $this->assertEqual(count($errors), 1, 'Oversize images that cannot be scaled get an error.', 'File');
    }
  }

  /**
   * This will ensure the filename length is valid.
   */
  public function testFileValidateNameLength() {
    // Create a new file entity.
    $file = File::create();

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
  public function testFileValidateSize() {
    // Create a file with a size of 1000 bytes, and quotas of only 1 byte.
    $file = File::create(['filesize' => 1000]);
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
