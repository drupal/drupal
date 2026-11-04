<?php

namespace Drupal\menu_link_content\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Constraint validator for changing menu link parents in pending revisions.
 */
class MenuTreeHierarchyConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * Creates a new MenuTreeHierarchyConstraintValidator instance.
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
  public function validate($entity, Constraint $constraint): void {
    if ($entity && !$entity->isNew() && !$entity->isDefaultRevision()) {
      $original = $this->entityTypeManager->getStorage($entity->getEntityTypeId())->loadUnchanged($entity->id());

      // Ensure that empty items do not affect the comparison checks below.
      // @todo Remove this filtering when
      //   https://www.drupal.org/project/drupal/issues/3039031 is fixed.
      $entity->parent->filterEmptyItems();
      if (($entity->parent->isEmpty() !== $original->parent->isEmpty()) || !$entity->parent->equals($original->parent)) {
        $this->context->buildViolation($constraint->message)
          ->atPath('menu_parent')
          ->addViolation();
      }
      if (!$entity->weight->equals($original->weight)) {
        $this->context->buildViolation($constraint->message)
          ->atPath('weight')
          ->addViolation();
      }
    }
  }

}
