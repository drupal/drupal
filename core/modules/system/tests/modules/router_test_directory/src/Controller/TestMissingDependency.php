<?php

declare(strict_types=1);

namespace Drupal\router_test\Controller;

use Drupal\a_module_that_does_not_exist\SomeController;
use Symfony\Component\Routing\Attribute\Route;

/**
 * A controller that declares a route but extends a class from a missing module.
 *
 * A missing parent class (rather than a missing trait) is used deliberately: it
 * is reported as a catchable error on all supported PHP versions, whereas a
 * missing trait only became catchable in PHP 8.5.
 */
class TestMissingDependency extends SomeController {

  /**
   * Builds something.
   *
   * @return array
   *   A render array.
   */
  #[Route('/test_missing_dependency', name: 'router_test.missing_dependency')]
  public function build(): array {
    return [];
  }

}
