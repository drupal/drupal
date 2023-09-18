<?php

namespace Drupal\user_language_test\Controller;

/**
 * Returns responses for User Language Test routes.
 */
class UserLanguageTestController {

  /**
   * Builds the response.
   */
  public function buildPostResponse() {
    return ['#markup' => 'It works!'];
  }

}
