<?php

namespace Drupal\tabbingmanager_test\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * For testing the Tabbing Manager.
 */
class TabbingManagerTestController extends ControllerBase {

  /**
   * Provides a page with the tabbingManager library for testing tabbing manager.
   *
   * @return array
   *   The render array.
   */
  public function build() {
    return [
      'container' => [
        '#type' => 'container',
        '#attributes' => [
          'id' => 'tabbingmanager-test-container',
        ],
        'first' => [
          '#type' => 'textfield',
          '#title' => $this->t('First'),
          '#attributes' => [
            'id' => 'first',
          ],
        ],
        'second' => [
          '#type' => 'textfield',
          '#title' => $this->t('Second'),
          '#attributes' => [
            'id' => 'second',
          ],
        ],
        'third' => [
          '#type' => 'textfield',
          '#title' => $this->t('Third'),
          '#attributes' => [
            'id' => 'third',
          ],
        ],
      ],
      'another_container' => [
        '#type' => 'container',
        '#attributes' => [
          'id' => 'tabbingmanager-test-another-container',
        ],
        'fourth' => [
          '#type' => 'textfield',
          '#title' => $this->t('Fourth'),
          '#attributes' => [
            'id' => 'fourth',
          ],
        ],
        'fifth' => [
          '#type' => 'textfield',
          '#title' => $this->t('Fifth'),
          '#attributes' => [
            'id' => 'fifth',
          ],
        ],
        'sixth' => [
          '#type' => 'textfield',
          '#title' => $this->t('Sixth'),
          '#attributes' => [
            'id' => 'sixth',
          ],
        ],
      ],
      '#attached' => ['library' => ['core/drupal.tabbingmanager']],

    ];
  }

}
