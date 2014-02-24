<?php

/**
 * @file
 * Contains \Drupal\Core\Validation\Plugin\Validation\Constraint\AllowedValuesConstraintValidator.
 */

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Drupal\Core\TypedData\AllowedValuesInterface;
use Drupal\Core\TypedData\ComplexDataInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\ChoiceValidator;

/**
 * Validates the AllowedValues constraint.
 */
class AllowedValuesConstraintValidator extends ChoiceValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    $typed_data = $this->context->getMetadata()->getTypedData();

    if ($typed_data instanceof AllowedValuesInterface) {
      $account = \Drupal::currentUser();
      $allowed_values = $typed_data->getSettableValues($account);
      $constraint->choices = $allowed_values;

      // If the data is complex, we have to validate its main property.
      if ($typed_data instanceof ComplexDataInterface) {
        $name = $typed_data->getDataDefinition()->getMainPropertyName();
        if (!isset($name)) {
          throw new \LogicException('Cannot validate allowed values for complex data without a main property.');
        }
        $value = $typed_data->get($name)->getValue();
      }
    }

    // The parent implementation ignores values that are not set, but makes
    // sure some choices are available firstly. However, we want to support
    // empty choices for undefined values, e.g. if a term reference field
    // points to an empty vocabulary.
    if (!isset($value)) {
      return;
    }

    parent::validate($value, $constraint);
  }

}
