<?php

declare(strict_types=1);

namespace Drupal\file\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * File extension secure constraint.
 *
 * @Constraint(
 *   id = "FileExtensionSecure",
 *   label = @Translation("File Extension Secure", context = "Validation"),
 *   type = "file"
 * )
 */
class FileExtensionSecureConstraint extends Constraint {

  /**
   * The error message.
   *
   * @var string
   */
  public string $message = 'For security reasons, your upload has been rejected.';

}
