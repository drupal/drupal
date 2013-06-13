<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\argument_validator\Numeric.
 */

namespace Drupal\views\Plugin\views\argument_validator;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Validate whether an argument is numeric or not.
 *
 * @ingroup views_argument_validate_plugins
 *
 * @Plugin(
 *   id = "numeric",
 *   title = @Translation("Numeric")
 * )
 */
class Numeric extends ArgumentValidatorPluginBase {

  public function validateArgument($argument) {
    return is_numeric($argument);
  }

}
