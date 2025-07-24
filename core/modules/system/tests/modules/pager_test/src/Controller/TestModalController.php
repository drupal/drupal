<?php

declare(strict_types=1);

namespace Drupal\pager_test\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Renders a link to open a route in route with a pager in a modal.
 */
class TestModalController extends ControllerBase {

  /**
   * Renders a link to open pager_test.multiple_pagers in a modal dialog.
   */
  public function modal(): array {
    $build = [];

    $build['open_pager_link'] = [
      '#type' => 'link',
      '#title' => $this->t('Open modal'),
      '#url' => Url::fromRoute('pager_test.multiple_pagers'),
      '#attributes' => [
        'class' => ['use-ajax'],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => Json::encode([
          'dialogClass' => 'pager-test-modal',
          'height' => '50%',
          'width' => '50%',
          'title' => $this->t('Pagers in modal'),
        ]),
      ],
      '#attached' => [
        'library' => [
          'core/drupal.dialog.ajax',
        ],
      ],
    ];

    return $build;
  }

}
