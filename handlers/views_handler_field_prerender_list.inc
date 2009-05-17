<?php

/**
 * @file
 * Definition of views_handler_field_prerender_list.
 */

/**
 * Field handler to provide a list of items.
 *
 * The items are expected to be loaded by a child object during pre_render,
 * and 'my field' is expected to be the pointer to the items in the list.
 *
 * Items to render should be in a list in $this->items
 *
 * @ingroup views_field_handlers
 */
class views_handler_field_prerender_list extends views_handler_field {
  /**
   * Stores all items which are used to render the items.
   * It should be keyed first by the id of the base table, for example nid.
   * The second key is the id of the thing which is displayed multiple times
   * per row, for example the tid.
   *
   * @var array
   */
  var $items = array();

  function option_definition() {
    $options = parent::option_definition();

    $options['type'] = array('default' => 'separator');
    $options['separator'] = array('default' => ', ');

    return $options;
  }

  function options_form(&$form, &$form_state) {
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
      '#dependency' => array('radio:options[type]' => array('separator')),
    );
    parent::options_form($form, $form_state);
  }

  /**
   * Render the field.
   *
   * This function is deprecated, but left in for older systems that have not
   * yet or won't update their prerender list fields. If a render_item method
   * exists, this will not get used by advanced_render.
   */
  function render($values) {
    $field = $this->get_value($values);
    if (!empty($this->items[$field])) {
      if ($this->options['type'] == 'separator') {
        return implode($this->sanitize_value($this->options['separator']), $this->items[$field]);
      }
      else {
        return theme('item_list',
          array(
            'items' => $this->items[$field],
            'title' => NULL,
            'type' => $this->options['type']
          ));
      }
    }
  }

  /**
   * Render all items in this field together.
   *
   * When using advanced render, each possible item in the list is rendered
   * individually. Then the items are all pasted together.
   */
  function render_items($items) {
    if (!empty($items)) {
      if ($this->options['type'] == 'separator') {
        return implode($this->sanitize_value($this->options['separator'], 'xss_admin'), $items);
      }
      else {
        return theme('item_list',
          array(
            'items' => $items,
            'title' => NULL,
            'type' => $this->options['type']
          ));
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
  function get_items($values) {
    // Only the parent get_value returns a single field.
    $field = parent::get_value($values);
    if (!empty($this->items[$field])) {
      return $this->items[$field];
    }

    return array();
  }

  /**
   * Get the value that's supposed to be rendered.
   *
   * @param $values
   *   An object containing all retrieved values.
   * @param $field
   *   Optional name of the field where the value is stored.
   * @param $raw
   *   Use the raw data and not the data defined in pre_render
   */
  function get_value($values, $field = NULL, $raw = FALSE) {
    if ($raw) {
      return parent::get_value($values, $field);
    }
    $item = $this->get_items($values);
    $item = (array) $item;
    if (isset($field) && isset($item[$field])) {
      return $item[$field];
    }
    return $item;
  }

  /**
   * Determine if advanced rendering is allowed.
   *
   * By default, advanced rendering will NOT be allowed if the class
   * inheriting from this does not implement a 'render_items' method.
   */
  function allow_advanced_render() {
    // Note that the advanced render bits also use the presence of
    // this method to determine if it needs to render items as a list.
    return method_exists($this, 'render_item');
  }
}
