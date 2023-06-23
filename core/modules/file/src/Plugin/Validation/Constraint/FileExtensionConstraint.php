<?php

declare(strict_types=1);

namespace Drupal\file\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * File extension constraint.
 *
 * @Constraint(
 *   id = "FileExtension",
 *   label = @Translation("File Extension", context = "Validation"),
 *   type = "file"
 * )
 */
class FileExtensionConstraint extends Constraint {

  /**
   * The error message.
   *
   * @var string
   */
  public string $message = 'Only files with the following extensions are allowed: %files-allowed.';

  /**
   * The allowed file extensions.
   *
   * @var string
   */
  public string $extensions;

  /**
   * {@inheritdoc}
   */
  public function getDefaultOption(): string {
    return 'extensions';
  }

}
