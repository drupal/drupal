<?php

namespace Drupal\entity_reference_test\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;

/**
 * Allows access to all entities except for the host entity.
 *
 * @EntityReferenceSelection(
 *   id = "entity_test_all_except_host",
 *   label = @Translation("All except host entity."),
 *   group = "entity_test_all_except_host"
 * )
 */
class AllExceptHostEntity extends DefaultSelection {

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);

    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    if ($entity = $this->configuration['entity']) {
      $target_type = $this->configuration['target_type'];
      $entity_type = $this->entityTypeManager->getDefinition($target_type);
      $query->condition($entity_type->getKey('id'), $entity->id(), '<>');
    }

    return $query;
  }

}
