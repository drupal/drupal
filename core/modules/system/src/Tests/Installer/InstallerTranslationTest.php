<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Installer\InstallerTranslationTest.
 */

namespace Drupal\system\Tests\Installer;

use Drupal\simpletest\InstallerTestBase;

/**
 * Selects German as the installation language and verifies the following page
 * is not in English.
 *
 * @group Installer
 */
class InstallerTranslationTest extends InstallerTestBase {

  /**
   * Overrides the language code in which to install Drupal.
   *
   * @var string
   */
  protected $langcode = 'de';

  /**
   * Overrides InstallerTest::setUpLanguage().
   */
  protected function setUpLanguage() {
    parent::setUpLanguage();
    // After selecting a different language than English, all following screens
    // should be translated already.
    // @todo Instead of actually downloading random translations that cannot be
    //   asserted, write and supply a German translation file. Until then, take
    //   over whichever string happens to be there, but ensure that the English
    //   string no longer appears.
    $elements = $this->xpath('//input[@type="submit"]/@value');
    $string = (string) current($elements);
    $this->assertNotEqual($string, 'Save and continue');
    $this->translations['Save and continue'] = $string;

    // Check the language direction.
    $direction = (string) current($this->xpath('/html/@dir'));
    $this->assertEqual($direction, 'ltr');
  }

  /**
   * Verifies that installation succeeded.
   */
  public function testInstaller() {
    $this->assertUrl('user/1');
    $this->assertResponse(200);

    // Ensure that we can enable basic_auth on a non-english site.
    $this->drupalPostForm('admin/modules', array('modules[Web services][basic_auth][enable]' => TRUE), t('Save configuration'));
    $this->assertResponse(200);
  }

}
