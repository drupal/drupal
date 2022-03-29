<?php

namespace Drupal\comment\Plugin\EntityReferenceSelection;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\comment\CommentInterface;

/**
 * Provides specific access control for the comment entity type.
 *
 * @EntityReferenceSelection(
 *   id = "default:comment",
 *   label = @Translation("Comment selection"),
 *   entity_types = {"comment"},
 *   group = "default",
 *   weight = 1
 * )
 */
class CommentSelection extends DefaultSelection {

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);

    // Adding the 'comment_access' tag is sadly insufficient for comments:
    // core requires us to also know about the concept of 'published' and
    // 'unpublished'.
    if (!$this->currentUser->hasPermission('administer comments')) {
      $query->condition('status', CommentInterface::PUBLISHED);
    }
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function createNewEntity($entity_type_id, $bundle, $label, $uid) {
    $comment = parent::createNewEntity($entity_type_id, $bundle, $label, $uid);

    // In order to create a referenceable comment, it needs to published.
    /** @var \Drupal\comment\CommentInterface $comment */
    $comment->setPublished();

    return $comment;
  }

  /**
   * {@inheritdoc}
   */
  public function validateReferenceableNewEntities(array $entities) {
    $entities = parent::validateReferenceableNewEntities($entities);
    // Mirror the conditions checked in buildEntityQuery().
    if (!$this->currentUser->hasPermission('administer comments')) {
      $entities = array_filter($entities, function ($comment) {
        /** @var \Drupal\comment\CommentInterface $comment */
        return $comment->isPublished();
      });
    }
    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function entityQueryAlter(SelectInterface $query) {
    parent::entityQueryAlter($query);

    $tables = $query->getTables();
    $data_table = 'comment_field_data';
    if (!isset($tables['comment_field_data']['alias'])) {
      // If no conditions join against the comment data table, it should be
      // joined manually to allow node access processing.
      $query->innerJoin($data_table, NULL, "[base_table].[cid] = [$data_table].[cid] AND [$data_table].[default_langcode] = 1");
    }

    // The Comment module doesn't implement any proper comment access,
    // and as a consequence doesn't make sure that comments cannot be viewed
    // when the user doesn't have access to the node.
    $node_alias = $query->innerJoin('node_field_data', 'n', "[%alias].[nid] = [$data_table].[entity_id] AND [$data_table].[entity_type] = 'node'");
    // Pass the query to the node access control.
    $this->reAlterQuery($query, 'node_access', $node_alias);

    // Passing the query to node_query_node_access_alter() is sadly
    // insufficient for nodes.
    // @see \Drupal\node\Plugin\EntityReferenceSelection\NodeSelection::buildEntityQuery()
    if (!$this->currentUser->hasPermission('bypass node access') && !$this->moduleHandler->hasImplementations('node_grants')) {
      $query->condition($node_alias . '.status', 1);
    }
  }

}
