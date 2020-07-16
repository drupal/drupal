<?php

namespace Drupal\Core\Block\Plugin\Block;

use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Block\BlockPluginTrait;
use Drupal\Core\Cache\CacheableDependencyTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;

/**
 * Defines a fallback plugin for missing block plugins.
 *
 * @Block(
 *   id = "broken",
 *   admin_label = @Translation("Broken/Missing"),
 *   category = @Translation("Block"),
 * )
 */
class Broken extends PluginBase implements BlockPluginInterface {

  use BlockPluginTrait;
  use CacheableDependencyTrait;

  /**
   * {@inheritdoc}
   */
  public function build() {
    return $this->brokenMessage();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    return $this->brokenMessage();
  }

  /**
   * Generate message with debugging information as to why the block is broken.
   *
   * @return array
   *   Render array containing debug information.
   */
  protected function brokenMessage() {
    $build['message'] = [
      '#markup' => $this->t('This block is broken or missing. You may be missing content or you might need to enable the original module.'),
    ];

    return $build;
  }

}
