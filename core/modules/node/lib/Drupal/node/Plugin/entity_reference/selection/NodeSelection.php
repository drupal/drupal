<?php

/**
 * @file
 * Contains \Drupal\node\Plugin\Type\selection\NodeSelection.
 */

namespace Drupal\node\Plugin\entity_reference\selection;

use Drupal\entity_reference\Plugin\entity_reference\selection\SelectionBase;

/**
 * Provides specific access control for the node entity type.
 *
 * @EntityReferenceSelection(
 *   id = "node_default",
 *   label = @Translation("Node selection"),
 *   entity_types = {"node"},
 *   group = "default",
 *   weight = 1
 * )
 */
class NodeSelection extends SelectionBase {

  /**
   * {@inheritdoc}
   */
  public function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);
    // Adding the 'node_access' tag is sadly insufficient for nodes: core
    // requires us to also know about the concept of 'published' and
    // 'unpublished'. We need to do that as long as there are no access control
    // modules in use on the site. As long as one access control module is there,
    // it is supposed to handle this check.
    if (!user_access('bypass node access') && !count(\Drupal::moduleHandler()->getImplementations('node_grants'))) {
      $query->condition('status', NODE_PUBLISHED);
    }
    return $query;
  }
}
