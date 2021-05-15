<?php

namespace Drupal\Tests\search\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the service "search.index".
 *
 * @group search
 */
class SearchIndexTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['search'];

  /**
   * Test that the service "search.index" is backend overridable.
   */
  public function testSearchIndexServiceIsBackendOverridable() {
    $definition = $this->container->getDefinition('search.index');
    $this->assertTrue($definition->hasTag('backend_overridable'));
  }

}
