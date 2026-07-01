<?php

declare(strict_types=1);

namespace Drupal\router_test\Form;

use Symfony\Component\Routing\Attribute\Route;

/**
 * Test class that does not implement FormInterface.
 *
 * Any class that is in the Form namespace and does not implement FormInterface
 * will not be discoverable for form routes, even with the attribute.
 */
#[Route(
  path: '/non-form-object-route',
  name: 'router_test.invalid_controller_route',
)]
class TestInvalidFormNamespaceController {}
