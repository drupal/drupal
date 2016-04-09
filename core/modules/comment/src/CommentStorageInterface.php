<?php

namespace Drupal\comment;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Entity\FieldableEntityInterface;

/**
 * Defines an interface for comment entity storage classes.
 */
interface CommentStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets the maximum encoded thread value for the top level comments.
   *
   * @param \Drupal\comment\CommentInterface $comment
   *   A comment entity.
   *
   * @return string
   *   The maximum encoded thread value among the top level comments of the
   *   node $comment belongs to.
   */
  public function getMaxThread(CommentInterface $comment);

  /**
   * Gets the maximum encoded thread value for the children of this comment.
   *
   * @param \Drupal\comment\CommentInterface $comment
   *   A comment entity.
   *
   * @return string
   *   The maximum encoded thread value among all replies of $comment.
   */
  public function getMaxThreadPerThread(CommentInterface $comment);

  /**
   * Calculates the page number for the first new comment.
   *
   * @param int $total_comments
   *   The total number of comments that the entity has.
   * @param int $new_comments
   *   The number of new comments that the entity has.
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity to which the comments belong.
   * @param string $field_name
   *   The field name on the entity to which comments are attached.
   *
   * @return array|null
   *   The page number where first new comment appears. (First page returns 0.)
   */
  public function getNewCommentPageNumber($total_comments, $new_comments, FieldableEntityInterface $entity, $field_name);

  /**
   * Gets the display ordinal or page number for a comment.
   *
   * @param \Drupal\comment\CommentInterface $comment
   *   The comment to use as a reference point.
   * @param int $comment_mode
   *   The comment display mode: CommentManagerInterface::COMMENT_MODE_FLAT or
   *   CommentManagerInterface::COMMENT_MODE_THREADED.
   * @param int $divisor
   *   Defaults to 1, which returns the display ordinal for a comment. If the
   *   number of comments per page is provided, the returned value will be the
   *   page number. (The return value will be divided by $divisor.)
   *
   * @return int
   *   The display ordinal or page number for the comment. It is 0-based, so
   *   will represent the number of items before the given comment/page.
   */
  public function getDisplayOrdinal(CommentInterface $comment, $comment_mode, $divisor = 1);

  /**
   * Gets the comment ids of the passed comment entities' children.
   *
   * @param \Drupal\comment\CommentInterface[] $comments
   *   An array of comment entities keyed by their ids.
   * @return array
   *   The entity ids of the passed comment entities' children as an array.
   */
  public function getChildCids(array $comments);

  /**
   * Retrieves comments for a thread, sorted in an order suitable for display.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity whose comment(s) needs rendering.
   * @param string $field_name
   *   The field_name whose comment(s) needs rendering.
   * @param int $mode
   *   The comment display mode: CommentManagerInterface::COMMENT_MODE_FLAT or
   *   CommentManagerInterface::COMMENT_MODE_THREADED.
   * @param int $comments_per_page
   *   (optional) The amount of comments to display per page.
   *   Defaults to 0, which means show all comments.
   * @param int $pager_id
   *   (optional) Pager id to use in case of multiple pagers on the one page.
   *   Defaults to 0; is only used when $comments_per_page is greater than zero.
   *
   * @return array
   *   Ordered array of comment objects, keyed by comment id.
   */
  public function loadThread(EntityInterface $entity, $field_name, $mode, $comments_per_page = 0, $pager_id = 0);

  /**
   * Returns the number of unapproved comments.
   *
   * @return int
   *   The number of unapproved comments.
   */
  public function getUnapprovedCount();

}
