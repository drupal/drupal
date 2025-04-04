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
 *
 * @deprecated in drupal:11.2.0 and is removed from drupal:12.0.0.
 *    Use \Drupal\Core\Render\Element\Fieldset instead.
 *
 * @see https://www.drupal.org/node/3515272
 */
#[RenderElement('fieldgroup')]
class Fieldgroup extends Fieldset {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    @trigger_error('The ' . __CLASS__ . ' element is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Use ' . Fieldset::class . ' instead. See https://www.drupal.org/node/3515272', E_USER_DEPRECATED);
  }

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
