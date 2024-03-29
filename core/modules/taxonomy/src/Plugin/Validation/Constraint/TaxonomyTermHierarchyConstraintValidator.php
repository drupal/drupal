<?php

namespace Drupal\taxonomy\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\taxonomy\TermStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Constraint validator for changing term parents in pending revisions.
 */
class TaxonomyTermHierarchyConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * Creates a new TaxonomyTermHierarchyConstraintValidator instance.
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
  public function validate($entity, Constraint $constraint): void {
    $term_storage = $this->entityTypeManager->getStorage($entity->getEntityTypeId());
    assert($term_storage instanceof TermStorageInterface);

    // Newly created entities should be able to specify a parent.
    if ($entity && $entity->isNew()) {
      return;
    }

    $is_pending_revision = !$entity->isDefaultRevision();
    $pending_term_ids = $term_storage->getTermIdsWithPendingRevisions();
    $ancestors = $term_storage->loadAllParents($entity->id());
    $ancestor_is_pending_revision = (bool) array_intersect_key($ancestors, array_flip($pending_term_ids));

    $new_parents = array_column($entity->parent->getValue(), 'target_id');
    $original_parents = array_keys($term_storage->loadParents($entity->id())) ?: [0];
    if (($is_pending_revision || $ancestor_is_pending_revision) && $new_parents != $original_parents) {
      $this->context->buildViolation($constraint->message)
        ->atPath('parent')
        ->addViolation();
    }

    $original = $term_storage->loadUnchanged($entity->id());
    if (($is_pending_revision || $ancestor_is_pending_revision) && !$entity->weight->equals($original->weight)) {
      $this->context->buildViolation($constraint->message)
        ->atPath('weight')
        ->addViolation();
    }
  }

}
