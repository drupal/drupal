<?php

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates that a field is unique for the given entity type.
 */
class UniqueFieldValueValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * Creates a UniqueFieldValueValidator object.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(protected EntityFieldManagerInterface $entityFieldManager, protected EntityTypeManagerInterface $entityTypeManager) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_field.manager'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    if (!$items->first()) {
      return;
    }

    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $items->getEntity();
    $entity_type = $entity->getEntityType();
    $entity_type_id = $entity_type->id();
    $entity_label = $entity->getEntityType()->getSingularLabel();

    $field_name = $items->getFieldDefinition()->getName();
    $field_label = $items->getFieldDefinition()->getLabel();
    $field_storage_definitions = $this->entityFieldManager->getFieldStorageDefinitions($entity_type_id);
    $property_name = $field_storage_definitions[$field_name]->getMainPropertyName();

    $id_key = $entity_type->getKey('id');
    $is_multiple = $field_storage_definitions[$field_name]->isMultiple();
    $is_new = $entity->isNew();
    $item_values = array_column($items->getValue(), $property_name);

    // Check if any item values for this field already exist in other entities.
    $query = $this->entityTypeManager
      ->getStorage($entity_type_id)
      ->getAggregateQuery()
      ->accessCheck(FALSE)
      ->groupBy("$field_name.$property_name");
    if (!$is_new) {
      $entity_id = $entity->id();
      $query->condition($id_key, $entity_id, '<>');
    }

    if ($constraint->caseSensitive) {
      $query->condition($field_name, $item_values, 'IN');
    }
    else {
      $or_group = $query->orConditionGroup();
      foreach ($item_values as $item_value) {
        $or_group->condition($field_name, \Drupal::database()->escapeLike($item_value), 'LIKE');
      }
      $query->condition($or_group);
    }

    $results = $query->execute();

    if (!empty($results)) {
      // The results array is a single-column multidimensional array. The
      // column key includes the field name but may or may not include the
      // property name. Pop the column key from the first result to be sure.
      $column_key = key(reset($results));
      $other_entity_values = array_column($results, $column_key);

      // If our entity duplicates field values in any other entity, the query
      // will return all field values that belong to those entities. Narrow
      // down to only the specific duplicate values.
      $duplicate_values = $this->caseInsensitiveArrayIntersect($item_values, $other_entity_values);

      foreach ($duplicate_values as $delta => $dupe) {
        $violation = $this->context
          ->buildViolation($constraint->message)
          ->setParameter('@entity_type', $entity_label)
          ->setParameter('@field_name', $field_label)
          ->setParameter('%value', $dupe);
        if ($is_multiple) {
          $violation->atPath((string) $delta);
        }
        $violation->addViolation();
      }
    }

    // Check if items are duplicated within this entity.
    if ($is_multiple) {
      $duplicate_values = $this->extractDuplicates($item_values);
      foreach ($duplicate_values as $delta => $dupe) {
        $this->context
          ->buildViolation($constraint->message)
          ->setParameter('@entity_type', $entity_label)
          ->setParameter('@field_name', $field_label)
          ->setParameter('%value', $dupe)
          ->atPath((string) $delta)
          ->addViolation();
      }
    }
  }

  /**
   * Perform a case-insensitive array intersection, but keep original capitalization.
   *
   * @param array $orig_values
   *   The original values to be returned.
   * @param array $comp_values
   *   The values to intersect $orig_values with.
   *
   * @return array
   *   Elements of $orig_values contained in $comp_values when ignoring capitalization.
   */
  private function caseInsensitiveArrayIntersect(array $orig_values, array $comp_values): array {
    $lowercase_comp_values = array_map('strtolower', $comp_values);
    $intersect_map = array_map(fn (string $x) => in_array(strtolower($x), $lowercase_comp_values, TRUE) ? $x : NULL, $orig_values);

    return array_filter($intersect_map, function ($x) {
      return $x !== NULL;
    });
  }

  /**
   * Get an array of duplicate field values.
   *
   * @param array $item_values
   *   The item values.
   *
   * @return array
   *   Item values only for deltas that duplicate an earlier delta.
   */
  private function extractDuplicates(array $item_values): array {
    $value_frequency = array_count_values($item_values);

    // Filter out item values which are not duplicates while preserving deltas
    $duplicate_values = array_intersect($item_values, array_keys(array_filter(
      $value_frequency, function ($value) {
        return $value > 1;
      })
    ));

    // Exclude the first delta of each duplicate value.
    $first_deltas = array_unique($duplicate_values);
    return array_diff_key($duplicate_values, $first_deltas);
  }

}
