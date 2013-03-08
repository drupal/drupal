<?php

/**
 * @file
 * Definition of Drupal\comment\Plugin\views\field\LinkReply.
 */

namespace Drupal\comment\Plugin\views\field;

use Drupal\Component\Annotation\Plugin;

/**
 * Field handler to present a link to reply to a comment.
 *
 * @ingroup views_field_handlers
 *
 * @Plugin(
 *   id = "comment_link_reply",
 *   module = "comment"
 * )
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
    $this->options['alter']['path'] = "comment/reply/{$comment->entity_type->value}/{$comment->entity_id->target_id}/{$comment->field_name->value}/{$comment->id()}";

    return $text;
  }

}
