<?php

namespace Drupal\views\Plugin\views\argument_validator;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views\Attribute\ViewsArgumentValidator;

/**
 * Do not validate the argument.
 *
 * @ingroup views_argument_validate_plugins
 */
#[ViewsArgumentValidator(
  id: 'none',
  title: new TranslatableMarkup('- Basic validation -')
)]
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
