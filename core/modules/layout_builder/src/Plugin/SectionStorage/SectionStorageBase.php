<?php

namespace Drupal\layout_builder\Plugin\SectionStorage;

use Drupal\Core\Plugin\PluginBase;
use Drupal\layout_builder\Routing\LayoutBuilderRoutesTrait;
use Drupal\layout_builder\Section;
use Drupal\layout_builder\SectionListInterface;
use Drupal\layout_builder\SectionStorageInterface;

/**
 * Provides a base class for Section Storage types.
 *
 * @internal
 *   Layout Builder is currently experimental and should only be leveraged by
 *   experimental modules and development releases of contributed modules.
 *   See https://www.drupal.org/core/experimental for more information.
 */
abstract class SectionStorageBase extends PluginBase implements SectionStorageInterface {

  use LayoutBuilderRoutesTrait;

  /**
   * The section storage instance.
   *
   * @var \Drupal\layout_builder\SectionListInterface|null
   */
  protected $sectionList;

  /**
   * {@inheritdoc}
   */
  public function setSectionList(SectionListInterface $section_list) {
    $this->sectionList = $section_list;
    return $this;
  }

  /**
   * Gets the section list.
   *
   * @return \Drupal\layout_builder\SectionListInterface
   *   The section list.
   *
   * @throws \RuntimeException
   *   Thrown if ::setSectionList() is not called first.
   */
  protected function getSectionList() {
    if (!$this->sectionList) {
      throw new \RuntimeException(sprintf('%s::setSectionList() must be called first', static::class));
    }
    return $this->sectionList;
  }

  /**
   * {@inheritdoc}
   */
  public function getStorageType() {
    return $this->getPluginId();
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    return $this->getSectionList()->count();
  }

  /**
   * {@inheritdoc}
   */
  public function getSections() {
    return $this->getSectionList()->getSections();
  }

  /**
   * {@inheritdoc}
   */
  public function getSection($delta) {
    return $this->getSectionList()->getSection($delta);
  }

  /**
   * {@inheritdoc}
   */
  public function appendSection(Section $section) {
    $this->getSectionList()->appendSection($section);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function insertSection($delta, Section $section) {
    $this->getSectionList()->insertSection($delta, $section);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeSection($delta) {
    $this->getSectionList()->removeSection($delta);
    return $this;
  }

}
