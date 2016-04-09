<?php

namespace Drupal\Core\Entity\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Checks if the current user has access to newly referenced entities.
 */
class ReferenceAccessConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    /* @var \Drupal\Core\Field\FieldItemInterface $value */
    if (!isset($value)) {
      return;
    }
    $id = $value->target_id;
    // '0' or NULL are considered valid empty references.
    if (empty($id)) {
      return;
    }
    /* @var \Drupal\Core\Entity\FieldableEntityInterface $referenced_entity */
    $referenced_entity = $value->entity;
    if ($referenced_entity) {
      $entity = $value->getEntity();
      $check_permission = TRUE;
      if (!$entity->isNew()) {
        $existing_entity = \Drupal::entityManager()->getStorage($entity->getEntityTypeId())->loadUnchanged($entity->id());
        $referenced_entities = $existing_entity->{$value->getFieldDefinition()->getName()}->referencedEntities();
        // Check permission if we are not already referencing the entity.
        foreach ($referenced_entities as $ref) {
           if (isset($referenced_entities[$ref->id()])) {
             $check_permission = FALSE;
             break;
           }
        }
      }
      // We check that the current user had access to view any newly added
      // referenced entity.
      if ($check_permission && !$referenced_entity->access('view')) {
        $type = $value->getFieldDefinition()->getSetting('target_type');
        $this->context->addViolation($constraint->message, array('%type' => $type, '%id' => $id));
      }
    }
  }
}
