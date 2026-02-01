<?php

declare(strict_types=1);

namespace Drupal\Core\Config\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
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

  #[HasNamedArguments]
  public function __construct(
    mixed $options = NULL,
    public string $missingMessage = "The @name config object must specify a language code, because it contains translatable values.",
    public string $superfluousMessage = "The @name config object does not contain any translatable values, so it should not specify a language code.",
    ?array $groups = NULL,
    mixed $payload = NULL,
  ) {
    parent::__construct($options, $groups, $payload);
  }

}
