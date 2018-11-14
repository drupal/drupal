<?php

namespace Drupal\layout_builder\SectionStorage;

use Drupal\layout_builder\Section;

/**
 * Provides a trait for storing sections on an object.
 *
 * @internal
 *   Layout Builder is currently experimental and should only be leveraged by
 *   experimental modules and development releases of contributed modules.
 *   See https://www.drupal.org/core/experimental for more information.
 */
trait SectionStorageTrait {

  /**
   * Stores the information for all sections.
   *
   * Implementations of this method are expected to call array_values() to rekey
   * the list of sections.
   *
   * @param \Drupal\layout_builder\Section[] $sections
   *   An array of section objects.
   *
   * @return $this
   */
  abstract protected function setSections(array $sections);

  /**
   * {@inheritdoc}
   */
  public function count() {
    return count($this->getSections());
  }

  /**
   * {@inheritdoc}
   */
  public function getSection($delta) {
    if (!$this->hasSection($delta)) {
      throw new \OutOfBoundsException(sprintf('Invalid delta "%s"', $delta));
    }

    return $this->getSections()[$delta];
  }

  /**
   * Sets the section for the given delta on the display.
   *
   * @param int $delta
   *   The delta of the section.
   * @param \Drupal\layout_builder\Section $section
   *   The layout section.
   *
   * @return $this
   */
  protected function setSection($delta, Section $section) {
    $sections = $this->getSections();
    $sections[$delta] = $section;
    $this->setSections($sections);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function appendSection(Section $section) {
    $delta = $this->count();

    $this->setSection($delta, $section);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function insertSection($delta, Section $section) {
    if ($this->hasSection($delta)) {
      // @todo Use https://www.drupal.org/node/66183 once resolved.
      $start = array_slice($this->getSections(), 0, $delta);
      $end = array_slice($this->getSections(), $delta);
      $this->setSections(array_merge($start, [$section], $end));
    }
    else {
      $this->appendSection($section);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeSection($delta) {
    $sections = $this->getSections();
    unset($sections[$delta]);
    $this->setSections($sections);
    return $this;
  }

  /**
   * Indicates if there is a section at the specified delta.
   *
   * @param int $delta
   *   The delta of the section.
   *
   * @return bool
   *   TRUE if there is a section for this delta, FALSE otherwise.
   */
  protected function hasSection($delta) {
    return isset($this->getSections()[$delta]);
  }

  /**
   * Magic method: Implements a deep clone.
   */
  public function __clone() {
    $sections = $this->getSections();

    foreach ($sections as $delta => $item) {
      $sections[$delta] = clone $item;
    }

    $this->setSections($sections);
  }

}
