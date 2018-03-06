<?php

namespace Drupal\layout_builder\Event;

use Drupal\Core\Cache\CacheableResponseTrait;
use Drupal\layout_builder\SectionComponent;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event fired when a section component's render array is being built.
 *
 * Subscribers to this event should manipulate the cacheability object and the
 * build array in this event.
 *
 * @see \Drupal\layout_builder\LayoutBuilderEvents::SECTION_COMPONENT_BUILD_RENDER_ARRAY
 *
 * @internal
 *   Layout Builder is currently experimental and should only be leveraged by
 *   experimental modules and development releases of contributed modules.
 *   See https://www.drupal.org/core/experimental for more information.
 */
class SectionComponentBuildRenderArrayEvent extends Event {

  use CacheableResponseTrait;

  /**
   * The section component whose render array is being built.
   *
   * @var \Drupal\layout_builder\SectionComponent
   */
  protected $component;

  /**
   * The available contexts.
   *
   * @var \Drupal\Core\Plugin\Context\ContextInterface[]
   */
  protected $contexts;

  /**
   * The plugin for the section component being built.
   *
   * @var \Drupal\Component\Plugin\PluginInspectionInterface
   */
  protected $plugin;

  /**
   * Whether the component is in preview mode or not.
   *
   * @var bool
   */
  protected $inPreview;

  /**
   * The render array built by the event subscribers.
   *
   * @var array
   */
  protected $build = [];

  /**
   * Creates a new SectionComponentBuildRenderArrayEvent object.
   *
   * @param \Drupal\layout_builder\SectionComponent $component
   *   The section component whose render array is being built.
   * @param \Drupal\Core\Plugin\Context\ContextInterface[] $contexts
   *   The available contexts.
   * @param bool $in_preview
   *   (optional) Whether the component is in preview mode or not.
   */
  public function __construct(SectionComponent $component, array $contexts, $in_preview = FALSE) {
    $this->component = $component;
    $this->contexts = $contexts;
    $this->plugin = $component->getPlugin($contexts);
    $this->inPreview = $in_preview;
  }

  /**
   * Get the section component whose render array is being built.
   *
   * @return \Drupal\layout_builder\SectionComponent
   *   The section component whose render array is being built.
   */
  public function getComponent() {
    return $this->component;
  }

  /**
   * Get the available contexts.
   *
   * @return array|\Drupal\Core\Plugin\Context\ContextInterface[]
   *   The available contexts.
   */
  public function getContexts() {
    return $this->contexts;
  }

  /**
   * Get the plugin for the section component being built.
   *
   * @return \Drupal\Component\Plugin\PluginInspectionInterface
   *   The plugin for the section component being built.
   */
  public function getPlugin() {
    return $this->plugin;
  }

  /**
   * Determine if the component is in preview mode.
   *
   * @return bool
   *   Whether the component is in preview mode or not.
   */
  public function inPreview() {
    return $this->inPreview;
  }

  /**
   * Get the render array in its current state.
   *
   * @return array
   *   The render array built by the event subscribers.
   */
  public function getBuild() {
    return $this->build;
  }

  /**
   * Set the render array.
   *
   * @param array $build
   *   A render array.
   */
  public function setBuild(array $build) {
    $this->build = $build;
  }

}
