<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Kernel;

use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the service views.views_data.
 */
#[Group('views')]
class ViewsDataTest extends ViewsKernelTestBase {

  /**
   * Tests that the service "views.views_data" is backend-overridable.
   */
  public function testViewsViewsDataIsBackendOverridable(): void {
    $definition = $this->container->getDefinition('views.views_data');
    $this->assertTrue($definition->hasTag('backend_overridable'));
  }

}
