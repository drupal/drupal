<?php

/**
 * @file
 * Contains \Drupal\search\Plugin\Block\SearchBlock.
 */

namespace Drupal\search\Plugin\Block;

use Drupal\Core\Session\AccountInterface;
use Drupal\block\BlockBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

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
  public function access(AccountInterface $account) {
    return $account->hasPermission('search content');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return \Drupal::formBuilder()->getForm('Drupal\search\Form\SearchBlockForm');
  }

}
