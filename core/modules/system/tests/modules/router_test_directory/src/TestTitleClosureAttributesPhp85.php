<?php

declare(strict_types=1);

namespace Drupal\router_test\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\Attribute\Route;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Test controller for PHP 8.5 title closures in Route attributes.
 */
class TestTitleClosureAttributesPhp85 extends ControllerBase {

  /**
   * Two routes on one method, each with its own title closure.
   */
  #[Route(
    path: '/test_title_closure/{parameter}',
    name: 'router_test.title_closure',
    title: static function (string $parameter) {
      return new TranslatableMarkup('First title for @parameter', ['@parameter' => $parameter]);
    },
    requirements: ['_access' => 'TRUE'],
  )]
  #[Route(
    path: '/test_title_closure-other-path/{parameter}',
    name: 'router_test.title_closure_other',
    title: static function (string $parameter) {
      return new TranslatableMarkup('Second title for @parameter', ['@parameter' => $parameter]);
    },
    requirements: ['_access' => 'TRUE'],
  )]
  public function titleClosure(string $parameter): array {
    return ['#markup' => 'Testing method with title closures'];
  }

}
