<?php

namespace Drupal\views\Plugin\views\argument_validator;

use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views\Attribute\ViewsArgumentValidator;

/**
 * Validate whether an argument is numeric or not.
 *
 * @ingroup views_argument_validate_plugins
 */
#[ViewsArgumentValidator(
  id: 'numeric',
  title: new TranslatableMarkup('Numeric')
)]
class NumericArgumentValidator extends ArgumentValidatorPluginBase {

  public function validateArgument($argument) {
    return is_numeric($argument);
  }

  /**
   * {@inheritdoc}
   */
  public function getContextDefinition() {
    return new ContextDefinition('integer', $this->argument->adminLabel(), FALSE);
  }

}
