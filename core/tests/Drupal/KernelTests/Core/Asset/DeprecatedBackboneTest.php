<?php

namespace Drupal\KernelTests\Core\Asset;

use Drupal\KernelTests\KernelTestBase;

/**
 * Checks the deprecation status of Backbone.
 *
 * @group Asset
 * @group legacy
 */
class DeprecatedBackboneTest extends KernelTestBase {

  /**
   * The library discovery service.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryInterface
   */
  protected $libraryDiscovery;

  /**
   * {@inheritdoc}
   */
  protected function setUp():void {
    parent::setUp();
    $this->libraryDiscovery = $this->container->get('library.discovery');
  }

  /**
   * Tests that the Backbone library is deprecated.
   */
  public function testBackboneDeprecation() {
    $this->libraryDiscovery->getLibraryByName('core', 'backbone');
    $this->expectDeprecation("The core/backbone asset library is deprecated in Drupal 9.4.0 and will be removed in Drupal 10.0.0.");
  }

}
