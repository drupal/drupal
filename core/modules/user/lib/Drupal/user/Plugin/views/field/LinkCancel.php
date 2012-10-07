<?php

/**
 * @file
 * Definition of Drupal\user\Plugin\views\field\LinkCancel.
 */

namespace Drupal\user\Plugin\views\field;

use Drupal\Core\Annotation\Plugin;

/**
 * Field handler to present a link to user cancel.
 *
 * @ingroup views_field_handlers
 *
 * @Plugin(
 *   id = "user_link_cancel",
 *   module = "user"
 * )
 */
class LinkCancel extends Link {

  function render_link($data, $values) {
    $uid = $values->{$this->aliases['uid']};

    // Build a pseudo account object to be able to check the access.
    $account = entity_create('user', array());
    $account->uid = $uid;

    if ($uid && user_cancel_access($account)) {
      $this->options['alter']['make_link'] = TRUE;

      $text = !empty($this->options['text']) ? $this->options['text'] : t('cancel');

      $this->options['alter']['path'] = "user/$uid/cancel";
      $this->options['alter']['query'] = drupal_get_destination();

      return $text;
    }
  }

}
