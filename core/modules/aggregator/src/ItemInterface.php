<?php

namespace Drupal\aggregator;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining an aggregator item entity.
 */
interface ItemInterface extends ContentEntityInterface {

  /**
   * Returns the feed id of aggregator item.
   *
   * @return int
   *   The feed id.
   */
  public function getFeedId();

  /**
   * Sets the feed id of aggregator item.
   *
   * @param int $fid
   *   The feed id.
   *
   * @return $this
   *   The called feed item entity.
   */
  public function setFeedId($fid);

  /**
   * Returns the title of the feed item.
   *
   * @return string
   *   The title of the feed item.
   */
  public function getTitle();

  /**
   * Sets the title of the feed item.
   *
   * @param string $title
   *   The title of the feed item.
   *
   * @return $this
   *   The called feed item entity.
   */
  public function setTitle($title);

  /**
   * Returns the link to the feed item.
   *
   * @return string
   *   The link to the feed item.
   */
  public function getLink();

  /**
   * Sets the link to the feed item.
   *
   * @param string $link
   *   The link to the feed item.
   *
   * @return $this
   *   The called feed item entity.
   */
  public function setLink($link);

  /**
   * Returns the author of the feed item.
   *
   * @return string
   *   The author of the feed item.
   */
  public function getAuthor();

  /**
   * Sets the author of the feed item.
   *
   * @param string $author
   *   The author name of the feed item.
   *
   * @return $this
   *   The called feed item entity.
   */
  public function setAuthor($author);

  /**
   * Returns the body of the feed item.
   *
   * @return string
   *   The body of the feed item.
   */
  public function getDescription();

  /**
   * Sets the body of the feed item.
   *
   * @param string $description
   *   The body of the feed item.
   *
   * @return $this
   *   The called feed item entity.
   */
  public function setDescription($description);

  /**
   * Returns the posted date of the feed item, as a Unix timestamp.
   *
   * @return int
   *   The posted date of the feed item, as a Unix timestamp.
   */
  public function getPostedTime();

  /**
   * Sets the posted date of the feed item, as a Unix timestamp.
   *
   * @param int $timestamp
   *   The posted date of the feed item, as a Unix timestamp.
   *
   * @return $this
   *   The called feed item entity.
   */
  public function setPostedTime($timestamp);

  /**
   * Returns the unique identifier for the feed item.
   *
   * @return string
   *   The unique identifier for the feed item.
   */
  public function getGuid();

  /**
   * Sets the unique identifier for the feed item.
   *
   * @param string $guid
   *   The unique identifier for the feed item.
   *
   * @return $this
   *   The called feed item entity.
   */
  public function setGuid($guid);

}
