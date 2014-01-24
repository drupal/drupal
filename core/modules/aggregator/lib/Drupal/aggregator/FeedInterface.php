<?php

/**
 * @file
 * Contains \Drupal\aggregator\Entity\FeedInterface.
 */

namespace Drupal\aggregator;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining an aggregator feed entity.
 */
interface FeedInterface extends ContentEntityInterface {

  /**
   * Sets the title of the feed.
   *
   * @param string $title
   *   The short title of the feed.
   *
   * @return \Drupal\aggregator\FeedInterface
   *   The class instance that this method is called on.
   */
  public function setTitle($title);

  /**
   * Returns the url to the feed.
   *
   * @return string
   *   The url to the feed.
   */
  public function getUrl();

  /**
   * Sets the url to the feed.
   *
   * @param string $url
   *   A string containing the url of the feed.
   *
   * @return \Drupal\aggregator\FeedInterface
   *   The class instance that this method is called on.
   */
  public function setUrl($url);

  /**
   * Returns the refresh rate of the feed in seconds.
   *
   * @return int
   *   The refresh rate of the feed in seconds.
   */
  public function getRefreshRate();

  /**
   * Sets the refresh rate of the feed in seconds.
   *
   * @param int $refresh
   *   The refresh rate of the feed in seconds.
   *
   * @return \Drupal\aggregator\FeedInterface
   *   The class instance that this method is called on.
   */
  public function setRefreshRate($refresh);

  /**
   * Returns the last time where the feed was checked for new items.
   *
   * @return int
   *   The timestamp when new items were last checked for.
   */
  public function getLastCheckedTime();

  /**
   * Sets the time when this feed was queued for refresh, 0 if not queued.
   *
   * @param int $checked
   *   The timestamp of the last refresh.
   *
   * @return \Drupal\aggregator\FeedInterface
   *   The class instance that this method is called on.
   */
  public function setLastCheckedTime($checked);

  /**
   * Returns the time when this feed was queued for refresh, 0 if not queued.
   *
   * @return int
   *   The timestamp of the last refresh.
   */
  public function getQueuedTime();

  /**
   * Sets the time when this feed was queued for refresh, 0 if not queued.
   *
   * @param int $queued
   *   The timestamp of the last refresh.
   *
   * @return \Drupal\aggregator\FeedInterface
   *   The class instance that this method is called on.
   */
  public function setQueuedTime($queued);

  /**
   * Returns the parent website of the feed.
   *
   * @return string
   *   The parent website of the feed.
   */
  public function getWebsiteUrl();

  /**
   * Sets the parent website of the feed.
   *
   * @param string $link
   *   A string containing the parent website of the feed.
   *
   * @return \Drupal\aggregator\FeedInterface
   *   The class instance that this method is called on.
   */
  public function setWebsiteUrl($link);

  /**
   * Returns the description of the feed.
   *
   * @return string
   *   The description of the feed.
   */
  public function getDescription();

  /**
   * Sets the description of the feed.
   *
   * @param string $description
   *   The description of the feed.
   *
   * @return \Drupal\aggregator\FeedInterface
   *   The class instance that this method is called on.
   */
  public function setDescription($description);

  /**
   * Returns the primary image attached to the feed.
   *
   * @return string
   *   The URL of the primary image attached to the feed.
   */
  public function getImage();

  /**
   * Sets the primary image attached to the feed.
   *
   * @param string $image
   *   An image URL.
   *
   * @return \Drupal\aggregator\FeedInterface
   *   The class instance that this method is called on.
   */
  public function setImage($image);

  /**
   * Returns the calculated hash of the feed data, used for validating cache.
   *
   * @return string
   *   The calculated hash of the feed data.
   */
  public function getHash();

  /**
   * Sets the calculated hash of the feed data, used for validating cache.
   *
   * @param string $hash
   *   A string containing the calculated hash of the feed.
   *
   * @return \Drupal\aggregator\FeedInterface
   *   The class instance that this method is called on.
   */
  public function setHash($hash);

  /**
   * Returns the entity tag HTTP response header, used for validating cache.
   *
   * @return string
   *   The entity tag HTTP response header.
   */
  public function getEtag();

  /**
   * Sets the entity tag HTTP response header, used for validating cache.
   *
   * @param string $etag
   *   A string containing the entity tag HTTP response header.
   *
   * @return \Drupal\aggregator\FeedInterface
   *   The class instance that this method is called on.
   */
  public function setEtag($etag);

  /**
   * Return when the feed was modified last time.
   *
   * @return int
   *   The timestamp of the last time the feed was modified.
   */
  public function getLastModified();

  /**
   * Sets the last modification of the feed.
   *
   * @param int $modified
   *   The timestamp when the feed was modified.
   *
   * @return \Drupal\aggregator\FeedInterface
   *   The class instance that this method is called on.
   */
  public function setLastModified($modified);

  /**
   * Removes all items from a feed.
   *
   * @return \Drupal\aggregator\FeedInterface
   *   The class instance that this method is called on.
   */
  public function removeItems();

}
