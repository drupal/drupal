<?php

namespace Drupal\content_translation\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
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

  /**
   * Message shown for non-translatable field changes in non-default revision.
   *
   * In this case "elements" refers to "field properties". It is what we are
   * using in the UI elsewhere.
   */
  public string $defaultRevisionMessage = 'Non-translatable field elements can only be changed when updating the current revision.';

  /**
   * Message shown for non-translatable field changes in different language.
   *
   * In this case "elements" refers to "field properties". It is what we are
   * using in the UI elsewhere.
   */
  public string $defaultTranslationMessage = 'Non-translatable field elements can only be changed when updating the original language.';

}
