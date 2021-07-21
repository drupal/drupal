<?php

namespace Drupal\jqueryui_library_assets_test\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for testing jQuery UI asset loading order.
 */
class JqueryUiTestAssetsController extends ControllerBase {

  /**
   * Provides a page that loads a library.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object, typically containing "library" query
   *   containing a pipe delimited list of library names.
   *
   * @return array
   *   The render array.
   */
  public function build(Request $request) {
    // @see https://www.drupal.org/project/drupal/issues/2741939
    $library = $request->query->get('library', '');
    // If there are pipes in $library, they are separating multiple library
    // names.
    if (strpos($library, '|') !== FALSE) {
      $library = explode('|', $library);
      $library = array_map(function ($item) {
        if (strpos($item, '/') === FALSE) {
          return "core/$item";
        }
        return $item;
      }, $library);
    }
    else {
      if (strpos($library, '/') === FALSE) {
        $library = "core/$library";
      }
    }

    return [
      '#markup' => 'I am a page for testing jQuery UI asset loading order.',
      '#attached' => [
        'library' => $library,
      ],
      '#cache' => [
        'max-age' => 0,
        'contexts' => [
          'url.path',
          'url.query_args:library',
        ],
      ],
    ];
  }

  /**
   * Provides a page that loads jQuery UI as a library.
   *
   * With a large third party library in the middle of two other jquery.ui
   * dependents.
   *
   * @return array
   *   The render array.
   */
  public function largeJavascriptDependencies() {
    return [
      '#markup' => 'I am a page for testing jQuery UI asset loading order when large asset dependencies are involved between two jquery.ui dependents.',
      '#attached' => [
        'library' => [
          'core/drupal.dialog',
          'jqueryui_library_assets_test/many-dependencies',
          'core/drupal.autocomplete',
        ],
      ],
    ];
  }

}
