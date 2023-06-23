<?php

declare(strict_types=1);

namespace Drupal\file\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * File extension dimensions constraint.
 *
 * @Constraint(
 *   id = "FileImageDimensions",
 *   label = @Translation("File Image Dimensions", context = "Validation"),
 *   type = "file"
 * )
 */
class FileImageDimensionsConstraint extends Constraint {

  /**
   * The minimum dimensions.
   *
   * @var string|int
   */
  public string | int $minDimensions = 0;

  /**
   * The maximum dimensions.
   *
   * @var string|int
   */
  public string | int $maxDimensions = 0;

  /**
   * The resized image too small message.
   *
   * @var string
   */
  public string $messageResizedImageTooSmall = 'The resized image is too small. The minimum dimensions are %dimensions pixels and after resizing, the image size will be %widthx%height pixels.';

  /**
   * The image too small message.
   *
   * @var string
   */
  public string $messageImageTooSmall = 'The image is too small. The minimum dimensions are %dimensions pixels and the image size is %widthx%height pixels.';

  /**
   * The resize failed message.
   *
   * @var string
   */
  public string $messageResizeFailed = 'The image exceeds the maximum allowed dimensions and an attempt to resize it failed.';

}
