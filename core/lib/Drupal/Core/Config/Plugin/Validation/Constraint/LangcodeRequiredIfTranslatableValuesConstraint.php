<?php

declare(strict_types=1);

namespace Drupal\Core\Config\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Validation constraint for translatable configuration.
 */
#[Constraint(
  id: 'LangcodeRequiredIfTranslatableValues',
  label: new TranslatableMarkup('Translatable config has langcode', [], ['context' => 'Validation']),
  type: ['config_object']
)]
class LangcodeRequiredIfTranslatableValuesConstraint extends SymfonyConstraint {

  /**
   * The error message if this config object is missing a `langcode`.
   *
   * @var string
   */
  public string $missingMessage = "The @name config object must specify a language code, because it contains translatable values.";

  /**
   * The error message if this config object contains a superfluous `langcode`.
   *
   * @var string
   */
  public string $superfluousMessage = "The @name config object does not contain any translatable values, so it should not specify a language code.";

}
