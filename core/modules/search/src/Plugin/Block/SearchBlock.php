<?php

/**
 * @file
 * Contains \Drupal\search\Plugin\Block\SearchBlock.
 */

namespace Drupal\search\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Block\BlockBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Search form' block.
 *
 * @Block(
 *   id = "search_form_block",
 *   admin_label = @Translation("Search form"),
 *   category = @Translation("Forms")
 * )
 */
class SearchBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'search content');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return \Drupal::formBuilder()->getForm('Drupal\search\Form\SearchBlockForm');
  }

  /**
   * {@inheritdoc}
   *
   * @todo Make cacheable once https://www.drupal.org/node/2351015 lands.
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
