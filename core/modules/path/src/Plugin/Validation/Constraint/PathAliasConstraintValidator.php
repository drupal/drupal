<?php

namespace Drupal\path\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Constraint validator for changing path aliases in pending revisions.
 */
class PathAliasConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * Creates a new PathAliasConstraintValidator instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
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
  public function validate($value, Constraint $constraint) {
    $entity = !empty($value->getParent()) ? $value->getEntity() : NULL;

    if ($entity && !$entity->isNew() && !$entity->isDefaultRevision()) {
      /** @var \Drupal\Core\Entity\ContentEntityInterface $original */
      $original = $this->entityTypeManager->getStorage($entity->getEntityTypeId())->loadUnchanged($entity->id());
      if ($value->alias != $original->path->alias) {
        $this->context->addViolation($constraint->message);
      }
    }
  }

}
