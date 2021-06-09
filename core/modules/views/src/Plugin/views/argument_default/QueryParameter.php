<?php

namespace Drupal\views\Plugin\views\argument_default;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * A query parameter argument default handler.
 *
 * @ingroup views_argument_default_plugins
 *
 * @ViewsArgumentDefault(
 *   id = "query_parameter",
 *   title = @Translation("Query parameter")
 * )
 */
class QueryParameter extends ArgumentDefaultPluginBase implements CacheableDependencyInterface {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['query_param'] = ['default' => ''];
    $options['fallback'] = ['default' => ''];
    $options['multiple'] = ['default' => 'and'];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['query_param'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Query parameter'),
      '#description' => $this->t('The query parameter to use.'),
      '#default_value' => $this->options['query_param'],
    ];
    $form['fallback'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Fallback value'),
      '#description' => $this->t('The fallback value to use when the above query parameter is not present.'),
      '#default_value' => $this->options['fallback'],
    ];
    $form['multiple'] = [
      '#type' => 'radios',
      '#title' => $this->t('Multiple values'),
      '#description' => $this->t('Conjunction to use when handling multiple values. E.g. "?value[0]=a&value[1]=b".'),
      '#default_value' => $this->options['multiple'],
      '#options' => [
        'and' => $this->t('AND'),
        'or' => $this->t('OR'),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getArgument() {
    $current_request = $this->view->getRequest();

    if ($current_request->query->has($this->options['query_param'])) {
      $param = $current_request->query->get($this->options['query_param']);
      if (is_array($param)) {
        $conjunction = ($this->options['multiple'] == 'and') ? ',' : '+';
        $param = implode($conjunction, $param);
      }

      return $param;
    }
    else {
      // Otherwise, use the fixed fallback value.
      return $this->options['fallback'];
    }
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
    return ['url'];
  }

}
