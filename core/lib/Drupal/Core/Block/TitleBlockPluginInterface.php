<?php

namespace Drupal\Core\Block;

/**
 * The interface for "title" blocks.
 *
 * A title block shows the title returned by the controller.
 *
 * @ingroup block_api
 *
 * @see \Drupal\Core\Render\Element\PageTitle
 */
interface TitleBlockPluginInterface extends BlockPluginInterface {

  /**
   * Sets the title.
   *
   * @param string|array $title
   *   The page title: either a string for plain titles or a render array for
   *   formatted titles.
   */
  public function setTitle($title);

}
