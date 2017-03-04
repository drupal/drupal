<?php

namespace Drupal\views\Plugin\views\argument_default;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * The fixed argument default handler.
 *
 * @ingroup views_argument_default_plugins
 *
 * @ViewsArgumentDefault(
 *   id = "fixed",
 *   title = @Translation("Fixed")
 * )
 */
class Fixed extends ArgumentDefaultPluginBase implements CacheableDependencyInterface {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['argument'] = ['default' => ''];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['argument'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Fixed value'),
      '#default_value' => $this->options['argument'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getArgument() {
    return $this->options['argument'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return [];
  }

}
