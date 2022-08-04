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
   * Confirm deprecation status and contents of jQuery UI libraries.
   *
   * @group legacy
   */
  public function testDeprecatedJqueryUi() {
    /** @var \Drupal\Core\Asset\LibraryDiscoveryInterface $library_discovery */
    $library_discovery = $this->container->get('library.discovery');
    $deprecated_jquery_ui_libraries = [
      'jquery.ui' => '3c4551a9802f6f88da8f685f3d78ccba',
      'jquery.ui.autocomplete' => 'ff434e5a016731d7a62a3c46283e20b0',
      'jquery.ui.button' => '4e521b5804eaa76ae908539ed1612028',
      'jquery.ui.dialog' => 'faf28d84752fea7264209d8fee4b5414',
      'jquery.ui.draggable' => '86ea35efa688f090c9d435cb1666014a',
      'jquery.ui.menu' => 'de39d6e2b23c0b83cb38f98026e757b3',
      'jquery.ui.mouse' => '4d9f68fec0cc54bf963322952394d747',
      'jquery.ui.position' => '2db44403539779784f281c6f2bcf27ae',
      'jquery.ui.resizable' => '088c49425278a556f56099aa3279bc52',
      'jquery.ui.widget' => '076795e1a215a8203cbd048082166419',
    ];
    // DrupalCI uses a precision of 100 in certain environments which breaks
    // this test.
    ini_set('serialize_precision', -1);
    foreach ($deprecated_jquery_ui_libraries as $library => $expected_hashed_library_definition) {
      $this->expectDeprecation("The \"core/$library\" asset library is deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. See https://www.drupal.org/node/3067969");
      $library_definition = $library_discovery->getLibraryByName('core', $library);
      $this->assertNotEmpty($library_definition['dependencies'], "$library must declare dependencies");

      // Confirm that the libraries extending jQuery UI functionality depend on
      // core/jquery.ui directly or via a dependency on core/jquery.ui.widget.
      if (!in_array($library, ['jquery.ui', 'jquery.ui.dialog', 'jquery.ui.position'])) {
        $has_main_or_widget = (in_array('core/jquery.ui', $library_definition['dependencies']) || in_array('core/jquery.ui.widget', $library_definition['dependencies']));
        $this->assertTrue($has_main_or_widget, "$library must depend on core/jquery.ui or core/jquery.ui.widget");
      }
      elseif ($library === 'jquery.ui.dialog') {
        // jquery.ui.dialog must be evaluated differently due to it loading
        // jQuery UI assets directly instead of depending on core/jquery.ui.
        // This makes it necessary to depend on core/jquery as that dependency
        // is not inherited from depending on core/jquery.ui.
        //
        // @todo Remove the tests specific to only jquery.ui.dialog as part of
        //   https://drupal.org/node/3192804
        $dialog_depends_on_jquery_core = in_array('core/jquery', $library_definition['dependencies']) && $library === 'jquery.ui.dialog';
        $this->assertTrue($dialog_depends_on_jquery_core, 'core/jquery.ui.dialog must depend on core/jquery');
      }

      $this->assertEquals($expected_hashed_library_definition, md5(serialize($library_definition)));
    }
  }

}
