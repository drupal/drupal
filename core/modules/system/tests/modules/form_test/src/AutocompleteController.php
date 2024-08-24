<?php

declare(strict_types=1);

namespace Drupal\form_test;

use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Defines a controller class with methods for autocompletion.
 */
class AutocompleteController {

  /**
   * Returns some autocompletion content with a slight delay.
   *
   * The delay is present so tests can make assertions on the "processing"
   * layout of autocompletion.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response.
   */
  public function delayed_autocomplete() {
    sleep(1);
    return new JsonResponse([['value' => 'value', 'label' => 'label']]);
  }

}
