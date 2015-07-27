<?php

/**
 * @file
 * Contains \Drupal\display_variant_test\Plugin\DisplayVariant\TestDisplayVariant.
 */

namespace Drupal\display_variant_test\Plugin\DisplayVariant;

use Drupal\Core\Display\VariantBase;
use Drupal\Core\Display\PageVariantInterface;

/**
 * Provides a display variant that requires configuration.
 *
 * @DisplayVariant(
 *   id = "display_variant_test",
 *   admin_label = @Translation("Test display variant")
 * )
 */
class TestDisplayVariant extends VariantBase implements PageVariantInterface {

  /**
   * The render array representing the main page content.
   *
   * @var array
   */
  protected $mainContent = [];

  /**
   * {@inheritdoc}
   */
  public function setMainContent(array $main_content) {
    $this->mainContent = $main_content;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    if (empty($config['required_configuration'])) {
      throw new \Exception('Required configuration is missing!');
    }

    $build = [];
    $build['content']['default'] = [
      '#markup' => $config['required_configuration'],
    ];
    return $build;
  }

}
