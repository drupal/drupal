<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\argument_validator\Numeric.
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
class Numeric extends ArgumentValidatorPluginBase {

  public function validateArgument($argument) {
    return is_numeric($argument);
  }

}
