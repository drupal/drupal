<?php
// $Id: views_handler_field_prerender_list.inc,v 1.3.4.4 2010/11/05 07:20:54 dereine Exp $

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
  function option_definition() {
    $options = parent::option_definition();

    $options['type'] = array('default' => 'separator');
    $options['separator'] = array('default' => ', ');

    return $options;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
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
      '#process' => array('ctools_dependent_process'),
      '#dependency' => array('radio:options[type]' => array('separator')),
    );
  }

  /**
   * Render the field.
   *
   * This function is deprecated, but left in for older systems that have not
   * yet or won't update their prerender list fields. If a render_item method
   * exists, this will not get used by advanced_render.
   */
  function render($values) {
    $field = $values->{$this->field_alias};
    if (!empty($this->items[$field])) {
      if ($this->options['type'] == 'separator') {
        return implode(check_plain($this->options['separator']), $this->items[$field]);
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
        return implode(check_plain($this->options['separator']), $items);
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
    $field = $values->{$this->field_alias};
    if (!empty($this->items[$field])) {
      return $this->items[$field];
    }

    return array();
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
