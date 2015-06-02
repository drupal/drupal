<?php

/**
 * @file
 * Contains \Drupal\file\Plugin\Validation\Constraint\FileUriUnique.
 */

namespace Drupal\file\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Supports validating file URIs.
 *
 * @Plugin(
 *   id = "FileUriUnique",
 *   label = @Translation("File URI", context = "Validation")
 * )
 */
class FileUriUnique extends Constraint {

  public $message = 'The file %value already exists. Enter a unique file URI.';

  /**
   * {@inheritdoc}
   */
  public function validatedBy() {
    return '\Drupal\Core\Validation\Plugin\Validation\Constraint\UniqueFieldValueValidator';
  }

}
