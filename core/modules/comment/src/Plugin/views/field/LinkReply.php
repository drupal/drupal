<?php

namespace Drupal\comment\Plugin\views\field;

use Drupal\Core\Url;
use Drupal\views\Attribute\ViewsField;
use Drupal\views\Plugin\views\field\LinkBase;
use Drupal\views\ResultRow;

/**
 * Field handler to present a link to reply to a comment.
 *
 * @ingroup views_field_handlers
 */
#[ViewsField("comment_link_reply")]
class LinkReply extends LinkBase {

  /**
   * {@inheritdoc}
   */
  protected function getUrlInfo(ResultRow $row) {
    /** @var \Drupal\comment\CommentInterface $comment */
    $comment = $this->getEntity($row);
    if (!$comment) {
      return NULL;
    }
    return Url::fromRoute('comment.reply', [
      'entity_type' => $comment->getCommentedEntityTypeId(),
      'entity' => $comment->getCommentedEntityId(),
      'field_name' => $comment->getFieldName(),
      'pid' => $comment->id(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultLabel() {
    return $this->t('Reply');
  }

}
