<?php

/**
 * @file
 * Definition of views_handler_field_comment_link_delete.
 */

namespace Views\comment\Plugin\views\field;

use Drupal\Core\Annotation\Plugin;

/**
 * Field handler to present a link to delete a node.
 *
 * @ingroup views_field_handlers
 */

/**
 * @Plugin(
 *   plugin_id = "comment_link_delete"
 * )
 */
class LinkDelete extends Link {
  function access() {
    //needs permission to administer comments in general
    return user_access('administer comments');
  }

  function render_link($data, $values) {
    $text = !empty($this->options['text']) ? $this->options['text'] : t('delete');
    $cid =  $this->get_value($values, 'cid');

    $this->options['alter']['make_link'] = TRUE;
    $this->options['alter']['path'] = "comment/" . $cid . "/delete";
    $this->options['alter']['query'] = drupal_get_destination();

    return $text;
  }
}
