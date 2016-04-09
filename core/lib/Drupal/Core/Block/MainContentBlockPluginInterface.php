<?php

namespace Drupal\Core\Block;

/**
 * The interface for "main page content" blocks.
 *
 * A main page content block represents the content returned by the controller.
 *
 * @ingroup block_api
 */
interface MainContentBlockPluginInterface extends BlockPluginInterface {

  /**
   * Sets the main content render array.
   *
   * @param array $main_content
   *   The render array representing the main content.
   */
  public function setMainContent(array $main_content);

}
