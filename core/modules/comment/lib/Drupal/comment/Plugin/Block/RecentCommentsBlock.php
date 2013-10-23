<?php

/**
 * @file
 * Contains \Drupal\comment\Plugin\Block\RecentCommentsBlock.
 */

namespace Drupal\comment\Plugin\Block;

use Drupal\block\BlockBase;
use Drupal\block\Annotation\Block;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a 'Recent comments' block.
 *
 * @Block(
 *  id = "recent_comments",
 *  admin_label = @Translation("Recent comments")
 * )
 */
class RecentCommentsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'block_count' => 10,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    return $account->hasPermission('access comments');
  }

  /**
   * Overrides \Drupal\block\BlockBase::blockForm().
   */
  public function blockForm($form, &$form_state) {
    $form['block_count'] = array(
      '#type' => 'select',
      '#title' => t('Number of recent comments'),
      '#default_value' => $this->configuration['block_count'],
      '#options' => drupal_map_assoc(array(2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 25, 30)),
    );
    return $form;
  }

  /**
   * Overrides \Drupal\block\BlockBase::blockSubmit().
   */
  public function blockSubmit($form, &$form_state) {
    $this->configuration['block_count'] = $form_state['values']['block_count'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return array(
      '#theme' => 'comment_block',
      '#number' => $this->configuration['block_count'],
    );
  }

}
