<?php

namespace Drupal\content_translation\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Validation constraint for the entity changed timestamp.
 *
 * @internal
 */
#[Constraint(
  id: 'ContentTranslationSynchronizedFields',
  label: new TranslatableMarkup('Content translation synchronized fields', [], ['context' => 'Validation']),
  type: ['entity']
)]
class ContentTranslationSynchronizedFieldsConstraint extends SymfonyConstraint {

  #[HasNamedArguments]
  public function __construct(
    mixed $options = NULL,
    public string $defaultRevisionMessage = 'Non-translatable field elements can only be changed when updating the current revision.',
    public string $defaultTranslationMessage = 'Non-translatable field elements can only be changed when updating the original language.',
    ?array $groups = NULL,
    mixed $payload = NULL,
  ) {
    parent::__construct($options, $groups, $payload);
  }

}
