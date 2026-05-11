<?php

declare(strict_types=1);

namespace Drupal\field\Plugin\Validation\Constraint;

use Drupal\Core\Config\Schema\TypeResolver;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the NoFieldItemsExistWithHigherCardinality constraint.
 *
 * This validator checks whether existing field items of a specified field
 * exceed the given cardinality limit. It performs an aggregate query to find
 * the maximum delta (index of field items) for the specified field across all
 * entities of the given entity type, and compares it against the provided
 * cardinality.
 *
 * The validation:
 * - Skips if cardinality is unlimited (-1)
 * - Skips if the field storage configuration doesn't exist
 * - Uses EntityTypeManager to query the maximum field delta
 * - Adds a violation if the maximum delta exceeds the cardinality
 *
 * This validator implements ContainerInjectionInterface to access the entity
 * type manager service from the Drupal service container.
 *
 * @see \Drupal\field\Plugin\Validation\Constraint\NoFieldItemsExistWithHigherCardinality
 */
class NoFieldItemsExistWithHigherCardinalityValidator extends ConstraintValidator implements ContainerInjectionInterface {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate(mixed $value, SymfonyConstraint $constraint): void {
    assert($constraint instanceof NoFieldItemsExistWithHigherCardinality);

    // Cardinality should be an int, but could be passed differently.
    $cardinality = (int) $value;

    if ($cardinality === FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) {
      return;
    }

    $object = $this->context->getObject();
    assert($object instanceof TypedDataInterface);

    $entity_type = TypeResolver::resolveExpression($constraint->entityType, $object);
    $field_name = TypeResolver::resolveExpression($constraint->fieldName, $object);

    // We cannot check this constraint if the field storage does not exist.
    $fieldStorageConfig = $this->entityTypeManager->getStorage('field_storage_config')
      ->load($entity_type . '.' . $field_name);
    if ($fieldStorageConfig === NULL) {
      return;
    }

    if ($fieldStorageConfig->hasCustomStorage()) {
      // If the field storage has custom storage, we cannot check this
      // constraint.
      return;
    }

    $max_delta_alias = 'max_delta';
    $query = $this->entityTypeManager->getStorage($entity_type)
      ->getAggregateQuery()
      ->aggregate($field_name . '.%delta', 'MAX', NULL, $max_delta_alias)
      ->accessCheck(FALSE);
    $result = $query->execute();

    $max_delta = 0;
    if (is_array($result) && !empty($result)) {
      if ($result[0][$max_delta_alias] !== NULL) {
        // Delta starts at 0, so we need to add 1 to get the count of
        // existing values.
        $max_delta = (int) $result[0][$max_delta_alias] + 1;
      }
    }

    if ($max_delta > $cardinality) {
      $this->context->addViolation($constraint->message, [
        '@entity_type' => $entity_type,
        '@field_name' => $field_name,
        '@max_delta' => $max_delta,
        '@cardinality' => $cardinality,
      ]);
    }
  }

}
