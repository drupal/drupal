<?php
/**
 * @file
 * Contains \Drupal\comment\CommentStatisticsInterface.
 */

namespace Drupal\comment;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides an interface for storing and retrieving comment statistics.
 */
interface CommentStatisticsInterface {

  /**
   * Returns an array of ranking information for hook_ranking().
   *
   * @return array
   *   Array of ranking information as expected by hook_ranking().
   *
   * @see hook_ranking()
   * @see comment_ranking()
   */
  public function getRankingInfo();

  /**
   * Read comment statistics records for an array of entities.
   *
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   *   Array of entities on which commenting is enabled, keyed by id
   * @param string $entity_type
   *   The entity type of the passed entities.
   *
   * @return object[]
   *   Array of statistics records keyed by entity id.
   */
  public function read($entities, $entity_type);

  /**
   * Delete comment statistics records for an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which comment statistics should be deleted.
   */
  public function delete(EntityInterface $entity);

  /**
   * Update or insert comment statistics records after a comment is added.
   *
   * @param \Drupal\comment\CommentInterface $comment
   *   The comment added or updated.
   */
  public function update(CommentInterface $comment);

  /**
   * Find the maximum number of comments for the given entity type.
   *
   * Used to influence search rankings.
   *
   * @param string $entity_type
   *   The entity type to consider when fetching the maximum comment count for.
   *
   * @return int
   *   The maximum number of comments for and entity of the given type.
   *
   * @see comment_update_index()
   */
  public function getMaximumCount($entity_type);

  /**
   * Insert an empty record for the given entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The created entity for which a statistics record is to be initialized.
   * @param array $fields
   *   Array of comment field definitions for the given entity.
   */
  public function create(ContentEntityInterface $entity, $fields);

}
