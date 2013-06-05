<?php

/**
 * @file
 * Definition of Drupal\comment\Plugin\views\field\LinkReply.
 */

namespace Drupal\comment\Plugin\views\field;

use Drupal\Component\Annotation\PluginID;

/**
 * Field handler to present a link to delete a node.
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("comment_link_reply")
 */
class LinkReply extends Link {

  public function access() {
    //check for permission to reply to comments
    return user_access('post comments');
  }

  function render_link($data, $values) {
    $text = !empty($this->options['text']) ? $this->options['text'] : t('reply');
    $nid =  $this->getValue($values, 'nid');
    $cid =  $this->getValue($values, 'cid');

    $this->options['alter']['make_link'] = TRUE;
    $this->options['alter']['path'] = "comment/reply/" . $nid . '/' . $cid;

    return $text;
  }

}
