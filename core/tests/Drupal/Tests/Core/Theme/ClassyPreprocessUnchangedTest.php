<?php

namespace Drupal\Tests\Core\Theme;

use Drupal\Tests\UnitTestCase;

/**
 * Confirms that classy.theme has not added new functionality.
 *
 * @group Theme
 */
class ClassyPreprocessUnchangedTest extends UnitTestCase {

  /**
   * Confirms that classy.theme has not added any new functionality.
   *
   * Part of Classy decoupling includes no longer depending on the functionality
   * in classy.theme. This test confirms that classy.theme has not been changed.
   * If a change has occurred the test will fail and provide a warning that all
   * functionality changes should be moved to the themes inheriting Classy.
   */
  public function testNoNewPreprocess() {
    $classy_theme_contents = file_get_contents($this->root . '/core/themes/classy/classy.theme');
    $hash = md5($classy_theme_contents);
    $this->assertSame($hash, 'c42ff3a1291a258b42f0c44010cd28c7', "The file hash for classy.theme has changed. Any additions or changes to preprocess functions should be added to the themes that inherit Classy. \nIf the changes to classy.theme are not changes to preprocess functions, update the hash in this test to: '$hash' so it will pass.");
  }

}
