<?php

/**
 * @file
 * Contains \Drupal\Core\Render\Element\Form.
 */

namespace Drupal\Core\Render\Element;

use Drupal\Core\Render\Element;

/**
 * Provides a render element for a form.
 *
 * @RenderElement("form")
 */
class Form extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return array(
      '#method' => 'post',
      '#action' => request_uri(),
      '#theme_wrappers' => array('form'),
    );
  }

}
