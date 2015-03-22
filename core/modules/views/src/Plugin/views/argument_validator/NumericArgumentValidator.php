<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\views\argument_validator\NumericArgumentValidator.
 */

namespace Drupal\views\Plugin\views\argument_validator;

/**
 * Validate whether an argument is numeric or not.
 *
 * @ingroup views_argument_validate_plugins
 *
 * @ViewsArgumentValidator(
 *   id = "numeric",
 *   title = @Translation("Numeric")
 * )
 */
class NumericArgumentValidator extends ArgumentValidatorPluginBase {

  public function validateArgument($argument) {
    return is_numeric($argument);
  }

}
