<?php

namespace Drupal\KernelTests\Core\Asset;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests that deprecated asset libraries trigger a deprecation error.
 *
 * @group Asset
 * @group legacy
 */
class LegacyLibraryDiscoveryTest extends KernelTestBase {

  /**
   * The library discovery service.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryInterface
   */
  protected $libraryDiscovery;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->libraryDiscovery = $this->container->get('library.discovery');
  }

  /**
   * Tests that the jquery.ui.checkboxradio library is deprecated.
   *
   * @expectedDeprecation The "core/jquery.ui.checkboxradio" asset library is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. See https://www.drupal.org/node/3067969
   * @doesNotPerformAssertions
   */
  public function testJqueryUiCheckboxradio() {
    $this->libraryDiscovery->getLibraryByName('core', 'jquery.ui.checkboxradio');
  }

  /**
   * Tests that the jquery.ui.controlgroup library is deprecated.
   *
   * @expectedDeprecation The "core/jquery.ui.controlgroup" asset library is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. See https://www.drupal.org/node/3067969
   * @doesNotPerformAssertions
   */
  public function testJqueryUiControlgroup() {
    $this->libraryDiscovery->getLibraryByName('core', 'jquery.ui.controlgroup');
  }

}
