<?php

namespace Drupal\comment;

use Drupal\Core\Entity\EntityInterface;

/**
 * Comment manager contains common functions to manage comment fields.
 */
interface CommentManagerInterface {

  /**
   * Comments are displayed in a flat list - expanded.
   */
  const COMMENT_MODE_FLAT = 0;

  /**
   * Comments are displayed as a threaded list - expanded.
   */
  const COMMENT_MODE_THREADED = 1;

  /**
   * Utility function to return an array of comment fields.
   *
   * @param string $entity_type_id
   *   The content entity type to which the comment fields are attached.
   *
   * @return array
   *   An array of comment field map definitions, keyed by field name. Each
   *   value is an array with two entries:
   *   - type: The field type.
   *   - bundles: The bundles in which the field appears, as an array with entity
   *     types as keys and the array of bundle names as values.
   */
  public function getFields($entity_type_id);

  /**
   * Creates a comment_body field.
   *
   * @param string $comment_type
   *   The comment bundle.
   */
  public function addBodyField($comment_type);

  /**
   * Provides a message if posting comments is forbidden.
   *
   * If authenticated users can post comments, a message is returned that
   * prompts the anonymous user to log in (or register, if applicable) that
   * redirects to entity comment form. Otherwise, no message is returned.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to which comments are attached to.
   * @param string $field_name
   *   The field name on the entity to which comments are attached to.
   *
   * @return string
   *   HTML for a "you can't post comments" notice.
   */
  public function forbiddenMessage(EntityInterface $entity, $field_name);

  /**
   * Returns the number of new comments available on a given entity for a user.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to which the comments are attached to.
   * @param string $field_name
   *   (optional) The field_name to count comments for. Defaults to any field.
   * @param int $timestamp
   *   (optional) Time to count from. Defaults to time of last user access the
   *   entity.
   *
   * @return int|false
   *   The number of new comments or FALSE if the user is not authenticated.
   */
  public function getCountNewComments(EntityInterface $entity, $field_name = NULL, $timestamp = 0);

}
