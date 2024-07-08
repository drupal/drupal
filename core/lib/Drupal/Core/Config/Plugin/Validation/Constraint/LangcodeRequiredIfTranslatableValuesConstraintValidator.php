<?php

declare(strict_types=1);

namespace Drupal\Core\Config\Plugin\Validation\Constraint;

use Drupal\Core\Config\Schema\Mapping;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the LangcodeRequiredIfTranslatableValues constraint.
 */
final class LangcodeRequiredIfTranslatableValuesConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate(mixed $value, Constraint $constraint): void {
    assert($constraint instanceof LangcodeRequiredIfTranslatableValuesConstraint);

    $mapping = $this->context->getObject();
    assert($mapping instanceof Mapping);
    $root = $this->context->getRoot();
    if ($mapping !== $root) {
      @trigger_error(sprintf(
        'The LangcodeRequiredIfTranslatableValues constraint can only be applied to the root object being validated, using the \'config_object\' schema type on \'%s\' is deprecated in drupal:10.3.0 and will trigger a \LogicException in drupal:11.0.0. See https://www.drupal.org/node/3459863',
        $root->getName() . '::' . $mapping->getName()
      ), E_USER_DEPRECATED);
      return;
    }

    assert(in_array('langcode', $mapping->getValidKeys(), TRUE));

    $is_translatable = $mapping->hasTranslatableElements();

    if ($is_translatable && !array_key_exists('langcode', $value)) {
      $this->context->buildViolation($constraint->missingMessage)
        ->setParameter('@name', $mapping->getName())
        ->addViolation();
      return;
    }
    if (!$is_translatable && array_key_exists('langcode', $value)) {
      // @todo Convert this deprecation to an actual validation error in
      //   https://www.drupal.org/project/drupal/issues/3440238.
      // phpcs:ignore
      @trigger_error(str_replace('@name', $mapping->getName(), $constraint->superfluousMessage), E_USER_DEPRECATED);
    }
  }

}
