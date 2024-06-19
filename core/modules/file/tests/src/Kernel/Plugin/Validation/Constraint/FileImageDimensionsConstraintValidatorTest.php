<?php

declare(strict_types=1);

namespace Drupal\Tests\file\Kernel\Plugin\Validation\Constraint;

use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\Tests\file\Kernel\Validation\FileValidatorTestBase;

/**
 * Tests the FileImageDimensionsConstraintValidator.
 *
 * @group file
 * @coversDefaultClass \Drupal\file\Plugin\Validation\Constraint\FileImageDimensionsConstraintValidator
 */
class FileImageDimensionsConstraintValidatorTest extends FileValidatorTestBase {

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
    $this->nonImage->setFileUri('core/assets/scaffold/README.txt');
    $this->nonImage->setFilename($file_system->basename($this->nonImage->getFileUri()));
  }

  /**
   * This ensures the dimensions of a specific file is within bounds.
   *
   * The image will be resized if it's too large.
   *
   * @covers ::validate
   */
  public function testFileValidateImageResolution(): void {
    // Non-images.
    $validators = ['FileImageDimensions' => []];
    $violations = $this->validator->validate($this->nonImage, $validators);
    $this->assertCount(0, $violations, 'Should not get any errors for a non-image file.');
    $validators = [
      'FileImageDimensions' => [
        'maxDimensions' => '50x50',
        'minDimensions' => '100x100',
      ],
    ];
    $violations = $this->validator->validate($this->nonImage, $validators);
    $this->assertCount(0, $violations, 'Do not check the dimensions on non files.');

    // Minimum size.
    $validators = ['FileImageDimensions' => []];
    $violations = $this->validator->validate($this->image, $validators);
    $this->assertCount(0, $violations, 'No errors for an image when there is no minimum or maximum resolution.');
    $validators = [
      'FileImageDimensions' => [
        'maxDimensions' => 0,
        'minDimensions' => '200x1',
      ],
    ];
    $violations = $this->validator->validate($this->image, $validators);
    $this->assertCount(1, $violations);
    $this->assertEquals('The image is too small. The minimum dimensions are 200x1 pixels and the image size is 88x100 pixels.', $violations->get(0)->getMessage());

    $validators = [
      'FileImageDimensions' => [
        'maxDimensions' => 0,
        'minDimensions' => '1x200',
      ],
    ];
    $violations = $this->validator->validate($this->image, $validators);
    $this->assertCount(1, $violations);
    $this->assertEquals('The image is too small. The minimum dimensions are 1x200 pixels and the image size is 88x100 pixels.', $violations->get(0)->getMessage());

    $validators = [
      'FileImageDimensions' => [
        'maxDimensions' => 0,
        'minDimensions' => '200x200',
      ],
    ];
    $violations = $this->validator->validate($this->image, $validators);
    $this->assertCount(1, $violations);
    $this->assertEquals('The image is too small. The minimum dimensions are 200x200 pixels and the image size is 88x100 pixels.', $violations->get(0)->getMessage());

    // Maximum size.
    if ($this->container->get('image.factory')->getToolkitId()) {
      // Copy the image so that the original doesn't get resized.
      copy('core/misc/druplicon.png', 'temporary://druplicon.png');
      $this->image->setFileUri('temporary://druplicon.png');

      $validators = [
        'FileImageDimensions' => [
          'maxDimensions' => '10x5',
        ],
      ];
      $violations = $this->validator->validate($this->image, $validators);
      $this->assertCount(0, $violations, 'No errors should be reported when an oversized image can be scaled down.');

      $image = $this->container->get('image.factory')
        ->get($this->image->getFileUri());
      // Verify that the image was scaled to the correct width and height.
      $this->assertLessThanOrEqual(10, $image->getWidth());
      $this->assertLessThanOrEqual(5, $image->getHeight());

      // Once again, now with negative width and height to force an error.
      copy('core/misc/druplicon.png', 'temporary://druplicon.png');
      $this->image->setFileUri('temporary://druplicon.png');
      $validators = [
        'FileImageDimensions' => [
          'maxDimensions' => '-10x-5',
        ],
      ];
      $violations = $this->validator->validate($this->image, $validators);
      $this->assertCount(1, $violations);
      $this->assertEquals('The image exceeds the maximum allowed dimensions and an attempt to resize it failed.', $violations->get(0)->getMessage());

      \Drupal::service('file_system')->unlink('temporary://druplicon.png');
    }
    else {
      // @todo Should check that the error is returned if no toolkit is available.
      $validators = [
        'FileImageDimensions' => [
          'maxDimensions' => '5x10',
        ],
      ];
      $violations = $this->validator->validate($this->image, $validators);
      $this->assertCount(1, $violations, 'Oversize images that cannot be scaled get an error.');
      $this->assertEquals('The image exceeds the maximum allowed dimensions and an attempt to resize it failed.', $violations->get(0)->getMessage());
    }
  }

}
