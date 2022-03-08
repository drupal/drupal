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
      'jquery.ui' => '85b66ea404a9aa3ca71ee243f849efea',
      'jquery.ui.autocomplete' => '76ef835c38b36f0fb4f3609681870223',
      'jquery.ui.button' => 'e3a8fd396547c14bd508ccd302e23c2c',
      'jquery.ui.dialog' => '5774b51ff4a57dae7137b65d8025fb13',
      'jquery.ui.draggable' => 'bcb81f27f5f90036b5fe91eb92950872',
      'jquery.ui.menu' => '9acdd7d55c7c03600c161385353eeff7',
      'jquery.ui.mouse' => '4c755c0bfc5860b59b9a3a9dd2dcd016',
      'jquery.ui.position' => 'd51b206fb9272838e23ff9f4f24608aa',
      'jquery.ui.resizable' => '9e128d4abf2efe50c688475390808b54',
      'jquery.ui.widget' => '6a2eff802beb4439333502dd2516239d',
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
