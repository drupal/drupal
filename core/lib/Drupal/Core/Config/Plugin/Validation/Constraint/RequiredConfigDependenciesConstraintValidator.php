<?php

declare(strict_types = 1);

namespace Drupal\Core\Config\Plugin\Validation\Constraint;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Config\Entity\ConfigEntityTypeInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\LogicException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates the RequiredConfigDependencies constraint.
 */
class RequiredConfigDependenciesConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructs a RequiredConfigDependenciesConstraintValidator object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate(mixed $entity, Constraint $constraint): void {
    assert($constraint instanceof RequiredConfigDependenciesConstraint);

    // Only config entities can have config dependencies.
    if (!$entity instanceof ConfigEntityInterface) {
      throw new UnexpectedTypeException($entity, ConfigEntityInterface::class);
    }

    $config_dependencies = $entity->getDependencies()['config'] ?? [];

    foreach ($constraint->entityTypes as $entity_type_id) {
      $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);

      if (!$entity_type instanceof ConfigEntityTypeInterface) {
        throw new LogicException("'$entity_type_id' is not a config entity type.");
      }

      // Ensure the current entity type's config prefix is found in the config
      // dependencies of the entity being validated.
      $pattern = sprintf('/^%s\\.\\w+/', $entity_type->getConfigPrefix());
      if (!preg_grep($pattern, $config_dependencies)) {
        $this->context->addViolation($constraint->message, [
          '@entity_type' => $entity->getEntityType()->getSingularLabel(),
          '@dependency_type' => $entity_type->getSingularLabel(),
        ]);
      }
    }
  }

}
