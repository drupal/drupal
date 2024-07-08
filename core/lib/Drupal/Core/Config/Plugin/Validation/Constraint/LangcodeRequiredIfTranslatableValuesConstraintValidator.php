<?php

declare(strict_types=1);

namespace Drupal\Core\Config\Plugin\Validation\Constraint;

use Drupal\Core\Config\Schema\Mapping;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\LogicException;

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
      throw new LogicException(sprintf(
        'The LangcodeRequiredIfTranslatableValues constraint is applied to \'%s\'. This constraint can only operate on the root object being validated.',
        $root->getName() . '::' . $mapping->getName()
      ));
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
