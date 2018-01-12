<?php

namespace Drupal\layout_builder;

/**
 * Defines the interface for an object that stores layout sections.
 *
 * @internal
 *   Layout Builder is currently experimental and should only be leveraged by
 *   experimental modules and development releases of contributed modules.
 *   See https://www.drupal.org/core/experimental for more information.
 *
 * @see \Drupal\layout_builder\Section
 */
interface SectionStorageInterface extends \Countable {

  /**
   * Gets the layout sections.
   *
   * @return \Drupal\layout_builder\Section[]
   *   An array of sections.
   */
  public function getSections();

  /**
   * Gets a domain object for the layout section.
   *
   * @param int $delta
   *   The delta of the section.
   *
   * @return \Drupal\layout_builder\Section
   *   The layout section.
   */
  public function getSection($delta);

  /**
   * Appends a new section to the end of the list.
   *
   * @param \Drupal\layout_builder\Section $section
   *   The section to append.
   *
   * @return $this
   */
  public function appendSection(Section $section);

  /**
   * Inserts a new section at a given delta.
   *
   * If a section exists at the given index, the section at that position and
   * others after it are shifted backward.
   *
   * @param int $delta
   *   The delta of the section.
   * @param \Drupal\layout_builder\Section $section
   *   The section to insert.
   *
   * @return $this
   */
  public function insertSection($delta, Section $section);

  /**
   * Removes the section at the given delta.
   *
   * @param int $delta
   *   The delta of the section.
   *
   * @return $this
   */
  public function removeSection($delta);

  /**
   * Provides any available contexts for the object using the sections.
   *
   * @return \Drupal\Core\Plugin\Context\ContextInterface[]
   *   The array of context objects.
   */
  public function getContexts();

  /**
   * Returns an identifier for this storage.
   *
   * @return string
   *   The unique identifier for this storage.
   */
  public function getStorageId();

  /**
   * Returns the type of this storage.
   *
   * Used in conjunction with the storage ID.
   *
   * @return string
   *   The type of storage.
   */
  public function getStorageType();

  /**
   * Gets the label for the object using the sections.
   *
   * @return string
   *   The label, or NULL if there is no label defined.
   */
  public function label();

  /**
   * Saves the sections.
   *
   * @return int
   *   SAVED_NEW or SAVED_UPDATED is returned depending on the operation
   *   performed.
   */
  public function save();

  /**
   * Returns a URL for viewing the object using the sections.
   *
   * @return \Drupal\Core\Url
   *   The URL object.
   */
  public function getCanonicalUrl();

  /**
   * Returns a URL to edit the sections in the Layout Builder UI.
   *
   * @return \Drupal\Core\Url
   *   The URL object.
   */
  public function getLayoutBuilderUrl();

}
