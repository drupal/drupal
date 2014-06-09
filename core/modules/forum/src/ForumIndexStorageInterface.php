<?php
/**
 * @file
 * Contains
 */
namespace Drupal\forum;

use Drupal\node\NodeInterface;


/**
 * Handles CRUD operations to {forum_index} table.
 */
interface ForumIndexStorageInterface {

  /**
   * Returns the forum term id associated with an existing forum node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The existing forum node.
   *
   * @return int
   *   The forum term id currently associated with the node.
   */
  public function getOriginalTermId(NodeInterface $node);

  /**
   * Creates a record in {forum} table for the given node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node for which the record is to be created.
   */
  public function create(NodeInterface $node);

  /**
   * Reads an array of {forum} records for the given revision ids.
   *
   * @param array $vids
   *   An array of node revision ids.
   *
   * @return \Drupal\Core\Database\StatementInterface
   *   The records from {forum} for the given vids.
   */
  public function read(array $vids);

  /**
   * Updates the {forum} table for the given node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node for which the record is to be updated.
   */
  public function update(NodeInterface $node);

  /**
   * Deletes the records in {forum} table for the given node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node for which the records are to be deleted.
   */
  public function delete(NodeInterface $node);

  /**
   * Deletes the records in {forum} table for a given node revision.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node revision for which the records are to be deleted.
   */
  public function deleteRevision(NodeInterface $node);

  /**
   * Creates a {forum_index} entry for the given node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node for which the index records are to be created.
   */
  public function createIndex(NodeInterface $node);

  /**
   * Updates the {forum_index} records for a given node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node for which the index records are to be updated.
   */
  public function updateIndex(NodeInterface $node);

  /**
   * Deletes the {forum_index} records for a given node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node for which the index records are to be deleted.
   */
  public function deleteIndex(NodeInterface $node);

}
