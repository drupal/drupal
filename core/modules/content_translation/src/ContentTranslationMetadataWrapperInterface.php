<?php

/**
 * @file
 * Contains \Drupal\content_translation\ContentTranslationMetadataInterface.
 */

namespace Drupal\content_translation;

use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\user\UserInterface;

/**
 * Common interface for content translation metadata wrappers.
 *
 * This acts as a wrapper for an entity translation object, encapsulating the
 * logic needed to retrieve translation metadata.
 */
interface ContentTranslationMetadataWrapperInterface extends EntityChangedInterface {

  /**
   * Retrieves the source language for this translation.
   *
   * @return string
   *   The source language code.
   */
  public function getSource();

  /**
   * Sets the source language for this translation.
   *
   * @param string $source
   *   The source language code.
   *
   * @return $this
   */
  public function setSource($source);

  /**
   * Returns the translation outdated status.
   *
   * @return bool
   *   TRUE if the translation is outdated, FALSE otherwise.
   */
  public function isOutdated();

  /**
   * Sets the translation outdated status.
   *
   * @param bool $outdated
   *   TRUE if the translation is outdated, FALSE otherwise.
   *
   * @return $this
   */
  public function setOutdated($outdated);

  /**
   * Returns the translation author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the translation author.
   */
  public function getAuthor();

  /**
   * Sets the translation author.
   *
   * @param \Drupal\user\UserInterface $account
   *   The translation author user entity.
   *
   * @return $this
   */
  public function setAuthor(UserInterface $account);

  /**
   * Returns the translation published status.
   *
   * @return bool
   *   TRUE if the translation is published, FALSE otherwise.
   */
  public function isPublished();

  /**
   * Sets the translation published status.
   *
   * @param bool $published
   *   TRUE if the translation is published, FALSE otherwise.
   *
   * @return $this
   */
  public function setPublished($published);

  /**
   * Returns the translation creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when the translation was created.
   */
  public function getCreatedTime();

  /**
   * Sets the translation creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when the translation was created.
   *
   * @return $this
   */
  public function setCreatedTime($timestamp);

  /**
   * Sets the translation modification timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when the translation was last modified.
   *
   * @return $this
   */
  public function setChangedTime($timestamp);

}
