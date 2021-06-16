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
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * Creates a new MenuTreeHierarchyConstraintValidator instance.
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
  public function validate($entity, Constraint $constraint) {
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
