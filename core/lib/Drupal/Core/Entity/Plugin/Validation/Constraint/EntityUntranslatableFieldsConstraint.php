<?php

namespace Drupal\Core\Entity\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Validation constraint for the entity changed timestamp.
 *
 * @Constraint(
 *   id = "EntityUntranslatableFields",
 *   label = @Translation("Entity untranslatable fields", context = "Validation"),
 *   type = {"entity"}
 * )
 */
class EntityUntranslatableFieldsConstraint extends Constraint {

  public $defaultRevisionMessage = 'Non-translatable fields can only be changed when updating the current revision.';
  public $defaultTranslationMessage = 'Non-translatable fields can only be changed when updating the original language.';

}
