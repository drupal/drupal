<?php

namespace Drupal\Core\TypedData\Validation;

use Drupal\Core\TypedData\TypedDataInterface;

/**
 * Defines a trait to access the typed data object of a validated value.
 *
 * The trait assumes to be used on classes extending
 * \Symfony\Component\Validator\ConstraintValidator.
 */
trait TypedDataAwareValidatorTrait {

  /**
   * Gets the typed data object for the validated value.
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface
   *   The typed data object.
   */
  public function getTypedData() {
    $context = $this->context;
    /** @var \Symfony\Component\Validator\Context\ExecutionContextInterface $context */
    $data = $context->getObject();
    if (!$data instanceof TypedDataInterface) {
      throw new \LogicException("There is no Typed Data object available.");
    }
    return $data;
  }

}
