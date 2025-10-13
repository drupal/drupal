<?php

declare(strict_types=1);

namespace Drupal\Tests\file\Kernel\Plugin\Validation\Constraint;

use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\file\Plugin\Validation\Constraint\FileIsImageConstraintValidator;
use Drupal\Tests\file\Kernel\Validation\FileValidatorTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the FileIsImageConstraintValidator.
 */
#[CoversClass(FileIsImageConstraintValidator::class)]
#[Group('file')]
#[RunTestsInSeparateProcesses]
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
    $this->image->setFilename(basename($this->image->getFileUri()));

    $this->nonImage = File::create();
    $this->nonImage->setFileUri('core/assets/vendor/jquery/jquery.min.js');
    $this->nonImage->setFilename(basename($this->nonImage->getFileUri()));
  }

  /**
   * This ensures a specific file is actually an image.
   *
   * @legacy-covers ::validate
   */
  public function testFileIsImage(): void {
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
