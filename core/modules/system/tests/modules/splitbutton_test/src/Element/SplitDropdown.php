<?php

namespace Drupal\splitbutton_test\Element;

use Drupal\Core\Render\Element\Splitbutton;

/**
 * Used for testing a render element extending Splitbutton.
 *
 * @RenderElement("dropdown_extends_splitbutton")
 */
class SplitDropdown extends Splitbutton {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#pre_render' => [
        [$class, 'preRenderSplitDropdown'],
      ],
    ] + parent::getInfo();
  }

  /**
   * Pre-render callback. Pre-defines properties before Splitbutton rendering.
   *
   * The end result is a Splitbutton that is configured to behave as a
   * dropdown.
   *
   * @param array $element
   *   The render element.
   *
   * @return array
   *   Render array.
   */
  public static function preRenderSplitDropdown(array $element) {
    if (empty($element['#title'])) {
      $element['#title'] = ' ';
    }
    if (empty($element['#splitbutton_items']) && !empty($element['#items'])) {
      $element['#splitbutton_items'] = $element['#items'];
    }
    return parent::preRenderSplitbutton($element);
  }

  /**
   * {@inheritdoc}
   */
  public static function buildItemList(&$element, $items) {
    $trigger_id = $element['#trigger_id'];

    // The navigable items in Splitbutton default are <a>, <input> or <button>
    // elements within each list item. For SplitDropdown, the list items will
    // be focusable as they have no child elements.
    foreach ($items as &$item) {
      $item['#wrapper_attributes']['tabindex'] = 0;
    }

    $element['#splitbutton_item_list'] = [
      '#items' => $items,
      '#theme' => 'item_list',
      '#attributes' => [
        'data-drupal-splitbutton-item-list' => '',
        'data-drupal-splitbutton-target' => $trigger_id,
        'data-drupal-splitbutton-item-tags' => 'li',
        'class' => ['splitbutton__operation-list'],
      ],
    ];
  }

}
