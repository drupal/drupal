<?php

namespace Drupal\node;

use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining a node entity.
 */
interface NodeInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface, RevisionLogInterface, EntityPublishedInterface {

  /**
   * Denotes that the node is not published.
   */
  const NOT_PUBLISHED = 0;

  /**
   * Denotes that the node is published.
   */
  const PUBLISHED = 1;

  /**
   * Denotes that the node is not promoted to the front page.
   */
  const NOT_PROMOTED = 0;

  /**
   * Denotes that the node is promoted to the front page.
   */
  const PROMOTED = 1;

  /**
   * Denotes that the node is not sticky at the top of the page.
   */
  const NOT_STICKY = 0;

  /**
   * Denotes that the node is sticky at the top of the page.
   */
  const STICKY = 1;

  /**
   * Gets the node type.
   *
   * @return string
   *   The node type.
   */
  public function getType();

  /**
   * Gets the node title.
   *
   * @return string
   *   Title of the node.
   */
  public function getTitle();

  /**
   * Sets the node title.
   *
   * @param string $title
   *   The node title.
   *
   * @return $this
   *   The called node entity.
   */
  public function setTitle($title);

  /**
   * Gets the node creation timestamp.
   *
   * @return int
   *   Creation timestamp of the node.
   */
  public function getCreatedTime();

  /**
   * Sets the node creation timestamp.
   *
   * @param int $timestamp
   *   The node creation timestamp.
   *
   * @return $this
   *   The called node entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the node promotion status.
   *
   * @return bool
   *   TRUE if the node is promoted.
   */
  public function isPromoted();

  /**
   * Sets the node promoted status.
   *
   * @param bool $promoted
   *   TRUE to set this node to promoted, FALSE to set it to not promoted.
   *
   * @return $this
   *   The called node entity.
   */
  public function setPromoted($promoted);

  /**
   * Returns the node sticky status.
   *
   * @return bool
   *   TRUE if the node is sticky.
   */
  public function isSticky();

  /**
   * Sets the node sticky status.
   *
   * @param bool $sticky
   *   TRUE to set this node to sticky, FALSE to set it to not sticky.
   *
   * @return $this
   *   The called node entity.
   */
  public function setSticky($sticky);

  /**
   * Gets the node revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the node revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return $this
   *   The called node entity.
   */
  public function setRevisionCreationTime($timestamp);

}
