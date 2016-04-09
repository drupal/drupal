<?php

namespace Drupal\form_test;

use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Defines a controller class with methods for autocompletion.
 */
class AutocompleteController {

  /**
   * Returns some autocompletion content.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response.
   */
  public function autocomplete1() {
    return new JsonResponse(array('key' => 'value'));
  }

}
