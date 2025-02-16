<?php

namespace Drupal\node\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\Attribute\EntityReferenceSelection;
use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\node\NodeInterface;

/**
 * Provides specific access control for the node entity type.
 */
#[EntityReferenceSelection(
  id: "default:node",
  label: new TranslatableMarkup("Node selection"),
  entity_types: ["node"],
  group: "default",
  weight: 1
)]
class NodeSelection extends DefaultSelection {

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);
    // Adding the 'node_access' tag is sadly insufficient for nodes: core
    // requires us to also know about the concept of 'published' and
    // 'unpublished'. We need to do that as long as there are no access control
    // modules in use on the site. As long as one access control module is
    // there, it is supposed to handle this check.
    if (!$this->currentUser->hasPermission('bypass node access') && !$this->moduleHandler->hasImplementations('node_grants')) {
      $query->condition('status', NodeInterface::PUBLISHED);
    }
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function createNewEntity($entity_type_id, $bundle, $label, $uid) {
    $node = parent::createNewEntity($entity_type_id, $bundle, $label, $uid);

    // In order to create a referenceable node, it needs to published.
    /** @var \Drupal\node\NodeInterface $node */
    $node->setPublished();

    return $node;
  }

  /**
   * {@inheritdoc}
   */
  public function validateReferenceableNewEntities(array $entities) {
    $entities = parent::validateReferenceableNewEntities($entities);
    // Mirror the conditions checked in buildEntityQuery().
    if (!$this->currentUser->hasPermission('bypass node access') && !$this->moduleHandler->hasImplementations('node_grants')) {
      $entities = array_filter($entities, function ($node) {
        /** @var \Drupal\node\NodeInterface $node */
        return $node->isPublished();
      });
    }
    return $entities;
  }

}
