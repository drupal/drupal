<?php

declare(strict_types=1);

namespace Drupal\contextual_test\Controller;

/**
 * Test controller to provide a callback for the contextual link.
 */
class TestController {

  /**
   * Callback for the contextual link.
   *
   * @return array
   *   Render array.
   */
  public function render() {
    return [
      '#type' => 'markup',
      '#markup' => 'Everything is contextual!',
    ];
  }

}
