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
    if (!\Drupal::currentUser()->hasPermission('administer comments')) {
      $query->condition('status', CommentInterface::PUBLISHED);
    }
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function entityQueryAlter(SelectInterface $query) {
    $tables = $query->getTables();
    $data_table = 'comment_field_data';
    if (!isset($tables['comment_field_data']['alias'])) {
      // If no conditions join against the comment data table, it should be
      // joined manually to allow node access processing.
      $query->innerJoin($data_table, NULL, "base_table.cid = $data_table.cid AND $data_table.default_langcode = 1");
    }

    // The Comment module doesn't implement any proper comment access,
    // and as a consequence doesn't make sure that comments cannot be viewed
    // when the user doesn't have access to the node.
    $node_alias = $query->innerJoin('node_field_data', 'n', '%alias.nid = ' . $data_table . '.entity_id AND ' . $data_table . ".entity_type = 'node'");
    // Pass the query to the node access control.
    $this->reAlterQuery($query, 'node_access', $node_alias);

    // Passing the query to node_query_node_access_alter() is sadly
    // insufficient for nodes.
    // @see SelectionEntityTypeNode::entityQueryAlter()
    if (!\Drupal::currentUser()->hasPermission('bypass node access') && !count(\Drupal::moduleHandler()->getImplementations('node_grants'))) {
      $query->condition($node_alias . '.status', 1);
    }
  }
}
