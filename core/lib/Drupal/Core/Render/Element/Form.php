<?php

namespace Drupal\Core\Render\Element;

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
      '#theme_wrappers' => array('form'),
    );
  }

}
