<?php

/**
 * @file
 * Contains \Drupal\Core\Render\Plugin\DisplayVariant\SimplePageVariant.
 */

namespace Drupal\Core\Render\Plugin\DisplayVariant;

use Drupal\Core\Display\PageVariantInterface;
use Drupal\Core\Display\VariantBase;

/**
 * Provides a page display variant that simply renders the main content.
 *
 * @PageDisplayVariant(
 *   id = "simple_page",
 *   admin_label = @Translation("Simple page")
 * )
 */
class SimplePageVariant extends VariantBase implements PageVariantInterface {

  /**
   * The render array representing the main content.
   *
   * @var array
   */
  protected $mainContent;

  /**
   * {@inheritdoc}
   */
  public function setMainContent(array $main_content) {
    $this->mainContent = $main_content;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [
      'content' => $this->mainContent,
    ];
    return $build;
  }

}
