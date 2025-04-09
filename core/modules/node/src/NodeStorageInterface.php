<?php

namespace Drupal\node;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an interface for node entity storage classes.
 */
interface NodeStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of node revision IDs for a specific node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity.
   *
   * @return int[]
   *   Node revision IDs (in ascending order).
   */
  public function revisionIds(NodeInterface $node);

  /**
   * Gets a list of revision IDs having a given user as node author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Node revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(NodeInterface $node);

  /**
   * Updates all nodes of one type to be of another type.
   *
   * @param string $old_type
   *   The current node type of the nodes.
   * @param string $new_type
   *   The new node type of the nodes.
   *
   * @return int
   *   The number of nodes whose node type field was modified.
   *
   * @deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. There is no
   *   replacement.
   *
   * @see https://www.drupal.org/node/3515214
   */
  public function updateType($old_type, $new_type);

  /**
   * Unsets the language for all nodes with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
