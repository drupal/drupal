<?php

declare(strict_types = 1);

namespace Drupal\Core\Entity\Plugin\Validation\Constraint;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\LogicException;
use Symfony\Component\Validator\Exception\RuntimeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Validates the ImmutableProperties constraint.
 */
class ImmutablePropertiesConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * Constructs an ImmutablePropertiesConstraintValidator object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   */
  public function __construct(protected EntityTypeManagerInterface $entityTypeManager) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate(mixed $value, Constraint $constraint): void {
    assert($constraint instanceof ImmutablePropertiesConstraint);

    if (!$value instanceof ConfigEntityInterface) {
      throw new UnexpectedValueException($value, ConfigEntityInterface::class);
    }
    // This validation is irrelevant on new entities.
    if ($value->isNew()) {
      return;
    }

    $id = $value->getOriginalId() ?: $value->id();
    if (empty($id)) {
      throw new LogicException('The entity does not have an ID.');
    }

    $original = $this->entityTypeManager->getStorage($value->getEntityTypeId())
      ->loadUnchanged($id);
    if (empty($original)) {
      throw new RuntimeException('The original entity could not be loaded.');
    }

    foreach ($constraint->properties as $name) {
      // The property must be concretely defined in the class.
      if (!property_exists($value, $name)) {
        throw new LogicException("The entity does not have a '$name' property.");
      }

      if ($original->get($name) !== $value->get($name)) {
        $this->context->addViolation($constraint->message, ['@name' => $name]);
      }
    }
  }

}
