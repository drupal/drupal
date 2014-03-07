<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Installer\InstallerTranslationTest.
 */

namespace Drupal\system\Tests\Installer;

use Drupal\simpletest\InstallerTestBase;

/**
 * Tests the installer translation detection.
 */
class InstallerTranslationTest extends InstallerTestBase {

  /**
   * Overrides the language code in which to install Drupal.
   *
   * @var string
   */
  protected $langcode = 'de';

  public static function getInfo() {
    return array(
      'name' => 'Installer translation test',
      'description' => 'Selects German as the installation language and verifies the following page is not in English.',
      'group' => 'Installer',
    );
  }

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
  }

  /**
   * Overrides InstallerTest::setUpConfirm().
   */
  protected function setUpConfirm() {
    // We don't know the translated link text of "Visit your new site", but
    // luckily, there is only one link.
    $elements = $this->xpath('//a');
    $string = (string) current($elements);
    $this->assertNotEqual($string, 'Visit your new site');
    $this->translations['Visit your new site'] = $string;
    parent::setUpConfirm();
  }

  /**
   * Verifies that installation succeeded.
   */
  public function testInstaller() {
    $this->assertUrl('user/1');
    $this->assertResponse(200);
  }

}
