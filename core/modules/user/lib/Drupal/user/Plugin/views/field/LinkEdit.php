<?php

/**
 * @file
 * Definition of Drupal\user\Plugin\views\field\LinkEdit.
 */

namespace Drupal\user\Plugin\views\field;

use Drupal\Core\Annotation\Plugin;

/**
 * Field handler to present a link to user edit.
 *
 * @ingroup views_field_handlers
 *
 * @Plugin(
 *   id = "user_link_edit",
 *   module = "user"
 * )
 */
class LinkEdit extends Link {

  function render_link($data, $values) {
    // Build a pseudo account object to be able to check the access.
    $account = entity_create('user', array());
    $account->uid = $data;

    if ($data && user_edit_access($account)) {
      $this->options['alter']['make_link'] = TRUE;

      $text = !empty($this->options['text']) ? $this->options['text'] : t('edit');

      $this->options['alter']['path'] = "user/$data/edit";
      $this->options['alter']['query'] = drupal_get_destination();

      return $text;
    }
  }

}
