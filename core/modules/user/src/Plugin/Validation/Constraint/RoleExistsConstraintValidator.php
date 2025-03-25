<?php

declare(strict_types = 1);

namespace Drupal\user\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates that a role exists.
 */
class RoleExistsConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * Create a new RoleExistsConstraintValidator instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(private readonly EntityTypeManagerInterface $entity_type_manager) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get(EntityTypeManagerInterface::class),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint): void {
    assert($constraint instanceof RoleExistsConstraint);

    if (!is_string($value)) {
      throw new UnexpectedTypeException($value, 'string');
    }

    $roleStorage = $this->entity_type_manager->getStorage('user_role');
    if (!$roleStorage->load($value)) {
      $this->context->addViolation($constraint->message, [
        '@rid' => $value,
      ]);
    }
  }

}
