<?php

namespace Drupal\layout_builder\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\Core\Cache\CacheableResponseTrait;
use Drupal\layout_builder\Section;

/**
 * Event fired when a section's regions array is being built.
 *
 * Subscribers to this event should manipulate the regions array in this event.
 */
class SectionBuildRenderArrayEvent extends Event {

  use CacheableResponseTrait;

  /**
   * The section whose regions array is being built.
   *
   * @var \Drupal\layout_builder\Section
   */
  protected $section;

  /**
   * The available contexts.
   *
   * @var \Drupal\Core\Plugin\Context\ContextInterface[]
   */
  protected $contexts;

  /**
   * Whether the section is in preview mode or not.
   *
   * @var bool
   */
  protected $inPreview;

  /**
   * The regions array being built by the event subscribers.
   *
   * @var array
   */
  protected $regions = [];

  /**
   * Constructs a new event instance.
   *
   * @param \Drupal\layout_builder\Section $section
   *   The section whose regions array is being built.
   * @param \Drupal\Core\Plugin\Context\ContextInterface[] $contexts
   *   The available contexts.
   * @param bool $in_preview
   *   Whether the section is in preview mode or not.
   */
  public function __construct(Section $section, array $contexts, bool $in_preview) {
    $this->section = $section;
    $this->contexts = $contexts;
    $this->inPreview = $in_preview;
  }

  /**
   * Get the section whose regions array is being built.
   *
   * @return \Drupal\layout_builder\Section
   *   The section whose regions array is being built.
   */
  public function getSection(): Section {
    return $this->section;
  }

  /**
   * Get the available contexts.
   *
   * @return array|\Drupal\Core\Plugin\Context\ContextInterface[]
   *   The available contexts.
   */
  public function getContexts(): array {
    return $this->contexts;
  }

  /**
   * Determine if the section is in preview mode.
   *
   * @return bool
   *   Whether the section is in preview mode or not.
   */
  public function isInPreview(): bool {
    return $this->inPreview;
  }

  /**
   * Get the regions render array in its current state.
   *
   * @return array
   *   The regions array built by the event subscribers.
   */
  public function getRegions(): array {
    return $this->regions;
  }

  /**
   * Set the regions render array.
   *
   * @param array $regions
   *   A regions render array.
   *
   * @return $this
   */
  public function setRegions(array $regions): self {
    $this->regions = $regions;
    return $this;
  }

}
