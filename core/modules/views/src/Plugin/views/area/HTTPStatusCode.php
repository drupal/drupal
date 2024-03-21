<?php

namespace Drupal\views\Plugin\views\area;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Attribute\ViewsArea;
use Symfony\Component\HttpFoundation\Response;

/**
 * Alter the HTTP response status code used by the view.
 *
 * @ingroup views_area_handlers
 */
#[ViewsArea("http_status_code")]
class HTTPStatusCode extends AreaPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['status_code'] = ['default' => 200];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    // Get all possible status codes defined by symfony.
    $options = Response::$statusTexts;

    // Move 403/404/500 to the top.
    $options = [
      '404' => $options['404'],
      '403' => $options['403'],
      '500' => $options['500'],
    ] + $options;

    // Add the HTTP status code, so it's easier for people to find it.
    array_walk($options, function ($title, $code) use (&$options) {
      $options[$code] = $this->t('@code (@title)', ['@code' => $code, '@title' => $title]);
    });

    $form['status_code'] = [
      '#title' => $this->t('HTTP status code'),
      '#type' => 'select',
      '#default_value' => $this->options['status_code'],
      '#options' => $options,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function render($empty = FALSE) {
    if (!$empty || !empty($this->options['empty'])) {
      $build['#attached']['http_header'][] = ['Status', $this->options['status_code']];
      return $build;
    }
  }

}
