<?php

namespace Drupal\layout_builder\Plugin\SectionStorage;

use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\ContextAwarePluginBase;
use Drupal\layout_builder\Routing\LayoutBuilderRoutesTrait;
use Drupal\layout_builder\Section;
use Drupal\layout_builder\SectionListInterface;
use Drupal\layout_builder\SectionStorageInterface;
use Drupal\layout_builder\TempStoreIdentifierInterface;

/**
 * Provides a base class for Section Storage types.
 */
abstract class SectionStorageBase extends ContextAwarePluginBase implements SectionStorageInterface, TempStoreIdentifierInterface {

  use LayoutBuilderRoutesTrait;

  /**
   * Sets the section list on the storage.
   *
   * @param \Drupal\layout_builder\SectionListInterface $section_list
   *   The section list.
   *
   * @internal
   *   As of Drupal 8.7.0, this method should no longer be used. It previously
   *   should only have been used during storage instantiation.
   *
   * @throws \Exception
   *
   * @deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. This
   *   method should no longer be used. The section list should be derived from
   *   context. See https://www.drupal.org/node/3016262.
   */
  public function setSectionList(SectionListInterface $section_list) {
    @trigger_error('\Drupal\layout_builder\SectionStorageInterface::setSectionList() is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. This method should no longer be used. The section list should be derived from context. See https://www.drupal.org/node/3016262.', E_USER_DEPRECATED);
    throw new \Exception('\Drupal\layout_builder\SectionStorageInterface::setSectionList() must no longer be called. The section list should be derived from context. See https://www.drupal.org/node/3016262.');
  }

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
