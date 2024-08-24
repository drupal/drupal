<?php

declare(strict_types=1);

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
