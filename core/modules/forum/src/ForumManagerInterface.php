<?php

namespace Drupal\forum;

use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

/**
 * Provides forum manager interface.
 */
interface ForumManagerInterface {

  /**
   * Gets list of forum topics.
   *
   * @param int $tid
   *   Term ID.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Account to fetch topics for.
   *
   * @return array
   *   Array with keys 'topics' and 'header'.
   */
  public function getTopics($tid, AccountInterface $account);

  /**
   * Utility method to fetch the child forums for a given forum.
   *
   * @param int $vid
   *   The forum vocabulary ID.
   * @param int $tid
   *   The forum ID to fetch the children for.
   *
   * @return array
   *   Array of children.
   */
  public function getChildren($vid, $tid);

  /**
   * Generates and returns the forum index.
   *
   * The forum index is a pseudo term that provides an overview of all forums.
   *
   * @return \Drupal\taxonomy\TermInterface
   *   A pseudo term representing the overview of all forums.
   */
  public function getIndex();

  /**
   * Resets the ForumManager index and history.
   */
  public function resetCache();

  /**
   * Fetches the parent forums for a given forum.
   *
   * @param int $tid
   *   Term ID.
   *
   * @return array
   *   Array of parent terms.
   *
   * @deprecated in drupal:8.1.0 and is removed from drupal:9.0.0. Call
   * loadAllParents() on taxonomy term storage directly.
   *
   * @see https://www.drupal.org/node/3069599
   */
  public function getParents($tid);

  /**
   * Checks whether a node can be used in a forum, based on its content type.
   *
   * @param \Drupal\node\NodeInterface $node
   *   A node entity.
   *
   * @return bool
   *   Boolean indicating if the node can be assigned to a forum.
   */
  public function checkNodeType(NodeInterface $node);

  /**
   * Calculates the number of new posts in a forum that the user has not yet read.
   *
   * Nodes are new if they are newer than HISTORY_READ_LIMIT.
   *
   * @param int $term
   *   The term ID of the forum.
   * @param int $uid
   *   The user ID.
   *
   * @return
   *   The number of new posts in the forum that have not been read by the user.
   */
  public function unreadTopics($term, $uid);

}
