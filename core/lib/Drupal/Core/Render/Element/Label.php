<?php

namespace Drupal\Core\Render\Element;

use Drupal\Core\Render\Attribute\RenderElement;

/**
 * Provides a render element for displaying the label for a form element.
 *
 * Labels are generated automatically from element properties during processing
 * of most form elements. This element is used internally by the form system
 * to render labels for form elements.
 */
#[RenderElement('label')]
class Label extends RenderElementBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#theme' => 'form_element_label',
    ];
  }

}
