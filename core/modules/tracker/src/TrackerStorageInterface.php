<?php

namespace Drupal\tracker;

use Drupal\node\NodeInterface;

/**
 * Defines the tracker storage interface.
 */
interface TrackerStorageInterface {

  /**
   * Updates indexing tables when a node is added, updated, or commented on.
   *
   * @param int $nid
   *   A node ID.
   * @param int $uid
   *   The node or comment author.
   * @param int $changed
   *   The node updated timestamp or comment timestamp.
   */
  public function add($nid, $uid, $changed);

  /**
   * Cleans up indexed data when nodes or comments are removed.
   *
   * @param int $nid
   *   The node ID.
   * @param int $uid
   *   The author of the node or comment.
   * @param int $changed
   *   The last changed timestamp of the node.
   */
  public function remove($nid, $uid = NULL, $changed = NULL);

  /**
   * Deletes tracking information for a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity.
   */
  public function removeNode(NodeInterface $node);

  /**
   * Picks the most recent timestamp between node changed and the last comment.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity.
   *
   * @return int
   *   The node changed timestamp, or most recent comment timestamp, whichever is
   *   the greatest.
   *
   * @todo Check if we should introduce 'language context' here, because the
   *   callers may need different timestamps depending on the users' language?
   */
  public function calculateChanged($node);

  /**
   * Updates tracking information for any items still to be tracked.
   *
   * The state 'tracker.index_nid' is set to
   * ((the last node ID that was indexed) - 1) and used to select the nodes to
   * be processed. If there are no remaining nodes to process,
   * 'tracker.index_nid' will be 0. This process does not run regularly on live
   * sites, rather it updates tracking info once on an existing site just after
   * the tracker module was installed.
   */
  public function updateAll();

}
