<?php

namespace Drupal\layout_builder;

/**
 * Defines events for the layout_builder module.
 *
 * @see \Drupal\layout_builder\Event\SectionComponentBuildRenderArrayEvent
 */
final class LayoutBuilderEvents {

  /**
   * Name of the event fired when a component's render array is built.
   *
   * This event allows modules to collaborate on creating the render array of
   * the SectionComponent object. The event listener method receives a
   * \Drupal\layout_builder\Event\SectionComponentBuildRenderArrayEvent
   * instance.
   *
   * @Event
   *
   * @see \Drupal\layout_builder\Event\SectionComponentBuildRenderArrayEvent
   * @see \Drupal\layout_builder\SectionComponent::toRenderArray()
   *
   * @var string
   */
  const SECTION_COMPONENT_BUILD_RENDER_ARRAY = 'section_component.build.render_array';

}
