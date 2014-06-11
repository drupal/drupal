<?php

/**
 * @file
 * Contains \Drupal\node\Plugin\Block\SyndicateBlock.
 */

namespace Drupal\node\Plugin\Block;

use Drupal\block\BlockBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a 'Syndicate' block that links to the site's RSS feed.
 *
 * @Block(
 *   id = "node_syndicate_block",
 *   admin_label = @Translation("Syndicate"),
 *   category = @Translation("System")
 * )
 */
class SyndicateBlock extends BlockBase {

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
  protected function blockAccess(AccountInterface $account) {
    return $account->hasPermission('access content');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return array(
      '#theme' => 'feed_icon',
      '#url' => 'rss.xml',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, array &$form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // @see ::isCacheable()
    $form['cache']['#disabled'] = TRUE;
    $form['cache']['#description'] = t('This block is never cacheable, it is not configurable.');
    $form['cache']['max_age']['#value'] = 0;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function isCacheable() {
    // The 'Syndicate' block is never cacheable, because it is cheaper to just
    // render it rather than to cache it and incur I/O.
    return FALSE;
  }

}
