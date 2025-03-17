<?php

namespace Drupal\Core\Render\Element;

use Drupal\Core\Render\Attribute\RenderElement;

/**
 * Provides a render element for a group of form elements.
 *
 * In default rendering, the only difference between a 'fieldgroup' and a
 * 'fieldset' is the CSS class applied to the containing HTML element. Normally
 * use a fieldset.
 *
 * @see \Drupal\Core\Render\Element\Fieldset for documentation and usage.
 *
 * @see \Drupal\Core\Render\Element\Fieldset
 * @see \Drupal\Core\Render\Element\Details
 */
#[RenderElement('fieldgroup')]
class Fieldgroup extends Fieldset {

  public function getInfo() {
    $info = parent::getInfo();
    $info['#attributes']['class'] = ['fieldgroup'];
    $info['#pre_render'][] = [static::class, 'preRenderAttachments'];
    return $info;
  }

  /**
   * Adds the fieldgroup library.
   */
  public static function preRenderAttachments($element): array {
    $element['#attached']['library'][] = 'core/drupal.fieldgroup';
    return $element;
  }

}
