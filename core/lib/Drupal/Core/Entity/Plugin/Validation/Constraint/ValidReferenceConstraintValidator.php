<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Plugin\Validation\Constraint\ValidReferenceConstraintValidator.
 */

namespace Drupal\Core\Entity\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionWithAutocreateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Checks if referenced entities are valid.
 */
class ValidReferenceConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The selection plugin manager.
   *
   * @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface
   */
  protected $selectionManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a ValidReferenceConstraintValidator object.
   *
   * @param \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface $selection_manager
   *   The selection plugin manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(SelectionPluginManagerInterface $selection_manager, EntityTypeManagerInterface $entity_type_manager) {
    $this->selectionManager = $selection_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.entity_reference_selection'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    /** @var \Drupal\Core\Field\FieldItemListInterface $value */
    /** @var ValidReferenceConstraint $constraint */
    if (!isset($value)) {
      return;
    }

    // Collect new entities and IDs of existing entities across the field items.
    $new_entities = [];
    $target_ids = [];
    foreach ($value as $delta => $item) {
      $target_id = $item->target_id;
      // We don't use a regular NotNull constraint for the target_id property as
      // NULL is allowed if the entity property contains an unsaved entity.
      // @see \Drupal\Core\TypedData\DataReferenceTargetDefinition::getConstraints()
      if (!$item->isEmpty() && $target_id === NULL) {
        if (!$item->entity->isNew()) {
          $this->context->buildViolation($constraint->nullMessage)
            ->atPath((string) $delta)
            ->addViolation();
          return;
        }
        $new_entities[$delta] = $item->entity;
      }

      // '0' or NULL are considered valid empty references.
      if (!empty($target_id)) {
        $target_ids[$delta] = $target_id;
      }
    }

    // Early opt-out if nothing to validate.
    if (!$new_entities && !$target_ids) {
      return;
    }

    /** @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface $handler * */
    $handler = $this->selectionManager->getSelectionHandler($value->getFieldDefinition());
    $target_type_id = $value->getFieldDefinition()->getSetting('target_type');

    // Add violations on deltas with a new entity that is not valid.
    if ($new_entities) {
      if ($handler instanceof SelectionWithAutocreateInterface) {
        $valid_new_entities = $handler->validateReferenceableNewEntities($new_entities);
        $invalid_new_entities = array_diff_key($new_entities, $valid_new_entities);
      }
      else {
        // If the selection handler does not support referencing newly created
        // entities, all of them should be invalidated.
        $invalid_new_entities = $new_entities;
      }

      foreach ($invalid_new_entities as $delta => $entity) {
        $this->context->buildViolation($constraint->invalidAutocreateMessage)
          ->setParameter('%type', $target_type_id)
          ->setParameter('%label', $entity->label())
          ->atPath((string) $delta . '.entity')
          ->setInvalidValue($entity)
          ->addViolation();
      }
    }

    // Add violations on deltas with a target_id that is not valid.
    if ($target_ids) {
      $valid_target_ids = $handler->validateReferenceableEntities($target_ids);
      if ($invalid_target_ids = array_diff($target_ids, $valid_target_ids)) {
        // For accuracy of the error message, differentiate non-referenceable
        // and non-existent entities.
        $target_type = $this->entityTypeManager->getDefinition($target_type_id);
        $existing_ids = $this->entityTypeManager->getStorage($target_type_id)->getQuery()
          ->condition($target_type->getKey('id'), $invalid_target_ids, 'IN')
          ->execute();
        foreach ($invalid_target_ids as $delta => $target_id) {
          $message = in_array($target_id, $existing_ids) ? $constraint->message : $constraint->nonExistingMessage;
          $this->context->buildViolation($message)
            ->setParameter('%type', $target_type_id)
            ->setParameter('%id', $target_id)
            ->atPath((string) $delta . '.target_id')
            ->setInvalidValue($target_id)
            ->addViolation();
        }
      }
    }
  }

}
