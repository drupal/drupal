<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\views\area\Messages.
 */

namespace Drupal\views\Plugin\views\area;

/**
 * Provides an area for messages.
 *
 * @ingroup views_area_handlers
 *
 * @PluginID("messages")
 */
class Messages extends AreaPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    // Set the default to TRUE so it shows on empty pages by default.
    $options['empty']['default'] = TRUE;
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function render($empty = FALSE) {
    if (!$empty || !empty($this->options['empty'])) {
      return array(
        '#type' => 'status_messages',
      );
    }
    return array();
  }

}
