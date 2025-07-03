<?php

namespace Drupal\Core\Breadcrumb;

/**
 * Breadcrumb theme preprocess.
 *
 * @internal
 */
class BreadcrumbPreprocess {

  /**
   * Prepares variables for breadcrumb templates.
   *
   * Default template: breadcrumb.html.twig.
   *
   * @param array $variables
   *   An associative array containing:
   *   - links: A list of \Drupal\Core\Link objects which should be rendered.
   */
  public function preprocessBreadcrumb(array &$variables): void {
    $variables['breadcrumb'] = [];
    /** @var \Drupal\Core\Link $link */
    foreach ($variables['links'] as $key => $link) {
      $variables['breadcrumb'][$key] = [
        'text' => $link->getText(),
        'url' => $link->getUrl()->toString(),
      ];
    }
  }

}
