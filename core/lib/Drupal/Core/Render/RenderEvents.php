<?php

namespace Drupal\Core\Render;

/**
 * Defines events for the render system.
 */
final class RenderEvents {

  /**
   * Name of the event when selecting a page display variant to use.
   *
   * This event allows you to select a different page display variant to use
   * when rendering a page. The event listener method receives a
   * \Drupal\Core\Render\PageDisplayVariantSelectionEvent instance.
   *
   * @Event
   *
   * @see \Drupal\Core\Render\PageDisplayVariantSelectionEvent
   * @see \Drupal\Core\Render\MainContent\HtmlRenderer
   * @see \Drupal\block\EventSubscriber\BlockPageDisplayVariantSubscriber
   */
  const SELECT_PAGE_DISPLAY_VARIANT = 'render.page_display_variant.select';

}
