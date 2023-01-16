<?php

namespace Drupal\Tests\views\Kernel;

/**
 * Tests the service views.views_data.
 *
 * @group views
 */
class ViewsDataTest extends ViewsKernelTestBase {

  /**
   * Tests that the service "views.views_data" is backend-overridable.
   */
  public function testViewsViewsDataIsBackendOverridable() {
    $definition = $this->container->getDefinition('views.views_data');
    $this->assertTrue($definition->hasTag('backend_overridable'));
  }

}
