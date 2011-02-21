<?php
// $Id: views_handler_field_profile_list.inc,v 1.4.4.4 2010/12/20 20:14:28 dereine Exp $
/**
 * Field handler display a profile list item.
 */
class views_handler_field_profile_list extends views_handler_field_prerender_list {
  /**
   * Break up our field into a proper list.
   */
  function pre_render(&$values) {
    $this->items = array();
    foreach ($values as $value) {
      $field = $value->{$this->field_alias};
      $this->items[$field] = array();
      foreach (preg_split("/[,\n\r]/", $field) as $item) {
        if ($item != '' && $item !== NULL) {
          $this->items[$field][] = array('item' => $item);
        }
      }
    }
  }

  function render_item($count, $item) {
    return $item['item'];
  }

  function document_self_tokens(&$tokens) {
    $tokens['[' . $this->options['id'] . '-item' . ']'] = t('The text of the profile item.');
  }

  function add_self_tokens(&$tokens, $item) {
    $tokens['[' . $this->options['id'] . '-item' . ']'] = $item['item'];
  }
}
