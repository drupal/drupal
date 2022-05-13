<?php

namespace Drupal\KernelTests\Core\Asset;

use Drupal\KernelTests\KernelTestBase;

/**
 * Checks the deprecation status of jQuery.form.
 *
 * @group Asset
 * @group legacy
 */
class DeprecatedJqueryFormTest extends KernelTestBase {
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
   * Tests that the jQuery.form library is deprecated.
   */
  public function testJqueryFormDeprecation() {
    $this->libraryDiscovery->getLibraryByName('core', 'jquery.form');
    $this->expectDeprecation("The core/jquery.form asset library is deprecated in Drupal 9.4.0 and will be removed in Drupal 10.0.0.");
  }

}
