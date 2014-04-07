<?php

/**
 * @file
 * Contains \Drupal\forum\Plugin\Block\ForumBlockBase.
 */

namespace Drupal\forum\Plugin\Block;

use Drupal\block\BlockBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Cache\Cache;

/**
 * Provides a base class for Forum blocks.
 */
abstract class ForumBlockBase extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $result = $this->buildForumQuery()->execute();
    $elements = array();
    if ($node_title_list = node_title_list($result)) {
      $elements['forum_list'] = $node_title_list;
      $elements['forum_more'] = array(
        '#theme' => 'more_link',
        '#url' => 'forum',
        '#title' => t('Read the latest forum topics.')
      );
    }
    return $elements;
  }

  /**
   * Builds the select query to use for this forum block.
   *
   * @return \Drupal\Core\Database\Query\Select
   *   A Select object.
   */
  abstract protected function buildForumQuery();

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'properties' => array(
        'administrative' => TRUE,
      ),
      'block_count' => 5,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    return $account->hasPermission('access content');
  }

  /**
   * Overrides \Drupal\block\BlockBase::blockForm().
   */
  public function blockForm($form, &$form_state) {
    $range = range(2, 20);
    $form['block_count'] = array(
      '#type' => 'select',
      '#title' => t('Number of topics'),
      '#default_value' => $this->configuration['block_count'],
      '#options' => array_combine($range, $range),
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
  public function getCacheKeys() {
    return array_merge(parent::getCacheKeys(), Cache::keyFromQuery($this->buildForumQuery()));
  }

}
