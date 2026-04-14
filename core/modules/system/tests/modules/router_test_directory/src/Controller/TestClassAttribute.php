<?php

declare(strict_types=1);

namespace Drupal\router_test\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Test controller for class-level Route attribute with method-level merging.
 */
#[Route(
  path: '/test_class_attribute',
  name: 'router_test.class_',
  requirements: ['_access' => 'TRUE'],
  options: ['option_a' => 'from_class'],
  defaults: ['default_a' => 'from_class', '_title' => 'Class title'],
  methods: ['GET'],
  schemes: ['http'],
  priority: 5,
)]
class TestClassAttribute extends ControllerBase {

  #[Route(name: 'invoke')]
  public function __invoke(): array {
    return ['#markup' => 'Testing __invoke() with a Route attribute on the class'];
  }

  #[Route(
    path: '/inherits',
    name: 'inherits',
  )]
  public function inherits(): array {
    return ['#markup' => 'Inherits class globals'];
  }

  #[Route(
    path: '/overrides/{id}',
    name: 'overrides',
    requirements: ['id' => '\d+'],
    options: ['option_a' => 'from_method', 'option_b' => 'from_method'],
    defaults: ['default_a' => 'from_method', 'default_b' => 'from_method'],
    host: 'method.example.com',
    methods: ['POST'],
    schemes: ['https'],
    priority: 10,
  )]
  public function overrides(): array {
    return ['#markup' => 'Overrides class globals'];
  }

}
