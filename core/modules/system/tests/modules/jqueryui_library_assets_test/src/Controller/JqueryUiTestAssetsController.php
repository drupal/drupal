<?php

declare(strict_types=1);

namespace Drupal\jqueryui_library_assets_test\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for testing jQuery UI asset loading order.
 */
class JqueryUiTestAssetsController extends ControllerBase {

  /**
   * Provides a page that loads a library.
   *
   * @param string $library
   *   A pipe delimited list of library names.
   *
   * @return array
   *   The render array.
   */
  public function build($library) {
    // If there are pipes in $library, they are separating multiple library
    // names.
    if (str_contains($library, '|')) {
      $library = explode('|', $library);
      $library = array_map(function ($item) {
        return "core/$item";
      }, $library);
    }
    else {
      $library = "core/$library";
    }

    return [
      '#markup' => 'I am a page for testing jQuery UI asset loading order.',
      '#attached' => [
        'library' => $library,
      ],
    ];
  }

}
