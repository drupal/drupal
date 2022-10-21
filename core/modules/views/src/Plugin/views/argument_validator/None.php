<?php

namespace Drupal\views\Plugin\views\argument_validator;

/**
 * Do not validate the argument.
 *
 * @ingroup views_argument_validate_plugins
 *
 * @ViewsArgumentValidator(
 *   id = "none",
 *   title = @Translation(" - Basic validation - ")
 * )
 */
class None extends ArgumentValidatorPluginBase {

  public function validateArgument($argument) {
    if (!empty($this->argument->options['must_not_be'])) {
      return !isset($argument);
    }

    if (!isset($argument) || $argument === '') {
      return FALSE;
    }

    if (!empty($this->argument->definition['numeric']) && !isset($this->argument->options['break_phrase'])) {
      return FALSE;
    }

    return TRUE;
  }

}
