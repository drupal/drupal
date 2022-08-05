<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5\Plugin\Validation\Constraint;

use Drupal\ckeditor5\HTMLRestrictions;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * CKEditor 5 element validator.
 *
 * @internal
 */
class CKEditor5ElementConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\Validator\Exception\UnexpectedTypeException
   *   Thrown when the given constraint is not supported by this validator.
   */
  public function validate($element, $constraint) {
    if (!$constraint instanceof CKEditor5ElementConstraint) {
      throw new UnexpectedTypeException($constraint, __NAMESPACE__ . '\CKEditor5Element');
    }

    $parsed = HTMLRestrictions::fromString($element);
    if ($parsed->allowsNothing() || count($parsed->getAllowedElements()) > 1 || $element !== $parsed->toCKEditor5ElementsArray()[0]) {
      $this->context->buildViolation($constraint->message)
        ->setParameter('%provided_element', $element)
        ->addViolation();
    }

    // The optional "requiredAttributes" constraint property allows more
    // detailed validation.
    if (isset($constraint->requiredAttributes)) {
      $allowed_elements = $parsed->getAllowedElements();
      $tag = array_keys($allowed_elements)[0];
      $attribute_restrictions = $allowed_elements[$tag];
      assert(is_array($constraint->requiredAttributes));
      foreach ($constraint->requiredAttributes as $required_attribute) {
        // Validate attributeName.
        $required_attribute_name = $required_attribute['attributeName'];
        if (!is_array($attribute_restrictions) || !isset($attribute_restrictions[$required_attribute_name])) {
          $this->context->buildViolation($constraint->missingRequiredAttributeMessage)
            ->setParameter('@provided_element', $element)
            ->setParameter('@required_attribute_name', $required_attribute_name)
            ->addViolation();
          continue;
        }

        $attribute_values = $attribute_restrictions[$required_attribute_name];

        // Validate minAttributeValueCount if specified.
        if (isset($required_attribute['minAttributeValueCount'])) {
          $min_attribute_value_count = $required_attribute['minAttributeValueCount'];
          if (!is_array($attribute_values) || count($attribute_values) < $min_attribute_value_count) {
            $this->context->buildViolation($constraint->requiredAttributeMinValuesMessage)
              ->setParameter('@provided_element', $element)
              ->setParameter('@required_attribute_name', $required_attribute_name)
              ->setParameter('@min_attribute_value_count', $min_attribute_value_count)
              ->addViolation();
            continue;
          }
        }
      }
    }
  }

}
