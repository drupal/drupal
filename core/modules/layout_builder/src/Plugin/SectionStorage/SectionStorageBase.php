<?php

namespace Drupal\layout_builder\Plugin\SectionStorage;

use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\ContextAwarePluginBase;
use Drupal\layout_builder\Routing\LayoutBuilderRoutesTrait;
use Drupal\layout_builder\Section;
use Drupal\layout_builder\SectionStorageInterface;
use Drupal\layout_builder\TempStoreIdentifierInterface;

/**
 * Provides a base class for Section Storage types.
 */
abstract class SectionStorageBase extends ContextAwarePluginBase implements SectionStorageInterface, TempStoreIdentifierInterface {

  use LayoutBuilderRoutesTrait;

  /**
   * Gets the section list.
   *
   * @return \Drupal\layout_builder\SectionListInterface
   *   The section list.
   */
  abstract protected function getSectionList();

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

  /**
   * {@inheritdoc}
   */
  public function removeAllSections($set_blank = FALSE) {
    $this->getSectionList()->removeAllSections($set_blank);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getContextsDuringPreview() {
    $contexts = $this->getContexts();

    // view_mode is a required context, but SectionStorage plugins are not
    // required to return it (for example, the layout_library plugin provided
    // in the Layout Library module. In these instances, explicitly create a
    // view_mode context with the value "default".
    if (!isset($contexts['view_mode']) || $contexts['view_mode']->validate()->count() || !$contexts['view_mode']->getContextValue()) {
      $contexts['view_mode'] = new Context(new ContextDefinition('string'), 'default');
    }
    return $contexts;
  }

  /**
   * {@inheritdoc}
   */
  public function getTempstoreKey() {
    return $this->getStorageId();
  }

}
