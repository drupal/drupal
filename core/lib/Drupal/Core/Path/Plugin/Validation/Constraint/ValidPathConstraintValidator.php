<?php

namespace Drupal\Core\Path\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Constraint validator for validating system paths.
 */
class ValidPathConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * Creates a new ValidPathConstraintValidator instance.
   *
   * @param \Drupal\Core\Path\PathValidatorInterface $pathValidator
   *   The path validator.
   */
  public function __construct(
    protected PathValidatorInterface $pathValidator,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('path.validator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint): void {
    if (!isset($value)) {
      return;
    }

    $path = trim($value, '/');
    if (!$this->pathValidator->isValid($path)) {
      $this->context->addViolation($constraint->message, [
        '%link_path' => $value,
      ]);
    }
  }

}
