<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Controller;

use Drupal\Core\Routing\Attribute\Route;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides an example title closure for TitleResolverTest.
 *
 * This file intentionally contains a closure in an attribute argument, which
 * is valid only in PHP 8.5 and later. It is loaded only by the PHP-gated test.
 */
class TitleClosureCallback {

  /**
   * A controller with a title closure on its route attribute.
   *
   * @return array
   *   A render array.
   */
  #[Route(
    path: '/test-route/{value}',
    name: 'title_resolver_test.closure',
    title: static function (string $value) {
      return new TranslatableMarkup('Title for @value', ['@value' => $value]);
    },
  )]
  public function example(string $value): array {
    return [];
  }

}
