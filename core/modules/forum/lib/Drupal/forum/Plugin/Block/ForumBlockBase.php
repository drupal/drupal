<?php

/**
 * @file
 * Contains \Drupal\forum\Plugin\Block\ForumBlockBase.
 */

namespace Drupal\forum\Plugin\Block;

use Drupal\block\BlockBase;

/**
 * Provides a base class for Forum blocks.
 */
abstract class ForumBlockBase extends BlockBase {

  /**
   * Overrides \Drupal\block\BlockBase::settings().
   */
  public function settings() {
    return array(
      'cache' => DRUPAL_CACHE_CUSTOM,
      'properties' => array(
        'administrative' => TRUE,
      ),
      'block_count' => 5,
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
      '#title' => t('Number of topics'),
      '#default_value' => $this->configuration['block_count'],
      '#options' => drupal_map_assoc(range(2, 20)),
    );
    return $form;
  }

  /**
   * Overrides \Drupal\block\BlockBase::blockSubmit().
   */
  public function blockSubmit($form, &$form_state) {
    $this->configuration['block_count'] = $form_state['values']['block_count'];
  }

}
