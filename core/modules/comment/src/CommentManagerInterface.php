<?php

/**
 * @file
 * Contains \Drupal\comment\CommentManagerInterface.
 */

namespace Drupal\comment;
use Drupal\Core\Entity\EntityInterface;

use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;

/**
 * Comment manager contains common functions to manage comment fields.
 */
interface CommentManagerInterface {

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
   *
   * @see \Drupal\Core\Entity\EntityManagerInterface::getFieldMap()
   */
  public function getFields($entity_type_id);

  /**
   * Utility function to return all comment fields.
   */
  public function getAllFields();

  /**
   * Utility method to add the default comment field to an entity.
   *
   * Attaches a comment field named 'comment' to the given entity type and
   * bundle. Largely replicates the default behavior in Drupal 7 and earlier.
   *
   * @param string $entity_type
   *   The entity type to attach the default comment field to.
   * @param string $bundle
   *   The bundle to attach the default comment field instance to.
   * @param string $field_name
   *   (optional) Field name to use for the comment field. Defaults to
   *     'comment'.
   * @param int $default_value
   *   (optional) Default value, one of CommentItemInterface::HIDDEN,
   *   CommentItemInterface::OPEN, CommentItemInterface::CLOSED. Defaults to
   *   CommentItemInterface::OPEN.
   * @param string $comment_type_id
   *   (optional) ID of comment type to use. Defaults to 'comment'.
   */
  public function addDefaultField($entity_type, $bundle, $field_name = 'comment', $default_value = CommentItemInterface::OPEN, $comment_type_id = 'comment');

  /**
   * Creates a comment_body field instance.
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

}
