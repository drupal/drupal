<?php

namespace Drupal\Core\Plugin;

/**
 * Provides an interface to support preview mode injection in plugins.
 *
 * Block and layout plugins can implement this interface to be informed when
 * preview mode is being used in Layout Builder.
 *
 * @see \Drupal\layout_builder\Event\SectionComponentBuildRenderArrayEvent
 * @see \Drupal\layout_builder\Section::toRenderArray()
 */
interface PreviewAwarePluginInterface {

  /**
   * Set preview mode for the plugin.
   *
   * @param bool $in_preview
   *   TRUE if the plugin should be set to preview mode, FALSE otherwise.
   */
  public function setInPreview(bool $in_preview): void;

}
