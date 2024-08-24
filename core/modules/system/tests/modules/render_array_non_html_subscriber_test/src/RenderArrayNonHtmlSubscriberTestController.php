<?php

declare(strict_types=1);

namespace Drupal\render_array_non_html_subscriber_test;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;

class RenderArrayNonHtmlSubscriberTestController extends ControllerBase {

  /**
   * @return string
   */
  public function rawString() {
    return new Response((string) $this->t('Raw controller response.'));
  }

  /**
   * @return array
   */
  public function renderArray() {
    return [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Controller response successfully rendered.'),
    ];
  }

}
