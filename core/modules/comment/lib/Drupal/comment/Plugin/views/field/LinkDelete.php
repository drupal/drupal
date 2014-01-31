<?php

/**
 * @file
 * Contains \Drupal\comment\Plugin\views\field\LinkDelete.
 */

namespace Drupal\comment\Plugin\views\field;

use Drupal\Core\Session\AccountInterface;
use Drupal\views\ResultRow;

/**
 * Field handler to present a link to delete a comment.
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("comment_link_delete")
 */
class LinkDelete extends Link {

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    //needs permission to administer comments in general
    return $account->hasPermission('administer comments');
  }

  /**
   * Prepares the link for deleting the comment.
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
    $text = !empty($this->options['text']) ? $this->options['text'] : t('Delete');
    $comment = $this->getEntity($values);

    $this->options['alter']['make_link'] = TRUE;
    $this->options['alter']['path'] = $comment->getSystemPath('delete-form');
    $this->options['alter']['query'] = drupal_get_destination();

    return $text;
  }

}
