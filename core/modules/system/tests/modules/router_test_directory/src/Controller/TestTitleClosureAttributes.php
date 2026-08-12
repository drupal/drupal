<?php

declare(strict_types=1);

namespace Drupal\router_test\Controller;

/**
 * Makes AttributeRouteDiscovery load this PHP-version bridge via #[Route].
 */
if (PHP_VERSION_ID >= 80500) {
  require_once dirname(__DIR__) . '/TestTitleClosureAttributesPhp85.php';
  class_alias(TestTitleClosureAttributesPhp85::class, TestTitleClosureAttributes::class);
}
else {
  /**
   * Satisfies the PSR-4 autoloader on PHP versions that cannot parse the fixture.
   */
  class TestTitleClosureAttributes {}
}
