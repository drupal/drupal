<?php

namespace Drupal\layout_builder\SectionStorage;

use Drupal\layout_builder\Section;

/**
 * Provides a trait for storing sections on an object.
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
    if ($this->hasBlankSection()) {
      return 0;
    }

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
    // Clear the section list if there is currently a blank section.
    if ($this->hasBlankSection()) {
      $this->removeAllSections();
    }

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
   * Adds a blank section to the list.
   *
   * @return $this
   *
   * @see \Drupal\layout_builder\Plugin\Layout\BlankLayout
   */
  protected function addBlankSection() {
    if ($this->hasSection(0)) {
      throw new \Exception('A blank section must only be added to an empty list');
    }

    $this->appendSection(new Section('layout_builder_blank'));
    return $this;
  }

  /**
   * Indicates if this section list contains a blank section.
   *
   * A blank section is used to differentiate the difference between a layout
   * that has never been instantiated and one that has purposefully had all
   * sections removed.
   *
   * @return bool
   *   TRUE if the section list contains a blank section, FALSE otherwise.
   *
   * @see \Drupal\layout_builder\Plugin\Layout\BlankLayout
   */
  protected function hasBlankSection() {
    // A blank section will only ever exist when the delta is 0, as added by
    // ::removeSection().
    return $this->hasSection(0) && $this->getSection(0)->getLayoutId() === 'layout_builder_blank';
  }

  /**
   * {@inheritdoc}
   */
  public function removeSection($delta) {
    // Clear the section list if there is currently a blank section.
    if ($this->hasBlankSection()) {
      $this->removeAllSections();
    }

    $sections = $this->getSections();
    unset($sections[$delta]);
    $this->setSections($sections);
    // Add a blank section when the last section is removed.
    if (empty($sections)) {
      $this->addBlankSection();
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeAllSections($set_blank = FALSE) {
    $this->setSections([]);
    if ($set_blank) {
      $this->addBlankSection();
    }
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
