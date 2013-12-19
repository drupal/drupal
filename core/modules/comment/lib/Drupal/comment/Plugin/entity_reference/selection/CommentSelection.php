<?php

/**
 * @file
 * Contains \Drupal\comment\Plugin\entity_reference\selection\CommentSelection.
 */

namespace Drupal\comment\Plugin\entity_reference\selection;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\comment\CommentInterface;
use Drupal\entity_reference\Plugin\entity_reference\selection\SelectionBase;

/**
 * Provides specific access control for the comment entity type.
 *
 * @EntityReferenceSelection(
 *   id = "comment_default",
 *   label = @Translation("Comment selection"),
 *   entity_types = {"comment"},
 *   group = "default",
 *   weight = 1
 * )
 */
class CommentSelection extends SelectionBase {

  /**
   * {@inheritdoc}
   */
  public function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);

    // Adding the 'comment_access' tag is sadly insufficient for comments:
    // core requires us to also know about the concept of 'published' and
    // 'unpublished'.
    if (!user_access('administer comments')) {
      $query->condition('status', CommentInterface::PUBLISHED);
    }
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function entityQueryAlter(SelectInterface $query) {
    $tables = $query->getTables();
    $base_table = $tables['base_table']['alias'];

    // The Comment module doesn't implement any proper comment access,
    // and as a consequence doesn't make sure that comments cannot be viewed
    // when the user doesn't have access to the node.
    $node_alias = $query->innerJoin('node_field_data', 'n', '%alias.nid = ' . $base_table . '.entity_id AND ' . $base_table . ".entity_type = 'node'");
    // Pass the query to the node access control.
    $this->reAlterQuery($query, 'node_access', $node_alias);

    // Alas, the comment entity exposes a bundle, but doesn't have a bundle
    // column in the database. We have to alter the query ourselves to go fetch
    // the bundle.
    $conditions = &$query->conditions();
    foreach ($conditions as $key => &$condition) {
      if ($key !== '#conjunction' && is_string($condition['field']) && $condition['field'] === 'node_type') {
        $condition['field'] = $node_alias . '.type';
        foreach ($condition['value'] as &$value) {
          if (substr($value, 0, 13) == 'comment_node_') {
            $value = substr($value, 13);
          }
        }
        break;
      }
    }

    // Passing the query to node_query_node_access_alter() is sadly
    // insufficient for nodes.
    // @see SelectionEntityTypeNode::entityQueryAlter()
    if (!user_access('bypass node access') && !count(\Drupal::moduleHandler()->getImplementations('node_grants'))) {
      $query->condition($node_alias . '.status', 1);
    }
  }
}
