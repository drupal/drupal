<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\field\PrerenderList.
 */

namespace Drupal\views\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;

/**
 * Field handler to provide a list of items.
 *
 * The items are expected to be loaded by a child object during preRender,
 * and 'my field' is expected to be the pointer to the items in the list.
 *
 * Items to render should be in a list in $this->items
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("prerender_list")
 */
class PrerenderList extends FieldPluginBase {

  /**
   * Stores all items which are used to render the items.
   * It should be keyed first by the id of the base table, for example nid.
   * The second key is the id of the thing which is displayed multiple times
   * per row, for example the tid.
   *
   * @var array
   */
  var $items = array();

  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['type'] = array('default' => 'separator');
    $options['separator'] = array('default' => ', ');

    return $options;
  }

  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['type'] = array(
      '#type' => 'radios',
      '#title' => t('Display type'),
      '#options' => array(
        'ul' => t('Unordered list'),
        'ol' => t('Ordered list'),
        'separator' => t('Simple separator'),
      ),
      '#default_value' => $this->options['type'],
    );

    $form['separator'] = array(
      '#type' => 'textfield',
      '#title' => t('Separator'),
      '#default_value' => $this->options['separator'],
      '#states' => array(
        'visible' => array(
          ':input[name="options[type]"]' => array('value' => 'separator'),
        ),
      ),
    );
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * Render all items in this field together.
   *
   * When using advanced render, each possible item in the list is rendered
   * individually. Then the items are all pasted together.
   */
  protected function renderItems($items) {
    if (!empty($items)) {
      if ($this->options['type'] == 'separator') {
        return implode($this->sanitizeValue($this->options['separator'], 'xss_admin'), $items);
      }
      else {
        $item_list = array(
          '#theme' => 'item_list',
          '#items' => $items,
          '#title' => NULL,
          '#list_type' => $this->options['type'],
        );
        return drupal_render($item_list);
      }
    }
  }

  /**
   * Return an array of items for the field.
   *
   * Items should be stored in the result array, if possible, as an array
   * with 'value' as the actual displayable value of the item, plus
   * any items that might be found in the 'alter' options array for
   * creating links, such as 'path', 'fragment', 'query' etc, such a thing
   * is to be made. Additionally, items that might be turned into tokens
   * should also be in this array.
   */
  public function getItems($values) {
    $field = $this->getValue($values);
    if (!empty($this->items[$field])) {
      return $this->items[$field];
    }

    return array();
  }

  /**
   * Determine if advanced rendering is allowed.
   *
   * By default, advanced rendering will NOT be allowed if the class
   * inheriting from this does not implement a 'renderItems' method.
   */
  protected function allowAdvancedRender() {
    // Note that the advanced render bits also use the presence of
    // this method to determine if it needs to render items as a list.
    return method_exists($this, 'render_item');
  }

}
