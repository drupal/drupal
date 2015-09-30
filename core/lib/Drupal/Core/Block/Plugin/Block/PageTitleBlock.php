<?php

/**
 * @file
 * Contains \Drupal\Core\Plugin\Block\PageTitleBlock.
 */

namespace Drupal\Core\Block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\TitleBlockPluginInterface;

/**
 * Provides a block to display the page title.
 *
 * @Block(
 *   id = "page_title_block",
 *   admin_label = @Translation("Page title"),
 * )
 */
class PageTitleBlock extends BlockBase implements TitleBlockPluginInterface {

  /**
   * The page title: a string (plain title) or a render array (formatted title).
   *
   * @var string|array
   */
  protected $title = '';

  /**
   * {@inheritdoc}
   */
  public function setTitle($title) {
    $this->title = $title;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['label_display' => FALSE];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#type' => 'page_title',
      '#title' => $this->title,
    ];
  }

}
