<?php

/**
 * @file
 * Contains \Drupal\comment\Plugin\views\field\LinkReply.
 */

namespace Drupal\comment\Plugin\views\field;

use Drupal\Component\Annotation\PluginID;
use Drupal\views\ResultRow;

/**
 * Field handler to present a link to reply to a comment.
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

  protected function renderLink($data, ResultRow $values) {
    $text = !empty($this->options['text']) ? $this->options['text'] : t('reply');
    $nid =  $this->getValue($values, 'nid');
    $cid =  $this->getValue($values, 'cid');

    $this->options['alter']['make_link'] = TRUE;
    $this->options['alter']['path'] = "comment/reply/" . $nid . '/' . $cid;

    return $text;
  }

}
