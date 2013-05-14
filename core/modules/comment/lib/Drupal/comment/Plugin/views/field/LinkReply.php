<?php

/**
 * @file
 * Definition of Drupal\comment\Plugin\views\field\LinkReply.
 */

namespace Drupal\comment\Plugin\views\field;

use Drupal\Component\Annotation\PluginID;

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

  function render_link($data, $values) {
    $text = !empty($this->options['text']) ? $this->options['text'] : t('reply');
    $comment = $this->get_entity($values);

    $this->options['alter']['make_link'] = TRUE;
    $this->options['alter']['path'] = "comment/reply/{$comment->entity_type->value}/{$comment->entity_id->value}/{$comment->field_name->value}/{$comment->id()}";

    return $text;
  }

}
