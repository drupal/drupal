<?php

/**
 * @file
 * Contains \Drupal\comment\Plugin\views\field\LinkReply.
 */

namespace Drupal\comment\Plugin\views\field;

use Drupal\Core\Session\AccountInterface;
use Drupal\views\ResultRow;

/**
 * Field handler to present a link to reply to a comment.
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("comment_link_reply")
 */
class LinkReply extends Link {

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    //check for permission to reply to comments
    return $account->hasPermission('post comments');
  }

  /**
   * Prepare the link for replying to the comment.
   *
   * @param \Drupal\Core\Entity\EntityInterface $data
   *   The comment entity.
   * @param \Drupal\views\ResultRow $values
   *   The values retrieved from a single row of a view's query result.
   *
   * @return string
   *   Returns a string for the link text.
   */
  protected function renderLink($data, ResultRow $values) {
    $text = !empty($this->options['text']) ? $this->options['text'] : t('Reply');
    $comment = $this->getEntity($values);

    $this->options['alter']['make_link'] = TRUE;
    $this->options['alter']['path'] = "comment/reply/{$comment->getCommentedEntityTypeId()}/{$comment->getCommentedEntityId()}/{$comment->getFieldName()}/{$comment->id()}";

    return $text;
  }

}
