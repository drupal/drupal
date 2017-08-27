<?php

namespace Drupal\media;

use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining an entity for media items.
 */
interface MediaInterface extends ContentEntityInterface, EntityChangedInterface, RevisionLogInterface, EntityOwnerInterface, EntityPublishedInterface {

  /**
   * Gets the media item name.
   *
   * @return string
   *   The name of the media item.
   */
  public function getName();

  /**
   * Sets the media item name.
   *
   * @param string $name
   *   The name of the media item.
   *
   * @return $this
   */
  public function setName($name);

  /**
   * Returns the media item creation timestamp.
   *
   * @todo Remove and use the new interface when #2833378 is done.
   * @see https://www.drupal.org/node/2833378
   *
   * @return int
   *   Creation timestamp of the media item.
   */
  public function getCreatedTime();

  /**
   * Sets the media item creation timestamp.
   *
   * @todo Remove and use the new interface when #2833378 is done.
   * @see https://www.drupal.org/node/2833378
   *
   * @param int $timestamp
   *   The media creation timestamp.
   *
   * @return \Drupal\media\MediaInterface
   *   The called media item.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the media source.
   *
   * @return \Drupal\media\MediaSourceInterface
   *   The media source.
   */
  public function getSource();

}
