<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Installer\InstallerLanguageDirectionTest.
 */

namespace Drupal\system\Tests\Installer;

use Drupal\simpletest\InstallerTestBase;

/**
 * Verifies that the early installer uses the correct language direction.
 *
 * @group Installer
 */
class InstallerLanguageDirectionTest extends InstallerTestBase {

  /**
   * Overrides the language code the installer should use.
   *
   * @var string
   */
  protected $langcode = 'ar';

  /**
   * {@inheritdoc}
   */
  protected function setUpLanguage() {
    parent::setUpLanguage();
    // After selecting a different language than English, all following screens
    // should be translated already.
    // @todo Instead of actually downloading random translations that cannot be
    //   asserted, write and supply a translation file. Until then, take
    //   over whichever string happens to be there, but ensure that the English
    //   string no longer appears.
    $elements = $this->xpath('//input[@type="submit"]/@value');
    $string = (string) current($elements);
    $this->assertNotEqual($string, 'Save and continue');
    $this->translations['Save and continue'] = $string;

    // Verify that language direction is right-to-left.
    $direction = (string) current($this->xpath('/html/@dir'));
    $this->assertEqual($direction, 'rtl');
  }

  /**
   * Confirms that the installation succeeded.
   */
  public function testInstalled() {
    $this->assertUrl('user/1');
    $this->assertResponse(200);
  }

}
