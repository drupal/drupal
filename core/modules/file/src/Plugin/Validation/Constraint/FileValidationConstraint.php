<?php

/**
 * @file
 * Contains \Drupal\file\Plugin\Validation\Constraint\FileValidationConstraint.
 */

namespace Drupal\file\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Validation File constraint.
 *
 * @Constraint(
 *   id = "FileValidation",
 *   label = @Translation("File Validation", context = "Validation")
 * )
 */
class FileValidationConstraint extends Constraint {

}
