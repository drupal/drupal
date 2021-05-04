<?php

namespace Drupal\autocomplete_shim_test\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * For testing the jQuery UI autocomplete shim.
 */
class AutocompleteShimTestController extends ControllerBase {

  /**
   * Provides a page with the jQuery UI autocomplete library for testing.
   *
   * @return array
   *   The render array.
   */
  public function build() {
    return [
      'container1' => [
        '#type' => 'container',
        '#attributes' => [
          'id' => 'autocomplete-wrap1',
          'class' => ['autocomplete-wrap'],
        ],
      ],
      'container2' => [
        '#type' => 'container',
        '#attributes' => [
          'id' => 'autocomplete-wrap2',
          'class' => ['autocomplete-wrap'],
        ],
        'input' => [
          '#type' => 'html_tag',
          '#tag' => 'input',
          '#attributes' => [
            'id' => 'autocomplete',
            'class' => ['foo'],
          ],
        ],
      ],
      'container_contenteditable' => [
        '#type' => 'container',
        '#attributes' => [
          'id' => 'autocomplete-contenteditable',
          'tabindex' => 0,
          'contenteditable' => '',
        ],
      ],
      'textarea' => [
        '#type' => 'html_tag',
        '#tag' => 'textarea',
        '#attributes' => [
          'id' => ['autocomplete-textarea'],
        ],
      ],
      '#attached' => [
        'library' => [
          'core/jquery.ui.autocomplete',
          'core/jquery.simulate',
          'core/jquery.ui',
        ],
      ],
    ];
  }

}
