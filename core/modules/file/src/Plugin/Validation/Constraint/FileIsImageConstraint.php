<?php

declare(strict_types=1);

namespace Drupal\file\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * File is image constraint.
 *
 * @Constraint(
 *   id = "FileIsImage",
 *   label = @Translation("File Is Image", context = "Validation"),
 *   type = "file"
 * )
 */
class FileIsImageConstraint extends Constraint {

  /**
   * The error message.
   *
   * @var string
   */
  public string $message = 'The image file is invalid or the image type is not allowed. Allowed types: %types';

}
