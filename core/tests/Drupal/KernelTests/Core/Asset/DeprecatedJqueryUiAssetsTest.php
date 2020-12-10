<?php

namespace Drupal\KernelTests\Core\Asset;

use Drupal\KernelTests\KernelTestBase;

/**
 * Checks the deprecation status and contents of jQuery UI libraries.
 *
 * @group Asset
 */
class DeprecatedJqueryUiAssetsTest extends KernelTestBase {

  /**
   * The library discovery service.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryInterface
   */
  protected $libraryDiscovery;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->libraryDiscovery = $this->container->get('library.discovery');
  }

  /**
   * Confirm deprecation status and contents of jQuery UI libraries.
   *
   * @group legacy
   */
  public function testDeprecatedJqueryUi() {
    $deprecated_jquery_ui_libraries = [
      'jquery.ui' => '1396fab9268ee2cce47df6ac3e4781c8',
      'jquery.ui.autocomplete' => '153f2836f8f2da39767208b6e09cb5b4',
      'jquery.ui.button' => 'ad23e5de0fa1de1f511d10ba2e10d2dd',
      'jquery.ui.dialog' => 'dc72e5bd38a3d2697bcf3e7964852e4b',
      'jquery.ui.draggable' => 'af0f2bdc8aa4ade1e3de8042f31a9312',
      'jquery.ui.menu' => '7d0c4d57f43d2f881d2cd5e5b79effbb',
      'jquery.ui.mouse' => '626bb203807fa2cdc62510412685df4a',
      'jquery.ui.position' => '6d1759c7d3eb94accbed78416487469b',
      'jquery.ui.resizable' => 'a2448fa87071a17a9756f39c9becb70d',
      'jquery.ui.widget' => 'eacd675de09572383b58e52309ba2245',
    ];
    foreach ($deprecated_jquery_ui_libraries as $library => $expected_hashed_library_definition) {
      $this->expectDeprecation("The \"core/$library\" asset library is deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. See https://www.drupal.org/node/3067969");
      $library_definition = $this->libraryDiscovery->getLibraryByName('core', $library);
      $this->assertEquals($expected_hashed_library_definition, md5(serialize($library_definition)));
    }
  }

}
