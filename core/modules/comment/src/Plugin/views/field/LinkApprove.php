<?php

/**
 * @file
 * Definition of Drupal\comment\Plugin\views\field\LinkApprove.
 */

namespace Drupal\comment\Plugin\views\field;

use Drupal\comment\CommentInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\views\ResultRow;

/**
 * Provides a comment approve link.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("comment_link_approve")
 */
class LinkApprove extends Link {

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    //needs permission to administer comments in general
    return $account->hasPermission('administer comments');
  }

  /**
   * Prepares the link pointing for approving the comment.
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
    $status = $this->getValue($values, 'status');

    // Don't show an approve link on published comment.
    if ($status == CommentInterface::PUBLISHED) {
      return;
    }

    $text = !empty($this->options['text']) ? $this->options['text'] : $this->t('Approve');
    $comment = $this->get_entity($values);

    $this->options['alter']['make_link'] = TRUE;
    $this->options['alter']['url'] = Url::fromRoute('comment.approve', ['comment' => $comment->id()]);
    $this->options['alter']['query'] = $this->getDestinationArray() + array('token' => \Drupal::csrfToken()->get($this->options['alter']['url']->toString()));

    return $text;
  }

}
