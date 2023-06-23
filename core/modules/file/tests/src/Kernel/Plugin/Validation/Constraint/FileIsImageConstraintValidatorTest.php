<?php

declare(strict_types=1);

namespace Drupal\Tests\file\Kernel\Plugin\Validation\Constraint;

use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\Tests\file\Kernel\Validation\FileValidatorTestBase;

/**
 * Tests the FileIsImageConstraintValidator.
 *
 * @group file
 * @coversDefaultClass \Drupal\file\Plugin\Validation\Constraint\FileIsImageConstraintValidator
 */
class FileIsImageConstraintValidatorTest extends FileValidatorTestBase {

  /**
   * An image file.
   *
   * @var \Drupal\file\FileInterface
   */
  protected FileInterface $image;

  /**
   * A file which is not an image.
   *
   * @var \Drupal\file\FileInterface
   */
  protected FileInterface $nonImage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
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
   * This ensures a specific file is actually an image.
   *
   * @covers ::validate
   */
  public function testFileIsImage() {
    $this->assertFileExists($this->image->getFileUri());
    $validators = [
      'FileIsImage' => [],
    ];
    $violations = $this->validator->validate($this->image, $validators);
    $this->assertCount(0, $violations, 'No error reported for our image file.');

    $this->assertFileExists($this->nonImage->getFileUri());
    $violations = $this->validator->validate($this->nonImage, $validators);
    $this->assertCount(1, $violations, 'An error reported for our non-image file.');
  }

}
