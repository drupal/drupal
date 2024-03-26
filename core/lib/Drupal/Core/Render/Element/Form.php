<?php

namespace Drupal\Core\Render\Element;

use Drupal\Core\Render\Attribute\RenderElement;
use Drupal\Core\Render\Element\RenderElement as RenderElementBase;

/**
 * Provides a render element for a form.
 */
#[RenderElement('form')]
class Form extends RenderElementBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#method' => 'post',
      '#theme_wrappers' => ['form'],
    ];
  }

}
