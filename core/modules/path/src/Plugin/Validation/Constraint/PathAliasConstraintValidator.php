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
   * Creates a new PathAliasConstraintValidator instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    private EntityTypeManagerInterface $entityTypeManager,
  ) {
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
  public function validate($value, Constraint $constraint): void {
    $entity = !empty($value->getParent()) ? $value->getEntity() : NULL;

    if ($entity && !$entity->isNew() && !$entity->isDefaultRevision()) {
      /** @var \Drupal\Core\Entity\ContentEntityInterface $original */
      $original = $this->entityTypeManager->getStorage($entity->getEntityTypeId())->loadUnchanged($entity->id());
      $entity_langcode = $entity->language()->getId();

      // Only add the violation if the current translation does not have the
      // same path alias.
      if ($original->hasTranslation($entity_langcode)) {
        if ($value->alias != $original->getTranslation($entity_langcode)->path->alias) {
          $this->context->addViolation($constraint->message);
        }
      }
    }
  }

}
