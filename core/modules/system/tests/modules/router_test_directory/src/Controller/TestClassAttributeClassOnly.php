<?php

declare(strict_types=1);

namespace Drupal\router_test\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Test controller: class-only #[Route] with bare __invoke().
 */
#[Route(
  path: '/test_class_attribute_class_only',
  name: 'router_test.class_only',
  requirements: ['_access' => 'TRUE'],
)]
class TestClassAttributeClassOnly extends ControllerBase {

  /**
   * Provides test content.
   */
  public function __invoke(): array {
    return ['#markup' => 'Testing class-only #[Route] with bare __invoke()'];
  }

}
