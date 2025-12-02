<?php

declare(strict_types = 1);

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Drupal\Core\Config\Schema\Mapping;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\CollectionValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates the MappingCollection constraint.
 */
class MappingCollectionConstraintValidator extends CollectionValidator {

  /**
   * {@inheritdoc}
   */
  public function validate(mixed $value, Constraint $constraint): void {
    if (!$constraint instanceof MappingCollectionConstraint) {
      throw new UnexpectedTypeException($constraint, Collection::class);
    }

    if (NULL === $value) {
      return;
    }

    if (!$this->context->getObject() instanceof Mapping) {
      throw new UnexpectedTypeException($this->context->getObject(), Mapping::class);
    }

    $value = $this->context->getObject()->getIterator();
    parent::validate($value, $constraint);
  }

}
