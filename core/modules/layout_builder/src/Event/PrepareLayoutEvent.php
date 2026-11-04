<?php

namespace Drupal\layout_builder\Event;

use Drupal\layout_builder\SectionStorageInterface;
use Drupal\Component\EventDispatcher\Event;

/**
 * Event fired in #pre_render of \Drupal\layout_builder\Element\LayoutBuilder.
 *
 * Subscribers to this event can prepare section storage before rendering.
 *
 * @see \Drupal\layout_builder\LayoutBuilderEvents::PREPARE_LAYOUT
 * @see \Drupal\layout_builder\Element\LayoutBuilder::prepareLayout()
 */
class PrepareLayoutEvent extends Event {

  /**
   * Constructs a new PrepareLayoutEvent.
   *
   * @param \Drupal\layout_builder\SectionStorageInterface $sectionStorage
   *   The section storage preparing the Layout.
   */
  public function __construct(
    protected SectionStorageInterface $sectionStorage,
  ) {
  }

  /**
   * Gets the section storage.
   *
   * @return \Drupal\layout_builder\SectionStorageInterface
   *   The section storage.
   */
  public function getSectionStorage(): SectionStorageInterface {
    return $this->sectionStorage;
  }

}
