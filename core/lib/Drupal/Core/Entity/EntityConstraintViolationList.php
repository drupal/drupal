<?php

namespace Drupal\Core\Entity;

use Drupal\Core\Entity\Plugin\Validation\Constraint\CompositeConstraintBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * Implements an entity constraint violation list.
 */
class EntityConstraintViolationList extends ConstraintViolationList implements EntityConstraintViolationListInterface {

  use StringTranslationTrait;

  /**
   * The entity that has been validated.
   *
   * @var \Drupal\Core\Entity\FieldableEntityInterface
   */
  protected $entity;

  /**
   * Violations offsets of entity level violations.
   *
   * @var int[]|null
   */
  protected $entityViolationOffsets;

  /**
   * Violation offsets grouped by field.
   *
   * Keys are field names, values are arrays of violation offsets.
   *
   * @var array[]|null
   */
  protected $violationOffsetsByField;

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity that has been validated.
   * @param array $violations
   *   The array of violations.
   */
  public function __construct(FieldableEntityInterface $entity, array $violations = []) {
    parent::__construct($violations);
    $this->entity = $entity;
  }

  /**
   * Groups violation offsets by field and entity level.
   *
   * Sets the $violationOffsetsByField and $entityViolationOffsets properties.
   */
  protected function groupViolationOffsets() {
    if (!isset($this->violationOffsetsByField)) {
      $this->violationOffsetsByField = [];
      $this->entityViolationOffsets = [];
      foreach ($this as $offset => $violation) {
        if ($path = $violation->getPropertyPath()) {
          // An example of $path might be 'title.0.value'.
          [$field_name] = explode('.', $path, 2);
          if ($this->entity->hasField($field_name)) {
            $this->violationOffsetsByField[$field_name][$offset] = $offset;
          }
          // If the first part of the violation property path is not a valid
          // field name, we're dealing with an entity-level validation.
          else {
            $this->entityViolationOffsets[$offset] = $offset;
          }
        }
        else {
          $this->entityViolationOffsets[$offset] = $offset;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityViolations() {
    $this->groupViolationOffsets();
    $violations = [];
    foreach ($this->entityViolationOffsets as $offset) {
      $violations[] = $this->get($offset);
    }
    return new static($this->entity, $violations);
  }

  /**
   * {@inheritdoc}
   */
  public function getByField($field_name) {
    return $this->getByFields([$field_name]);
  }

  /**
   * {@inheritdoc}
   */
  public function getByFields(array $field_names) {
    $this->groupViolationOffsets();
    $violations = [];
    foreach (array_intersect_key($this->violationOffsetsByField, array_flip($field_names)) as $offsets) {
      foreach ($offsets as $offset) {
        $violations[] = $this->get($offset);
      }
    }
    return new static($this->entity, $violations);
  }

  /**
   * {@inheritdoc}
   */
  public function filterByFields(array $field_names) {
    $this->groupViolationOffsets();
    $new_violations = [];
    foreach (array_intersect_key($this->violationOffsetsByField, array_flip($field_names)) as $field_name => $offsets) {
      foreach ($offsets as $offset) {
        $violation = $this->get($offset);
        // Take care of composite field violations and re-map them to some
        // covered field if necessary.
        if ($violation->getConstraint() instanceof CompositeConstraintBase) {
          $covered_fields = $violation->getConstraint()->coversFields();

          // Keep the composite field if it covers some remaining field and put
          // a violation on some other covered field instead.
          if ($remaining_fields = array_diff($covered_fields, $field_names)) {
            $message_params = ['%field_name' => $field_name];
            $violation = new ConstraintViolation(
              $this->t('The validation failed because the value conflicts with the value in %field_name, which you cannot access.', $message_params),
              'The validation failed because the value conflicts with the value in %field_name, which you cannot access.',
              $message_params,
              $violation->getRoot(),
              reset($remaining_fields),
              $violation->getInvalidValue(),
              $violation->getPlural(),
              $violation->getCode(),
              $violation->getConstraint(),
              $violation->getCause()
            );
            $new_violations[] = $violation;
          }
        }

        $this->remove($offset);
      }
    }
    foreach ($new_violations as $violation) {
      $this->add($violation);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function filterByFieldAccess(?AccountInterface $account = NULL) {
    $filtered_fields = [];
    foreach ($this->getFieldNames() as $field_name) {
      if (!$this->entity->get($field_name)->access('edit', $account)) {
        $filtered_fields[] = $field_name;
      }
    }
    return $this->filterByFields($filtered_fields);
  }

  /**
   * {@inheritdoc}
   */
  public function findByCodes(string|array $codes): static {
    $violations = [];
    foreach ($this as $violation) {
      if (in_array($violation->getCode(), $codes, TRUE)) {
        $violations[] = $violation;
      }
    }

    return new static($this->getEntity(), $violations);
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldNames() {
    $this->groupViolationOffsets();
    return array_keys($this->violationOffsetsByField);
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * {@inheritdoc}
   *
   * phpcs:ignore Drupal.Commenting.FunctionComment.VoidReturn
   * @return void
   */
  public function add(ConstraintViolationInterface $violation) {
    parent::add($violation);
    $this->violationOffsetsByField = NULL;
    $this->entityViolationOffsets = NULL;
  }

  /**
   * {@inheritdoc}
   *
   * phpcs:ignore Drupal.Commenting.FunctionComment.VoidReturn
   * @return void
   */
  public function remove($offset) {
    parent::remove($offset);
    $this->violationOffsetsByField = NULL;
    $this->entityViolationOffsets = NULL;
  }

  /**
   * {@inheritdoc}
   *
   * phpcs:ignore Drupal.Commenting.FunctionComment.VoidReturn
   * @return void
   */
  public function set($offset, ConstraintViolationInterface $violation) {
    parent::set($offset, $violation);
    $this->violationOffsetsByField = NULL;
    $this->entityViolationOffsets = NULL;
  }

}
