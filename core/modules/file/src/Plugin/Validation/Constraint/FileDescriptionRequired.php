<?php

namespace Drupal\file\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Constrains the existence of a file description if it has been configured.
 *
 * @Constraint(
 *   id = "FileDescriptionRequired",
 *   label = @Translation("File required description", context = "Validation"),
 * )
 */
class FileDescriptionRequired extends Constraint {

  /**
   * Constraint violation message template.
   *
   * @var string
   */
  public $message = 'The @name field description is required.';

}
