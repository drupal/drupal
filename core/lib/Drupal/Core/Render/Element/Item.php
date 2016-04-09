<?php

namespace Drupal\Core\Render\Element;

/**
 * Provides a display-only form element with an optional title and description.
 *
 * Note: since this is a read-only field, setting the #required property will do
 * nothing except theme the form element to look as if it were actually required
 * (i.e. by placing a red star next to the #title).
 *
 * @FormElement("item")
 */
class Item extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return array(
      // Forms that show author fields to both anonymous and authenticated users
      // need to dynamically switch between #type 'textfield' and #type 'item'
      // to automatically take over the authenticated user's information.
      // Therefore, we allow #type 'item' to receive input, which is internally
      // assigned by Form API based on the #default_value or #value properties.
      '#input' => TRUE,
      '#markup' => '',
      '#theme_wrappers' => array('form_element'),
    );
  }

}
