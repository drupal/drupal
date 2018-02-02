<?php

namespace Drupal\content_translation\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Validation constraint for the entity changed timestamp.
 *
 * @internal
 *
 * @Constraint(
 *   id = "ContentTranslationSynchronizedFields",
 *   label = @Translation("Content translation synchronized fields", context = "Validation"),
 *   type = {"entity"}
 * )
 */
class ContentTranslationSynchronizedFieldsConstraint extends Constraint {

  // In this case "elements" refers to "field properties", in fact it is what we
  // are using in the UI elsewhere.
  public $defaultRevisionMessage = 'Non-translatable field elements can only be changed when updating the current revision.';
  public $defaultTranslationMessage = 'Non-translatable field elements can only be changed when updating the original language.';

}
