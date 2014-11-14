<?php

/**
 * @file
 * Contains \Drupal\Core\Render\RenderEvents.
 */

namespace Drupal\Core\Render;

/**
 * Defines events for the render system.
 */
final class RenderEvents {

  /**
   * Name of the event when selecting a page display variant to use.
   *
   * @see \Drupal\Core\Render\PageDisplayVariantSelectionEvent
   */
  const SELECT_PAGE_DISPLAY_VARIANT = 'render.page_display_variant.select';

}
