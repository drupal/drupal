<?php

/**
 * @file
 * Contains \Drupal\node\Plugin\Block\RecentContentBlock.
 */

namespace Drupal\node\Plugin\Block;

use Drupal\block\BlockBase;
use Drupal\block\Annotation\Block;
use Drupal\Core\Annotation\Translation;

/**
 * Provides a 'Recent content' block.
 *
 * @Block(
 *   id = "node_recent_block",
 *   admin_label = @Translation("Recent content")
 * )
 */
class RecentContentBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'block_count' => 10,
    );
  }

  /**
   * Overrides \Drupal\block\BlockBase::access().
   */
  public function access() {
    return user_access('access content');
  }

  /**
   * Overrides \Drupal\block\BlockBase::blockForm().
   */
  public function blockForm($form, &$form_state) {
    $form['block_count'] = array(
      '#type' => 'select',
      '#title' => t('Number of recent content items to display'),
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
    if ($nodes = node_get_recent($this->configuration['block_count'])) {
      return array(
        '#theme' => 'node_recent_block',
        '#nodes' => $nodes,
      );
    }
    else {
      return array(
        '#children' => t('No content available.'),
      );
    }
  }

}
