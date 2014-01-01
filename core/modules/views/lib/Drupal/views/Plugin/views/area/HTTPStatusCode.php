<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\views\area\HTTPStatusCode.
 */

namespace Drupal\views\Plugin\views\area;

use Symfony\Component\HttpFoundation\Response;

/**
 * Alter the HTTP response status code used by the view.
 *
 * @ingroup views_area_handlers
 *
 * @PluginID("http_status_code")
 */
class HTTPStatusCode extends AreaPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['status_code'] = array('default' => 200);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);

    // Get all possible status codes defined by symfony.
    $options = Response::$statusTexts;

    // Move 403/404/500 to the top.
    $options = array(
      '404' => $options['404'],
      '403' => $options['403'],
      '500' => $options['500'],
    ) + $options;

    // Add the HTTP status code, so it's easier for people to find it.
    array_walk($options, function($title, $code) use(&$options) {
      $options[$code] = t('@code (!title)', array('@code' => $code, '!title' => $title));
    });

    $form['status_code'] = array(
      '#title' => t('HTTP status code'),
      '#type' => 'select',
      '#default_value' => $this->options['status_code'],
      '#options' => $options,
    );
  }

  /**
   * {@inheritdoc}
   */
  function render($empty = FALSE) {
    if (!$empty || !empty($this->options['empty'])) {
      $this->view->getResponse()->setStatusCode($this->options['status_code']);
      $this->view->getRequest()->attributes->set('_http_statuscode', $this->options['status_code']);
    }
  }

}
