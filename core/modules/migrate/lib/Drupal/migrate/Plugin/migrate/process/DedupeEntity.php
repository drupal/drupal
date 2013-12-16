<?php

/**
 * @file
 * Contains \Drupal\migrate\Plugin\migrate\process\DedupeEntity.
 */

namespace Drupal\migrate\Plugin\migrate\process;

/**
 * Ensures value is not duplicated against an entity field.
 *
 * @MigrateProcessPlugin(
 *   id = "dedupe_entity"
 * )
 */
class DedupeEntity extends DedupeBase {

  /**
   * @var \Drupal\Core\Entity\Query\QueryInterface
   */
  protected $entityQuery;

  /**
   * {@inheritdoc}
   */
  protected function exists($value) {
    return $this->getEntityQuery()->condition($this->configuration['field'], $value)->count()->execute();
  }

  /**
   * Returns an entity query object.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   The entity query object for the configured entity type.
   */
  protected function getEntityQuery() {
    if (!isset($this->entityQuery)) {
      $this->entityQuery = \Drupal::entityQuery($this->configuration['entity_type']);
    }
    return $this->entityQuery;
  }
}
