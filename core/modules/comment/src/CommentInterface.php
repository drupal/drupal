<?php

namespace Drupal\comment;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a comment entity.
 */
interface CommentInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Comment is awaiting approval.
   */
  const NOT_PUBLISHED = 0;

  /**
   * Comment is published.
   */
  const PUBLISHED = 1;

  /**
   * Determines if this comment is a reply to another comment.
   *
   * @return bool
   *   TRUE if the comment has a parent comment otherwise FALSE.
   */
  public function hasParentComment();

  /**
   * Returns the parent comment entity if this is a reply to a comment.
   *
   * @return \Drupal\comment\CommentInterface|NULL
   *   A comment entity of the parent comment or NULL if there is no parent.
   */
  public function getParentComment();

  /**
   * Returns the entity to which the comment is attached.
   *
   * @return \Drupal\Core\Entity\FieldableEntityInterface
   *   The entity on which the comment is attached.
   */
  public function getCommentedEntity();

  /**
   * Returns the ID of the entity to which the comment is attached.
   *
   * @return int
   *   The ID of the entity to which the comment is attached.
   */
  public function getCommentedEntityId();

  /**
   * Returns the type of the entity to which the comment is attached.
   *
   * @return string
   *   An entity type.
   */
  public function getCommentedEntityTypeId();

  /**
   * Sets the field ID for which this comment is attached.
   *
   * @param string $field_name
   *   The field name through which the comment was added.
   *
   * @return $this
   *   The class instance that this method is called on.
   */
  public function setFieldName($field_name);

  /**
   * Returns the name of the field the comment is attached to.
   *
   * @return string
   *   The name of the field the comment is attached to.
   */
  public function getFieldName();

  /**
   * Returns the subject of the comment.
   *
   * @return string
   *   The subject of the comment.
   */
  public function getSubject();

  /**
   * Sets the subject of the comment.
   *
   * @param string $subject
   *   The subject of the comment.
   *
   * @return $this
   *   The class instance that this method is called on.
   */
  public function setSubject($subject);

  /**
   * Returns the comment author's name.
   *
   * For anonymous authors, this is the value as typed in the comment form.
   *
   * @return string
   *   The name of the comment author.
   */
  public function getAuthorName();

  /**
   * Sets the name of the author of the comment.
   *
   * @param string $name
   *   A string containing the name of the author.
   *
   * @return $this
   *   The class instance that this method is called on.
   */
  public function setAuthorName($name);

  /**
   * Returns the comment author's email address.
   *
   * For anonymous authors, this is the value as typed in the comment form.
   *
   * @return string
   *   The email address of the author of the comment.
   */
  public function getAuthorEmail();

  /**
   * Returns the comment author's home page address.
   *
   * For anonymous authors, this is the value as typed in the comment form.
   *
   * @return string
   *   The homepage address of the author of the comment.
   */
  public function getHomepage();

  /**
   * Sets the comment author's home page address.
   *
   * For anonymous authors, this is the value as typed in the comment form.
   *
   * @param string $homepage
   *   The homepage address of the author of the comment.
   *
   * @return $this
   *   The class instance that this method is called on.
   */
  public function setHomepage($homepage);

  /**
   * Returns the comment author's hostname.
   *
   * @return string
   *   The hostname of the author of the comment.
   */
  public function getHostname();

  /**
   * Sets the hostname of the author of the comment.
   *
   * @param string $hostname
   *   The hostname of the author of the comment.
   *
   * @return $this
   *   The class instance that this method is called on.
   */
  public function setHostname($hostname);

  /**
   * Returns the time that the comment was created.
   *
   * @return int
   *   The timestamp of when the comment was created.
   */
  public function getCreatedTime();

  /**
   * Sets the creation date of the comment.
   *
   * @param int $created
   *   The timestamp of when the comment was created.
   *
   * @return $this
   *   The class instance that this method is called on.
   */
  public function setCreatedTime($created);

  /**
   * Checks if the comment is published.
   *
   * @return bool
   *   TRUE if the comment is published.
   */
  public function isPublished();

  /**
   * Returns the comment's status.
   *
   * @return int
   *   One of CommentInterface::PUBLISHED or CommentInterface::NOT_PUBLISHED
   */
  public function getStatus();

  /**
   * Sets the published status of the comment entity.
   *
   * @param bool $status
   *   Set to TRUE to publish the comment, FALSE to unpublish.
   *
   * @return \Drupal\comment\CommentInterface
   *   The class instance that this method is called on.
   */
  public function setPublished($status);

  /**
   * Returns the alphadecimal representation of the comment's place in a thread.
   *
   * @return string
   *   The alphadecimal representation of the comment's place in a thread.
   */
  public function getThread();

  /**
   * Sets the alphadecimal representation of the comment's place in a thread.
   *
   * @param string $thread
   *   The alphadecimal representation of the comment's place in a thread.
   *
   * @return $this
   *   The class instance that this method is called on.
   */
  public function setThread($thread);

  /**
   * Returns the permalink URL for this comment.
   *
   * @return \Drupal\Core\Url
   */
  public function permalink();

  /**
   * Get the comment type id for this comment.
   *
   * @return string
   *   The id of the comment type.
   */
  public function getTypeId();

}
